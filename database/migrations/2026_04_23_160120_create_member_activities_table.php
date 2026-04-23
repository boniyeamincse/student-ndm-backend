<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('committee_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->string('type', 50)->default('general');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('activity_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'activity_at']);
            $table->index(['committee_id', 'activity_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_activities');
    }
};
