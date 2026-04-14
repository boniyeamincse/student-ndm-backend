<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notice_id');
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('notice_id', 'fk_notice_status_histories_notice')
                ->references('id')->on('notices')->cascadeOnDelete();
            $table->foreign('changed_by', 'fk_notice_status_histories_changed_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['notice_id', 'created_at'], 'idx_notice_status_histories_notice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_status_histories');
    }
};
