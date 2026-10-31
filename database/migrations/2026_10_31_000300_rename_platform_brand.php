<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('mail_settings')->where('from_name','BLACKMAIL.OM')->update(['from_name'=>'منصة إبتزاز','updated_at'=>now()]);
        DB::table('email_templates')->orderBy('id')->get()->each(function ($template): void {
            $subject=str_replace('BLACKMAIL.OM','منصة إبتزاز',$template->subject);
            $body=str_replace('BLACKMAIL.OM','منصة إبتزاز',$template->body);
            if ($subject!==$template->subject || $body!==$template->body) DB::table('email_templates')->where('id',$template->id)->update(compact('subject','body')+['updated_at'=>now()]);
        });
    }
    public function down(): void
    {
        // Branding updates do not modify the database schema.
    }
};
