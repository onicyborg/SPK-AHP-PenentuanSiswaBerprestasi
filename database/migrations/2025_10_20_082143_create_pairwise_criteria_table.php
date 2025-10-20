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
        Schema::create('pairwise_criteria', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->uuid('i_criterion_id');
            $table->uuid('j_criterion_id');
            $table->decimal('value', 10, 4);
            $table->uuid('updated_by');
            $table->timestampTz('updated_at');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('i_criterion_id')->references('id')->on('criteria');
            $table->foreign('j_criterion_id')->references('id')->on('criteria');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->unique(['period_id','i_criterion_id','j_criterion_id']);
            $table->index('i_criterion_id');
            $table->index('j_criterion_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pairwise_criteria ADD CONSTRAINT pairwise_criteria_i_neq_j CHECK (i_criterion_id <> j_criterion_id)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pairwise_criteria');
    }
};

