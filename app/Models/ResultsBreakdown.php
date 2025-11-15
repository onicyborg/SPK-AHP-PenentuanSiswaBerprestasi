<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Periods;
use App\Models\Candidates;
use App\Models\Criteria;
use App\Models\User;

class ResultsBreakdown extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'results_breakdown';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'period_id',
        'candidate_id',
        'criterion_id',
        'raw_value',
        'normalized_value',
        'weight',
        'contribution',
        'computed_at',
        'computed_by',
    ];

    protected $casts = [
        'raw_value' => 'float',
        'normalized_value' => 'float',
        'weight' => 'float',
        'contribution' => 'float',
        'computed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidates::class, 'candidate_id');
    }

    public function criterion()
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }

    public function computedBy()
    {
        return $this->belongsTo(User::class, 'computed_by');
    }
}

