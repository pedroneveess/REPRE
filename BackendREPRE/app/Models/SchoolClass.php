<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class SchoolClass extends Model
{
    use HasUuids; 

    protected $fillable = [
        'name', 
        'course', 
        'semester',
        'year', 
    ];

    protected $casts = [
        'semester' => 'integer',
        'year'=> 'integer',
    ]; 
}
