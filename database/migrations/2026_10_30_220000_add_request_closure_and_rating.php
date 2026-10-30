<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by_role')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamp('rated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('consultation_requests', fn (Blueprint $table) => $table->dropColumn(['closed_at', 'closed_by_role', 'rating', 'rating_comment', 'rated_at']));
    }
};
