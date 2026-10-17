<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'organization_id',
        'created_by',
    ];

    protected $casts = [
        'description' => 'string',
    ];

    public function organization(){
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }
}