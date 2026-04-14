<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('status');
            $table->timestamp('last_status_changed_at')->nullable()->after('bio');
            $table->text('status_note')->nullable()->after('last_status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['bio', 'last_status_changed_at', 'status_note']);
        });
    }
};
