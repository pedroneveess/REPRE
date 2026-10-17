<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'due_date',
        'class_id',
        'created_by',
    ];

    protected $casts = [
        'semester' => 'integer',
        'year'=> 'integer',
    ];

    public function schoolClass(){
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions(){
        return $this->hasMany(TaskCompletion::class, 'task_id');
    }
}
