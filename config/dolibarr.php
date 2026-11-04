<?php

return [
    // Customer imports are independent of the renewal invoice backend.
    'sync' => [
        'enabled' => (bool) env('DOLIBARR_SYNC_ENABLED', false),
        'scheduled' => (bool) env('DOLIBARR_SYNC_SCHEDULED', false),
        'page_size' => 100,
        'max_pages' => 10000,
    ],
];
