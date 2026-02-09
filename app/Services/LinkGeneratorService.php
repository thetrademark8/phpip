<?php

namespace App\Services;

use App\Models\Matter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LinkGeneratorService
{
    /**
     * URL patterns for different IP offices
     * %s will be replaced with the application/registration number
     */
    private array $patterns = [
        // Trademark Offices
        'WIPO' => [
            'url' => 'https://www3.wipo.int/madrid/monitor/en/showData.jsp?ID=%s',
            'name' => 'WIPO Madrid Monitor',
            'icon' => 'Globe',
            'categories' => ['TM'],
            'number_field' => 'registration' // Usually registration number for international marks
        ],
        'USPTO' => [
            'url' => 'https://tsdr.uspto.gov/#caseNumber=%s&caseType=SERIAL_NO',
            'name' => 'USPTO TSDR',
            'icon' => 'ExternalLink',
            'categories' => ['TM'],
            'number_field' => 'filing' // Serial number
        ],
        'EUIPO' => [
            'url' => 'https://euipo.europa.eu/eSearch/#details/trademarks/%s',
            'name' => 'EUIPO eSearch',
            'icon' => 'Search',
            'categories' => ['TM'],
            'number_field' => 'filing', // Application number
            'number_format' => 'pad_zeros_9' // Pad with zeros to 9 digits
        ],
        'UKIPO' => [
            'url' => 'https://trademarks.ipo.gov.uk/ipo-tmcase/page/Results/1/%s%s',
            'name' => 'UK IPO Search',
            'icon' => 'Search',
            'categories' => ['TM'],
            'number_field' => 'filing',
            'requires_country_prefix' => true
        ],
        'INPI' => [
            'url' => 'https://data.inpi.fr/marques/%s%s',
            'name' => 'INPI Data',
            'icon' => 'Database',
            'categories' => ['TM'],
            'number_field' => 'filing',
            'requires_country_prefix' => true
        ],
        'DPMA' => [
            'url' => 'https://register.dpma.de/DPMAregister/marke/basis?AKZ=%s',
            'name' => 'DPMA Register',
            'icon' => 'FileText',
            'categories' => ['TM'],
            'number_field' => 'filing'
        ],
        'JPO' => [
            'url' => 'https://www.j-platpat.inpit.go.jp/c1800/TR/JP-%s',
            'name' => 'J-PlatPat Trademarks',
            'icon' => 'Search',
            'categories' => ['TM'],
            'number_field' => 'filing'
        ],
        
        // Patent Offices (bonus functionality)
        'EPO' => [
            'url' => 'https://worldwide.espacenet.com/patent/search/family/%s',
            'name' => 'Espacenet',
            'icon' => 'Search',
            'categories' => ['PAT'],
            'number_field' => 'publication'
        ],
        'USPTO_PAT' => [
            'url' => 'https://ppubs.uspto.gov/dirsearch-public/print/downloadPdf/%s',
            'name' => 'USPTO Patents',
            'icon' => 'FileText',
            'categories' => ['PAT'],
            'number_field' => 'publication'
        ],
        
        // Design Offices (DP = Design Patent category code)
        // Example FR: https://data.inpi.fr/dessins_modeles/FR20200885-003
        'INPI_DSG' => [
            'url' => 'https://data.inpi.fr/dessins_modeles/%s',
            'name' => 'INPI Dessins & Modèles',
            'icon' => 'Palette',
            'categories' => ['DP', 'DSG'],
            'number_field' => 'filing', // Use filing number for French designs
            'number_format' => 'inpi_design_format'
        ],
        // Example EU: https://euipo.europa.eu/eSearch/#details/designs/007708706-0001
        'EUIPO_DSG' => [
            'url' => 'https://euipo.europa.eu/eSearch/#details/designs/%s',
            'name' => 'EUIPO Designs',
            'icon' => 'Palette',
            'categories' => ['DP', 'DSG'],
            'number_field' => 'filing', // Use filing/application number for EU designs
            'number_format' => 'euipo_design_format'
        ],
        // Example WO: https://designdb.wipo.int/designdb/hague/fr/showData.jsp?ID=HAGUE.D215987
        'WIPO_DSG' => [
            'url' => 'https://designdb.wipo.int/designdb/hague/fr/showData.jsp?ID=HAGUE.%s',
            'name' => 'WIPO Hague Design Database',
            'icon' => 'Globe',
            'categories' => ['DP', 'DSG'],
            'number_field' => 'registration',
            'number_format' => 'wipo_design_format'
        ],
        'USPTO_DSG' => [
            'url' => 'https://ppubs.uspto.gov/dirsearch-public/print/downloadPdf/D%s',
            'name' => 'USPTO Design Patents',
            'icon' => 'FileText',
            'categories' => ['DP', 'DSG'],
            'number_field' => 'publication'
        ],
        'DPMA_DSG' => [
            'url' => 'https://register.dpma.de/DPMAregister/gsm/basis?AKZ=%s',
            'name' => 'DPMA Designs',
            'icon' => 'FileText',
            'categories' => ['DP', 'DSG'],
            'number_field' => 'filing'
        ]
    ];

    /**
     * Country to office mapping
     */
    private array $countryToOffice = [
        'WO' => 'WIPO',
        'US' => 'USPTO',
        'EP' => 'EUIPO',
        'EM' => 'EUIPO', // EU marks
        'GB' => 'UKIPO',
        'UK' => 'UKIPO',
        'FR' => 'INPI',
        'DE' => 'DPMA',
        'JP' => 'JPO',
        'CN' => null, // No direct link support yet
        'IN' => null, // No direct link support yet
        'AU' => null, // No direct link support yet
        'BR' => null, // No direct link support yet
        'KR' => null, // No direct link support yet
        'RU' => null  // No direct link support yet
    ];

    /**
     * Generate a single link for a specific office and number
     */
    public function generateLink(string $office, string $number, string $countryCode = ''): ?string
    {
        if (!isset($this->patterns[$office])) {
            return null;
        }

        $pattern = $this->patterns[$office];

        // Clean and validate the number
        $cleanNumber = $this->cleanNumber($number);
        if (empty($cleanNumber)) {
            return null;
        }

        // Apply office-specific formatting
        $formattedNumber = $this->formatNumberForOffice($cleanNumber, $office, $countryCode);

        // Validate number format for the specific office (use original clean number for validation)
        if (!$this->isValidNumberFormat($office, $cleanNumber)) {
            Log::warning("Invalid number format for office", [
                'office' => $office,
                'number' => $cleanNumber
            ]);
            return null;
        }

        // Check if this office requires a country prefix
        if (!empty($pattern['requires_country_prefix']) && $pattern['requires_country_prefix']) {
            if (empty($countryCode)) {
                Log::warning("Country code required for office but not provided", [
                    'office' => $office,
                    'number' => $cleanNumber
                ]);
                return null;
            }
            return sprintf($pattern['url'], $countryCode, $formattedNumber);
        }

        return sprintf($pattern['url'], $formattedNumber);
    }

    /**
     * Get all available links for a matter
     */
    public function getAllLinksForMatter(Matter $matter): array
    {
        $links = [];

        // Determine office based on country
        $office = $this->detectOfficeFromCountry($matter->country, $matter->category_code);
        
        if (!$office) {
            return [];
        }

        $pattern = $this->patterns[$office];

        // Extract numbers from matter events
        $numbers = $this->extractNumbers($matter, $pattern['number_field']);

        foreach ($numbers as $numberData) {
            $link = $this->generateLink($office, $numberData['number'], $matter->country);
            if ($link) {
                $links[] = [
                    'office' => $office,
                    'office_name' => $pattern['name'],
                    'icon' => $pattern['icon'],
                    'url' => $link,
                    'number' => $numberData['number'],
                    'type' => $numberData['type'], // 'filing', 'publication', 'registration'
                    'date' => $numberData['date']
                ];
            }
        }

        return $links;
    }

    /**
     * Detect office from country and category
     */
    public function detectOfficeFromCountry(string $country, string $category): ?string
    {
        // Handle Design Patents specifically (DP is the category code used in this system)
        if ($category === 'DSG' || $category === 'DP') {
            switch ($country) {
                case 'FR':
                    return 'INPI_DSG';
                case 'EP':
                case 'EM':
                    return 'EUIPO_DSG';
                case 'WO':
                    return 'WIPO_DSG';
                case 'US':
                    return 'USPTO_DSG';
                case 'DE':
                    return 'DPMA_DSG';
                default:
                    return null;
            }
        }

        $baseOffice = $this->countryToOffice[$country] ?? null;
        
        if (!$baseOffice) {
            return null;
        }

        // Handle special cases for patents vs trademarks
        if ($category === 'PAT' && $baseOffice === 'USPTO') {
            return 'USPTO_PAT';
        }

        // Check if office supports this category
        $pattern = $this->patterns[$baseOffice] ?? null;
        if (!$pattern || !in_array($category, $pattern['categories'])) {
            return null;
        }

        return $baseOffice;
    }

    /**
     * Extract relevant numbers from matter events
     */
    private function extractNumbers(Matter $matter, string $preferredField): array
    {
        $numbers = [];

        // Filing number
        if ($matter->filing->exists() && $matter->filing->detail) {
            $numbers[] = [
                'number' => $matter->filing->detail,
                'type' => 'filing',
                'date' => $matter->filing->event_date,
                'priority' => $preferredField === 'filing' ? 1 : 3
            ];
        }

        // Publication number
        if ($matter->publication->exists() && $matter->publication->detail) {
            $numbers[] = [
                'number' => $matter->publication->detail,
                'type' => 'publication',
                'date' => $matter->publication->event_date,
                'priority' => $preferredField === 'publication' ? 1 : 2
            ];
        }

        // Registration/Grant number
        if ($matter->grant->exists() && $matter->grant->detail) {
            $numbers[] = [
                'number' => $matter->grant->detail,
                'type' => 'registration',
                'date' => $matter->grant->event_date,
                'priority' => $preferredField === 'registration' ? 1 : 2
            ];
        }

        // Sort by priority (preferred field first)
        usort($numbers, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $numbers;
    }

    /**
     * Clean number format (remove spaces, special characters, country prefixes)
     * Preserves design variant suffixes (e.g., -003, -0001)
     */
    private function cleanNumber(string $number): string
    {
        // First, extract and preserve any variant suffix (e.g., -003, -0001)
        $variant = '';
        if (preg_match('/(\-\d{1,4})$/', $number, $matches)) {
            $variant = $matches[1];
            $number = substr($number, 0, -strlen($variant));
        }

        // Remove country prefixes (FR, DE, etc.) at the beginning
        $cleaned = preg_replace('/^[A-Z]{2}/', '', $number);

        // Remove common separators and spaces (but not hyphens in the middle)
        $cleaned = preg_replace('/[\s\.\/]/', '', $cleaned);

        // Remove non-alphanumeric characters but keep basic ones
        $cleaned = preg_replace('/[^\w]/', '', $cleaned);

        // Re-add the variant suffix if it existed
        return trim($cleaned . $variant);
    }

    /**
     * Format number according to office-specific requirements
     */
    private function formatNumberForOffice(string $number, string $office, string $countryCode = ''): string
    {
        $pattern = $this->patterns[$office] ?? null;

        if (!$pattern || empty($pattern['number_format'])) {
            return $number;
        }

        switch ($pattern['number_format']) {
            case 'pad_zeros_9':
                // Pad with leading zeros to make it 9 digits
                if (is_numeric($number)) {
                    return str_pad($number, 9, '0', STR_PAD_LEFT);
                }
                break;

            case 'inpi_design_format':
                // INPI Design format: FR{number} (e.g., FR20200885-003)
                // If number already contains country prefix, use as-is
                if (preg_match('/^FR/', $number)) {
                    return $number;
                }
                return 'FR' . $number;

            case 'euipo_design_format':
                // EUIPO Design format: {9-digit-number}-{variant} (e.g., 007708706-0001)
                // If number already contains hyphen (variant), pad the base number
                if (str_contains($number, '-')) {
                    $parts = explode('-', $number);
                    $baseNumber = str_pad($parts[0], 9, '0', STR_PAD_LEFT);
                    return $baseNumber . '-' . $parts[1];
                }
                // No variant, pad number and add default variant
                return str_pad($number, 9, '0', STR_PAD_LEFT) . '-0001';

            case 'wipo_design_format':
                // WIPO Hague Design format: D{number} (e.g., D215987 for HAGUE.D215987)
                // Remove any DM/ or D prefix if present
                $cleanNumber = preg_replace('/^(DM\/|D)/', '', $number);
                return 'D' . $cleanNumber;

            default:
                break;
        }

        return $number;
    }

    /**
     * Validate number format for specific office
     */
    public function isValidNumberFormat(string $office, string $number): bool
    {
        if (empty($number)) {
            return false;
        }

        // Basic validation patterns for different offices (relaxed for better compatibility)
        $validationPatterns = [
            'WIPO' => '/^\d+$/', // Numeric only
            'USPTO' => '/^\d{7,10}$/', // 7-10 digits (more flexible)
            'EUIPO' => '/^\d{6,9}$/', // 6-9 digits
            'UKIPO' => '/^(UK)?\d{7,10}$/', // Optional UK prefix + 7-10 digits
            'INPI' => '/^(FR)?\d{5,9}$/', // Accept optional FR prefix + 5-9 digits
            'DPMA' => '/^(DE)?\d{6,10}$/', // Optional DE prefix + 6-10 digits
            'JPO' => '/^\d+$/', // Numeric
            'EPO' => '/^\d+$/', // Numeric
            'USPTO_PAT' => '/^\d{7,8}$/', // 7-8 digits for patents
            
            // Design validation patterns (flexible to handle various formats)
            'INPI_DSG' => '/^(FR)?\d{6,10}(\-\d{1,3})?$/', // Optional FR prefix + 6-10 digits + optional variant
            'EUIPO_DSG' => '/^\d{6,9}(\-\d{4})?$/', // 6-9 digits + optional variant (e.g., -0001)
            'WIPO_DSG' => '/^(DM\/|D)?\d{5,7}$/', // Optional DM/ or D prefix + 5-7 digits
            'USPTO_DSG' => '/^D?\d{6,8}$/', // Optional D prefix + 6-8 digits
            'DPMA_DSG' => '/^\d{8,14}$/' // 8-14 digits for German designs
        ];

        $pattern = $validationPatterns[$office] ?? '/^.+$/'; // Default: any non-empty
        
        return preg_match($pattern, $number) === 1;
    }

    /**
     * Get available offices for a specific country and category
     */
    public function getAvailableOffices(string $country, string $category): array
    {
        $office = $this->detectOfficeFromCountry($country, $category);
        
        if (!$office) {
            return [];
        }

        return [$this->patterns[$office]];
    }

    /**
     * Get all supported offices
     */
    public function getAllSupportedOffices(): array
    {
        return array_map(function ($pattern, $code) {
            return [
                'code' => $code,
                'name' => $pattern['name'],
                'icon' => $pattern['icon'],
                'categories' => $pattern['categories']
            ];
        }, $this->patterns, array_keys($this->patterns));
    }
}