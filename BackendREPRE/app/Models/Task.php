<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Task extends Model
{
    use HasUuids; 

    protected $fillable = [
        'title', 
        'course', 
        'semester',
        'year', 
    ];

    protected $casts = [
        'semester' => 'integer',
        'year'=> 'integer',
    ]; 
}
