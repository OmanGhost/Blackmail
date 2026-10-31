<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('articles',function(Blueprint $table){$table->string('post_type',20)->default('article')->index();$table->string('video_url',1000)->nullable();}); }
    public function down(): void { Schema::table('articles',function(Blueprint $table){$table->dropIndex(['post_type']);$table->dropColumn(['post_type','video_url']);}); }
};
