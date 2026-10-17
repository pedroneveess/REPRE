<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('class_members', function (Blueprint $table) {
            $table->foreignUuid('class_id')->constrained('school_classes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('role')->default('student');
            $table->timestamps();
            $table->primary(['class_id', 'user_id']);
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('class_members');
    }
};
