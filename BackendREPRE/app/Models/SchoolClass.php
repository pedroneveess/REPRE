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

    public function ClassMembersUser(){
        return $this->belongsToMany(User::class);
    }

    public function Tasks(){
        return $this->hasMany(Task::class);
    }

    public function Posts(){
        return $this->hasMany(Post::class);
    }
}
