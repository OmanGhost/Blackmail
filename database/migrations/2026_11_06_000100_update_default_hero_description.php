<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Repeats the hero heading's words in its paragraph so search engines see the heading matches the page.
// Only replaces the original default text; descriptions edited in the admin panel are left alone.
return new class extends Migration
{
    private const OLD = 'المنصة العربية لمكافحة الابتزاز الإلكتروني. نساعدك على فهم التهديدات الرقمية وحماية خصوصيتك.';

    private const NEW = 'نحن هنا حتى لا تواجه الابتزاز الإلكتروني وحدك: المنصة العربية لمكافحة الابتزاز الإلكتروني تساعدك على فهم التهديد والتصرف بأمان وحماية خصوصيتك.';

    public function up(): void
    {
        DB::table('homepage_sections')->where('key', 'hero')->where('description_ar', self::OLD)->update(['description_ar' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('homepage_sections')->where('key', 'hero')->where('description_ar', self::NEW)->update(['description_ar' => self::OLD]);
    }
};
