<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title', 
        'content', 
    ];

    protected $casts = [
        'content' => 'string',
    ]; 

    public function OrganizationAnnouncement(){
        return $this->hasMany(Organization::class);
    }
}
