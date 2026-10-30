<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use App\Models\HomepageSection;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['super-admin' => 'مدير النظام', 'content-manager' => 'مدير المحتوى', 'legal-reviewer' => 'المراجع القانوني', 'security-consultant' => 'مستشار الأمن السيبراني', 'support-agent' => 'موظف الدعم', 'member' => 'عضو'] as $name => $label) {
            Role::updateOrCreate(['name' => $name], ['label_ar' => $label]);
        }
        foreach ([['sexual-extortion', 'الابتزاز الجنسي', 'Sexual extortion'], ['financial-blackmail', 'الابتزاز المالي', 'Financial blackmail'], ['account-hacking', 'اختراق الحسابات', 'Account hacking'], ['identity-impersonation', 'انتحال الهوية', 'Identity impersonation'], ['ai-deepfake', 'التزييف العميق', 'AI deepfake blackmail'], ['digital-privacy', 'الخصوصية الرقمية', 'Digital privacy']] as $i => [$slug,$ar,$en]) {
            Category::updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'sort_order' => $i]);
        }
        foreach ([['OM', 'عُمان', 'Oman'], ['SA', 'السعودية', 'Saudi Arabia'], ['AE', 'الإمارات', 'United Arab Emirates'], ['BH', 'البحرين', 'Bahrain'], ['KW', 'الكويت', 'Kuwait'], ['QA', 'قطر', 'Qatar'], ['YE', 'اليمن', 'Yemen'], ['IQ', 'العراق', 'Iraq'], ['JO', 'الأردن', 'Jordan'], ['PS', 'فلسطين', 'Palestine'], ['LB', 'لبنان', 'Lebanon'], ['SY', 'سوريا', 'Syria'], ['EG', 'مصر', 'Egypt'], ['SD', 'السودان', 'Sudan'], ['SO', 'الصومال', 'Somalia'], ['DJ', 'جيبوتي', 'Djibouti'], ['KM', 'جزر القمر', 'Comoros'], ['LY', 'ليبيا', 'Libya'], ['TN', 'تونس', 'Tunisia'], ['DZ', 'الجزائر', 'Algeria'], ['MA', 'المغرب', 'Morocco'], ['MR', 'موريتانيا', 'Mauritania']] as [$code,$ar,$en]) {
            Country::updateOrCreate(['code' => $code], ['name_ar' => $ar, 'name_en' => $en, 'slug' => str($en)->slug(), 'verification_status' => 'unverified', 'is_published' => false]);
        }
        foreach ([
            ['hero', 'القسم الرئيسي', 'لا تواجه الابتزاز وحدك', 'المنصة العربية لمكافحة الابتزاز الإلكتروني. نساعدك على فهم التهديدات الرقمية وحماية خصوصيتك.', 'أتعرض للابتزاز الآن', '/help', 0],
            ['help', 'المساعدة الفورية', 'لست مضطراً لمعرفة كل شيء الآن', 'أجب عن أسئلة عامة قليلة لنقدم لك خطوات آمنة. لا نحفظ إجاباتك افتراضياً.', 'ابدأ دليل المساعدة', '/help', 10],
            ['categories', 'التصنيفات', 'أشكال الابتزاز الإلكتروني', 'تعرّف على الأنواع الشائعة وكيفية التعامل معها.', 'تصفح الموسوعة', '/encyclopedia', 20],
            ['protection', 'الحماية الرقمية', 'دقائق قليلة تصنع فرقاً', 'ابنِ طبقات حماية بسيطة وفعالة لحساباتك وأجهزتك.', 'ابدأ قائمة التحقق', '/tools', 30],
            ['countries', 'دليل الدول', 'قنوات الإبلاغ في بلدك', 'ننشر المصادر الرسمية فقط بعد مراجعتها.', 'عرض دليل الدول', '/countries', 40],
            ['consultation', 'الاستشارات', 'تحتاج إلى مساعدة من مختص؟', 'اطلب استشارة دون إنشاء حساب مع حماية التفاصيل الحساسة.', 'اطلب استشارة آمنة', '/consultation', 50],
        ] as [$key,$name,$heading,$description,$button,$url,$order]) {
            HomepageSection::updateOrCreate(['key' => $key], ['name_ar' => $name, 'heading_ar' => $heading, 'description_ar' => $description, 'primary_button_text_ar' => $button, 'primary_button_url' => $url, 'sort_order' => $order, 'is_enabled' => true]);
        }
    }
}
