# Documentation phpIP

Ce répertoire regroupe l'ensemble de la documentation du projet. Le fichier
[`CLAUDE.md`](../CLAUDE.md) à la racine reste le point d'entrée des conventions
de développement.

## Guides développeur

| Document | Description |
|----------|-------------|
| [TABLE_INTEGRATION.md](TABLE_INTEGRATION.md) | Pattern standard d'intégration des tableaux (DataTable + pagination). |
| [TRANSLATIONS.md](TRANSLATIONS.md) | Gestion des traductions front (`t()`) et base de données (`translated()`). |
| [LOCALIZATION.md](LOCALIZATION.md) | Localisation de l'application (langues, formats de date, refresh des traductions). |

## Guides fonctionnels et exploitation

| Document | Description |
|----------|-------------|
| [AUTOMATIC_STATUS_MANAGEMENT.md](AUTOMATIC_STATUS_MANAGEMENT.md) | Annulation automatique des renouvellements sur statut terminal. |
| [TASK_REMINDERS.md](TASK_REMINDERS.md) | Configuration des e-mails de rappel hebdomadaires (SMTP + cron). |

## Vision produit

| Document | Description |
|----------|-------------|
| [ROADMAP.md](ROADMAP.md) | Cahier des charges de la refonte (fonctionnalités à retirer, must-have, nice-to-have). |

## Installation

Le guide d'installation serveur (Apache / PHP / MySQL) et les scripts associés
se trouvent dans le répertoire [`../doc/`](../doc/).
