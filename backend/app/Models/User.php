<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'birthdate',
        'email',
        'password',
        'system_role',    
        ];

    protected $casts = [
        'birthdate' => 'date',
        'password' => 'hashed',
    ];    

    protected $hidden = [
        'password',
    ];

    public function schoolClasses(){
        return $this->belongsToMany(SchoolClass::class, 'class_members', 'user_id', 'class_id');
    }

    public function taskCompletions(){
        return $this->hasMany(TaskCompletion::class, 'user_id');
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
