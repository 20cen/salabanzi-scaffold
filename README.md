# Salabanzi Laravel Scaffold

> **Une migration → un projet complet, du backend au déploiement.**

`salabanzi/laravel-scaffold` est un package Composer qui génère automatiquement l'ensemble du code d'une ressource Laravel en analysant une migration existante. En une seule commande, il produit le model, le controller, les requests, les routes, la factory, le seeder, les tests, la documentation OpenAPI, les policies, les observers, les events, le SDK TypeScript, la collection Postman, la configuration Docker, le pipeline CI/CD, la ressource Filament et un rapport d'audit de performance.

Aucun fichier YAML supplémentaire à écrire. Aucune configuration obligatoire. Le package lit ce que vous avez déjà.

---

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Démarrage rapide](#démarrage-rapide)
- [Les 5 couches de génération](#les-5-couches-de-génération)
- [Commandes disponibles](#commandes-disponibles)
- [Options détaillées](#options-détaillées)
- [Détection automatique](#détection-automatique)
- [Fichiers générés](#fichiers-générés)
- [Personnalisation des stubs](#personnalisation-des-stubs)
- [Configuration](#configuration)
- [Structure du package](#structure-du-package)
- [Cas d'usage avancés](#cas-dusage-avancés)
- [FAQ](#faq)
- [Contribution](#contribution)
- [Licence](#licence)

---

## Prérequis

Avant d'installer le package, assurez-vous que votre environnement respecte ces conditions.

| Prérequis | Version minimale |
|-----------|-----------------|
| PHP | 8.2 |
| Laravel | 11.x / 12.x / 13.x |
| Composer | 2.x |

Le package fonctionne avec les bases de données suivantes :

- MySQL 5.7+ / 8.0+
- MariaDB 10.3+
- PostgreSQL 13+
- SQLite 3.8+

---

## Installation

### Sur un projet existant

```bash
composer require salabanzi/laravel-scaffold --dev
```

Laravel découvre automatiquement le package grâce au mécanisme de `package discovery`. Aucune étape supplémentaire n'est requise.

### Publier la configuration (optionnel)

```bash
php artisan vendor:publish --tag=scaffold-config
```

Cela crée le fichier `config/scaffold.php` dans votre projet, que vous pouvez modifier selon vos besoins.

### Publier les stubs (optionnel)

```bash
php artisan vendor:publish --tag=scaffold-stubs
```

Cela copie les templates dans `stubs/scaffold/` pour que vous puissiez les personnaliser.

---

## Démarrage rapide

**Étape 1 — Créez votre migration**

```bash
php artisan make:migration create_posts_table
```

**Étape 2 — Remplissez la migration**

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title', 200);
    $table->text('content')->nullable();
    $table->string('slug')->unique();
    $table->boolean('is_published')->default(false);
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
    $table->softDeletes();
});
```

**Étape 3 — Lancez le scaffold**

```bash
php artisan scaffold:generate database/migrations/2024_01_01_create_posts_table.php --layers=all
```

**Résultat — 22 fichiers générés en quelques secondes**

```
✓ app/Models/Post.php
✓ app/Http/Controllers/Api/PostController.php
✓ app/Http/Requests/StorePostRequest.php
✓ app/Http/Requests/UpdatePostRequest.php
✓ app/Http/Resources/PostResource.php
✓ routes/api.php
✓ database/factories/PostFactory.php
✓ database/seeders/PostSeeder.php
✓ tests/Feature/PostTest.php
✓ storage/api-docs/posts.yaml
✓ resources/js/services/PostService.ts
✓ postman/Post.collection.json
✓ Dockerfile
✓ docker-compose.yml
✓ .dockerignore
✓ .github/workflows/ci.yml
✓ app/Policies/PostPolicy.php
✓ app/Observers/PostObserver.php
✓ app/Events/PostCreated.php
✓ app/Events/PostUpdated.php
✓ app/Events/PostDeleted.php
✓ storage/reports/posts-audit.md
```

**Étape 4 — Lancez les migrations et les tests**

```bash
php artisan migrate
php artisan test --filter=PostTest
```

---

## Les 5 couches de génération

Le package organise la génération en 5 couches indépendantes. Vous pouvez en activer une, plusieurs ou toutes selon vos besoins.

### Couche 1 — Code de base

Génère l'ensemble du code backend nécessaire pour une ressource CRUD complète.

| Fichier | Description |
|---------|-------------|
| `app/Models/{Model}.php` | Model Eloquent avec fillable, casts, relations BelongsTo et SoftDeletes |
| `app/Http/Controllers/Api/{Model}Controller.php` | Controller API RESTful avec pagination, recherche et tri |
| `app/Http/Requests/Store{Model}Request.php` | Validation à la création, règles déduites du schéma |
| `app/Http/Requests/Update{Model}Request.php` | Validation à la modification avec règles `sometimes` |
| `app/Http/Resources/{Model}Resource.php` | Resource JSON pour la transformation des données |
| `routes/api.php` | Route `apiResource` avec middleware `auth:sanctum` |
| `database/factories/{Model}Factory.php` | Factory avec données Faker réalistes selon les noms de colonnes |
| `database/seeders/{Model}Seeder.php` | Seeder qui crée 10 entrées via la factory |
| `tests/Feature/{Model}Test.php` | 7 tests couvrant list, create, show, update, delete, auth, validation |
| `storage/api-docs/{plural}.yaml` | Documentation OpenAPI 3.0 complète |

### Couche 2 — Frontend & intégration

Génère les outils d'intégration côté client.

| Fichier | Description |
|---------|-------------|
| `resources/js/services/{Model}Service.ts` | SDK TypeScript typé avec interface, méthodes CRUD et gestion d'erreurs |
| `postman/{Model}.collection.json` | Collection Postman avec tous les endpoints et exemples de body |

### Couche 3 — DevOps & infrastructure

Génère la configuration de déploiement.

| Fichier | Description |
|---------|-------------|
| `Dockerfile` | Image PHP 8.2 optimisée pour la production |
| `docker-compose.yml` | Stack complète avec app, nginx, MySQL et Redis |
| `.dockerignore` | Fichiers exclus du build Docker |
| `.github/workflows/ci.yml` | Pipeline GitHub Actions avec tests et lint |

### Couche 4 — Expérience développeur Laravel

Génère les composants avancés du framework.

| Fichier | Description |
|---------|-------------|
| `app/Policies/{Model}Policy.php` | Policy avec toutes les gates (viewAny, view, create, update, delete, restore, forceDelete) |
| `app/Observers/{Model}Observer.php` | Observer avec dispatch des events et logging automatique |
| `app/Events/{Model}Created.php` | Event dispatché à la création |
| `app/Events/{Model}Updated.php` | Event dispatché à la modification |
| `app/Events/{Model}Deleted.php` | Event dispatché à la suppression |

### Couche 5 — Intelligence & analyse

Génère les outils de monitoring et d'administration.

| Fichier | Description |
|---------|-------------|
| `app/Filament/Resources/{Model}Resource.php` | Ressource Filament admin (générée uniquement si Filament est installé) |
| `storage/reports/{table}-audit.md` | Rapport d'audit avec détection N+1, index manquants et suggestions |
| `CHANGELOG.md` | Entrée automatique dans le changelog avec la version incrémentée |

---

## Commandes disponibles

### `scaffold:generate`

Commande principale qui génère le scaffold depuis une migration.

```bash
php artisan scaffold:generate {migration} [options]
```

**Argument obligatoire**

```
migration    Chemin vers la migration, relatif à la racine du projet
             Exemple : database/migrations/2024_01_01_create_posts_table.php
```

---

## Options détaillées

### `--layers`

Définit les couches à générer. Par défaut utilise la valeur de `config/scaffold.php`.

```bash
# Couche 1 uniquement (code de base)
php artisan scaffold:generate database/migrations/... --layers=1

# Couches 1 et 4 (code + DX Laravel)
php artisan scaffold:generate database/migrations/... --layers=1,4

# Toutes les couches
php artisan scaffold:generate database/migrations/... --layers=all

# Couches définies dans config/scaffold.php (défaut : 1,4,5)
php artisan scaffold:generate database/migrations/... --layers=default
```

### `--force`

Écrase les fichiers existants. Sans cette option, les fichiers déjà présents sont ignorés (`skip`).

```bash
php artisan scaffold:generate database/migrations/... --force
```

**Attention** : cette option réécrit tous les fichiers déjà générés. Vos modifications manuelles seront perdues.

### `--dry-run`

Affiche les fichiers qui seraient générés sans en écrire aucun. Utile pour prévisualiser avant de lancer.

```bash
php artisan scaffold:generate database/migrations/... --dry-run
```

Exemple de sortie :

```
  Salabanzi Scaffold
  ─────────────────────────────────
  Model    : Post
  Table    : posts
  Colonnes : 5
  Couches  : 1, 4, 5
  Frontend : none
  Tests    : phpunit
  Mode dry-run — aucun fichier ne sera écrit

── Couche 1
   ok    app/Models/Post.php
   ok    app/Http/Controllers/Api/PostController.php
   ...

Dry-run : 15 fichier(s) seraient générés, 0 ignoré(s).
```

### `--frontend`

Force le générateur à utiliser un frontend spécifique, ignorant la détection automatique.

```bash
# Force Vue
php artisan scaffold:generate database/migrations/... --frontend=vue

# Force React
php artisan scaffold:generate database/migrations/... --frontend=react

# Force Livewire
php artisan scaffold:generate database/migrations/... --frontend=livewire

# Pas de frontend (API pure)
php artisan scaffold:generate database/migrations/... --frontend=none
```

### `--tests`

Force le générateur à utiliser un framework de tests spécifique.

```bash
# Force Pest
php artisan scaffold:generate database/migrations/... --tests=pest

# Force PHPUnit
php artisan scaffold:generate database/migrations/... --tests=phpunit
```

---

## Détection automatique

Le package détecte automatiquement votre environnement pour générer le code adapté.

### Détection du frontend

Le package analyse `composer.json` et `package.json` pour déterminer votre stack frontend.

| Condition détectée | Frontend généré |
|-------------------|-----------------|
| `inertiajs/inertia-laravel` + `vue` dans package.json | Composants Vue + composable |
| `inertiajs/inertia-laravel` + `react` dans package.json | Composants React + hook |
| `livewire/livewire` dans composer.json | Composant Livewire + Blade |
| Aucun frontend détecté | SDK TypeScript + Postman |

### Détection du système d'authentification

| Package détecté | Middleware généré |
|----------------|------------------|
| `laravel/sanctum` | `auth:sanctum` |
| `laravel/passport` | `auth:api` |
| `tymon/jwt-auth` | `auth:api` |
| Aucun | Installe Sanctum automatiquement |

### Détection du framework de tests

| Package détecté | Tests générés |
|----------------|---------------|
| `pestphp/pest` | Syntaxe Pest |
| Aucun | Syntaxe PHPUnit |

### Détection de Filament

La ressource Filament n'est générée que si `filament/filament` est présent dans `composer.json`.

---

## Fichiers générés — exemples

### Model généré

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Règles de validation déduites automatiquement

Le parser analyse chaque colonne et génère les règles correspondantes :

| Type SQL | Règle générée |
|----------|--------------|
| `string('name', 200)` | `required\|string\|max:200` |
| `text()->nullable()` | `nullable\|string` |
| `string('slug')->unique()` | `required\|string\|max:255\|unique:posts,slug` |
| `boolean()` | `required\|boolean` |
| `foreignId('user_id')` | `required\|integer\|exists:users,id` |
| `date()` | `required\|date` |
| `integer()` | `required\|integer` |

---

## Personnalisation des stubs

Tous les templates sont personnalisables. Publiez-les d'abord :

```bash
php artisan vendor:publish --tag=scaffold-stubs
```

Les stubs sont copiés dans `stubs/scaffold/` de votre projet :

```
stubs/scaffold/
├── model.stub
├── controller.stub
├── request.stub
├── policy.stub
├── observer.stub
├── event.stub
├── test-pest.stub
└── test-phpunit.stub
```

Le package cherche d'abord dans `stubs/scaffold/` de votre projet avant d'utiliser ses propres templates. Vos personnalisations sont donc préservées lors des mises à jour du package.

**Exemple de stub personnalisé** — `stubs/scaffold/model.stub` :

```php
<?php

namespace {{ namespace }}\Models;

// Votre en-tête personnalisé ici

class {{ model }} extends Model
{
    // ...
}
```

---

## Configuration

Publiez la configuration :

```bash
php artisan vendor:publish --tag=scaffold-config
```

Contenu de `config/scaffold.php` :

```php
return [

    // Couches activées par défaut quand --layers=default
    'layers' => [1, 4, 5],

    // Namespace de base de l'application
    'namespace' => 'App',

    // Stack frontend (détecté automatiquement si non défini)
    'frontend' => env('SCAFFOLD_FRONTEND', 'none'),

    // Framework de tests
    'tests' => [
        'framework' => env('SCAFFOLD_TESTS', 'pest'),
    ],

    // Filament
    'filament' => [
        'auto_detect' => true,
    ],

];
```

---

## Structure du package

```
salabanzi/laravel-scaffold/
│
├── config/
│   └── scaffold.php                    Configuration par défaut
│
├── stubs/
│   ├── model.stub                      Template du Model
│   ├── controller.stub                 Template du Controller
│   ├── request.stub                    Template des Requests
│   ├── policy.stub                     Template de la Policy
│   ├── observer.stub                   Template de l'Observer
│   ├── event.stub                      Template des Events
│   ├── test-pest.stub                  Template des tests Pest
│   └── test-phpunit.stub               Template des tests PHPUnit
│
├── src/
│   ├── ScaffoldServiceProvider.php     Enregistrement du package
│   ├── MigrationParser.php             Analyse la migration
│   │
│   ├── Support/
│   │   ├── FrontendDetector.php        Détecte Vue/React/Livewire
│   │   ├── AuthDetector.php            Détecte Sanctum/Passport/JWT
│   │   └── TestsDetector.php           Détecte Pest/PHPUnit
│   │
│   ├── Commands/
│   │   └── ScaffoldGenerate.php        Commande Artisan principale
│   │
│   └── Generators/
│       ├── BaseGenerator.php           Classe abstraite parente
│       ├── ModelGenerator.php          Couche 1
│       ├── ControllerGenerator.php     Couche 1
│       ├── RequestGenerator.php        Couche 1
│       ├── ResourceGenerator.php       Couche 1
│       ├── RouteGenerator.php          Couche 1
│       ├── FactoryGenerator.php        Couche 1
│       ├── SeederGenerator.php         Couche 1
│       ├── TestGenerator.php           Couche 1
│       ├── OpenApiGenerator.php        Couche 1
│       ├── TypeScriptSdkGenerator.php  Couche 2
│       ├── PostmanGenerator.php        Couche 2
│       ├── DockerGenerator.php         Couche 3
│       ├── GithubActionsGenerator.php  Couche 3
│       ├── PolicyGenerator.php         Couche 4
│       ├── ObserverGenerator.php       Couche 4
│       ├── EventGenerator.php          Couche 4
│       ├── FilamentResourceGenerator.php Couche 5
│       ├── AuditReportGenerator.php    Couche 5
│       └── ChangelogGenerator.php      Couche 5
│
└── tests/
    └── MigrationParserTest.php
```

---

## Cas d'usage avancés

### Générer plusieurs ressources d'un coup

```bash
for migration in database/migrations/*_create_*_table.php; do
    php artisan scaffold:generate "$migration" --layers=all
done
```

### Utiliser avec un projet Laravel API existant

```bash
# Installer le package
composer require salabanzi/laravel-scaffold --dev

# Générer uniquement le backend (couche 1 et 4)
php artisan scaffold:generate database/migrations/create_articles_table.php --layers=1,4

# Lancer les tests générés
php artisan test
```

### Prévisualiser avant de générer

```bash
# Voir ce qui sera généré sans toucher aux fichiers
php artisan scaffold:generate database/migrations/create_products_table.php --layers=all --dry-run
```

### Régénérer après modification de la migration

```bash
# Réécrit tous les fichiers existants
php artisan scaffold:generate database/migrations/create_posts_table.php --layers=1 --force
```

### Intégration dans un workflow CI

```bash
# Dans votre Makefile ou script de déploiement
php artisan scaffold:generate database/migrations/create_orders_table.php --layers=1,4,5 --tests=pest
php artisan migrate
php artisan test
```

---

## FAQ

**Le package modifie-t-il mes fichiers existants ?**

Non, sauf si vous utilisez l'option `--force`. Par défaut, tout fichier déjà présent reçoit le statut `skip` et n'est pas touché.

**Que se passe-t-il si ma migration utilise des types SQL non reconnus ?**

Le parser utilise `string` comme type par défaut pour les colonnes inconnues. Vous pouvez personnaliser ce comportement en publiant les stubs.

**Le package fonctionne-t-il sans authentification dans le projet ?**

Oui. Si aucun système d'authentification n'est détecté (Sanctum, Passport ou JWT), le package installe automatiquement Laravel Sanctum via `php artisan install:api`.

**Puis-je utiliser le package sur Laravel 10 ?**

Non, le package requiert Laravel 11 minimum car il utilise la syntaxe `bootstrap/app.php` introduite dans Laravel 11.

**Les fichiers générés sont-ils prêts pour la production ?**

Les fichiers sont fonctionnels et suivent les bonnes pratiques Laravel. Cependant, certains éléments comme les policies (qui utilisent `hasRole`) nécessitent un package de gestion des rôles comme `spatie/laravel-permission`.

**Comment désinstaller le package après avoir généré le code ?**

```bash
composer remove salabanzi/laravel-scaffold --dev
```

Les fichiers générés restent dans votre projet. Seul le package est supprimé.

---

## Contribution

Les contributions sont les bienvenues. Pour contribuer :

1. Forkez le repository
2. Créez une branche : `git checkout -b feat/ma-fonctionnalite`
3. Committez vos changements : `git commit -m "feat: description"`
4. Poussez la branche : `git push origin feat/ma-fonctionnalite`
5. Ouvrez une Pull Request

### Convention de commits

```
feat:     nouvelle fonctionnalité
fix:      correction de bug
docs:     modification de la documentation
refactor: refactoring sans changement de comportement
test:     ajout ou modification de tests
```

---

## Licence

MIT — voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

<div align="center">
  Fait avec ❤️ par <a href="https://github.com/salabanzi">Salabanzi</a>
</div>