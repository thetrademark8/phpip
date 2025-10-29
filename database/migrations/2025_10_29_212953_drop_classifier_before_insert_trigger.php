<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the classifier_before_insert trigger that was automatically converting
     * titles to title case (first letter uppercase, rest lowercase).
     * This trigger was causing Bug #006 where uppercase titles were being lowercased.
     */
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS classifier_before_insert');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the original trigger for rollback purposes.
     */
    public function down(): void
    {
        DB::unprepared("
            CREATE TRIGGER `classifier_before_insert` BEFORE INSERT ON `classifier` FOR EACH ROW
            BEGIN
                IF NEW.type_code = 'TITEN' THEN
                    SET NEW.value=tcase(NEW.value);
                ELSEIF NEW.type_code IN ('TIT', 'TITOF', 'TITAL') THEN
                    SET NEW.value=CONCAT(UCASE(SUBSTR(NEW.value, 1, 1)),LCASE(SUBSTR(NEW.value FROM 2)));
                END IF;
            END
        ");
    }
};
