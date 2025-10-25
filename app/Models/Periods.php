<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Candidates;
use App\Models\Criteria;
use App\Models\Scores;
use App\Models\Weights;
use App\Models\Results;
use App\Models\PairwiseCriteria;
use App\Models\PairwiseAlternatives;
use App\Models\AuditLogs;

class Periods extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'periods';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
        'finalized_at',
        'is_results_stale',
        'last_calculated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'finalized_at' => 'datetime',
        'is_results_stale' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function candidates()
    {
        return $this->hasMany(Candidates::class, 'period_id');
    }

    public function criteria()
    {
        return $this->hasMany(Criteria::class, 'period_id');
    }

    public function scores()
    {
        return $this->hasMany(Scores::class, 'period_id');
    }

    public function weights()
    {
        return $this->hasMany(Weights::class, 'period_id');
    }

    public function results()
    {
        return $this->hasMany(Results::class, 'period_id');
    }

    public function pairwiseCriteria()
    {
        return $this->hasMany(PairwiseCriteria::class, 'period_id');
    }

    public function pairwiseAlternatives()
    {
        return $this->hasMany(PairwiseAlternatives::class, 'period_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLogs::class, 'period_id');
    }
}
