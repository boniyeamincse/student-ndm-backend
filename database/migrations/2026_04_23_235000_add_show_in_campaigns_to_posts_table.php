<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('show_in_campaigns')->default(false)->after('allow_on_homepage');
            $table->index('show_in_campaigns', 'idx_posts_show_in_campaigns');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_show_in_campaigns');
            $table->dropColumn('show_in_campaigns');
        });
    }
};
