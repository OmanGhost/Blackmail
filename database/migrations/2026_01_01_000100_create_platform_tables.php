<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label_ar');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('mfa_confirmed_at')->nullable()->after('email_verified_at');
            $table->boolean('is_active')->default(true)->after('password');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->date('last_reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->text('summary');
            $table->longText('content');
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->json('open_graph')->nullable();
            $table->json('sources')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'locale']);
            $table->index(['locale', 'title']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('flag')->nullable();
            $table->text('emergency_information_ar')->nullable();
            $table->text('emergency_information_en')->nullable();
            $table->text('reporting_instructions_ar')->nullable();
            $table->text('reporting_instructions_en')->nullable();
            $table->enum('verification_status', ['unverified', 'in_review', 'verified', 'expired'])->default('unverified')->index();
            $table->date('last_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('reporting_authorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('official_url')->nullable();
            $table->string('phone')->nullable();
            $table->text('instructions_ar')->nullable();
            $table->enum('verification_status', ['unverified', 'in_review', 'verified', 'expired'])->default('unverified')->index();
            $table->date('last_verified_at')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('legal_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('official_url')->nullable();
            $table->text('summary_ar')->nullable();
            $table->enum('approval_status', ['draft', 'review', 'approved', 'rejected'])->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_ar');
            $table->text('answer_ar');
            $table->string('question_en')->nullable();
            $table->text('answer_en')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('tracking_hash', 64)->unique();
            $table->string('category');
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preferred_contact_method');
            $table->text('contact_details_encrypted');
            $table->text('description_encrypted');
            $table->timestamp('consented_at');
            $table->string('status')->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('retain_until')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('consultation_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('educational_resources', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('slug')->unique();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->longText('content_ar');
            $table->longText('content_en')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->json('value');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->nullableMorphs('auditable');
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('request_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('educational_resources');
        Schema::dropIfExists('consultation_status_history');
        Schema::dropIfExists('consultation_requests');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('legal_references');
        Schema::dropIfExists('reporting_authorities');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_translations');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['mfa_confirmed_at', 'is_active']));
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
