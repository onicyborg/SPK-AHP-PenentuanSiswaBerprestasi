<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('results_breakdown', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('period_id')->notNull();
            $table->uuid('candidate_id')->notNull();
            $table->uuid('criterion_id')->notNull();

            // Snapshot nilai saat komputasi (opsional raw untuk display proses)
            $table->decimal('raw_value', 18, 4)->nullable();
            $table->decimal('normalized_value', 18, 8)->notNull(); // 0..1
            $table->decimal('weight', 12, 8)->notNull();           // bobot kriteria yang dipakai
            $table->decimal('contribution', 18, 8)->notNull();     // weight * normalized_value

            $table->timestampTz('computed_at')->notNull();

            // Jejak (opsional)
            $table->uuid('computed_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // FKs
            $table->foreign('period_id')->references('id')->on('periods')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('criterion_id')->references('id')->on('criteria')->cascadeOnDelete();
            $table->foreign('computed_by')->references('id')->on('users');

            // Unik per (periode, kandidat, kriteria) agar bisa di-upsert per komputasi
            $table->unique(['period_id', 'candidate_id', 'criterion_id']);

            // Index untuk query umum
            $table->index(['period_id']);
            $table->index(['candidate_id']);
            $table->index(['criterion_id']);
            $table->index(['computed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results_breakdown');
    }
};
