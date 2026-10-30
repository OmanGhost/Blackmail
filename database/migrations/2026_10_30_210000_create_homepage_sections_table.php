<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_ar');
            $table->string('heading_ar')->nullable();
            $table->string('heading_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('primary_button_text_ar')->nullable();
            $table->string('primary_button_url')->nullable();
            $table->string('secondary_button_text_ar')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->string('eyebrow_ar')->nullable();
            $table->json('extra')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
