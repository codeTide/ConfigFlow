<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use PDO;

final class DatabaseBackupService
{
    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
    ) {
    }

    public function sendBackup(?int $targetChatId = null): bool
    {
        $targetRaw = trim($this->settings->get('backup_target_id', ''));
        $chatId = $targetChatId ?? (preg_match('/^-?\d+$/', $targetRaw) ? (int) $targetRaw : 0);
        if ($chatId === 0) {
            return false;
        }

        $file = $this->createSqlDumpFile();
        if ($file === null) {
            return false;
        }

        $caption = '🗄 بکاپ دیتابیس\n' . gmdate('Y-m-d H:i:s') . ' UTC';
        $this->telegram->sendDocumentFile($chatId, $file, $caption);

        $groupIdRaw = trim($this->settings->get('group_id', ''));
        $topicRaw = trim($this->settings->get('group_topic_backup', ''));
        if (preg_match('/^-?\d+$/', $groupIdRaw) && preg_match('/^\d+$/', $topicRaw)) {
            $this->telegram->sendDocumentFile((int) $groupIdRaw, $file, $caption, (int) $topicRaw);
        }

        @unlink($file);
        return true;
    }

    private function createSqlDumpFile(): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg_db_backup_');
        if (!is_string($tmp) || $tmp === '') {
            return null;
        }
        $file = $tmp . '.sql';
        @rename($tmp, $file);

        $pdo = $this->database->pdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
        if (!is_array($tables)) {
            @unlink($file);
            return null;
        }

        $chunks = [];
        $chunks[] = '-- ConfigFlow DB backup';
        $chunks[] = '-- Generated at: ' . gmdate('c');
        $chunks[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($tables as $row) {
            $table = (string) ($row[0] ?? '');
            if ($table === '') {
                continue;
            }

            $createRow = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = (string) ($createRow['Create Table'] ?? '');
            if ($createSql !== '') {
                $chunks[] = "\n-- Table: {$table}";
                $chunks[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
                $chunks[] = $createSql . ';';
            }

            $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $data) {
                $cols = [];
                $vals = [];
                foreach ($data as $k => $v) {
                    $cols[] = '`' . str_replace('`', '``', (string) $k) . '`';
                    $vals[] = $v === null ? 'NULL' : $pdo->quote((string) $v);
                }
                $chunks[] = 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ');';
            }
        }

        $chunks[] = 'SET FOREIGN_KEY_CHECKS=1;';
        file_put_contents($file, implode("\n", $chunks));

        return $file;
    }
}
