<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->uuid('criterion_id');
            $table->uuid('candidate_id');
            $table->decimal('raw_value', 5, 2);
            $table->decimal('normalized_value', 18, 8);
            $table->text('evidence_url')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('criterion_id')->references('id')->on('criteria');
            $table->foreign('candidate_id')->references('id')->on('candidates');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->timestampsTz();

            $table->unique(['period_id', 'criterion_id', 'candidate_id']);
            $table->index('criterion_id');
            $table->index('candidate_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE scores
                ADD CONSTRAINT scores_raw_value_bounds
                CHECK (raw_value >= 0 AND raw_value <= 100)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
