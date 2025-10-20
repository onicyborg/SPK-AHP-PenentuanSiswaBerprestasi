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
        Schema::create('criteria', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id');
            $table->string('name', 150);
            $table->string('type', 20); // benefit|cost
            $table->uuid('parent_id')->nullable();
            $table->integer('order_index')->default(0);
            $table->uuid('created_by');
            $table->uuid('updated_by');
            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->index(['period_id','parent_id','order_index']);
            $table->timestampsTz();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE criteria ADD CONSTRAINT criteria_type_check CHECK (type IN ('benefit','cost'))");
        }

        // Add self-referential FK after table creation to avoid Postgres order dependency
        Schema::table('criteria', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('criteria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};

