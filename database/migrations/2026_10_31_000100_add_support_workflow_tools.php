<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->timestamp('contact_verified_at')->nullable());
        Schema::create('request_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('request_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('title');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('request_recovery_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('consultation_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach ([
            ['access_recovery', 'استعادة الوصول للطلب', 'رابط متابعة طلبك', "مرحبًا {{name}}،\nلفتح طلبك استخدم الرابط الخاص التالي خلال 30 دقيقة (يستخدم مرة واحدة):\n{{message}}\nاحتفظ بالرابط لنفسك."],
            ['verify_contact', 'تأكيد بريد التواصل', 'تأكيد بريدك في المنصة', "مرحبًا {{name}}،\nلتأكيد البريد وإتاحة استعادة الوصول استخدم الرابط التالي خلال ساعة:\n{{message}}\nإذا لم تطلب التأكيد يمكنك تجاهل الرسالة."],
        ] as [$key, $name, $subject, $body]) {
            DB::table('email_templates')->insert(compact('key','name','subject','body') + ['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void
    {
        foreach (['privacy_requests','request_recovery_tokens','internal_notes','request_tasks','request_updates'] as $table) Schema::dropIfExists($table);
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->dropColumn('contact_verified_at'));
        DB::table('email_templates')->whereIn('key',['access_recovery','verify_contact'])->delete();
    }
};
