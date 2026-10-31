<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        Schema::table('users',fn(Blueprint $table)=>$table->unsignedInteger('auth_version')->default(1));
        Schema::table('reporting_authorities',fn(Blueprint $table)=>$table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete());
        DB::table('countries')->where('verification_status','verified')->where(fn($q)=>$q->whereNull('verified_by')->orWhereNull('last_verified_at'))->update(['verification_status'=>'in_review','is_published'=>false]);
        DB::table('reporting_authorities')->where('verification_status','verified')->update(['verification_status'=>'in_review','is_published'=>false]);
    }
    public function down(): void {
        Schema::table('reporting_authorities',function(Blueprint $table){$table->dropForeign(['verified_by']);$table->dropColumn('verified_by');});
        Schema::table('users',fn(Blueprint $table)=>$table->dropColumn('auth_version'));
    }
};
