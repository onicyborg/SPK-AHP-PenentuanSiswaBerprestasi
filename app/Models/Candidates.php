<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Candidates extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'candidates';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'period_id',
        'student_id',
        'created_by',
        'updated_by',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
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
        return $this->hasMany(Scores::class, 'candidate_id');
    }

    public function result()
    {
        return $this->hasOne(Results::class, 'candidate_id');
    }

    public function pairwiseAlternativesI()
    {
        return $this->hasMany(PairwiseAlternatives::class, 'i_candidate_id');
    }

    public function pairwiseAlternativesJ()
    {
        return $this->hasMany(PairwiseAlternatives::class, 'j_candidate_id');
    }
}
