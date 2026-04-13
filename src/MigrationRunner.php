<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private PDO $pdo, private string $migrationsDir)
    {
    }

    public function applyAll(): array
    {
        $this->ensureMigrationsTable();
        $applied = $this->appliedMigrationsMap();
        $files = $this->migrationFiles();
        $ran = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }

            $sql = file_get_contents($file);
            if (!is_string($sql) || trim($sql) === '') {
                throw new RuntimeException("Migration file is empty or unreadable: {$name}");
            }

            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);
                $insert = $this->pdo->prepare(
                    'INSERT INTO schema_migrations (migration_name, applied_at) VALUES (:migration_name, :applied_at)'
                );
                $insert->execute([
                    'migration_name' => $name,
                    'applied_at' => gmdate('Y-m-d H:i:s'),
                ]);
                $this->pdo->commit();
                $ran[] = $name;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }

        return $ran;
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(191) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL,
                INDEX idx_schema_migrations_applied_at (applied_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** @return array<string,bool> */
    private function appliedMigrationsMap(): array
    {
        $rows = $this->pdo->query('SELECT migration_name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $map = [];
        foreach ($rows as $name) {
            $map[(string) $name] = true;
        }
        return $map;
    }

    /** @return string[] */
    private function migrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }
        $files = glob(rtrim($this->migrationsDir, '/') . '/*.sql');
        if (!is_array($files)) {
            return [];
        }
        sort($files, SORT_STRING);
        return $files;
    }
}
