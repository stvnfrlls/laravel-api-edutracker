<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'quarter_1',
        'quarter_2',
        'quarter_3',
        'quarter_4',
        'final_grade',
        'remarks',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
