<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SchoolClass extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'course',
        'semester',
        'year',
        'starts_on',
        'ends_on',
    ];

    protected $casts = [
        'semester' => 'integer',
        'year'=> 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function members(){
        return $this->belongsToMany(User::class, 'class_members', 'class_id', 'user_id');
    }

    public function tasks(){
        return $this->hasMany(Task::class, 'class_id');
    }

    public function announcements(){
        return $this->hasMany(Announcement::class, 'class_id');
    }

    public function calendarEvents(){
        return $this->hasMany(CalendarEvent::class, 'class_id');
    }
}
