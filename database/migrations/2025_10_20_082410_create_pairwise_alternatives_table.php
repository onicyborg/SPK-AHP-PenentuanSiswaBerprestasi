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
        Schema::create('pairwise_alternatives', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->uuid('criterion_id');
            $table->uuid('i_candidate_id');
            $table->uuid('j_candidate_id');
            $table->decimal('value', 10, 4);
            $table->uuid('updated_by');
            $table->timestampTz('updated_at');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('criterion_id')->references('id')->on('criteria');
            $table->foreign('i_candidate_id')->references('id')->on('candidates');
            $table->foreign('j_candidate_id')->references('id')->on('candidates');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->unique(['period_id','criterion_id','i_candidate_id','j_candidate_id']);
            $table->index('criterion_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pairwise_alternatives ADD CONSTRAINT pairwise_alternatives_i_neq_j CHECK (i_candidate_id <> j_candidate_id)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pairwise_alternatives');
    }
};

