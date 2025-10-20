<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'criteria';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'period_id',
        'name',
        'type',
        'parent_id',
        'order_index',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function parent()
    {
        return $this->belongsTo(Criteria::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Criteria::class, 'parent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scores()
    {
        return $this->hasMany(Scores::class, 'criterion_id');
    }

    public function weights()
    {
        return $this->hasMany(Weights::class, 'node_id');
    }

    public function pairwiseCriteriaI()
    {
        return $this->hasMany(PairwiseCriteria::class, 'i_criterion_id');
    }

    public function pairwiseCriteriaJ()
    {
        return $this->hasMany(PairwiseCriteria::class, 'j_criterion_id');
    }

    public function pairwiseAlternatives()
    {
        return $this->hasMany(PairwiseAlternatives::class, 'criterion_id');
    }
}
