<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PairwiseCriteria extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pairwise_criteria';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // only updated_at column exists by design

    protected $fillable = [
        'period_id',
        'i_criterion_id',
        'j_criterion_id',
        'value',
        'updated_by',
        'updated_at',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function iCriterion()
    {
        return $this->belongsTo(Criteria::class, 'i_criterion_id');
    }

    public function jCriterion()
    {
        return $this->belongsTo(Criteria::class, 'j_criterion_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
