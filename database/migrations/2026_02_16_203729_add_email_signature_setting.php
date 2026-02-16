<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('email_settings')->insert([
            'key' => 'email_signature',
            'value' => '',
            'type' => 'html',
            'group' => 'branding',
            'description' => 'HTML signature appended to all outgoing emails (logo, contact info)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_settings')->where('key', 'email_signature')->delete();
    }
};
