# Cahier des charges — Gestion Argent Personnel

## Contexte et objectif

Application web de gestion de finances personnelles (comptes bancaires, transactions,
budgets, objectifs d'épargne) développée avec Symfony 8, dans le cadre du projet de fin de
cycle "Conception et développement d'une application Web d'envergure avec Symfony".

## Rôles utilisateurs (Use Cases)

- **Utilisateur (`ROLE_USER`)** : crée et gère ses propres comptes bancaires (courant,
  épargne, carte de crédit), enregistre ses transactions (revenus, dépenses, virements),
  définit des budgets mensuels par catégorie, des objectifs d'épargne et des transactions
  récurrentes. Ne voit et ne modifie que ses propres données.
- **Conseiller (`ROLE_ADVISOR`)** : hérite des droits utilisateur, et dispose en plus d'un
  espace "Mes clients" lui donnant un accès **en lecture seule** aux comptes des clients qui
  lui sont explicitement assignés par un administrateur.
- **Administrateur (`ROLE_ADMIN`)** : hérite des droits conseiller. Gère les comptes
  utilisateurs (statut, rôles), les catégories système, les assignations conseiller/client,
  et consulte le journal d'audit et les statistiques globales.

## Correspondance avec les exigences techniques

### 1. Documentation & livrables
- Cahier des charges : ce document.
- Schéma de base de données : [`docs/schema.puml`](schema.puml) (diagramme entité-association PlantUML).
- Fixtures : `fixtures/*.yaml` (Alice + Faker via `hautelook/alice-bundle`), voir [README](../README.md#comptes-de-test).
- Guide d'installation : [README.md](../README.md).

### 2. Architecture des données & entités
- **16 entités** Doctrine : `User`, `Account` (+ `CheckingAccount`, `SavingsAccount`,
  `CreditCardAccount`), `Category`, `Tag`, `Transaction`, `Attachment`, `Budget`,
  `SavingsGoal`, `RecurringTransaction`, `Notification`, `AuditLog`, `ExchangeRate`,
  `AdvisorAssignment`.
- **Héritage d'entités** : `Account` en *Single Table Inheritance* (discriminant `type`),
  factorisant propriétaire/nom/devise entre les 3 sous-types de compte.
- **ManyToMany** : `Transaction` ↔ `Tag` (simple) ; `User` ↔ `User` via l'entité de liaison
  `AdvisorAssignment` (avec attribut `assignedAt`).
- **OneToMany / ManyToOne** : 14 relations (voir `docs/schema.puml`), largement au-dessus du
  minimum de 8 requis.

### 3. Sécurité & droits d'accès
- Authentification via le Security Component (`form_login`, hachage automatique des mots de
  passe, CSRF, `login_throttling`).
- Hiérarchie de rôles à 3 niveaux : `ROLE_USER` → `ROLE_ADVISOR` → `ROLE_ADMIN`
  ([`config/packages/security.yaml`](../config/packages/security.yaml)).
- Voters personnalisés :
  - `App\Security\Voter\AccountVoter` (VIEW/EDIT/DELETE) — propriétaire, conseiller assigné
    (lecture seule) ou admin.
  - `App\Security\Voter\OwnershipVoter` (VIEW/EDIT) — pour `Budget`, `SavingsGoal`,
    `RecurringTransaction`.
  - `App\Security\Voter\CategoryVoter` (EDIT) — catégories personnalisées vs système.

### 4. API & communications
- Contrôleur API dédié `App\Controller\Api\V1\CurrencyConversionController`
  (`GET /api/v1/convert`) exploitant le Serializer avec un DTO à Groupes explicites.
- Ressources API Platform auto-découvertes sous `/api/v1/*` (`Account` et ses sous-types,
  `Transaction`, `Category`, `Budget`) avec sécurité par expression (`is_granted(...)`).
- Mailer : e-mail de bienvenue à l'inscription, alerte "budget dépassé"
  (`App\Service\BudgetAlertService`).
- Notifier : alerte administrateurs (canal e-mail) en cas de dépassement de budget.
- Consommation d'une API externe : taux de change via l'API Frankfurter
  (`App\Service\ExchangeRateProvider`, `HttpClientInterface`), avec cache "read-through" en
  base (`ExchangeRate`, rafraîchi après 24h).

### 5. Fonctionnalités avancées & qualité du code
- Interface d'administration Twig sur mesure (`Controller/Admin/*`) : utilisateurs, catégories
  système, assignations conseiller/client, journal d'audit, statistiques.
- Formulaires dynamiques : `App\Form\TransactionType` filtre la liste des catégories selon le
  type (revenu/dépense) via les Form Events `PRE_SET_DATA`/`PRE_SUBMIT`, complété côté client
  par un contrôleur Stimulus (`assets/controllers/transaction_form_controller.js`).
- Requêtes QueryBuilder anti-N+1 : `AccountRepository::findAllForUserWithBalances()`,
  `TransactionRepository::findForAccountWithCategoryAndTags()`,
  `BudgetRepository::findWithSpentAmountForUser()`, `AdvisorAssignmentRepository::findClientsForAdvisor()`.
- Plus de 25 pages Twig distinctes (front + admin), héritage de templates
  (`layout/{base,app,admin}.html.twig`), filtre personnalisé `money`/`signed_money`
  (`App\Twig\AppExtension`).

### 6. CI/CD
- `.github/workflows/ci.yml` : lint YAML/Twig/container, PHPStan niveau 5
  (`phpstan.dist.neon`), suite PHPUnit, sur un service PostgreSQL.
- Tests : 4 tests unitaires (`tests/Unit/Security/Voter/AccountVoterTest.php`) et 4 tests
  fonctionnels (`tests/Functional/AccessControlTest.php`).
- Déploiement : Clever Cloud (voir [README](../README.md#déploiement)).
