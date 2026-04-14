<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('notice_no', 30)->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('notice_type', 30);
            $table->string('priority', 20);
            $table->string('status', 30);
            $table->string('visibility', 30);
            $table->string('audience_type', 30);
            $table->unsignedBigInteger('committee_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('requires_acknowledgement')->default(false);
            $table->unsignedInteger('attachment_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('committee_id', 'fk_notices_committee')
                ->references('id')->on('committees')->nullOnDelete();
            $table->foreign('created_by', 'fk_notices_created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_notices_updated_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('author_id', 'fk_notices_author')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('approver_id', 'fk_notices_approver')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('notice_type', 'idx_notices_notice_type');
            $table->index('priority', 'idx_notices_priority');
            $table->index('status', 'idx_notices_status');
            $table->index('visibility', 'idx_notices_visibility');
            $table->index('audience_type', 'idx_notices_audience_type');
            $table->index('committee_id', 'idx_notices_committee');
            $table->index('is_pinned', 'idx_notices_is_pinned');
            $table->index('publish_at', 'idx_notices_publish_at');
            $table->index('expires_at', 'idx_notices_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
