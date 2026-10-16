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

    public function SchoolClassTasks(){
        return $this->belongsTo(SchoolClass::class);
    }

    public function UserTaskCompletion(){
        return $this->belongsToMany(User::class);
    }
}
