<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'task_completions_task_id_user_id_unique';

    public function up(): void
    {
        $duplicate = DB::table('task_completions')
            ->select('task_id', 'user_id')
            ->groupBy('task_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException('Cannot add task_completions(task_id, user_id) unique constraint: duplicate pairs exist. Resolve them explicitly; no rows were deleted.');
        }

        Schema::table('task_completions', function (Blueprint $table) {
            $table->unique(['task_id', 'user_id'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
