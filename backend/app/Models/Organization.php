<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name', 
        'description', 
        'type',
    ];

    protected $casts = [
        'description' => 'string',
        'type' => 'other',
    ]; 

    public function OrganizationMember(){
        return $this->belongsToMany(User::class);
    }

    public function OrganizationAnnouncement(){
        return $this->hasMany(Announcement::class);
    }
}