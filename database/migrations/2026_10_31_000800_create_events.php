<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('events',function(Blueprint $table){
        $table->id();$table->string('slug')->unique();
        $table->string('title_ar');$table->string('title_en')->nullable();
        $table->text('summary_ar');$table->text('summary_en')->nullable();
        $table->longText('description_ar');$table->longText('description_en')->nullable();
        $table->string('event_type',20)->default('workshop');$table->string('attendance_mode',20)->default('onsite');
        $table->string('status',20)->default('draft')->index();
        $table->dateTime('starts_at')->index();$table->dateTime('ends_at');
        $table->string('venue_ar')->nullable();$table->string('venue_en')->nullable();
        $table->string('speaker_ar')->nullable();$table->string('speaker_en')->nullable();
        $table->string('registration_url',1000)->nullable();$table->string('location_url',1000)->nullable();
        $table->string('cover_image')->nullable();$table->json('agenda')->nullable();$table->timestamps();
    }); }
    public function down(): void { Schema::dropIfExists('events'); }
};
