<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analysis_histories', function (Blueprint $table) {
            $table->id();
            $table->string('guest_uuid', 36)->index();
            $table->string('ticker', 20);
            $table->json('analysis_result');
            $table->timestamps();

            // Composite index for guest + recent queries
            $table->index(['guest_uuid', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_histories');
    }
};
