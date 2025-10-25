<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Candidates;

class Students extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'students';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nis',
        'name',
        'class',
        'photo_url',
    ];

    public function candidates()
    {
        return $this->hasMany(Candidates::class, 'student_id');
    }
}
