<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('text');
            $table->string('group', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('email_settings')->insert([
            [
                'key' => 'email_header',
                'value' => '',
                'type' => 'html',
                'group' => 'branding',
                'description' => 'HTML header for emails (logo, company name)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email_footer',
                'value' => '',
                'type' => 'html',
                'group' => 'branding',
                'description' => 'HTML footer for emails (contact info, legal text)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
