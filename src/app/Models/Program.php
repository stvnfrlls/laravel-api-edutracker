<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'curriculum')
            ->withPivot(['year_level', 'semester'])
            ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
