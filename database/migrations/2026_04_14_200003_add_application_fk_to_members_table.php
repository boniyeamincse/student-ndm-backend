<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 02 — Migration 4/4
 *
 * Adds FK constraint for members.membership_application_id → membership_applications.
 * Circular dependency resolved by splitting into this separate migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('membership_application_id', 'fk_members_application')
                  ->references('id')
                  ->on('membership_applications')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign('fk_members_application');
        });
    }
};
