# Architecture

phpIP est une application Laravel 12 dont l'interface est une SPA Vue 3 servie
via Inertia. Ce document décrit l'organisation du code et les conventions à
respecter.

## Vue d'ensemble

```
┌──────────────────────────────────────────────┐
│  Frontend — Vue 3 + Inertia (resources/js)    │
├──────────────────────────────────────────────┤
│  Controllers (app/Http/Controllers)           │
├──────────────────────────────────────────────┤
│  Services — logique métier (app/Services)     │
├──────────────────────────────────────────────┤
│  Repositories — accès données (app/Repositories) │
├──────────────────────────────────────────────┤
│  Eloquent Models (app/Models)                 │
├──────────────────────────────────────────────┤
│  Base de données MySQL / MariaDB              │
└──────────────────────────────────────────────┘
```

## Backend (`app/`)

| Répertoire | Rôle |
|------------|------|
| `Http/Controllers` | Point d'entrée HTTP. Orchestrent services/repositories et renvoient des réponses Inertia. |
| `Services` | Logique métier (Matter, Renewal, Notification, DocumentMerge, imports…). |
| `Repositories` | Accès aux données encapsulant les requêtes Eloquent complexes. |
| `Contracts` | Interfaces des services et repositories (injection de dépendances). |
| `Models` | Modèles Eloquent du domaine. |
| `Events` / `Listeners` | Réactions métier (ex. annulation des renouvellements sur statut terminal). |
| `Notifications` / `Mail` | E-mails et notifications. |
| `Providers` | Enregistrement des liaisons interface → implémentation. |

### Injection de dépendances

Les interfaces sont liées à leurs implémentations dans les service providers,
ce qui permet de typer les dépendances par contrat dans les constructeurs :

- `RepositoryServiceProvider` — services et repositories généraux.
- `RenewalServiceProvider` — sous-système de renouvellement.

Exemple : `MatterServiceInterface` → `MatterService`,
`MatterRepositoryInterface` → `MatterRepository`.

### Pattern d'un flux type

```
Controller → Service (règles métier) → Repository (requêtes) → Model → DB
```

Les contrôleurs restent fins : ils valident la requête, délèguent au service,
puis retournent une réponse Inertia.

## Couche de présentation (Inertia)

Tous les contrôleurs (hors endpoints d'autocomplétion/API) retournent une
réponse Inertia via `Inertia::render('Module/Page', [...])`. Les données JSON
brutes ne sont renvoyées que pour les requêtes non-Inertia.

## Frontend (`resources/js/`)

| Répertoire | Rôle |
|------------|------|
| `Pages` | Pages Inertia, une par écran (Matter, Actor, Renewal…). |
| `components` | Composants Vue regroupés par module + `ui/` (shadcn-vue). |
| `components/ui` | Composants de base réutilisables (DataTable, Pagination, Card…). |
| `composables` | Logique réutilisable (`useXxxFilters`, `useTranslation`, `usePermissions`…). |
| `Layouts` | Layouts applicatifs (ex. `MainLayout`). |
| `services` / `lib` / `constants` | Helpers front, configuration, constantes. |

Conventions front détaillées :
- Tableaux → voir [TABLE_INTEGRATION.md](TABLE_INTEGRATION.md).
- Traductions → voir [TRANSLATIONS.md](TRANSLATIONS.md).

## Domaine métier

- **Matter** (« Dossier ») — entité centrale (brevet, marque, dessin).
- **Actor** — partie liée à un dossier via un rôle (client, mandataire, inventeur…).
- **Event** / **Task** — événements d'un dossier et tâches à échéance qui en découlent.
- **Rule** — règles configurables générant tâches et échéances à partir des événements.
- **Renewal** — workflow de gestion des renouvellements (annuités) et de ses e-mails.

## Tests

Suite Pest organisée en groupes `unit`, `feature` et `arch`. Les tests
d'architecture vérifient le respect des conventions (suffixes, interfaces). Voir
[`tests/README.md`](../tests/README.md).
