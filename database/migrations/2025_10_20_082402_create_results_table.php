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
        Schema::create('results', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->uuid('candidate_id');
            $table->decimal('final_score', 18, 8);
            $table->integer('rank');
            $table->timestampTz('computed_at');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('candidate_id')->references('id')->on('candidates');

            $table->unique(['period_id','candidate_id']);
            $table->unique(['period_id','rank']);
            $table->index('final_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
