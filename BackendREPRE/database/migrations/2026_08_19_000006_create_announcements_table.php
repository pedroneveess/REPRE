<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('school_classes')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->string('title', 200);    
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
