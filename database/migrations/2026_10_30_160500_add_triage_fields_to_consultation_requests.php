<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->string('urgency')->default('normal')->index()->after('category');
            $table->string('incident_stage')->nullable()->after('urgency');
            $table->string('safe_contact_time')->nullable()->after('preferred_contact_method');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropIndex(['urgency']);
            $table->dropColumn(['urgency', 'incident_stage', 'safe_contact_time']);
        });
    }
};
