<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialEventSeeder extends Seeder
{
    /**
     * Seed essential events for production use.
     */
    public function run()
    {
        $events = [
            // Filing events
            ['code' => 'FIL', 'name' => '{"en": "Filed", "fr": "Déposé"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'PFIL', 'name' => '{"en": "Priority Filing", "fr": "Dépôt priorité"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            
            // Priority events
            ['code' => 'PRI', 'name' => '{"en": "Priority Claimed", "fr": "Priorité revendiquée"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            
            // Publication events
            ['code' => 'PUB', 'name' => '{"en": "Published", "fr": "Publié"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            
            // Grant/Registration events
            ['code' => 'GRT', 'name' => '{"en": "Granted", "fr": "Délivré"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'REG', 'name' => '{"en": "Registered", "fr": "Enregistré"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            
            // Allowance events
            ['code' => 'ALL', 'name' => '{"en": "Allowance", "fr": "Acceptation"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            
            // Examination events
            ['code' => 'REQ', 'name' => '{"en": "Request Examination", "fr": "Requête d\'examen"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'EXA', 'name' => '{"en": "Examination Report", "fr": "Rapport d\'examen"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 0],
            
            // Response/Reply events
            ['code' => 'REP', 'name' => '{"en": "Reply", "fr": "Réponse"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            
            // Opposition events
            ['code' => 'OPP', 'name' => '{"en": "Opposition", "fr": "Opposition"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 0],
            
            // National phase events
            ['code' => 'ENT', 'name' => '{"en": "Entered", "fr": "Entrée phase nationale"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            
            // Abandonment/Lapse events
            ['code' => 'LAPS', 'name' => '{"en": "Lapsed", "fr": "Déchu"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'ABAN', 'name' => '{"en": "Abandoned", "fr": "Abandonné"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            
            // System events
            ['code' => 'CRE', 'name' => '{"en": "Created", "fr": "Créé"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 1],
            
            // Task-related events
            ['code' => 'REM', 'name' => '{"en": "Reminder", "fr": "Rappel"}', 'is_task' => 1, 'status_event' => 0, 'use_matter_resp' => 1, 'unique' => 0],
            ['code' => 'REN', 'name' => '{"en": "Renewal", "fr": "Annuité"}', 'is_task' => 1, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            ['code' => 'PAY', 'name' => '{"en": "Payment", "fr": "Paiement"}', 'is_task' => 1, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            
            // Trademark specific
            ['code' => 'OPT', 'name' => '{"en": "Opposition Term", "fr": "Délai d\'opposition"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'PROV', 'name' => '{"en": "Provisional Refusal", "fr": "Refus provisoire"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 0],
        ];
        
        DB::table('event_name')->insertOrIgnore($events);
    }
}