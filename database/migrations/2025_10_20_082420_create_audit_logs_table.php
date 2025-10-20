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
        Schema::create('audit_logs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('period_id')->nullable();
            $table->string('entity', 100);
            $table->uuid('entity_id');
            $table->string('action', 50);
            $table->json('changes_json')->nullable();
            $table->uuid('acted_by');
            $table->timestampTz('acted_at');

            $table->foreign('period_id')->references('id')->on('periods');
            $table->foreign('acted_by')->references('id')->on('users');

            $table->index('period_id');
            $table->index(['entity','entity_id']);
            $table->index(['acted_by','acted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
