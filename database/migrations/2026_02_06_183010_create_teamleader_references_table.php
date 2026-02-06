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
        Schema::create('teamleader_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('actor_id');
            $table->string('teamleader_id')->unique();
            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('actor')->cascadeOnDelete();
            $table->index('teamleader_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teamleader_references');
    }
};
