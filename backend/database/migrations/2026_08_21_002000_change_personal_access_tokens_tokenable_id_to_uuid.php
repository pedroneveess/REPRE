<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $column = DB::selectOne("SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'personal_access_tokens' AND column_name = 'tokenable_id'");

        if ($column?->data_type === 'uuid') {
            return;
        }

        if (! in_array($column?->data_type, ['smallint', 'integer', 'bigint'], true)) {
            throw new RuntimeException('Cannot safely migrate personal_access_tokens.tokenable_id: expected a UUID or an empty numeric column.');
        }

        if (DB::table('personal_access_tokens')->exists()) {
            throw new RuntimeException('Cannot migrate populated numeric personal_access_tokens.tokenable_id values to UUID without an explicit ID mapping. No token rows were changed.');
        }

        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE uuid USING tokenable_id::text::uuid');
    }

    public function down(): void
    {
        // Keep UUID IDs: converting them back to integers is lossy and unsafe.
    }
};
