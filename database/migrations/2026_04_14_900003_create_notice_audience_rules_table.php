<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_audience_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notice_id');
            $table->string('rule_type', 30);
            $table->string('rule_value');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('notice_id', 'fk_notice_audience_rules_notice')
                ->references('id')->on('notices')->cascadeOnDelete();

            $table->index(['notice_id', 'rule_type'], 'idx_notice_audience_rules_notice_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_audience_rules');
    }
};
