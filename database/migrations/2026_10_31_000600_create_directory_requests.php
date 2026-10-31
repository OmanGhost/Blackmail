<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('directory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('kind',20);
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country_name',150);
            $table->text('contact_name');
            $table->text('organization')->nullable();
            $table->text('job_title')->nullable();
            $table->text('email');
            $table->text('phone')->nullable();
            $table->text('official_url');
            $table->text('details');
            $table->string('status',20)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->dateTime('consented_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('directory_requests'); }
};
