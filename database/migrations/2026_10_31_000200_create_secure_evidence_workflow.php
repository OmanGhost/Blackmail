<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evidence_requests',function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instructions');
            $table->string('kind');
            $table->string('status')->default('open');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('evidence_submissions',function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_request_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->text('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('ciphertext_sha256',64)->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });
        Schema::create('evidence_access_logs',function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('evidence_access_logs');
        Schema::dropIfExists('evidence_submissions');
        Schema::dropIfExists('evidence_requests');
    }
};
