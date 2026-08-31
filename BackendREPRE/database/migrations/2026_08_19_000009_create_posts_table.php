<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->string('title', 100);
            $table->text('description');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
