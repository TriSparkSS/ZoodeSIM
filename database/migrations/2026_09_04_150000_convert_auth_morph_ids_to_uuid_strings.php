<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert morph IDs from bigint to CHAR(36) so Partner UUID PKs fit.
     * Admin integer IDs continue to work as digit strings.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->convertTable('auth_activity_logs', nullable: true, morphIndex: 'auth_activity_logs_authenticatable_type_authenticatable_id_index');
        $this->convertTable('auth_sessions', nullable: false, morphIndex: 'auth_sessions_authenticatable_type_authenticatable_id_index');
    }

    public function down(): void
    {
        // Irreversible: UUID partner IDs cannot be stored in bigint.
    }

    protected function convertTable(string $table, bool $nullable, string $morphIndex): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $this->dropIndexIfExists($table, $morphIndex);

        if ($table === 'auth_sessions') {
            $this->dropIndexIfExists($table, 'auth_sessions_auth_revoked_index');
        }

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE `{$table}` MODIFY `authenticatable_id` CHAR(36) {$nullSql}");

        // Cast any legacy integer IDs already stored to string form is automatic in CHAR.
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$morphIndex}` (`authenticatable_type`, `authenticatable_id`)");

        if ($table === 'auth_sessions') {
            DB::statement('ALTER TABLE `auth_sessions` ADD INDEX `auth_sessions_auth_revoked_index` (`authenticatable_type`, `authenticatable_id`, `revoked_at`)');
        }
    }

    protected function dropIndexIfExists(string $table, string $index): void
    {
        $database = Schema::getConnection()->getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
