<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories',function (Blueprint $table) {
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
        });
        foreach (require database_path('data/category-guides.php') as $slug=>$guide) DB::table('categories')->where('slug',$slug)->update(['content_ar'=>$guide['content_ar'],'content_en'=>$guide['content_en']]);
    }
    public function down(): void { Schema::table('categories',fn (Blueprint $table)=>$table->dropColumn(['content_ar','content_en'])); }
};
