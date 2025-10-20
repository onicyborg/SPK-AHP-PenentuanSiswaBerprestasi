<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PairwiseAlternatives extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pairwise_alternatives';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // only updated_at column exists by design

    protected $fillable = [
        'period_id',
        'criterion_id',
        'i_candidate_id',
        'j_candidate_id',
        'value',
        'updated_by',
        'updated_at',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function criterion()
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }

    public function iCandidate()
    {
        return $this->belongsTo(Candidates::class, 'i_candidate_id');
    }

    public function jCandidate()
    {
        return $this->belongsTo(Candidates::class, 'j_candidate_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
