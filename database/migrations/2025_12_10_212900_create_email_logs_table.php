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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('matter_id');
            $table->unsignedInteger('template_id')->nullable();
            $table->unsignedInteger('sender_id');
            $table->string('recipient_email', 255);
            $table->string('recipient_name', 255)->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject', 500);
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->char('creator', 16)->nullable();
            $table->timestamps();

            $table->foreign('matter_id')->references('id')->on('matter')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('template_members')->onDelete('set null');
            $table->foreign('sender_id')->references('id')->on('actor')->onDelete('cascade');
            $table->index(['matter_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
