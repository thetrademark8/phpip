# Documentation phpIP

Ce répertoire regroupe l'ensemble de la documentation du projet. Le fichier
[`CLAUDE.md`](../CLAUDE.md) à la racine reste le point d'entrée des conventions
de développement.

## Guides développeur

| Document | Description |
|----------|-------------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Organisation du code (couches backend, Inertia/Vue, domaine métier). |
| [TABLE_INTEGRATION.md](TABLE_INTEGRATION.md) | Pattern standard d'intégration des tableaux (DataTable + pagination). |
| [TRANSLATIONS.md](TRANSLATIONS.md) | Gestion des traductions front (`t()`) et base de données (`translated()`). |
| [LOCALIZATION.md](LOCALIZATION.md) | Localisation de l'application (langues, formats de date, refresh des traductions). |

## Guides fonctionnels et exploitation

| Document | Description |
|----------|-------------|
| [AUTOMATIC_STATUS_MANAGEMENT.md](AUTOMATIC_STATUS_MANAGEMENT.md) | Annulation automatique des renouvellements sur statut terminal. |
| [TASK_REMINDERS.md](TASK_REMINDERS.md) | Configuration des e-mails de rappel hebdomadaires (SMTP + cron). |

## Installation

Le guide d'installation serveur (Apache / PHP / MySQL) et les scripts associés
se trouvent dans le répertoire [`../doc/`](../doc/).
