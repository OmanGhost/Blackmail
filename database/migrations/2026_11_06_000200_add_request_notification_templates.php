<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEYS = ['staff_new_request', 'staff_request_reply', 'request_replied_contact'];

    public function up(): void
    {
        foreach ([
            ['staff_new_request', 'تنبيه الفريق بطلب جديد', 'طلب جديد في المنصة', "مرحبًا {{name}}،\nوصل طلب جديد يحتاج المراجعة:\n{{message}}\nفريق BLACKMAIL.OM"],
            ['staff_request_reply', 'تنبيه الفريق برد صاحب الطلب', 'رد جديد من صاحب الطلب', "مرحبًا {{name}}،\nأضاف صاحب الطلب ردًا جديدًا:\n{{message}}\nفريق BLACKMAIL.OM"],
            ['request_replied_contact', 'رد جديد من الفريق (بريد التواصل)', 'لديك تحديث على طلبك', "مرحبًا،\nأضاف الفريق ردًا على طلبك. لقراءته افتح صفحة المتابعة وأدخل رمز الدخول السري الخاص بطلبك:\n{{message}}\nفريق BLACKMAIL.OM"],
        ] as [$key, $name, $subject, $body]) {
            if (! DB::table('email_templates')->where('key', $key)->exists()) {
                DB::table('email_templates')->insert(compact('key', 'name', 'subject', 'body') + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', self::KEYS)->delete();
    }
};
