<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ImportSqliteDatabase extends Command
{
    protected $signature = 'unifco:import-sqlite {source=database/database.sqlite : Path to the legacy SQLite database} {--force : Replace all existing MySQL application data}';

    protected $description = 'Import the legacy SQLite application data into a freshly migrated MySQL database.';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('The target connection must use MySQL.');

            return self::FAILURE;
        }

        $sourcePath = $this->sourcePath((string) $this->argument('source'));
        if (! is_file($sourcePath)) {
            $this->error('SQLite source not found: '.$sourcePath);

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->error('This command replaces MySQL application data. Re-run with --force after taking backups.');

            return self::FAILURE;
        }

        config(['database.connections.legacy_sqlite' => [
            'driver' => 'sqlite',
            'database' => $sourcePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('legacy_sqlite');

        $source = DB::connection('legacy_sqlite');
        if (($source->selectOne('PRAGMA quick_check')->quick_check ?? null) !== 'ok') {
            $this->error('SQLite integrity check failed.');

            return self::FAILURE;
        }
        if ($source->select('PRAGMA foreign_key_check') !== []) {
            $this->error('SQLite contains foreign-key violations.');

            return self::FAILURE;
        }

        $tables = collect($source->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->pluck('name')
            ->reject(fn (string $table): bool => $table === 'migrations')
            ->values();

        if ($tables->isEmpty()) {
            $this->error('The SQLite source has no application tables.');

            return self::FAILURE;
        }

        $missing = $tables->reject(fn (string $table): bool => Schema::hasTable($table));
        if ($missing->isNotEmpty()) {
            $this->error('Run all MySQL migrations first. Missing tables: '.$missing->join(', '));

            return self::FAILURE;
        }

        $this->warn('Replacing MySQL data from '.$sourcePath);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($tables as $table) {
                    DB::table($table)->truncate();
                }

                DB::transaction(function () use ($source, $tables): void {
                    foreach ($tables as $table) {
                        $this->copyTable($source, $table);
                    }
                });

                foreach ($tables as $table) {
                    $this->resetAutoIncrement($table);
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            foreach ($tables as $table) {
                $sourceCount = $source->table($table)->count();
                $targetCount = DB::table($table)->count();
                if ($sourceCount !== $targetCount) {
                    throw new RuntimeException("Row count mismatch for {$table}: SQLite {$sourceCount}, MySQL {$targetCount}");
                }
            }

            $this->validateTargetForeignKeys();
        } catch (Throwable $exception) {
            $this->error('Import failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge('legacy_sqlite');
        }

        $this->info('Imported and validated '.$tables->count().' application tables.');

        return self::SUCCESS;
    }

    private function copyTable(ConnectionInterface $source, string $table): void
    {
        $copied = 0;

        $source->table($table)->orderBy($this->orderColumn($source, $table))->chunk(500, function ($rows) use ($table, &$copied): void {
            $records = $rows->map(fn (object $row): array => (array) $row)->all();
            if ($records !== []) {
                DB::table($table)->insert($records);
                $copied += count($records);
            }
        });

        $this->line("{$table}: {$copied}");
    }

    private function orderColumn(ConnectionInterface $source, string $table): string
    {
        $columns = collect($source->select('PRAGMA table_info("'.str_replace('"', '""', $table).'")'));

        return $columns->firstWhere('pk', 1)?->name ?? $columns->first()?->name
            ?? throw new RuntimeException('Unable to determine an import order for '.$table);
    }

    private function resetAutoIncrement(string $table): void
    {
        if (! Schema::hasColumn($table, 'id')) {
            return;
        }

        $nextId = ((int) DB::table($table)->max('id')) + 1;
        $quotedTable = '`'.str_replace('`', '``', $table).'`';
        DB::statement("ALTER TABLE {$quotedTable} AUTO_INCREMENT = {$nextId}");
    }

    private function validateTargetForeignKeys(): void
    {
        $keys = DB::select(<<<'SQL'
            SELECT TABLE_NAME AS child_table,
                   COLUMN_NAME AS child_column,
                   REFERENCED_TABLE_NAME AS parent_table,
                   REFERENCED_COLUMN_NAME AS parent_column,
                   CONSTRAINT_NAME AS constraint_name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL);

        foreach ($keys as $key) {
            $childTable = $this->quoteIdentifier($key->child_table);
            $childColumn = $this->quoteIdentifier($key->child_column);
            $parentTable = $this->quoteIdentifier($key->parent_table);
            $parentColumn = $this->quoteIdentifier($key->parent_column);
            $orphans = DB::selectOne(
                "SELECT COUNT(*) AS aggregate FROM {$childTable} child LEFT JOIN {$parentTable} parent ON child.{$childColumn} = parent.{$parentColumn} WHERE child.{$childColumn} IS NOT NULL AND parent.{$parentColumn} IS NULL"
            )->aggregate;

            if ((int) $orphans > 0) {
                throw new RuntimeException("Foreign-key validation failed for {$key->constraint_name}: {$orphans} orphan rows");
            }
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function sourcePath(string $source): string
    {
        if (str_starts_with($source, '/')) {
            return $source;
        }

        return base_path($source);
    }
}
