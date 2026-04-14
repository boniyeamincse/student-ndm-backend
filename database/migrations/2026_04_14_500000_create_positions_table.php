<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255)->unique();
            $table->string('slug', 255)->unique();
            $table->string('code', 50)->nullable()->unique();
            $table->string('short_name', 50)->nullable();
            // Lower hierarchy_rank means higher authority (e.g., 1 is highest)
            $table->unsignedInteger('hierarchy_rank');
            $table->unsignedInteger('display_order')->nullable();
            $table->text('description')->nullable();
            $table->string('category', 30);
            $table->string('scope', 30);
            $table->boolean('is_leadership')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'scope', 'is_active'], 'idx_position_cat_scope_active');
            $table->index(['hierarchy_rank', 'display_order'], 'idx_position_rank_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
