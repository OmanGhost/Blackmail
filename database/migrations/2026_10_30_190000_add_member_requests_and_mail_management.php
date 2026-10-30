<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->default(587);
            $table->string('scheme')->default('smtp');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->default('BLACKMAIL.OM');
            $table->timestamps();
        });
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key');
            $table->string('status');
            $table->timestamps();
        });
        DB::table('mail_settings')->insert(['enabled' => false, 'port' => 587, 'scheme' => 'smtp', 'from_name' => 'BLACKMAIL.OM', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([
            ['request_received', 'استلام طلب جديد', 'تم استلام طلبك', "مرحبًا {{name}}،\nتم استلام طلبك. يمكنك متابعة حالته من حسابك:\n{{account_url}}\nفريق BLACKMAIL.OM"],
            ['request_replied', 'رد جديد من الفريق', 'لديك تحديث على طلبك', "مرحبًا {{name}}،\nأضاف الفريق ردًا على طلبك. لقراءته ادخل إلى حسابك:\n{{account_url}}\nفريق BLACKMAIL.OM"],
            ['general', 'رسالة عامة للمستخدم', 'رسالة من BLACKMAIL.OM', "مرحبًا {{name}}،\n{{message}}\nفريق BLACKMAIL.OM"],
        ] as [$key, $name, $subject, $body]) {
            DB::table('email_templates')->insert(compact('key', 'name', 'subject', 'body') + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('mail_settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('last_login_at'));
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
    }
};
