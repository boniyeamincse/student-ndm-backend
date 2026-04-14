<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('post_no', 30)->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('content_type', 30);
            $table->unsignedBigInteger('post_category_id')->nullable();
            $table->unsignedBigInteger('committee_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->json('tags')->nullable();
            $table->string('status', 30);
            $table->string('visibility', 30);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_on_homepage')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('post_category_id', 'fk_posts_category')
                ->references('id')->on('post_categories')->nullOnDelete();
            $table->foreign('committee_id', 'fk_posts_committee')
                ->references('id')->on('committees')->nullOnDelete();
            $table->foreign('author_id', 'fk_posts_author')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('editor_id', 'fk_posts_editor')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'fk_posts_created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_posts_updated_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('content_type', 'idx_posts_content_type');
            $table->index('status', 'idx_posts_status');
            $table->index('visibility', 'idx_posts_visibility');
            $table->index('is_featured', 'idx_posts_is_featured');
            $table->index('allow_on_homepage', 'idx_posts_allow_homepage');
            $table->index('published_at', 'idx_posts_published_at');
            $table->index('scheduled_at', 'idx_posts_scheduled_at');
            $table->index('post_category_id', 'idx_posts_category');
            $table->index('committee_id', 'idx_posts_committee');
            $table->index('author_id', 'idx_posts_author');
            $table->index('editor_id', 'idx_posts_editor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
