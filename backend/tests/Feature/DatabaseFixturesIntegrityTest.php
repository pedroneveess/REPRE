<?php

namespace Tests\Feature;

use App\Models\TaskCompletion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseFixturesIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_completion_factory_creates_valid_foreign_keys(): void
    {
        $completion = TaskCompletion::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $completion->user_id]);
        $this->assertDatabaseHas('tasks', ['id' => $completion->task_id]);
    }

    public function test_database_seeder_is_idempotent_and_seeds_task_due_dates(): void
    {
        $this->app->make(DatabaseSeeder::class)->run();
        $counts = $this->seededTableCounts();

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertSame($counts, $this->seededTableCounts());
        $this->assertNotNull(DB::table('tasks')->where('title', 'Lista de Exercicios de Matematica')->value('due_date'));
        $this->assertNotNull(DB::table('tasks')->where('title', 'Pesquisa sobre Python')->value('due_date'));
    }

    public function test_database_seeder_refuses_production_before_writing(): void
    {
        $before = $this->seededTableCounts();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $this->app->make(DatabaseSeeder::class)->run();
            $this->fail('Expected the seeder to refuse production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('cannot run in production', $exception->getMessage());
        }

        $this->assertSame($before, $this->seededTableCounts());
    }

    public function test_task_completion_unique_index_rolls_back_and_reapplies(): void
    {
        $migration = require database_path('migrations/2026_08_22_000001_add_unique_task_user_to_task_completions.php');

        $migration->down();
        $this->assertFalse($this->hasUniqueIndex());
        $migration->up();

        $this->assertTrue($this->hasUniqueIndex());
    }

    public function test_unique_index_rejects_duplicate_completion_pair(): void
    {
        $completion = TaskCompletion::factory()->create();

        try {
            DB::transaction(function () use ($completion) {
                TaskCompletion::factory()->create([
                    'user_id' => $completion->user_id,
                    'task_id' => $completion->task_id,
                ]);
            });
            $this->fail('Expected the unique index to reject a duplicate pair.');
        } catch (QueryException) {
            $this->assertDatabaseCount('task_completions', 1);
        }
    }

    public function test_migration_refuses_existing_duplicates_without_deleting_rows(): void
    {
        $migration = require database_path('migrations/2026_08_22_000001_add_unique_task_user_to_task_completions.php');
        $migration->down();
        $completion = TaskCompletion::factory()->create();
        TaskCompletion::factory()->create([
            'user_id' => $completion->user_id,
            'task_id' => $completion->task_id,
        ]);

        try {
            $migration->up();
            $this->fail('Expected the migration to refuse duplicate pairs.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('duplicate pairs exist', $exception->getMessage());
        }

        $this->assertFalse($this->hasUniqueIndex());
        $this->assertDatabaseCount('task_completions', 2);
    }

    private function hasUniqueIndex(): bool
    {
        return (bool) DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'task_completions' AND indexname = 'task_completions_task_id_user_id_unique') AS present")->present;
    }

    private function seededTableCounts(): array
    {
        $tables = ['users', 'school_classes', 'class_members', 'tasks', 'task_completions', 'announcements', 'organizations', 'organization_members', 'posts'];

        return collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }
}
