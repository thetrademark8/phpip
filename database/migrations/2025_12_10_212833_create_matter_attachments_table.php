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
        Schema::create('matter_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('matter_id');
            $table->string('filename', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('disk', 50)->default('s3');
            $table->string('path', 500);
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamps();

            $table->foreign('matter_id')->references('id')->on('matter')->onDelete('cascade');
            $table->index(['matter_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matter_attachments');
    }
};
