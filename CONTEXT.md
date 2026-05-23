# CONTEXT — Écosystème salabanzi

Fichier de continuité. Contient tout l'état du projet, les décisions techniques, les gotchas et les étapes restantes.
Mis à jour le : 2026-05-23.

---

## Environnement de développement

| Outil | Version |
|-------|---------|
| OS | Windows 11 Pro + Laragon |
| PHP | 8.3.7 (Laragon) |
| Composer | 2.8.12 |
| Node | 22.18.0 |
| npm | 10.9.3 |
| Git | 2.51.0 (Windows) |
| Shell CLI | Bash (via WSL/Git Bash) — PowerShell aussi disponible |

**Répertoire racine de tous les projets :** `C:/laragon/www/`

---

## Vue d'ensemble de l'écosystème

```
salabanzi/
├── laravel-scaffold          ✅ TERMINÉ — package générateur 5 couches
├── laravel-starter-api       ✅ TERMINÉ — Laravel 13 + Sanctum, pure API
├── laravel-starter-vue       ✅ TERMINÉ — Laravel 13 + Inertia + Vue 3 + Tailwind
├── laravel-starter-react     ❌ À FAIRE — Laravel 13 + Inertia + React + Tailwind
├── laravel-starter-livewire  ❌ À FAIRE — Laravel 13 + Livewire + Tailwind
└── laravel-installer         ❌ À FAIRE — CLI PHP : `salabanzi new mon-projet`
```

---

## 1. salabanzi/laravel-scaffold

**Localisation :** `C:/laragon/www/salabanzi-laravel-scaffold`
**Type :** Package Composer (library), pas un projet Laravel complet.
**Tests :** 7/7 via `vendor/bin/pest tests/` — ATTENTION : `php artisan test` ne fonctionne pas ici (pas de artisan, c'est une library).

### Stack

- PHP ^8.2
- illuminate/support|console|filesystem ^11|^12|^13
- orchestra/testbench ^9.0 (dev)
- pestphp/pest ^2.0 (dev)

### Commande principale

```bash
php artisan scaffold:generate {migration}
    --layers=default|1,2,3,4,5|all
    --frontend=vue|react|livewire|none
    --tests=pest|phpunit
    --force
    --dry-run
```

### 22 fichiers générés en 5 couches

| Couche | Générateurs |
|--------|------------|
| 1 | Model, Controller, Request, Resource, Routes, Factory, Seeder, Test, OpenAPI |
| 2 | TypeScript SDK, Postman Collection |
| 3 | Dockerfile, GitHub Actions workflow |
| 4 | Policy, Observer, Events (Created/Updated/Deleted) |
| 5 | Filament Resource, Audit Report, Changelog |

### Fichiers src/

```
src/
├── Commands/ScaffoldGenerate.php          ← commande artisan principale
├── MigrationParser.php                    ← parse les colonnes d'une migration
├── Support/
│   ├── AuthDetector.php                   ← détecte Sanctum/Passport/JWT
│   ├── FrontendDetector.php               ← détecte Vue/React/Livewire
│   └── TestsDetector.php                  ← détecte Pest/PHPUnit
├── Generators/
│   ├── BaseGenerator.php
│   ├── ModelGenerator.php
│   ├── ControllerGenerator.php
│   ├── RequestGenerator.php
│   ├── ResourceGenerator.php
│   ├── RouteGenerator.php                 ← patche bootstrap/app.php (Laravel 13)
│   ├── FactoryGenerator.php
│   ├── SeederGenerator.php
│   ├── TestGenerator.php
│   ├── OpenApiGenerator.php
│   ├── TypeScriptSdkGenerator.php
│   ├── PostmanGenerator.php
│   ├── DockerGenerator.php
│   ├── GithubActionsGenerator.php
│   ├── PolicyGenerator.php
│   ├── ObserverGenerator.php
│   ├── EventGenerator.php
│   ├── FilamentResourceGenerator.php
│   ├── AuditReportGenerator.php
│   └── ChangelogGenerator.php
└── ScaffoldServiceProvider.php
```

### Points importants

- `RouteGenerator` patche `bootstrap/app.php` automatiquement pour enregistrer les routes API (Laravel 13 n'a pas de `app/Http/Kernel.php`).
- `AuthDetector` installe Sanctum automatiquement si aucun package d'auth n'est détecté.
- Auto-détection du frontend via `package.json` (recherche `vue`, `react`, `livewire`).
- Auto-détection des tests via `composer.json` (recherche `pestphp/pest`).

### Git log

```
2e594b0 docs: README complet avec toutes les sections
ee5e53f feat: AuthDetector - détecte Sanctum/Passport/JWT et installe si absent
db7effb feat: RouteGenerator installe Sanctum automatiquement si aucun auth détecté
e47f0ed fix: tests assertions + auth:sanctum middleware sur les routes
04ba992 feat: RouteGenerator patche automatiquement bootstrap/app.php pour Laravel 13
...
```

---

## 2. laravel-starter-api ✅

**Localisation :** `C:/laragon/www/laravel-starter-api`
**Tests :** `php artisan test` → **10/10 — 26 assertions**
**Git :** initialisé, 1 commit `c1f3853`

### Stack

- Laravel 13.11.2 (PHP ^8.3)
- laravel/sanctum ^4.3.2
- DB : SQLite en test/dev, MySQL en prod
- Pas de frontend, pas de Vite, pas de Node

### Structure app/

```
app/Http/Controllers/Auth/AuthController.php
app/Http/Requests/Auth/LoginRequest.php
app/Http/Requests/Auth/RegisterRequest.php
app/Http/Resources/UserResource.php
app/Models/User.php                          ← HasApiTokens + HasFactory + Notifiable
```

### Routes (`routes/api.php`)

```
POST  /api/v1/auth/register   throttle:6,1   (public)
POST  /api/v1/auth/login      throttle:10,1  (public)
POST  /api/v1/auth/logout     auth:sanctum   (privé)
GET   /api/v1/auth/me         auth:sanctum   (privé)
```

### bootstrap/app.php — points clés

- Route `web` SUPPRIMÉE (API pure, pas besoin)
- `$middleware->statefulApi()` activé
- Exception handler JSON pour 401 (`AuthenticationException`) et 422 (`ValidationException`)
- Catch sur `$request->is('api/*')` pour forcer JSON même sans header `Accept`

### Tests (`tests/Feature/Auth/AuthTest.php`)

```
✅ test_user_can_register
✅ test_register_validates_required_fields
✅ test_register_requires_unique_email
✅ test_user_can_login
✅ test_login_fails_with_wrong_credentials
✅ test_authenticated_user_can_get_profile
✅ test_unauthenticated_request_returns_401
✅ test_user_can_logout
✅ (ExampleTest) test_health_check_returns_ok   ← /up endpoint
```

### DevOps

- `Dockerfile` : php-fpm Alpine + Nginx + Supervisord
- `docker-compose.yml` : app (port 8000) + MySQL 8 avec healthcheck
- `docker/nginx.conf` + `docker/supervisord.conf`
- `.github/workflows/tests.yml` : CI sur push main/develop et PR main

### .env par défaut

- `DB_CONNECTION=sqlite` (local/test)
- `.env.example` avec MySQL activé (pour prod/CI)

---

## 3. laravel-starter-vue ✅

**Localisation :** `C:/laragon/www/laravel-starter-vue`
**Tests :** `php artisan test` → **25/25 — 61 assertions**
**Git :** initialisé, 1 commit `9b97aad`

### Stack

- Laravel 13.11.2 (PHP ^8.3)
- laravel/breeze ^2.4.2 (dev-only, scaffold outil)
- inertiajs/inertia-laravel ^2.0.24
- laravel/sanctum ^4.3.2
- tightenco/ziggy ^2.6.2
- Vue 3 (Composition API)
- Tailwind CSS v4
- Vite 8
- Node 22

### Structure frontend (`resources/js/`)

```
Pages/
├── Auth/
│   ├── Login.vue, Register.vue, ForgotPassword.vue
│   ├── ResetPassword.vue, VerifyEmail.vue, ConfirmPassword.vue
├── Profile/
│   ├── Edit.vue
│   └── Partials/ (UpdateProfileInformationForm, UpdatePasswordForm, DeleteUserForm)
├── Dashboard.vue
└── Welcome.vue
Layouts/
├── AuthenticatedLayout.vue
└── GuestLayout.vue
Components/
└── 14 composants (TextInput, PrimaryButton, Modal, Dropdown...)
app.js      ← Inertia + ZiggyVue setup
bootstrap.js ← axios config (AJOUTÉ MANUELLEMENT — manquait après breeze:install)
```

### Routes

- Web : `/`, `/dashboard`, `/profile` + toutes les routes Breeze (`routes/auth.php`)
- Pas de routes API dans ce starter (session-based auth via Inertia)

### bootstrap/app.php — points clés

- Route `web` active (SPA via Inertia)
- Middleware Inertia : `HandleInertiaRequests` + `AddLinkHeadersForPreloadedAssets`
- Pas de modification des exceptions (Breeze gère)

### Tests

```
tests/Feature/Auth/
  ✅ AuthenticationTest.php     (login/logout session)
  ✅ EmailVerificationTest.php
  ✅ PasswordConfirmationTest.php
  ✅ PasswordResetTest.php
  ✅ PasswordUpdateTest.php
  ✅ RegistrationTest.php
tests/Feature/
  ✅ ProfileTest.php             (update info, update password, delete account)
  ✅ ExampleTest.php             (health check /up)
```

### DevOps

- `Dockerfile` **multi-stage** : Node (build assets) → php-fpm + Nginx
- `docker-compose.yml` : app (port 8001) + service `node` (hot-reload, profile `dev`) + MySQL 8 (port 3307)
- `docker-compose --profile dev up` pour le dev avec Vite live
- `.github/workflows/tests.yml` : CI avec build Node + tests PHP sur MySQL

### GOTCHA — bootstrap.js manquant

Breeze v2.4.2 génère un `app.js` qui importe `./bootstrap` mais ne crée pas le fichier.
**Solution appliquée :** créer manuellement `resources/js/bootstrap.js` :

```js
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

Et vérifier que `axios` est bien dans `package.json` (il l'est via `npm install axios`).

### GOTCHA — Race condition Composer

Si deux `composer` tournent en parallèle sur le même projet, l'autoloader est corrompu :
```
Class "Composer\Autoload\ComposerStaticInit..." not found
```
**Solution :** `rm -rf vendor && composer install`

---

## 4. laravel-starter-react ❌ À FAIRE

**Localisation cible :** `C:/laragon/www/laravel-starter-react`

### Plan d'installation

```bash
cd C:/laragon/www
composer create-project laravel/laravel laravel-starter-react
cd laravel-starter-react
php artisan key:generate
composer require laravel/breeze --dev
php artisan breeze:install react --no-interaction
# ⚠ Créer resources/js/bootstrap.js si manquant (même gotcha que Vue)
npm install axios   # si nécessaire
npm run build
php artisan migrate --force
php artisan test
```

### Différences vs Vue

- `@inertiajs/react` au lieu de `@inertiajs/vue3`
- `@vitejs/plugin-react` au lieu de `@vitejs/plugin-vue`
- Pages en `.jsx` / `.tsx` au lieu de `.vue`
- Pas de `ZiggyVue`, mais `ziggy-js` directement
- `jsconfig.json` ou `tsconfig.json` adapté React
- Breeze génère les mêmes pages auth mais en JSX

### Ce qui sera identique à Vue

- Structure Laravel (Controllers, Requests, Middleware, bootstrap/app.php)
- Tests Feature (même suite Breeze)
- Dockerfile multi-stage (remplacer plugin Vue par React)
- docker-compose.yml (port 8002)
- GitHub Actions
- .env.example

---

## 5. laravel-starter-livewire ❌ À FAIRE

**Localisation cible :** `C:/laragon/www/laravel-starter-livewire`

### Plan d'installation

```bash
cd C:/laragon/www
composer create-project laravel/laravel laravel-starter-livewire
cd laravel-starter-livewire
php artisan key:generate
composer require laravel/breeze --dev
php artisan breeze:install livewire --no-interaction
# Livewire stack = blade + Alpine.js + Tailwind (pas de Vite SPA)
npm install && npm run build
php artisan migrate --force
php artisan test
```

### Différences vs Vue/React

- **Pas d'Inertia.js** — rendu server-side avec Blade + Livewire
- **Alpine.js** pour les interactions légères côté client
- Pages en `.blade.php` au lieu de `.vue`/`.jsx`
- Livewire components : `app/Livewire/` (PHP) + `resources/views/livewire/` (Blade)
- **Pas de `bootstrap.js`** à créer manuellement (Livewire n'utilise pas axios)
- docker-compose.yml (port 8003) — Dockerfile plus simple (pas de multi-stage Node lourd)

---

## 6. laravel-installer ❌ À FAIRE

**Localisation cible :** `C:/laragon/www/laravel-installer`
**Type :** Binaire CLI PHP autonome (`salabanzi new mon-projet`)

### Comportement attendu

```bash
salabanzi new mon-projet
# → menu interactif :
#   Stack ?       [api / vue / react / livewire]
#   DB ?          [sqlite / mysql / pgsql]
#   Auth ?        [sanctum / passport / jwt]
#   Tests ?       [pest / phpunit]
#   Git init ?    [yes / no]
# → clone le bon starter depuis GitHub
# → configure .env
# → composer install + php artisan key:generate + migrate
# → npm install + npm run build (si frontend)
# → message de bienvenue
```

### Plan technique

```
laravel-installer/
├── bin/salabanzi          ← entrée exécutable (PHP CLI, shebang #!/usr/bin/env php)
├── src/
│   ├── NewCommand.php     ← commande principale (symfony/console)
│   ├── Installer.php      ← logique d'installation
│   └── Stubs.php          ← templates .env par stack
├── composer.json          ← bin: ["bin/salabanzi"]
└── README.md
```

### Dépendances

```json
{
  "require": {
    "php": "^8.2",
    "symfony/console": "^7.0",
    "symfony/process": "^7.0",
    "guzzlehttp/guzzle": "^7.0"
  }
}
```

### Starters à cloner par stack

```php
const STARTERS = [
    'api'      => 'https://github.com/salabanzi/laravel-starter-api',
    'vue'      => 'https://github.com/salabanzi/laravel-starter-vue',
    'react'    => 'https://github.com/salabanzi/laravel-starter-react',
    'livewire' => 'https://github.com/salabanzi/laravel-starter-livewire',
];
```

---

## Ports Docker par projet

| Projet | Port HTTP | Port MySQL |
|--------|-----------|------------|
| laravel-starter-api | 8000 | 3306 |
| laravel-starter-vue | 8001 | 3307 |
| laravel-starter-react | 8002 | 3308 |
| laravel-starter-livewire | 8003 | 3309 |

---

## Patterns communs à tous les starters

### Structure Dockerfile (API / no frontend)

```dockerfile
FROM php:8.3-fpm-alpine
RUN apk add --no-cache nginx supervisor curl zip unzip git \
    && docker-php-ext-install pdo pdo_mysql opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --optimize-autoloader --no-dev --no-interaction \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
```

### Dockerfile multi-stage (Vue / React)

```dockerfile
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js tsconfig.json* ./
COPY public/ public/
COPY vendor/tightenco/ziggy vendor/tightenco/ziggy
RUN npm run build

FROM php:8.3-fpm-alpine
# ... (même que API) + COPY --from=assets /app/public/build public/build
```

### GitHub Actions pattern

- Trigger : push `main`/`develop` + PR `main`
- Services : MySQL 8 avec healthcheck
- Steps : checkout → setup-php 8.3 → cache composer → composer install → cp .env.example .env.testing → key:generate → migrate → test
- Pour Vue/React : ajouter setup-node 22 + `npm ci` + `npm run build` entre composer et tests

### .env.example pattern

- `DB_CONNECTION=mysql` (pas sqlite, même si dev utilise sqlite)
- `APP_URL=http://localhost:PORT` avec le bon port
- `SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1` (pour les starters avec Sanctum)
- VITE_APP_NAME pour les starters frontend

---

## Ordre de création recommandé

1. ✅ `laravel-starter-api`
2. ✅ `laravel-starter-vue`
3. ❌ `laravel-starter-react` ← **prochain**
4. ❌ `laravel-starter-livewire`
5. ❌ `laravel-installer` ← en dernier (dépend de tous les starters)

---

## Commandes de vérification rapide

```bash
# Tester tous les starters en une fois
cd C:/laragon/www/laravel-starter-api && php artisan test
cd C:/laragon/www/laravel-starter-vue && php artisan test
cd C:/laragon/www/laravel-starter-react && php artisan test    # quand créé
cd C:/laragon/www/laravel-starter-livewire && php artisan test # quand créé

# Tester le package scaffold
cd C:/laragon/www/salabanzi-laravel-scaffold && vendor/bin/pest tests/

# Vérifier que les builds frontend passent
cd C:/laragon/www/laravel-starter-vue && npm run build
cd C:/laragon/www/laravel-starter-react && npm run build       # quand créé
```
