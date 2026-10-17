<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasFactory, HasUuids, HasApiTokens;

    protected $fillable = [
        'name',
        'birthdate',
        'email',
        'password',
        'system_role',    
        ];

    protected $casts = [
        'birthdate' => 'datetime',
        'password' => 'hashed',
    ];    

    protected $hidden = [
        'password',
    ];

    public function schoolClasses(){
        return $this->belongsToMany(SchoolClass::class, 'class_members', 'user_id', 'class_id');
    }

    public function taskCompletions(){
        return $this->belongsToMany(Task::class, 'task_completions');
    }

    public function organizations(){
        return $this->belongsToMany(Organization::class, 'organization_members');
    }

    public function createdTasks(){
        return $this->hasMany(Task::class, 'created_by');
    }

    public function createdAnnouncements(){
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function createdPosts(){
        return $this->hasMany(Post::class, 'created_by');
    }

    public function createdCalendarEvents(){
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }
}
