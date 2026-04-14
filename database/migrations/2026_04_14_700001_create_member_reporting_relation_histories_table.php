<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_reporting_relation_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_reporting_relation_id');
            $table->string('action', 30);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('member_reporting_relation_id', 'fk_mrrh_relation')
                ->references('id')->on('member_reporting_relations')->cascadeOnDelete();
            $table->foreign('changed_by', 'fk_mrrh_changed_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['member_reporting_relation_id', 'created_at'], 'idx_mrrh_relation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_reporting_relation_histories');
    }
};
