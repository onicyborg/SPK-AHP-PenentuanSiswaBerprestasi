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
        Schema::create('weights', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->uuid('node_id'); // references criteria.id
            $table->string('level', 20); // criterion | subcriterion
            $table->decimal('weight', 12, 8);
            $table->decimal('cr_at_level', 12, 8)->nullable();
            $table->timestampTz('computed_at');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('node_id')->references('id')->on('criteria');

            $table->unique(['period_id','node_id']);
            $table->index(['period_id','level']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE weights ADD CONSTRAINT weights_level_check CHECK (level IN ('criterion','subcriterion'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weights');
    }
};

