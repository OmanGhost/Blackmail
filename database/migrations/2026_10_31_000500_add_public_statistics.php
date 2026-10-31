<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->string('service_type', 20)->nullable()->index();
        });
        Schema::create('platform_visit_totals', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('visitor_sessions')->default(0);
            $table->timestamp('started_at');
        });
        \Illuminate\Support\Facades\DB::table('platform_visit_totals')->insert(['id'=>1, 'visitor_sessions'=>0, 'started_at'=>now()]);
    }
    public function down(): void {
        Schema::dropIfExists('platform_visit_totals');
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });
    }
};
