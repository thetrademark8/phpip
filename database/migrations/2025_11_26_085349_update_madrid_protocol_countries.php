<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Madrid Protocol member countries (as of 2024)
     * Source: WIPO - https://www.wipo.int/madrid/en/members/
     */
    private array $madridProtocolCountries = [
        'AF', // Afghanistan
        'AL', // Albania
        'DZ', // Algeria
        'AG', // Antigua and Barbuda
        'AM', // Armenia
        'AU', // Australia
        'AT', // Austria
        'AZ', // Azerbaijan
        'BH', // Bahrain
        'BY', // Belarus
        'BE', // Belgium
        'BT', // Bhutan
        'BA', // Bosnia and Herzegovina
        'BW', // Botswana
        'BR', // Brazil
        'BN', // Brunei Darussalam
        'BG', // Bulgaria
        'KH', // Cambodia
        'CA', // Canada
        'CN', // China
        'CO', // Colombia
        'HR', // Croatia
        'CU', // Cuba
        'CY', // Cyprus
        'CZ', // Czech Republic
        'KP', // Democratic People's Republic of Korea
        'DK', // Denmark
        'EG', // Egypt
        'EE', // Estonia
        'SZ', // Eswatini
        'FI', // Finland
        'FR', // France
        'GM', // Gambia
        'GE', // Georgia
        'DE', // Germany
        'GH', // Ghana
        'GR', // Greece
        'HU', // Hungary
        'IS', // Iceland
        'IN', // India
        'ID', // Indonesia
        'IR', // Iran
        'IE', // Ireland
        'IL', // Israel
        'IT', // Italy
        'JP', // Japan
        'KZ', // Kazakhstan
        'KE', // Kenya
        'KG', // Kyrgyzstan
        'LA', // Lao People's Democratic Republic
        'LV', // Latvia
        'LS', // Lesotho
        'LR', // Liberia
        'LI', // Liechtenstein
        'LT', // Lithuania
        'LU', // Luxembourg
        'MG', // Madagascar
        'MW', // Malawi
        'MY', // Malaysia
        'MV', // Maldives
        'MT', // Malta
        'MU', // Mauritius
        'MX', // Mexico
        'MC', // Monaco
        'MN', // Mongolia
        'ME', // Montenegro
        'MA', // Morocco
        'MZ', // Mozambique
        'NA', // Namibia
        'NL', // Netherlands
        'NZ', // New Zealand
        'MK', // North Macedonia
        'NO', // Norway
        'OM', // Oman
        'PK', // Pakistan
        'PH', // Philippines
        'PL', // Poland
        'PT', // Portugal
        'KR', // Republic of Korea
        'MD', // Republic of Moldova
        'RO', // Romania
        'RU', // Russian Federation
        'RW', // Rwanda
        'SM', // San Marino
        'ST', // Sao Tome and Principe
        'RS', // Serbia
        'SL', // Sierra Leone
        'SG', // Singapore
        'SK', // Slovakia
        'SI', // Slovenia
        'ZA', // South Africa
        'ES', // Spain
        'SD', // Sudan
        'SE', // Sweden
        'CH', // Switzerland
        'SY', // Syrian Arab Republic
        'TJ', // Tajikistan
        'TH', // Thailand
        'TT', // Trinidad and Tobago
        'TN', // Tunisia
        'TR', // Turkey
        'TM', // Turkmenistan
        'UA', // Ukraine
        'AE', // United Arab Emirates
        'GB', // United Kingdom
        'US', // United States of America
        'UZ', // Uzbekistan
        'VN', // Viet Nam
        'ZM', // Zambia
        'ZW', // Zimbabwe
        // Intergovernmental organizations
        'EM', // European Union (EUIPO)
        'OA', // African Intellectual Property Organization (OAPI)
        'BX', // Benelux Office for Intellectual Property
        'LK', // Sri Lanka
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set wo=1 for all Madrid Protocol member countries
        DB::table('country')
            ->whereIn('iso', $this->madridProtocolCountries)
            ->update(['wo' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset wo=0 for all Madrid Protocol member countries
        DB::table('country')
            ->whereIn('iso', $this->madridProtocolCountries)
            ->update(['wo' => 0]);
    }
};
