<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 
        'description', 
    ];

    protected $casts = [
        'description' => 'string',
    ]; 

    public function SchoolClassPosts(){
        return $this->belongsTo(SchoolClass::class);
    }
}