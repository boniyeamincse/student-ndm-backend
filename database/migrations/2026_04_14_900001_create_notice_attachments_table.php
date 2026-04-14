<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('notice_id');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_type', 20);
            $table->integer('sort_order')->default(1);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('notice_id', 'fk_notice_attachments_notice')
                ->references('id')->on('notices')->cascadeOnDelete();
            $table->foreign('uploaded_by', 'fk_notice_attachments_uploaded_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['notice_id', 'sort_order'], 'idx_notice_attachments_notice_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_attachments');
    }
};
