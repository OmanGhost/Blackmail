<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->text('safe_contact_time')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->string('safe_contact_time')->nullable()->change());
    }
};
