<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'content',
        'class_id',
        'created_by',
    ];

    protected $casts = [
        'content' => 'string',
    ];

    public function schoolClass(){
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }
}
