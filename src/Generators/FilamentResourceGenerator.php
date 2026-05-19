<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class FilamentResourceGenerator extends BaseGenerator
{
    public function generate(): array
    {
        // Génère uniquement si Filament est installé
        if (!$this->filamentInstalled()) {
            return [['path' => 'app/Filament/Resources/' . $this->parser->getModelName() . 'Resource.php', 'skipped' => true]];
        }

        $model   = $this->parser->getModelName();
        $plural  = Str::plural(Str::snake($model));
        $columns = $this->parser->getColumns();

        // Champs du formulaire
        $formFields = '';
        foreach ($columns as $col) {
            if ($col['is_foreign']) continue;
            $field        = $this->toFilamentField($col);
            $formFields  .= "                {$field},\n";
        }

        // Colonnes du tableau
        $tableColumns = '';
        foreach ($columns as $col) {
            if ($col['is_foreign']) continue;
            $tableColumns .= "                Tables\\Columns\\TextColumn::make('{$col['name']}')->sortable()->searchable(),\n";
        }

        $content = <<<PHP
<?php

namespace App\\Filament\\Resources;

use App\\Filament\\Resources\\{$model}Resource\\Pages;
use App\\Models\\{$model};
use Filament\\Forms;
use Filament\\Forms\\Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;

class {$model}Resource extends Resource
{
    protected static ?string \$model = {$model}::class;

    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string \$navigationGroup = null;

    public static function form(Form \$form): Form
    {
        return \$form->schema([
{$formFields}
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
{$tableColumns}
            ])
            ->filters([
                Tables\\Filters\\TrashedFilter::make(),
            ])
            ->actions([
                Tables\\Actions\\EditAction::make(),
                Tables\\Actions\\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\\Actions\\BulkActionGroup::make([
                    Tables\\Actions\\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\\List{$model}s::route('/'),
            'create' => Pages\\Create{$model}::route('/create'),
            'edit'   => Pages\\Edit{$model}::route('/{record}/edit'),
        ];
    }
}
PHP;

        return [$this->writeFile("app/Filament/Resources/{$model}Resource.php", $content)];
    }

    private function filamentInstalled(): bool
    {
        $composer = base_path('composer.json');
        if (!file_exists($composer)) return false;

        $data = json_decode(file_get_contents($composer), true);
        $deps = array_merge(
            $data['require']     ?? [],
            $data['require-dev'] ?? []
        );

        return isset($deps['filament/filament']);
    }

    private function toFilamentField(array $col): string
    {
        $name = $col['name'];

        return match(true) {
            in_array($col['type'], ['text', 'longText', 'mediumText'])
                => "Forms\\Components\\Textarea::make('{$name}')" . ($col['nullable'] ? '' : "->required()"),
            $col['type'] === 'boolean'
                => "Forms\\Components\\Toggle::make('{$name}')",
            in_array($col['type'], ['date'])
                => "Forms\\Components\\DatePicker::make('{$name}')" . ($col['nullable'] ? '' : "->required()"),
            in_array($col['type'], ['dateTime', 'timestamp'])
                => "Forms\\Components\\DateTimePicker::make('{$name}')" . ($col['nullable'] ? '' : "->required()"),
            in_array($col['type'], ['integer', 'bigInteger', 'unsignedBigInteger', 'float', 'double', 'decimal'])
                => "Forms\\Components\\TextInput::make('{$name}')->numeric()" . ($col['nullable'] ? '' : "->required()"),
            default
                => "Forms\\Components\\TextInput::make('{$name}')" . ($col['nullable'] ? '' : "->required()"),
        };
    }
}