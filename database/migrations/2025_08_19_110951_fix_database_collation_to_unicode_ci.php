<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, get the database name
        $databaseName = DB::getDatabaseName();
        
        // 1. Change database default collation
        DB::statement("ALTER DATABASE `{$databaseName}` COLLATE utf8mb4_0900_ai_ci");
        
        // 2. Convert notifications table
        DB::statement("ALTER TABLE `notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        
        // 3. Convert specific columns with utf8mb4_bin to utf8mb4_0900_ai_ci (excluding views)
        $columnsToConvert = [
            'actor_role' => ['name'],
            'classifier' => ['value'],
            'classifier_type' => ['type'],
            'event_name' => ['name'],
            // 'matter_actors' is a view, skip it
            'matter_category' => ['category'],
            // 'matter_classifiers' is a view, skip it
            'matter_type' => ['type'],
            // 'renewal_list' is a view, skip it
            'task' => ['detail'],
            // 'task_list' is a view, skip it
            'task_rules' => ['detail'],
        ];
        
        foreach ($columnsToConvert as $table => $columns) {
            foreach ($columns as $column) {
                // Get current column definition
                $columnInfo = DB::select("SHOW FULL COLUMNS FROM `{$table}` WHERE Field = '{$column}'")[0];
                
                // Extract column type without collation
                $columnType = preg_replace('/\s+COLLATE\s+[^\s]+/', '', $columnInfo->Type);
                
                // Apply the conversion
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$columnType} COLLATE utf8mb4_0900_ai_ci");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the database name
        $databaseName = DB::getDatabaseName();
        
        // Revert database collation
        DB::statement("ALTER DATABASE `{$databaseName}` COLLATE utf8mb4_0900_ai_ci");
        
        // Revert notifications table
        DB::statement("ALTER TABLE `notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        
        // Revert specific columns back to utf8mb4_bin (excluding views)
        $columnsToRevert = [
            'actor_role' => ['name'],
            'classifier' => ['value'],
            'classifier_type' => ['type'],
            'event_name' => ['name'],
            // 'matter_actors' is a view, skip it
            'matter_category' => ['category'],
            // 'matter_classifiers' is a view, skip it
            'matter_type' => ['type'],
            // 'renewal_list' is a view, skip it
            'task' => ['detail'],
            // 'task_list' is a view, skip it
            'task_rules' => ['detail'],
        ];
        
        foreach ($columnsToRevert as $table => $columns) {
            foreach ($columns as $column) {
                // Get current column definition
                $columnInfo = DB::select("SHOW FULL COLUMNS FROM `{$table}` WHERE Field = '{$column}'")[0];
                
                // Extract column type without collation
                $columnType = preg_replace('/\s+COLLATE\s+[^\s]+/', '', $columnInfo->Type);
                
                // Apply the reversion
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$columnType} COLLATE utf8mb4_bin");
            }
        }
    }
};
