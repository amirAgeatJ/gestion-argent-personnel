# Gestion Argent Personnel

## Contributeurs

| Nom Prénom | Pseudo GitHub |
|---|---|
| AGEAT JAMLY Amir | [amirAgeatJ](https://github.com/amirAgeatJ) |

## Démo en ligne

Application déployée (Clever Cloud) : https://app-3e15c4b2-7be7-45a6-8380-ff56d749be52.cleverapps.io/

Comptes de test :

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@admin.com` | `admin123` |
| Conseiller | `conseiller11@test.fr` | `conseiller123` |

Application de gestion de finances personnelles (comptes bancaires, transactions, budgets,
objectifs d'épargne, conseillers) — projet de fin de cycle Symfony.

Voir le [cahier des charges détaillé](docs/cahier-des-charges.md) et le
[schéma de base de données](docs/schema.puml).

## Stack technique

- PHP 8.4, Symfony 8
- PostgreSQL 16 (Doctrine ORM/Migrations)
- API Platform 4 (`/api/v1`)
- Twig + Tailwind (CDN), Stimulus/Turbo (Symfony UX, asset-mapper — pas de build Node)
- Mailer (Mailpit en local), Notifier
- Tests : PHPUnit, PHPStan (niveau 5)

## Installation (Docker)

```bash
make install   # build + démarre les conteneurs (php, database, adminer, mailer)
make fixtures  # charge les fixtures de démonstration
```

Une fois démarré :

- Application : http://localhost:8091
- Adminer (BDD) : http://localhost:8090 (serveur `database`, user `app`, mot de passe
  `my-super-secret-password`, base `app`)
- Mailpit (emails interceptés en dev) : http://localhost:8026

Autres commandes utiles : `make sh` (shell dans le conteneur PHP), `make logs`, `make cache`,
`make down`, `make restart`. Voir `make help`.

## Installation sans Docker

```bash
composer install
cp .env.example .env        # adapter DATABASE_URL/MAILER_DSN si besoin
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console hautelook:fixtures:load --no-interaction
symfony server:start        # ou : php -S 127.0.0.1:8000 -t public
```

## Comptes de test

Mot de passe identique pour tous : **`password`**

| Rôle | Email | Description |
|---|---|---|
| Administrateur | `admin@gestion-argent.test` | Accès à `/admin` (utilisateurs, catégories système, conseillers, journal d'audit) |
| Conseiller | `conseiller@gestion-argent.test` | Accès à `/conseiller` — vue lecture seule sur ses clients assignés |
| Utilisateur | `user@gestion-argent.test` | Compte de démonstration principal : comptes, transactions (mai-juillet), budgets, objectif d'épargne, transactions récurrentes |
| Utilisateur (client conseillé) | `client2@gestion-argent.test` | Second client assigné au conseiller, comptes en USD |

Le budget "Courses" de `user@gestion-argent.test` est volontairement dépassé sur le mois en
cours, pour démontrer l'alerte Mailer/Notifier (`App\Service\BudgetAlertService`).

## Tests

```bash
# créer/mettre à jour la base de test (une fois)
APP_ENV=test php bin/console doctrine:database:create --if-not-exists
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction

php bin/phpunit
php vendor/bin/phpstan analyse
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console lint:container
```

La CI GitHub Actions (`.github/workflows/ci.yml`) exécute ces mêmes étapes contre un service
PostgreSQL à chaque push.

## Déploiement

Le projet est prévu pour un déploiement sur **Clever Cloud** :

- `clevercloud/post_build.sh` exécute les migrations à chaque déploiement.
- `config/packages/framework.yaml` (bloc `when@prod`) configure `trusted_proxies` pour le
  reverse-proxy de Clever Cloud.
- Variables d'environnement à définir sur Clever Cloud : `CC_WEBROOT=/public`,
  `CC_POST_BUILD_HOOK=./clevercloud/post_build.sh`, `APP_ENV=prod`, `APP_SECRET`,
  `DATABASE_URL` (référence l'add-on PostgreSQL), `MAILER_DSN`, `CORS_ALLOW_ORIGIN`,
  `TRUSTED_PROXIES`.

## Structure du projet

```
src/
  Entity/           16 entités (dont Account en héritage STI)
  Repository/        méthodes QueryBuilder anti-N+1
  Security/Voter/     AccountVoter, OwnershipVoter, CategoryVoter
  Form/               dont TransactionType (Form Events dynamiques)
  Controller/
    Front/            espace utilisateur (+ Auth)
    Admin/            back-office
    Api/V1/           contrôleur API dédié (conversion de devises)
  Service/            ExchangeRateProvider, TransferService, BudgetAlertService
  EventListener/      AuditLogSubscriber
  Twig/               filtre `money` / `signed_money`
fixtures/             données de démonstration (Alice/Faker)
docs/                 cahier des charges, schéma BDD
```
