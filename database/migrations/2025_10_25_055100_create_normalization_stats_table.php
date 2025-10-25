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
        Schema::create('normalization_stats', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('period_id')->notNull();
            $table->uuid('criterion_id')->notNull();

            // Metode normalisasi & parameter ringkas
            // contoh method: 'max', 'minmax', 'zscore', 'inverse'
            $table->string('method', 50)->notNull();
            $table->json('params')->nullable(); // fleksibel (mis. bounds khusus)

            // Statistik yang umum dipakai untuk audit/penjelasan
            $table->decimal('min_value', 18, 6)->nullable();
            $table->decimal('max_value', 18, 6)->nullable();
            $table->decimal('sum_value', 24, 6)->nullable();
            $table->decimal('mean_value', 18, 6)->nullable();
            $table->decimal('std_dev_value', 18, 6)->nullable();
            $table->unsignedInteger('count_samples')->nullable();

            $table->timestampTz('computed_at')->notNull();

            // Jejak
            $table->uuid('computed_by')->nullable(); // optional; refer ke users jika ingin
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // FKs
            $table->foreign('period_id')->references('id')->on('periods')->cascadeOnDelete();
            $table->foreign('criterion_id')->references('id')->on('criteria')->cascadeOnDelete();
            $table->foreign('computed_by')->references('id')->on('users');

            // Unik per (periode, kriteria)
            $table->unique(['period_id', 'criterion_id']);

            // Index tambahan
            $table->index(['period_id']);
            $table->index(['criterion_id']);
            $table->index(['computed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('normalization_stats');
    }
};
