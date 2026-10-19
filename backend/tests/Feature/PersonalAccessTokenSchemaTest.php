<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PersonalAccessTokenSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_tokens_survive_compatibility_migration_rollback_and_reapply(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('schema-check')->accessToken;
        $migration = require database_path('migrations/2026_08_21_002000_change_personal_access_tokens_tokenable_id_to_uuid.php');

        $migration->down();
        $migration->up();

        $this->assertSame('uuid', $this->tokenableIdType());
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->id,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_empty_legacy_numeric_column_can_be_migrated_to_uuid(): void
    {
        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING 1');
        $migration = require database_path('migrations/2026_08_21_002000_change_personal_access_tokens_tokenable_id_to_uuid.php');

        $migration->up();

        $this->assertSame('uuid', $this->tokenableIdType());
    }

    public function test_populated_legacy_numeric_column_is_refused_without_changing_rows(): void
    {
        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING 1');
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => 123,
            'name' => 'legacy-token',
            'token' => str_repeat('a', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $migration = require database_path('migrations/2026_08_21_002000_change_personal_access_tokens_tokenable_id_to_uuid.php');

        try {
            $migration->up();
            $this->fail('Expected the migration to refuse populated numeric IDs.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('explicit ID mapping', $exception->getMessage());
        }

        $this->assertSame('bigint', $this->tokenableIdType());
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => 123,
            'token' => str_repeat('a', 64),
        ]);
    }

    private function tokenableIdType(): string
    {
        $column = DB::selectOne("SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'personal_access_tokens' AND column_name = 'tokenable_id'");

        return $column->data_type;
    }
}
