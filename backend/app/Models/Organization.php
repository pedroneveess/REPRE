<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function members(){
        return $this->belongsToMany(User::class, 'organization_members');
    }

    public function posts(){
        return $this->hasMany(Post::class, 'organization_id');
    }
}