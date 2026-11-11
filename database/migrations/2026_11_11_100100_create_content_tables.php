<?php

declare(strict_types=1);

use App\Support\Database\ContentColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * İçerik (CMS) tabloları — docs/DATABASE.md §10.1–§10.17.
 * application_type_id kolonlarının FK'leri application_types tablosuyla birlikte (Faz 8) eklenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('disk', 32)->default('public');
            $table->string('path', 255);
            $table->json('variants')->nullable();
            $table->string('original_name', 255);
            $table->string('mime', 100);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('size_bytes');
            $table->string('alt_text', 255)->nullable();
            $table->boolean('is_decorative')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetimes();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 150);
            $table->string('template', 32)->default('standard');
            $table->mediumText('body')->nullable();
            $table->string('version_label', 32)->nullable();
            $table->dateTime('effective_at')->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            ContentColumns::publishable($table);
            $table->softDeletesDatetime();
        });

        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 48);
            $table->json('data');
            $table->smallInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->datetimes();
            $table->index(['page_id', 'sort']);
        });

        Schema::create('page_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('from_path', 255)->unique();
            $table->string('to_path', 255);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('location', 32);
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('label', 64);
            $table->string('route_name', 100)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('visibility', 16)->default('all');
            $table->boolean('opens_new_tab')->default(false);
            $table->smallInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->datetimes();
            $table->index(['location', 'sort']);
        });

        Schema::create('social_links', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 32);
            $table->string('label', 64);
            $table->string('url', 255);
            $table->smallInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->datetimes();
        });

        Schema::create('news_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 64);
            $table->smallInteger('sort')->default(0);
            $table->datetimes();
        });

        Schema::create('news_posts', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('slug', 120)->unique();
            $table->foreignId('news_category_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->string('excerpt', 300)->nullable();
            $table->mediumText('body');
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('author_label', 64)->default('Yönetim Ekibi');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            ContentColumns::publishable($table);
            $table->softDeletesDatetime();
            $table->index(['news_category_id', 'published_at']);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 64);
        });

        Schema::create('news_post_tag', function (Blueprint $table): void {
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_post_id', 'tag_id']);
        });

        Schema::create('game_system_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 64);
            $table->string('icon', 48)->nullable();
            $table->smallInteger('sort')->default(0);
            $table->datetimes();
        });

        Schema::create('game_systems', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->foreignId('game_system_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('summary', 300);
            $table->mediumText('how_it_works');
            $table->text('how_to_access')->nullable();
            $table->text('requirements')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('video_url', 255)->nullable();
            $table->boolean('is_live_in_game')->default(false);
            $table->dateTime('last_reviewed_at')->nullable();
            ContentColumns::publishable($table);
        });

        Schema::create('rule_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 100);
            $table->string('description', 300)->nullable();
            $table->string('prefix', 8);
            ContentColumns::publishable($table);
        });

        Schema::create('rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 16)->unique();
            $table->string('title', 150);
            $table->text('body');
            $table->string('severity_hint', 16)->nullable();
            ContentColumns::publishable($table, withSeo: false);
        });

        Schema::create('rule_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_note', 255)->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('game_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 100);
            $table->string('summary', 300);
            $table->string('class', 16)->default('legal');
            $table->mediumText('description');
            $table->text('duties')->nullable();
            $table->text('progression')->nullable();
            $table->text('earnings_info')->nullable();
            $table->string('access_requirement', 255)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedBigInteger('application_type_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            ContentColumns::publishable($table);
        });

        Schema::create('factions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 100);
            $table->string('type', 16);
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('summary', 300);
            $table->mediumText('description');
            $table->text('min_requirements')->nullable();
            $table->unsignedBigInteger('application_type_id')->nullable()->index();
            $table->boolean('applications_open')->default(false);
            $table->foreignId('rule_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('accent_token', 16)->nullable();
            ContentColumns::publishable($table);
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 48);
            $table->string('question', 255);
            $table->text('answer');
            $table->boolean('show_on_home')->default(false);
            ContentColumns::publishable($table, withSeo: false);
        });

        Schema::create('community_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('slug', 120)->unique();
            $table->string('title', 150);
            $table->string('summary', 300);
            $table->mediumText('description');
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location_label', 100)->nullable();
            ContentColumns::publishable($table);
            $table->index(['status', 'starts_at']);
        });

        Schema::create('academy_courses', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 150);
            $table->string('summary', 300);
            $table->string('level', 16)->default('beginner');
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_onboarding')->default(false);
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            ContentColumns::publishable($table);
        });

        Schema::create('academy_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_course_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('title', 150);
            $table->mediumText('body');
            $table->string('step_label', 48)->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(3);
            ContentColumns::publishable($table);
            $table->unique(['academy_course_id', 'slug']);
        });

        Schema::create('academy_topic_rule', function (Blueprint $table): void {
            $table->foreignId('academy_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained()->cascadeOnDelete();
            $table->primary(['academy_topic_id', 'rule_id']);
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('name', 100);
            $table->string('email', 254);
            $table->string('subject', 150);
            $table->text('body');
            $table->char('ip_hash', 64)->nullable();
            $table->string('status', 16)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetimes();
            $table->index(['status', 'created_at']);
        });

        // docs/DATABASE.md §10.6: dahili rota veya harici URL'den biri zorunlu.
        DB::statement('ALTER TABLE navigation_items ADD CONSTRAINT chk_navigation_target CHECK (route_name IS NOT NULL OR url IS NOT NULL)');
    }

    public function down(): void
    {
        foreach ([
            'contact_messages', 'academy_topic_rule', 'academy_topics', 'academy_courses', 'community_events',
            'faqs', 'factions', 'game_jobs', 'rule_revisions', 'rules', 'rule_categories', 'game_systems',
            'game_system_categories', 'news_post_tag', 'tags', 'news_posts', 'news_categories', 'social_links',
            'navigation_items', 'redirects', 'page_revisions', 'page_sections', 'pages', 'media',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
