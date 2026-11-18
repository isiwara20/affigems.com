<?php
declare(strict_types=1);

/**
 * Repair tables where `id` was created without AUTO_INCREMENT (rows end up as id=0).
 */
function db_repair_auto_increment_table(PDO $conn, string $table, string $orderColumn = 'created_at'): void
{
    static $repaired = [];

    $table = preg_replace('/[^a-z_]/', '', strtolower($table));
    if ($table === '' || isset($repaired[$table])) {
        return;
    }

    $repaired[$table] = true;

    $allowedOrder = ['created_at', 'id', 'name'];
    $orderColumn = in_array($orderColumn, $allowedOrder, true) ? $orderColumn : 'created_at';

    $needsRowFix = (int) $conn->query(
        "SELECT COUNT(*) FROM `{$table}` WHERE id IS NULL OR id <= 0"
    )->fetchColumn() > 0;

    $extra = $conn->prepare(
        'SELECT EXTRA FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = \'id\''
    );
    $extra->execute([':table' => $table]);
    $extraValue = (string) ($extra->fetchColumn() ?: '');
    $needsAutoIncrement = stripos($extraValue, 'auto_increment') === false;

    if (!$needsRowFix && !$needsAutoIncrement) {
        return;
    }

    if ($needsRowFix) {
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');

        $rows = $conn->query(
            "SELECT id, name FROM `{$table}` WHERE id IS NULL OR id <= 0 ORDER BY `{$orderColumn}` ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $tempId = -1;
        foreach ($rows as $row) {
            $stmt = $conn->prepare(
                "UPDATE `{$table}` SET id = :temp_id WHERE (id IS NULL OR id <= 0) AND name = :name LIMIT 1"
            );
            $stmt->execute([
                ':temp_id' => $tempId,
                ':name' => $row['name'],
            ]);
            $tempId--;
        }

        $nextId = (int) $conn->query(
            "SELECT COALESCE(MAX(id), 0) FROM `{$table}` WHERE id > 0"
        )->fetchColumn();

        $tempRows = $conn->query(
            "SELECT id FROM `{$table}` WHERE id < 0 ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tempRows as $tempRow) {
            $nextId++;
            $stmt = $conn->prepare("UPDATE `{$table}` SET id = :new_id WHERE id = :old_id");
            $stmt->execute([
                ':new_id' => $nextId,
                ':old_id' => (int) $tempRow['id'],
            ]);
        }

        $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    if ($needsAutoIncrement || $needsRowFix) {
        try {
            $conn->exec("ALTER TABLE `{$table}` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        } catch (Throwable $e) {
            try {
                $conn->exec("ALTER TABLE `{$table}` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
            } catch (Throwable $e2) {
                // Table may already be correct or use a different key layout.
            }
        }

        $nextAi = (int) $conn->query(
            "SELECT COALESCE(MAX(id), 0) + 1 FROM `{$table}`"
        )->fetchColumn();

        if ($nextAi > 0) {
            $conn->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextAi}");
        }
    }
}
