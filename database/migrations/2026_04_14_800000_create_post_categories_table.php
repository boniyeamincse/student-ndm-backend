<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'fk_post_categories_created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_post_categories_updated_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('is_active', 'idx_post_categories_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
