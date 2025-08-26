# Plan Technique d'Implémentation - Refonte phpIP

## Table des matières
1. [Vue d'ensemble technique](#1-vue-densemble-technique)
2. [Architecture et état actuel](#2-architecture-et-état-actuel)
3. [Plan d'implémentation par phases](#3-plan-dimplémentation-par-phases)
4. [Détails techniques par fonctionnalité](#4-détails-techniques-par-fonctionnalité)
5. [Stratégie de tests](#5-stratégie-de-tests)
6. [Plan de déploiement](#6-plan-de-déploiement)
7. [Gestion des risques](#7-gestion-des-risques)
8. [Estimations et ressources](#8-estimations-et-ressources)

## 1. Vue d'ensemble technique

### Stack technologique actuel
- **Backend**: Laravel 12.0 avec PHP 8.2+
- **Frontend**: Vue.js 3.5.17 avec Composition API
- **Bridge SPA**: Inertia.js 2.0
- **Styling**: Tailwind CSS 4.1.11 + shadcn-vue
- **Base de données**: MySQL/MariaDB
- **Tests**: Pest PHP 3.0
- **Build**: Vite 6.3.5

### Score de santé: 7/10
- ✅ Architecture moderne et modulaire
- ✅ Refactoring récent en cours
- ⚠️ Migration Blade vers Vue en cours
- ⚠️ Couverture de tests partielle

## 📊 STATUT ACTUEL D'IMPLÉMENTATION (Mise à jour: 19 Août 2025)

### 🎯 **PHASE ACTUELLE : PHASE 4 - Notifications & Automatisations**

**Progression globale Phase 4: ~60% (3/5 sous-phases terminées)**

| Sous-phase | Statut | Durée prévue | Durée réelle | Reste |
|------------|--------|--------------|--------------|-------|
| 4A: Service Notification Global | ✅ **TERMINÉ** | 1-2 jours | ~2 jours | - |
| 4B: Scheduling Automatique | ✅ **TERMINÉ** | 1 jour | ~1 jour | - |
| 4C: Templates Email Améliorés | 🔄 **EN COURS (70%)** | 2-3 jours | 2 jours | 1 jour |
| 4D: Automatisation Marques Int. | 📋 **À FAIRE** | 2-3 jours | - | 2-3 jours |
| 4E: Liens Automatiques Offices | 📋 **À FAIRE** | 1-2 jours | - | 1-2 jours |

**⏱️ Temps restant estimé Phase 4**: 4-6 jours

### 🏆 **Éléments Complétés Récemment**
- ✅ **Database collation standardisée** (utf8mb4_unicode_ci)
- ✅ **Navigation dynamique** categories de dossiers
- ✅ **Système classifiers** corrigé (container_id logic)
- ✅ **Service NotificationService** + interface
- ✅ **Classes de notifications** (TaskReminder, UrgentTasks, etc.)
- ✅ **TaskEmailService** opérationnel
- ✅ **Templates de base** créés dans resources/views/notifications/
- ✅ **Traductions complètes** (EN/FR/DE) pour tous composants majeurs
- ✅ **Modal MatterEventManager** entièrement traduit

### 🎯 **Prochaines Priorités Immédiates**
1. **Cette semaine**: Finaliser Phase 4C (templates email Outlook-compatibles)
2. **Semaine prochaine**: Phase 4D (automatisation marques internationales)
3. **Après Phase 4**: Phase 4E puis passage à Phase 5

### 📅 **Planning Détaillé Phase 4 - Prochaines Semaines**

#### **Semaine Actuelle (19-23 Août 2025)**
- **Jour 1-2**: 🔄 Finaliser Phase 4C 
  - Améliorer styles CSS Outlook/Gmail
  - Tests compatibilité multi-clients email
  - ✅ Marquer 4C comme terminé

#### **Semaine Prochaine (26-30 Août 2025)**
- **Jour 1-3**: 📋 Phase 4D - Automatisation Marques Internationales
  - Créer InternationalTrademarkService.php
  - Développer composant Vue InternationalTrademarkCreator
  - Tests création batch de dossiers pays
- **Jour 4-5**: 📋 Phase 4E - Liens Automatiques Offices  
  - Créer LinkGeneratorService.php
  - Développer composant Vue OfficeLinks
  - Intégrer dans l'interface matter

#### **Début Septembre 2025**
- **✅ Phase 4 TERMINÉE** → Transition vers **Phase 5: Intégrations Externes**
- Bilan de la Phase 4 et planification Phase 5

## 2. Architecture et état actuel

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Frontend (Vue.js + Inertia)                 │
├─────────────────────────────────────────────────────────────────────┤
│                     Controllers (Laravel + Middleware)              │
├─────────────────────────────────────────────────────────────────────┤
│                         Services (Business Logic)                   │
├─────────────────────────────────────────────────────────────────────┤
│                      Repositories (Data Access)                     │
├─────────────────────────────────────────────────────────────────────┤
│                         Database (MySQL/MariaDB)                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Modules principaux
- **Matter Management**: Gestion des dossiers PI
- **Renewal System**: Système de renouvellement automatisé
- **Fee System**: Calcul et suivi des frais
- **Notification System**: Alertes et rappels email

## 3. Plan d'implémentation par phases

### Timeline globale: 20-26 semaines (5-6.5 mois)

### PHASE 1: Améliorations UI/UX Critiques (4-6 semaines)

#### 1.1 Calendrier Interactif (P0 - 1-2 semaines)
**Objectif**: Remplacer tous les champs de date par un calendrier interactif

**Implémentation technique**:
```javascript
// Extension du composant DatePicker existant
// resources/js/Components/ui/date-picker/DatePicker.vue
- Utiliser VCalendar ou date-fns (déjà installé)
- Support multi-langue (FR/EN/DE)
- Validation Zod intégrée
```

**Fichiers à modifier**:
- `resources/js/Components/dialogs/MatterDialog.vue`
- `resources/js/Components/dialogs/TaskDialog.vue`
- `resources/js/Components/dialogs/EventDialog.vue`
- Tous les formulaires avec champs date

#### 1.2 Conservation de la Casse (P0 - 1 semaine)
**Objectif**: Préserver la casse originale des titres

**Modifications base de données**:
```sql
-- Migration Laravel
ALTER TABLE matters 
MODIFY title VARCHAR(255) 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_bin;

ALTER TABLE matters 
MODIFY alt_title VARCHAR(255) 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_bin;
```

**Modifications backend**:
```php
// app/Models/Matter.php
protected $casts = [
    'title' => 'string', // Supprimer toute transformation
];

// app/Repositories/MatterRepository.php
public function search($query) {
    return $this->model
        ->where('title', 'LIKE', "%{$query}%") // Case-insensitive
        ->orWhereRaw('LOWER(title) LIKE LOWER(?)', ["%{$query}%"]);
}
```

#### 1.3 Système de Logo (P0 - 2-3 semaines)
**Objectif**: Afficher le logo sur login et navigation

**Nouvelle table**:
```sql
CREATE TABLE company_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (key)
);
```

**API Endpoints**:
```
GET    /api/settings/logo
POST   /api/settings/logo (multipart/form-data)
DELETE /api/settings/logo
```

**Composants Vue**:
```javascript
// resources/js/Components/settings/LogoUploader.vue
// resources/js/Components/common/CompanyLogo.vue
```

### PHASE 2: Gestion Catégories & Statuts (2-3 semaines)

#### 2.1 Mise à jour Menu Matters (P1 - 1 semaine)
**Modifications**:
- Supprimer catégorie "Patents"
- Ajouter catégories marques manquantes

**Seeder à modifier**:
```php
// database/seeders/CategorySeeder.php
$categories = [
    ['code' => 'TM', 'name' => ['en' => 'Trademark', 'fr' => 'Marque', 'de' => 'Marke']],
    ['code' => 'DES', 'name' => ['en' => 'Design', 'fr' => 'Dessin', 'de' => 'Design']],
    // Supprimer: ['code' => 'PAT', 'name' => 'Patent'],
];
```

#### 2.2 Gestion Automatique Statuts (P1 - 1-2 semaines)
**Event Listener**:
```php
// app/Events/MatterStatusChanged.php
class MatterStatusChanged {
    public function __construct(public Matter $matter, public string $oldStatus) {}
}

// app/Listeners/HandleStatusChange.php
class HandleStatusChange {
    public function handle(MatterStatusChanged $event) {
        if (in_array($event->matter->status, ['Refused', 'Abandoned'])) {
            // Supprimer dates de renouvellement
            $event->matter->renewals()->delete();
            $event->matter->tasks()
                ->where('code', 'REN')
                ->update(['done_date' => now()]);
        }
    }
}
```

### PHASE 3: Recherche & Affichage (3-4 semaines)

#### 3.1 Compteur de Résultats (P1 - 1 semaine)
**Modification Controller**:
```php
// app/Http/Controllers/MatterController.php
public function index(Request $request) {
    $query = Matter::query();
    // ... filtres ...
    
    $total = $query->count(); // Compteur total
    $matters = $query->paginate(25);
    
    return Inertia::render('Matter/Index', [
        'matters' => $matters,
        'total' => $total,
        'filters' => $request->all()
    ]);
}
```

#### 3.2 Fusion Actor/Status View (P1 - 2 semaines)
**Nouveau composant unifié**:
```javascript
// resources/js/Components/matter/UnifiedMatterTable.vue
// Colonnes: Title, Client, Status, Categories, Deadline, Actions
```

**Export CSV adapté**:
```php
// app/Services/ExportService.php
public function exportMatters($matters) {
    $headers = ['Title', 'Client', 'Status', 'Category', 'Deadline'];
    // ... génération CSV ...
}
```

### PHASE 4: Notifications & Automatisations (7-11 jours - 5 sous-phases)

#### PHASE 4A: Service de Notification Global ✅ **TERMINÉ** (P1 - 1-2 jours)
**Objectif**: Centraliser la gestion des notifications
- ✅ Table `notifications` déjà créée (migration 2025_08_06_141147)
- ✅ Système renewal notifications déjà implémenté
- ✅ **NotificationService créé** avec interface complète
- ✅ **Classes de notifications** implémentées :
  - TaskReminderNotification.php
  - UrgentTasksNotification.php  
  - TasksSummaryNotification.php
  - SystemTasksSummaryNotification.php
- ✅ **TaskEmailService** opérationnel

**Service créé**:
```php
// ✅ app/Services/NotificationService.php (CRÉÉ)
class NotificationService implements NotificationServiceInterface {
    // Toutes les méthodes de l'interface implémentées
    public function sendTaskReminder(Task $task, User $recipient): bool;
    public function sendUpcomingTaskReminders(int $daysAhead = 7): int;
    public function sendRenewalNotification(Task $renewalTask, array $recipients): bool;
    public function sendStatusChangeNotification(string $matterId, string $oldStatus, string $newStatus, array $recipients): bool;
}
```

#### PHASE 4B: Scheduling Automatique ✅ **TERMINÉ** (P1 - 1 jour)
**Objectif**: Automatiser l'envoi des rappels
- ✅ Command `SendTasksDueEmail` déjà existant
- ✅ **Scheduling configuré** dans Kernel.php
- ✅ **TaskEmailService** intégré avec le système de notifications

**Modifications effectuées**:
```php
// ✅ app/Console/Kernel.php (CONFIGURÉ)
protected function schedule(Schedule $schedule): void {
    $schedule->command('tasks:send-due-email')
        ->dailyAt('08:00')
        ->withoutOverlapping();
}
```

#### PHASE 4C: Templates Email Améliorés 🔄 **EN COURS (~70%)** (P1 - 2-3 jours)
**Objectif**: Améliorer l'apparence des emails
- ✅ Templates existants: renewalCall.blade.php, renewalInvoice.blade.php, renewalReport.blade.php
- ✅ **Templates de notifications** créés dans resources/views/notifications/
- ✅ **Base structure HTML** mise en place
- 🔄 **Styles Outlook-compatibles** en cours d'amélioration
- 📋 **Finalisation CSS responsive** à compléter

**Améliorations en cours**:
```blade
{{-- ✅ resources/views/notifications/ (CRÉÉ) --}}
{{-- 🔄 Amélioration des styles Outlook (EN COURS) --}}
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Styles Outlook-compatibles - EN COURS */
        table { border-collapse: collapse; width: 100%; }
        .email-header { background: #f8f9fa; padding: 20px; }
        .renewal-table { border: 1px solid #dee2e6; }
        .total-row { background: #e9ecef; font-weight: bold; }
    </style>
</head>
<body>
    {{-- Contenu HTML structuré pour Outlook --}}
</body>
</html>
```

**Prochaine étape**: Finaliser les styles CSS pour compatibilité Outlook/Gmail (1 jour restant)

#### PHASE 4D: Automatisation Marques Internationales 📋 **PROCHAINE PRIORITÉ** (P2 - 2-3 jours)
**Objectif**: Création automatique de dossiers pays multiples

**⚠️ Étape critique pour automatisation workflow**

**Service à créer** (prochaine tâche):
```php
// 📋 app/Services/InternationalTrademarkService.php (À CRÉER)
class InternationalTrademarkService {
    public function createCountryMatters(Matter $internationalMatter, array $countries): array;
    public function duplicateMatterData(Matter $source, Matter $target): void;
    public function getAvailableCountries(): Collection;
    
    // Nouvelles méthodes suggérées:
    public function validateCountrySelection(array $countries): bool;
    public function estimateFees(array $countries): array;
}
```

**Interface Vue à créer**:
```javascript
// 📋 resources/js/Components/matter/InternationalTrademarkCreator.vue (À CRÉER)
// - Sélecteur multi-pays avec recherche
// - Aperçu des dossiers à créer avec estimation coûts
// - Validation avant création batch
// - Progress bar pour création multiple
```

**Planning suggéré**: Semaine prochaine (après finalisation 4C)

#### PHASE 4E: Liens Automatiques Offices 📋 **DERNIÈRE ÉTAPE PHASE 4** (P2 - 1-2 jours)
**Objectif**: Génération automatique de liens vers les offices de PI

**💡 Fonctionnalité très utile pour accès rapide aux dossiers officiels**

**Service à créer**:
```php
// 📋 app/Services/LinkGeneratorService.php (À CRÉER - FINAL PHASE 4)
class LinkGeneratorService {
    private array $patterns = [
        'WIPO' => 'https://www3.wipo.int/madrid/monitor/en/showData.jsp?ID=%s',
        'USPTO' => 'https://tsdr.uspto.gov/#caseNumber=%s&caseType=SERIAL_NO',
        'EUIPO' => 'https://euipo.europa.eu/eSearch/#details/trademarks/%s',
        'UKIPO' => 'https://trademarks.ipo.gov.uk/ipo-tmcase/page/Results/1/%s',
        'INPI' => 'https://data.inpi.fr/marques/%s',
        'DPMA' => 'https://register.dpma.de/DPMAregister/marke/basis?AKZ=%s',
        'JPO' => 'https://www.j-platpat.inpit.go.jp/c1800/TR/JP-%s'
    ];
    
    public function generateLink(string $office, string $number): ?string;
    public function getAllLinksForMatter(Matter $matter): array;
    public function isValidNumberFormat(string $office, string $number): bool;
}
```

**Composant Vue à créer**:
```javascript
// 📋 resources/js/Components/matter/OfficeLinks.vue (À CRÉER)
// - Liens automatiques basés sur numéros de dépôt/publication
// - Ouverture dans nouvel onglet avec icônes office
// - Validation format numéros avant génération liens
// - Tooltips avec nom complet office
```

**Planning**: Après 4D, finalise la Phase 4 (1-2 jours)

**Ordre de progression**: 4A ✅ → 4B ✅ → 4C 🔄 → 4D 📋 → 4E 📋

**📈 Progression Phase 4**: **60% complète** (3/5 sous-phases terminées)
**⏰ Temps restant estimé**: 4-6 jours

### PHASE 5: Intégrations Externes (1-2 semaines)

#### 5.1 Templates Email Avancés (P2 - 1 semaine)
**Configuration des templates**:
```php
// config/email-templates.php
return [
    'templates' => [
        'renewal_first_call' => [
            'subject' => ['en' => 'Renewal reminder', 'fr' => 'Rappel de renouvellement'],
            'protected' => false, // Accessible aux clients
        ],
        'internal_report' => [
            'subject' => ['en' => 'Internal report', 'fr' => 'Rapport interne'],
            'protected' => true, // Interne uniquement
        ],
    ]
];
```

### PHASE 6: Fonctionnalités Avancées (6-8 semaines)

#### 6.1 Tableaux de Bord & Statistiques (P2 - 3-4 semaines)
**API Endpoints statistiques**:
```php
// app/Http/Controllers/Api/StatisticsController.php
class StatisticsController {
    public function yearlyFilings() {
        return Matter::selectRaw('MONTH(filing_date) as month, COUNT(*) as count')
            ->whereYear('filing_date', now()->year)
            ->groupBy('month')
            ->get();
    }
    
    public function territoryDistribution() {
        return Matter::selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->get();
    }
    
    public function upcomingRenewals() {
        return Task::where('code', 'REN')
            ->whereBetween('due_date', [now(), now()->addYear()])
            ->count();
    }
}
```

**Composants Dashboard Vue**:
```javascript
// resources/js/Pages/Dashboard/Index.vue
// Utiliser Chart.js ou ApexCharts pour visualisations
import { Bar, Pie, Line } from 'vue-chartjs'
```

#### 6.2 Modernisation UI/UX (P2 - 2-3 semaines)
**Nouveau système de design**:
```javascript
// tailwind.config.js - Extension configuration
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: { /* Palette personnalisée */ },
        secondary: { /* ... */ }
      }
    }
  }
}
```

**Composants shadcn-vue à implémenter**:
- DataTable amélioré avec tri/filtres
- Cards pour dashboard
- Forms avec validation temps réel
- Notifications toast

#### 6.3 Traduction Française Complète (P2 - 1-2 semaines)
**Structure des fichiers de traduction**:
```
lang/
├── en.json (référence complète)
├── fr.json (à compléter)
└── de.json (partiel)
```

**Audit et complétion**:
```php
// app/Console/Commands/AuditTranslations.php
class AuditTranslations {
    public function handle() {
        $enKeys = array_keys(json_decode(file_get_contents(lang_path('en.json')), true));
        $frKeys = array_keys(json_decode(file_get_contents(lang_path('fr.json')), true));
        
        $missing = array_diff($enKeys, $frKeys);
        // Générer rapport des traductions manquantes
    }
}
```

## 4. Détails techniques par fonctionnalité

### Modifications Base de Données Requises

```sql
-- Phase 1: Logo système
CREATE TABLE company_settings (...);

-- Phase 4: Notifications
CREATE TABLE notification_settings (...);

-- Phase 3: Champ libellé marque
ALTER TABLE matters ADD COLUMN trademark_label TEXT NULL;

-- Phase 2: Conservation casse
ALTER TABLE matters MODIFY title VARCHAR(255) COLLATE utf8mb4_bin;
```

### Services Backend à Créer

1. **CompanySettingsService**: Gestion logo et paramètres
2. **LinkGeneratorService**: Génération liens offices
3. **InternationalTrademarkService**: Automatisation marques int.
4. **NotificationService**: Gestion notifications
5. **StatisticsService**: Calculs dashboard
6. **ExportService**: Export CSV amélioré

### Composants Frontend Principaux

1. **DatePicker étendu**: Calendrier interactif
2. **LogoUploader**: Upload et gestion logo
3. **UnifiedMatterTable**: Table fusionnée
4. **Dashboard widgets**: Graphiques et stats
5. **NotificationSettings**: Config notifications
6. **OfficeLinks**: Liens automatiques

## 5. Stratégie de Tests

### Tests Unitaires (Pest PHP)
```php
// tests/Unit/Services/LinkGeneratorServiceTest.php
test('generates correct WIPO link', function () {
    $service = new LinkGeneratorService();
    $link = $service->generateLink('WIPO', '123456');
    expect($link)->toBe('https://www3.wipo.int/madrid/monitor/en/showData.jsp?ID=123456');
});
```

### Tests Feature
```php
// tests/Feature/MatterStatusTest.php
test('renewal dates removed when status is Refused', function () {
    $matter = Matter::factory()->withRenewals()->create();
    
    $matter->update(['status' => 'Refused']);
    
    expect($matter->renewals)->toBeEmpty();
});
```

### Tests E2E (optionnel - Cypress/Playwright)
- Parcours création matter
- Upload logo
- Génération rapport
- Export CSV

## 6. Plan de Déploiement

### Environnements
1. **Development**: Local avec Docker
2. **Staging**: Test complet fonctionnalités
3. **Production**: Déploiement progressif

### Stratégie de Migration
```php
// database/migrations/2025_01_01_phase1_updates.php
class Phase1Updates extends Migration {
    public function up() {
        // Créer nouvelles tables
        Schema::create('company_settings', ...);
        
        // Modifier tables existantes avec prudence
        if (!Schema::hasColumn('matters', 'trademark_label')) {
            Schema::table('matters', function (Blueprint $table) {
                $table->text('trademark_label')->nullable();
            });
        }
    }
    
    public function down() {
        // Rollback complet
    }
}
```

### Feature Flags
```php
// config/features.php
return [
    'calendar_picker' => env('FEATURE_CALENDAR_PICKER', false),
    'dashboard' => env('FEATURE_DASHBOARD', false),
    'international_automation' => env('FEATURE_INTL_AUTO', false),
];
```

## 7. Gestion des Risques

### Risques Identifiés et Mitigation

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Migration DB casse | Élevé | Faible | Backups + tests staging |
| Performance dégradée | Moyen | Moyen | Monitoring + optimisation queries |
| Incompatibilité Outlook | Faible | Moyen | Tests multi-clients email |
| Traductions incomplètes | Faible | Élevé | Validation linguistique |

### Plan de Rollback
1. Tags Git pour chaque phase
2. Migrations réversibles
3. Feature flags pour désactivation rapide
4. Backups DB avant chaque déploiement

## 8. Estimations et Ressources

### Timeline Détaillée

| Phase | Durée | Début | Fin | Dépendances |
|-------|-------|-------|-----|-------------|
| Phase 1 | 4-6 sem | S1 | S6 | - |
| Phase 2 | 2-3 sem | S5 | S8 | Phase 1 partielle |
| Phase 3 | 3-4 sem | S7 | S11 | - |
| Phase 4A | 1-2 jours | J1 | J2 | Phase 2 |
| Phase 4B | 1 jour | J3 | J3 | Phase 4A |
| Phase 4C | 2-3 jours | J4 | J6 | Phase 4A |
| Phase 4D | 2-3 jours | J7 | J9 | Phase 4A |
| Phase 4E | 1-2 jours | J10 | J11 | Phase 4A |
| Phase 5 | 1-2 sem | S12 | S14 | Phase 4 |
| Phase 6 | 6-8 sem | S15 | S22 | Phases 1-5 |

### Équipe Recommandée

| Rôle | Temps | Responsabilités |
|------|-------|-----------------|
| Laravel Backend Dev | 100% | Services, API, migrations |
| Vue.js Frontend Dev | 100% | Composants, UI/UX |
| UI/UX Designer | 50% | Maquettes, design system |
| QA Tester | 50% | Tests, validation |
| DevOps | 25% | CI/CD, déploiement |
| Chef de projet | 50% | Coordination, suivi |

### Budget Estimé
- **Développement**: 20-26 semaines × 2.5 ETP = 50-65 hommes-semaines
- **Infrastructure**: Staging + monitoring
- **Licences**: Outils de développement
- **Formation**: Documentation et formation utilisateurs

### Dépendances Critiques
1. ✅ Accès base de données production pour tests
2. ✅ Credentials API offices (WIPO, USPTO, etc.)
3. ✅ Logo entreprise en haute résolution
4. ✅ Validation des traductions françaises
5. ✅ Serveur email configuré (SMTP)

## Conclusion

Ce plan technique fournit une feuille de route complète pour la refonte de phpIP. L'approche par phases permet une livraison progressive avec des fonctionnalités testables à chaque étape. La priorité est donnée aux améliorations critiques de l'UX (Phase 1) avant d'aborder les fonctionnalités plus complexes.

**Prochaines étapes**:
1. Validation du plan avec les parties prenantes
2. Constitution de l'équipe de développement
3. Mise en place de l'environnement de développement
4. Début Phase 1 avec le calendrier interactif

---
*Document généré le 2025-08-13 | Version 1.0 | phpIP Technical Plan*