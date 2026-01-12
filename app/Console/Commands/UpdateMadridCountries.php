<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMadridCountries extends Command
{
    protected $signature = 'countries:update-madrid';

    protected $description = 'Update Madrid Protocol member countries (set wo=1)';

    /**
     * Madrid Protocol member countries (as of 2024)
     * Source: WIPO - https://www.wipo.int/madrid/en/members/
     */
    private array $madridProtocolCountries = [
        'AF', 'AL', 'DZ', 'AG', 'AM', 'AU', 'AT', 'AZ', 'BH', 'BY',
        'BE', 'BT', 'BA', 'BW', 'BR', 'BN', 'BG', 'KH', 'CA', 'CN',
        'CO', 'HR', 'CU', 'CY', 'CZ', 'KP', 'DK', 'EG', 'EE', 'SZ',
        'FI', 'FR', 'GM', 'GE', 'DE', 'GH', 'GR', 'HU', 'IS', 'IN',
        'ID', 'IR', 'IE', 'IL', 'IT', 'JP', 'KZ', 'KE', 'KG', 'LA',
        'LV', 'LS', 'LR', 'LI', 'LT', 'LU', 'MG', 'MW', 'MY', 'MV',
        'MT', 'MU', 'MX', 'MC', 'MN', 'ME', 'MA', 'MZ', 'NA', 'NL',
        'NZ', 'MK', 'NO', 'OM', 'PK', 'PH', 'PL', 'PT', 'KR', 'MD',
        'RO', 'RU', 'RW', 'SM', 'ST', 'RS', 'SL', 'SG', 'SK', 'SI',
        'ZA', 'ES', 'SD', 'SE', 'CH', 'SY', 'TJ', 'TH', 'TT', 'TN',
        'TR', 'TM', 'UA', 'AE', 'GB', 'US', 'UZ', 'VN', 'ZM', 'ZW',
        'EM', 'OA', 'BX', 'LK',
    ];

    public function handle(): int
    {
        $this->info('Updating Madrid Protocol member countries...');

        $updated = DB::table('country')
            ->whereIn('iso', $this->madridProtocolCountries)
            ->update(['wo' => 1]);

        $this->info("Updated {$updated} countries with wo=1");

        $total = DB::table('country')->where('wo', 1)->count();
        $this->info("Total Madrid Protocol countries in database: {$total}");

        return Command::SUCCESS;
    }
}
