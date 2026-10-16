<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasUuids, HasApiTokens;

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

    public function ClassMember(){
        return $this->belongsToMany(SchoolClass::class);
    }

    public function TaskCompletion(){
        return $this->belongsToMany(Task::class);
    }

    public function OrganizationMember(){
        return $this->belongsToMany(Organization::class);
    }
}