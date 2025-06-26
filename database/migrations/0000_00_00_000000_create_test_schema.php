<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run this migration for SQLite in testing
        if (DB::connection()->getDriverName() !== 'sqlite' || !app()->environment('testing')) {
            return;
        }
        
        // Create minimal schema for testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('default_role')->default('DRO');
            $table->integer('company_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        Schema::create('actor', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });
        
        Schema::create('matter', function (Blueprint $table) {
            $table->id();
            $table->string('caseref', 30);
            $table->string('country', 2);
            $table->string('category_code', 5);
            $table->timestamps();
        });
        
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5);
            $table->string('detail')->nullable();
            $table->timestamps();
        });
        
        Schema::create('event', function (Blueprint $table) {
            $table->id();
            $table->integer('matter_id');
            $table->string('code', 5);
            $table->date('event_date')->nullable();
            $table->timestamps();
        });
        
        Schema::create('default_role', function (Blueprint $table) {
            $table->string('code', 15)->primary();
            $table->string('name', 45);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        Schema::create('task_rules', function (Blueprint $table) {
            $table->id();
            $table->string('task', 5);
            $table->string('trigger', 5);
            $table->integer('qt')->default(0);
            $table->timestamps();
        });
        
        Schema::create('event_name', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->string('name', 45);
            $table->timestamps();
        });
        
        Schema::create('matter_actors', function (Blueprint $table) {
            $table->id();
            $table->integer('matter_id');
            $table->integer('actor_id');
            $table->string('role_code', 5);
            $table->timestamps();
        });
        
        // Insert basic roles
        DB::table('default_role')->insert([
            ['code' => 'DRO', 'name' => 'Data Read Only', 'description' => 'Read only access'],
            ['code' => 'DBRO', 'name' => 'DB Read Only', 'description' => 'Database read only access'],
            ['code' => 'DBRW', 'name' => 'DB Read Write', 'description' => 'Read and write access'],
            ['code' => 'DBA', 'name' => 'DB Admin', 'description' => 'Database admin access'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run this migration for SQLite in testing
        if (DB::connection()->getDriverName() !== 'sqlite' || !app()->environment('testing')) {
            return;
        }
        
        Schema::dropIfExists('matter_actors');
        Schema::dropIfExists('event_name');
        Schema::dropIfExists('task_rules');
        Schema::dropIfExists('default_role');
        Schema::dropIfExists('event');
        Schema::dropIfExists('task');
        Schema::dropIfExists('matter');
        Schema::dropIfExists('actor');
        Schema::dropIfExists('users');
    }
};