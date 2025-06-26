<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranslatedAttributesSeeder extends Seeder
{
    // Common translations that are reused multiple times
    private array $commonTranslations = [
        'clear' => ['en' => 'Clear', 'fr' => 'Acquitter', 'de' => 'Erfüllen'],
        'delete' => ['en' => 'Delete', 'fr' => 'Supprimer', 'de' => 'Löschen'],
        'examination' => ['en' => 'Examination', 'fr' => 'Examen', 'de' => 'Prüfung'],
        'examReport' => ['en' => 'Exam Report', 'fr' => 'Rapport d\'Examen', 'de' => 'Prüfungsbericht'],
        'grantFee' => ['en' => 'Grant Fee', 'fr' => 'Taxe de Délivrance', 'de' => 'Erteilungsgebühr'],
        'observations' => ['en' => 'Observations', 'fr' => 'Observations', 'de' => 'Anmerkungen'],
        'written' => ['en' => 'Written Opinion', 'fr' => 'Opinion Écrite', 'de' => 'Schriftlicher Bescheid'],
        'appeal' => ['en' => 'Appeal Brief', 'fr' => 'Mémoire de Recours', 'de' => 'Beschwerdebegründung'],
        'declaration' => ['en' => 'Decl. and Assignment', 'fr' => 'Décl. et Cession', 'de' => 'Erklärung u. Abtretung'],
        'opposition' => ['en' => 'Opposition deadline', 'fr' => 'Délai d\'Opposition', 'de' => 'Widerspruchsfrist'],
        'recurring' => ['en' => 'Recurring', 'fr' => 'Récurrent', 'de' => 'Wiederkehrend'],
        'extension' => ['en' => 'Request extension', 'fr' => 'Demander prolongation', 'de' => 'Verlängerung beantragen'],
    ];

    // Values that are standardized across languages (numbers, codes, acronyms)
    private array $standardizedValues = [
        'r71_3' => ['en' => 'R71(3)'],
        'r70_2' => ['en' => 'R70(2)'],
        'r161' => ['en' => 'R161'],
        'rce' => ['en' => 'RCE'],
        'ids' => ['en' => 'IDS'],
        'poa' => ['en' => 'POA'],
    ];

    /**
     * Run the database seeds.
     *
     * Populates the JSON columns with English (from original seeders),
     * French (machine-translated), and German (machine-translated) values.
     *
     * @return void
     */
    public function run()
    {
        Log::info('Starting TranslatedAttributesSeeder...');

        // --- actor_role.name ---
        // Based on ActorRoleTableSeeder.php
        $actorRoles = [
            'ADV' => ['en' => 'Adversary',        'fr' => 'Adversaire',           'de' => 'Gegenpartei'],
            'AGT' => ['en' => 'Primary Agent',    'fr' => 'Agent principal',      'de' => 'Hauptvertreter'],
            'AGT2' => ['en' => 'Secondary Agent',  'fr' => 'Agent secondaire',     'de' => 'Zweitvertreter'],
            'ANN' => ['en' => 'Annuity Agent',    'fr' => 'Agent annuités',       'de' => 'Jahresgebührenvertreter'],
            'APP' => ['en' => 'Applicant',        'fr' => 'Déposant',             'de' => 'Anmelder'],
            'CLI' => ['en' => 'Client',           'fr' => 'Client',               'de' => 'Mandant'],
            'CNT' => ['en' => 'Contact',          'fr' => 'Contact',              'de' => 'Kontakt'],
            'DBA' => ['en' => 'DB Administrator', 'fr' => 'BDD Admin.',           'de' => 'DB-Administrator'],
            'DBRO' => ['en' => 'DB Read-Only',     'fr' => 'BDD Lecture seule',    'de' => 'DB Nur-Lesezugriff'],
            'DBRW' => ['en' => 'DB Read/Write',    'fr' => 'BDD Lecture/écriture', 'de' => 'DB Lese-/Schreibzugriff'],
            'DEL' => ['en' => 'Delegate',         'fr' => 'Délégataire',          'de' => 'Bevollmächtigter'],
            'FAGT' => ['en' => 'Former Agent',     'fr' => 'Ancien agent',         'de' => 'Ehemaliger Vertreter'],
            'FOWN' => ['en' => 'Former Owner',     'fr' => 'Ancien titulairte',    'de' => 'Ehemaliger Inhaber'],
            'INV' => ['en' => 'Inventor',         'fr' => 'Inventeur',            'de' => 'Erfinder'],
            'LCN' => ['en' => 'Licensee',         'fr' => 'Licencié',             'de' => 'Lizenznehmer'],
            'OFF' => ['en' => 'Patent Office',    'fr' => 'Office des brevets',   'de' => 'Patentamt'],
            'OPP' => ['en' => 'Opponent',         'fr' => 'Opposant',             'de' => 'Einsprechender'],
            'OWN' => ['en' => 'Owner',            'fr' => 'Titulaire',            'de' => 'Inhaber'],
            'PAY' => ['en' => 'Payor',            'fr' => 'Payeur',               'de' => 'Zahler'],
            'PTNR' => ['en' => 'Partner',          'fr' => 'Partenaire',           'de' => 'Partner'],
            'TRA' => ['en' => 'Translator',       'fr' => 'Traducteur',           'de' => 'Übersetzer'],
            'WRI' => ['en' => 'Writer',           'fr' => 'Rédacteur',            'de' => 'Verfasser'],
        ];
        $this->updateTable('actor_role', 'code', 'name', $actorRoles);

        // --- classifier_type.type ---
        // Based on ClassifierTypeTableSeeder.php
        $classifierTypes = [
            'ABS' => ['en' => 'Abstract',         'fr' => 'Abrégé',           'de' => 'Zusammenfassung'],
            'AGR' => ['en' => 'Agreement',        'fr' => 'Accord',           'de' => 'Vereinbarung'],
            'BU' => ['en' => 'Business Unit',    'fr' => 'Unité commerciale', 'de' => 'Geschäftsbereich'],
            'DESC' => ['en' => 'Description',      'fr' => 'Description',      'de' => 'Beschreibung'],
            'EVAL' => ['en' => 'Evaluation',       'fr' => 'Évaluation',       'de' => 'Bewertung'],
            'IMG' => ['en' => 'Image',            'fr' => 'Image',            'de' => 'Bild'],
            'IPC' => ['en' => 'Int. Pat. Class.', 'fr' => 'Class. Int. des Brevets', 'de' => 'Int. Pat. Klass.'],
            'KW' => ['en' => 'Keyword',          'fr' => 'Mot-clé',          'de' => 'Stichwort'],
            'LNK' => ['en' => 'Link',             'fr' => 'Lien',             'de' => 'Link'],
            'LOC' => ['en' => 'Location',         'fr' => 'Lieu',             'de' => 'Standort'],
            'ORG' => ['en' => 'Organization',     'fr' => 'Organisation',     'de' => 'Organisation'],
            'PA' => ['en' => 'Prior Art',        'fr' => 'Art antérieur',    'de' => 'Stand der Technik'],
            'PROD' => ['en' => 'Product',          'fr' => 'Produit',          'de' => 'Produkt'],
            'PROJ' => ['en' => 'Project',          'fr' => 'Projet',           'de' => 'Projekt'],
            'TECH' => ['en' => 'Technology',       'fr' => 'Technologie',      'de' => 'Technologie'],
            'TIT' => ['en' => 'Title',            'fr' => 'Titre',            'de' => 'Titel'],
            'TITAL' => ['en' => 'Alt. Title',       'fr' => 'Titre alternatif', 'de' => 'Alternativer Titel'],
            'TITEN' => ['en' => 'English Title',    'fr' => 'Titre anglais',    'de' => 'Englischer Titel'],
            'TITOF' => ['en' => 'Official Title',   'fr' => 'Titre officiel',   'de' => 'Offizieller Titel'],
            'TM' => ['en' => 'Trademark',        'fr' => 'Marque',           'de' => 'Marke'],
            'TMCL' => ['en' => 'Class (TM)',       'fr' => 'Classe (Marque)',  'de' => 'Klasse (Marke)'],
            'TMTYP' => ['en' => 'Type (TM)',        'fr' => 'Type (Marque)',    'de' => 'Typ (Marke)'],
        ];
        $this->updateTable('classifier_type', 'code', 'type', $classifierTypes);

        // --- event_name.name ---
        // Based on EventNameTableSeeder.php
        $eventNames = [
            'ABA' => ['en' => 'Abandoned',           'fr' => 'Abandonné',                'de' => 'Aufgegeben'],
            'ABO' => ['en' => 'Abandon Original',    'fr' => 'Abandon original',         'de' => 'Ursprüngliches aufgeben'],
            'ADV' => ['en' => 'Advisory Action',     'fr' => 'Advisory Action',          'de' => 'Advisory Action'],
            'ALL' => ['en' => 'Allowance',           'fr' => 'Intention délivrance',     'de' => 'Zulassung'],
            'APL' => ['en' => 'Appeal',              'fr' => 'Recours',                  'de' => 'Beschwerde'],
            'CAN' => ['en' => 'Cancelled',           'fr' => 'Annulé',                   'de' => 'Storniert'],
            'CLO' => ['en' => 'Closed',              'fr' => 'Clôturé',                  'de' => 'Geschlossen'],
            'COM' => ['en' => 'Communication',       'fr' => 'Communication',            'de' => 'Mitteilung'],
            'CRE' => ['en' => 'Created',             'fr' => 'Création',                 'de' => 'Erstellt'],
            'DAPL' => ['en' => 'Decision on Appeal',  'fr' => 'Décision sur recours',     'de' => 'Beschwerdeentscheidung'],
            'DBY' => ['en' => 'Draft By',            'fr' => 'Rédiger avant le',         'de' => 'Entwurf bis'],
            'DEX' => ['en' => 'Deadline Extended',   'fr' => 'Délai prolongé',           'de' => 'Frist verlängert'],
            'DPAPL' => ['en' => 'Decision on Pre-Appeal', 'fr' => 'Décision pré-recours',  'de' => 'Vorentscheidung'],
            'DRA' => ['en' => 'Drafted',             'fr' => 'Rédigé',                   'de' => 'Entworfen'],
            'DW' => ['en' => 'Deemed withrawn',     'fr' => 'Réputé retiré',            'de' => 'Als zurückgenommen geltend'],
            'EHK' => ['en' => 'Extend to Hong Kong', 'fr' => 'Étendre à Hong Kong',      'de' => 'Erstreckung auf Hongkong'],
            'ENT' => ['en' => 'Entered',             'fr' => 'Entrée',                   'de' => 'Eingetreten'],
            'EOP' => ['en' => 'End of Procedure',    'fr' => 'Fin de procédure',         'de' => 'Verfahrensende'],
            'EXA' => ['en' => 'Examiner Action',     'fr' => 'Notification d\'examen',   'de' => 'Prüferbescheid'],
            'EXAF' => ['en' => 'Examiner Action (Final)', 'fr' => 'Notif. Exa. (Finale)', 'de' => 'Schlussbescheid Prüfer'],
            'EXP' => ['en' => 'Expiry',              'fr' => 'Expiration',               'de' => 'Ablauf'],
            'FAP' => ['en' => 'File Notice of Appeal', 'fr' => 'Déposer avis de recours', 'de' => 'Beschwerde einlegen'],
            'FBY' => ['en' => 'File by',             'fr' => 'Déposer avant le',         'de' => 'Einreichen bis'],
            'FDIV' => ['en' => 'File Divisional',     'fr' => 'Déposer divisionnaire',    'de' => 'Teilanmeldung einreichen'],
            'FIL' => ['en' => 'Filed',               'fr' => 'Déposé',                   'de' => 'Eingereicht'],
            'FOP' => ['en' => 'File Opposition',     'fr' => 'Déposer opposition',       'de' => 'Einspruch einlegen'],
            'FPR' => ['en' => 'Further Processing',  'fr' => 'Poursuite procédure',      'de' => 'Weiterbehandlung'],
            'FRCE' => ['en' => 'File RCE',            'fr' => 'Déposer RCE',              'de' => 'RCE einreichen'],
            'GRT' => ['en' => 'Granted',             'fr' => 'Délivré',                  'de' => 'Erteilt'],
            'INV' => ['en' => 'Invalidated',         'fr' => 'Invalidé',                 'de' => 'Für ungültig erklärt'],
            'LAP' => ['en' => 'Lapsed',              'fr' => 'Déchu',                    'de' => 'Verfallen'],
            'NPH' => ['en' => 'National Phase',      'fr' => 'Phase nationale',          'de' => 'Nationale Phase'],
            'OPP' => ['en' => 'Opposition',          'fr' => 'Opposition',               'de' => 'Einspruch'],
            'OPR' => ['en' => 'Oral Proceedings',    'fr' => 'Procédure orale',          'de' => 'Mündliche Verhandlung'],
            'ORE' => ['en' => 'Opposition rejected', 'fr' => 'Opposition rejetée',       'de' => 'Einspruch zurückgewiesen'],
            'PAY' => ['en' => 'Pay',                 'fr' => 'Payer',                    'de' => 'Zahlen'],
            'PDES' => ['en' => 'Post designation',    'fr' => 'Désignation postérieure',  'de' => 'Nachträgliche Benennung'],
            'PFIL' => ['en' => 'Parent Filed',        'fr' => 'Dépôt parent',             'de' => 'Stammanmeldung eingereicht'],
            'PR' => ['en' => 'Publication of Reg.', 'fr' => 'Publication enreg.',       'de' => 'Veröffentlichung Reg.'],
            'PREP' => ['en' => 'Prepare',             'fr' => 'Préparer',                 'de' => 'Vorbereiten'],
            'PRI' => ['en' => 'Priority Claim',      'fr' => 'Revendication priorité',   'de' => 'Prioritätsanspruch'],
            'PRID' => ['en' => 'Priority Deadline',   'fr' => 'Délai priorité',           'de' => 'Prioritätsfrist'],
            'PROD' => ['en' => 'Produce',             'fr' => 'Produire',                 'de' => 'Erstellen/Einreichen'],
            'PSR' => ['en' => 'Publication of SR',   'fr' => 'Publication rap. rech.',   'de' => 'Veröffentlichung Rech.ber.'],
            'PUB' => ['en' => 'Published',           'fr' => 'Publié',                   'de' => 'Veröffentlicht'],
            'RCE' => ['en' => 'Request Continued Examination', 'fr' => 'Requête RCE',    'de' => 'Antrag auf Fortsetzung Prüfung'],
            'REC' => ['en' => 'Received',            'fr' => 'Reçu',                     'de' => 'Empfangen'],
            'REF' => ['en' => 'Refused',             'fr' => 'Refusé',                   'de' => 'Zurückgewiesen'],
            'REG' => ['en' => 'Registration',        'fr' => 'Enregistrement',           'de' => 'Registrierung'],
            'REM' => ['en' => 'Reminder',            'fr' => 'Rappel',                   'de' => 'Erinnerung'],
            'REN' => ['en' => 'Renewal',             'fr' => 'Renouvellement',           'de' => 'Verlängerung'],
            'REP' => ['en' => 'Respond',             'fr' => 'Répondre',                 'de' => 'Erwidern'],
            'REQ' => ['en' => 'Request',             'fr' => 'Requête',                  'de' => 'Antrag'],
            'RSTR' => ['en' => 'Restriction Req.',    'fr' => 'Requête restriction',      'de' => 'Beschränkungsantrag'],
            'SOL' => ['en' => 'Sold',                'fr' => 'Vendu',                    'de' => 'Verkauft'],
            'SOP' => ['en' => 'Summons to Oral Proc.', 'fr' => 'Citation proc. orale',   'de' => 'Ladung zur Mündl. Verh.'],
            'SR' => ['en' => 'Search Report',       'fr' => 'Rapport de recherche',     'de' => 'Recherchenbericht'],
            'SUS' => ['en' => 'Suspended',           'fr' => 'Suspendu',                 'de' => 'Ausgesetzt'],
            'TRF' => ['en' => 'Transformation',      'fr' => 'Transformation',           'de' => 'Transformation'],
            'TRS' => ['en' => 'Transfer',            'fr' => 'Transfert',                'de' => 'Übertragung'],
            'VAL' => ['en' => 'Validate',            'fr' => 'Valider',                  'de' => 'Validieren'],
            'WAT' => ['en' => 'Watch',               'fr' => 'Surveiller',               'de' => 'Überwachen'],
            'WIT' => ['en' => 'Withdrawal',          'fr' => 'Retrait',                  'de' => 'Zurücknahme'],
        ];
        $this->updateTable('event_name', 'code', 'name', $eventNames);

        // --- matter_category.category ---
        // Based on MatterCategoryTableSeeder.php
        $matterCategories = [
            'AGR' => ['en' => 'Agreement',           'fr' => 'Accord',              'de' => 'Vereinbarung'],
            'DSG' => ['en' => 'Design',              'fr' => 'Dessin ou modèle',    'de' => 'Design'],
            'FTO' => ['en' => 'Freedom to Operate',  'fr' => 'Liberté d\'exploitation', 'de' => 'Freedom to Operate'],
            'LTG' => ['en' => 'Litigation',          'fr' => 'Contentieux',         'de' => 'Rechtsstreit'],
            'OP' => ['en' => 'Opposition (patent)', 'fr' => 'Opposition (brevet)', 'de' => 'Einspruch (Patent)'],
            'OPI' => ['en' => 'Opinion',             'fr' => 'Avis',                'de' => 'Gutachten'], // Or 'Meinung'? Context matters.
            'OTH' => ['en' => 'Others',              'fr' => 'Autres',              'de' => 'Sonstige'],
            'PAT' => ['en' => 'Patent',              'fr' => 'Brevet',              'de' => 'Patent'],
            'SO' => ['en' => 'Soleau Envelop',      'fr' => 'Enveloppe Soleau',    'de' => 'Soleau-Umschlag'],
            'SR' => ['en' => 'Search',              'fr' => 'Recherche',           'de' => 'Recherche'],
            'TM' => ['en' => 'Trademark',           'fr' => 'Marque',              'de' => 'Marke'],
            'TMOP' => ['en' => 'Opposition (TM)',     'fr' => 'Opposition (Marque)', 'de' => 'Widerspruch (Marke)'],
            'TS' => ['en' => 'Trade Secret',        'fr' => 'Secret de fabrique',  'de' => 'Geschäftsgeheimnis'],
            'UC' => ['en' => 'Utility Certificate', 'fr' => 'Certificat d\'utilité', 'de' => 'Gebrauchszertifikat'],
            'UM' => ['en' => 'Utility Model',       'fr' => 'Modèle d\'utilité',   'de' => 'Gebrauchsmuster'],
            'WAT' => ['en' => 'Watch',               'fr' => 'Surveillance',        'de' => 'Überwachung'],
        ];
        $this->updateTable('matter_category', 'code', 'category', $matterCategories);

        // --- matter_type.type ---
        // Based on MatterTypeTableSeeder.php
        $matterTypes = [
            'CIP' => ['en' => 'Continuation in Part', 'fr' => 'Continuation partielle', 'de' => 'Teilfortsetzungsanmeldung'],
            'CNT' => ['en' => 'Continuation',         'fr' => 'Continuation',           'de' => 'Fortsetzungsanmeldung'],
            'DIV' => ['en' => 'Divisional',           'fr' => 'Divisionnaire',          'de' => 'Teilanmeldung'],
            'PRO' => ['en' => 'Provisional',          'fr' => 'Provisoire',             'de' => 'Vorläufige Anmeldung'],
            'REI' => ['en' => 'Reissue',              'fr' => 'Redélivrance',           'de' => 'Neuerteilung'],
            'REX' => ['en' => 'Re-examination',       'fr' => 'Réexamen',               'de' => 'Neuprüfungsverfahren'],
        ];
        $this->updateTable('matter_type', 'code', 'type', $matterTypes);

        // --- task_rules.detail ---
        // Based on TaskRulesTableSeeder.php
        // !! WARNING !! MAPPING USES PRIMARY KEY `id`.
        $taskRuleDetails = [
            3 => $this->commonTranslations['clear'],
            5 => $this->commonTranslations['clear'],
            6 => $this->commonTranslations['examination'],
            7 => $this->commonTranslations['examination'],
            9 => ['en' => 'Search Report', 'fr' => 'Rapport de Recherche', 'de' => 'Recherchenbericht'],
            10 => $this->commonTranslations['examReport'],
            11 => $this->commonTranslations['examReport'],
            13 => $this->standardizedValues['r71_3'],
            14 => $this->commonTranslations['grantFee'],
            15 => ['en' => 'Claim Translation', 'fr' => 'Traduction Revendications', 'de' => 'Anspruchsübersetzung'],
            16 => ['en' => 'Translate where necessary', 'fr' => 'Traduire si nécessaire', 'de' => 'Übersetzen wo nötig'],
            18 => $this->commonTranslations['written'],
            19 => ['en' => 'Designation Fees', 'fr' => 'Taxes de Désignation', 'de' => 'Benennungsgebühren'],
            20 => $this->commonTranslations['declaration'],
            21 => ['en' => 'Priority Deadline', 'fr' => 'Délai de Priorité', 'de' => 'Prioritätsfrist'],
            23 => $this->commonTranslations['examination'],
            25 => $this->commonTranslations['delete'],
            30 => $this->standardizedValues['ids'],
            34 => ['en' => 'National Phase', 'fr' => 'Phase Nationale', 'de' => 'Nationale Phase'],
            35 => ['en' => 'Small Entity', 'fr' => 'Petite Entité', 'de' => 'Kleines Unternehmen'],
            36 => ['en' => 'HK Grant Fee', 'fr' => 'Taxe Délivrance HK', 'de' => 'HK Erteilungsgebühr'],
            37 => ['en' => 'Communication', 'fr' => 'Communication', 'de' => 'Mitteilung'],
            38 => $this->commonTranslations['clear'],
            39 => $this->commonTranslations['grantFee'],
            41 => $this->standardizedValues['r70_2'],
            44 => ['en' => 'Filing Fee', 'fr' => 'Taxe de Dépôt', 'de' => 'Anmeldegebühr'],
            46 => ['en' => 'Restriction Req.', 'fr' => 'Requête Restriction', 'de' => 'Beschränkungsantrag'],
            47 => $this->standardizedValues['r161'],
            49 => $this->commonTranslations['appeal'],
            52 => $this->commonTranslations['observations'],
            53 => $this->commonTranslations['examination'],
            54 => $this->commonTranslations['examination'],
            55 => $this->commonTranslations['examination'],
            56 => $this->commonTranslations['grantFee'],
            57 => ['en' => 'Priority Docs', 'fr' => 'Documents Priorité', 'de' => 'Prioritätsunterlagen'],
            58 => ['en' => 'Filing Fee', 'fr' => 'Taxe de Dépôt', 'de' => 'Anmeldegebühr'],
            60 => ['en' => 'File divisional', 'fr' => 'Déposer divisionnaire', 'de' => 'Teilanmeldung einreichen'],
            61 => $this->commonTranslations['examReport'],
            62 => $this->commonTranslations['examReport'],
            63 => $this->commonTranslations['extension'],
            64 => $this->commonTranslations['extension'],
            66 => $this->commonTranslations['grantFee'],
            67 => $this->standardizedValues['r70_2'],
            68 => ['en' => 'Designation Fees', 'fr' => 'Taxes de Désignation', 'de' => 'Benennungsgebühren'],
            69 => $this->commonTranslations['written'],
            70 => $this->commonTranslations['examination'],
            80 => $this->commonTranslations['recurring'],
            81 => $this->commonTranslations['recurring'],
            234 => $this->commonTranslations['grantFee'],
            235 => $this->commonTranslations['written'],
            236 => $this->commonTranslations['grantFee'],
            237 => ['en' => 'Working Report', 'fr' => 'Rapport d\'Exploitation', 'de' => 'Nutzungsbericht'],
            238 => $this->commonTranslations['opposition'],
            239 => $this->commonTranslations['opposition'],
            240 => $this->commonTranslations['opposition'],
            242 => ['en' => 'Declaration of use', 'fr' => 'Déclaration d\'Usage', 'de' => 'Benutzungserklärung'],
            1280 => $this->commonTranslations['observations'],
            1282 => ['en' => '2nd part of individual fee', 'fr' => '2ème partie taxe indiv.', 'de' => '2. Teil Individualgebühr'],
            1290 => ['en' => 'Soleau'],
            1291 => ['en' => 'End of protection', 'fr' => 'Fin de protection', 'de' => 'Schutzende'],
            1300 => $this->commonTranslations['observations'],
            1301 => $this->commonTranslations['declaration'],
            1302 => $this->commonTranslations['examination'],
            1303 => $this->commonTranslations['appeal'],
            1306 => $this->commonTranslations['delete'],
            1307 => $this->commonTranslations['delete'],
            1310 => ['en' => 'Opinion', 'fr' => 'Avis', 'de' => 'Gutachten'],
            1311 => ['en' => 'Report', 'fr' => 'Rapport', 'de' => 'Bericht'],
            1315 => $this->commonTranslations['examReport'],
            1316 => $this->standardizedValues['poa'],
            1321 => ['en' => 'Analysis of SR', 'fr' => 'Analyse du Rap. Rech.', 'de' => 'Analyse Rech.ber.'],
            1322 => $this->commonTranslations['appeal'],
            1323 => $this->standardizedValues['rce'],
            1326 => ['en' => 'Appeal', 'fr' => 'Recours', 'de' => 'Beschwerde'],
            1327 => $this->commonTranslations['clear'],
            1328 => ['en' => 'CompuMark Analysis', 'fr' => 'Analyse CompuMark', 'de' => 'CompuMark Analyse'],
            1329 => ['en' => 'Products & Services', 'fr' => 'Produits & Services', 'de' => 'Produkte & Dienstleistungen'],
        ];
        // Filter out any null detail values that might have slipped in, if desired
        $taskRuleDetails = array_filter($taskRuleDetails, fn ($detailArray) => isset($detailArray['en']) && $detailArray['en'] !== null);

        if (! empty($taskRuleDetails)) {
            $this->updateTable('task_rules', 'id', 'detail', $taskRuleDetails); // Uses 'id' as key
        } else {
            Log::info('No translatable details configured for task_rules. Skipping update for this table.');
        }

        Log::info('TranslatedAttributesSeeder finished.');
    }

    /**
     * Helper function to update a table with translated JSON data.
     * (Same helper function as before)
     *
     * @param  string  $keyColumn  (e.g., 'code' or 'id')
     * @param  string  $targetJsonColumn  (e.g., 'category', 'name', 'detail')
     * @param  array  $translationsData  [$keyValue => ['en' => ..., 'fr' => ..., 'de' => ...]]
     * @return void
     */
    private function updateTable(string $tableName, string $keyColumn, string $targetJsonColumn, array $translationsData)
    {
        if (empty($translationsData)) {
            Log::info("No data provided for {$tableName}. Skipping update.");

            return;
        }

        Log::info("Updating table '{$tableName}', column '{$targetJsonColumn}' using key '{$keyColumn}'...");
        $updatedCount = 0;
        $notFoundCount = 0;
        $errorCount = 0;

        // Wrap per-table update in transaction for atomicity
        DB::transaction(function () use ($tableName, $keyColumn, $targetJsonColumn, $translationsData, &$updatedCount, &$notFoundCount, &$errorCount) {
            foreach ($translationsData as $keyValue => $translations) {
                try {
                    // Ensure translations is an array and not empty (safeguard against incomplete entries)
                    if (! is_array($translations) || empty(array_filter($translations))) {
                        Log::warning("Skipping update for {$tableName}.{$keyColumn} = {$keyValue}: Invalid or empty translations array provided.");

                        continue;
                    }

                    // Ensure English translation exists if expected
                    if (! isset($translations['en'])) {
                        Log::warning("Skipping update for {$tableName}.{$keyColumn} = {$keyValue}: Missing 'en' key in translations array.");

                        continue;
                    }

                    $jsonPayload = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT); // Pretty print for readability in DB

                    $count = DB::table($tableName)
                        ->where($keyColumn, $keyValue)
                        ->update([$targetJsonColumn => $jsonPayload]);

                    if ($count > 0) {
                        $updatedCount += $count;
                    } elseif (DB::table($tableName)->where($keyColumn, $keyValue)->doesntExist()) {
                        Log::warning("Row not found in {$tableName} for {$keyColumn} = '{$keyValue}'. Could not update.");
                        $notFoundCount++;
                    }

                } catch (\JsonException $e) {
                    Log::error("JSON encoding failed for {$tableName}.{$keyColumn} = '{$keyValue}': ".$e->getMessage());
                    $errorCount++;
                } catch (\Exception $e) {
                    Log::error("Database update failed for {$tableName}.{$keyColumn} = '{$keyValue}': ".$e->getMessage());
                    $errorCount++;
                    // Consider re-throwing to stop the whole transaction on DB errors:
                    // throw $e;
                }
            }
        });

        Log::info("Finished updating {$tableName}. Updated records: {$updatedCount}. Records not found: {$notFoundCount}. Errors: {$errorCount}.");
    }
}
