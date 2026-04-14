<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 02 — Migration 3/4
 * Depends on: membership_applications, users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_application_id')
                  ->constrained('membership_applications')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            // History records are immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};
