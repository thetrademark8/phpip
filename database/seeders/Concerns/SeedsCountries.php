<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait SeedsCountries
{
    /**
     * Insert country rows, adapting to the schema in use.
     *
     * On databases where `country.name_DE` / `country.name_FR` are generated
     * columns derived from the translatable JSON `name`, writing to them raises
     * "value specified for generated column is not allowed". In that case the
     * translations are folded into `name` as JSON and the generated columns are
     * omitted. On the plain-column schema the rows are inserted unchanged.
     *
     * @param  array<int, array<string, mixed>>  $countries
     */
    protected function seedCountries(array $countries): void
    {
        if ($this->countryTranslationsAreGenerated()) {
            $countries = array_map(function (array $row) {
                $row['name'] = json_encode([
                    'en' => $row['name'],
                    'fr' => $row['name_FR'] ?? $row['name'],
                    'de' => $row['name_DE'] ?? $row['name'],
                ], JSON_UNESCAPED_UNICODE);

                unset($row['name_FR'], $row['name_DE']);

                return $row;
            }, $countries);
        }

        DB::table('country')->insertOrIgnore($countries);
    }

    /**
     * Whether the country name translations are stored as generated columns.
     */
    private function countryTranslationsAreGenerated(): bool
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $generated = array_map(
            fn ($column) => strtolower($column->column_name),
            DB::select(
                "SELECT COLUMN_NAME AS column_name FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'country'
                 AND EXTRA LIKE '%GENERATED%'"
            )
        );

        return in_array('name_de', $generated, true) || in_array('name_fr', $generated, true);
    }
}
