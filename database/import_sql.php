<?php
/**
 * 1-Click Database Importer for Dokploy
 * Reads database/*.sql and executes statements cleanly against the live MySQL server
 */
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

echo "=== Civentral Live Database Importer ===\n";

try {
    $pdo = getDbConnection();
    echo "[OK] Connected to MySQL successfully.\n";
} catch (Exception $e) {
    die("[ERROR] Could not connect: " . $e->getMessage() . "\n");
}

/**
 * Executes an SQL file by stripping BOM, removing comments, and running statement-by-statement.
 */
function runSqlScript(PDO $pdo, string $filePath): void {
    $fileName = basename($filePath);
    if (!file_exists($filePath)) {
        echo "[SKIP] {$fileName} does not exist.\n";
        return;
    }

    $raw = file_get_contents($filePath);
    if ($raw === false || empty(trim($raw))) {
        echo "[WARN] {$fileName} is empty.\n";
        return;
    }

    // 1. Strip UTF-8 Byte Order Mark (BOM: \xEF\xBB\xBF) if present
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

    // 2. Remove line comments (-- and #) and block comments
    $lines = explode("\n", $raw);
    $cleanSql = '';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $cleanSql .= $line . "\n";
    }

    // 3. Split by delimiter ';' respecting quoted strings
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($cleanSql);

    for ($i = 0; $i < $len; $i++) {
        $char = $cleanSql[$i];

        if ($inString) {
            if ($char === $stringChar) {
                $escaped = false;
                $j = $i - 1;
                while ($j >= 0 && $cleanSql[$j] === '\\') {
                    $escaped = !$escaped;
                    $j--;
                }
                if (!$escaped) {
                    $inString = false;
                }
            }
            $current .= $char;
        } else {
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($char === ';') {
                $stmt = trim($current);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }
    }

    $trailing = trim($current);
    if (!empty($trailing)) {
        $statements[] = $trailing;
    }

    echo "[INFO] Running {$fileName} (" . count($statements) . " statements)...\n";
    $success = 0;
    foreach ($statements as $index => $stmt) {
        try {
            $pdo->exec($stmt);
            $success++;
        } catch (PDOException $e) {
            echo "   [!] Statement " . ($index + 1) . " warning/error: " . $e->getMessage() . "\n";
        }
    }
    echo "[OK] {$fileName}: {$success}/" . count($statements) . " statements executed.\n";
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Run core schema
    runSqlScript($pdo, __DIR__ . '/citizen_verification.sql');

    // Run duplicate flags & resolution schema
    runSqlScript($pdo, __DIR__ . '/duplicate_flags.sql');

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Auto-migrate newly added columns if they don't exist yet in citizen_verifications
    $verifColumns = [
        'citizen_id_number' => "VARCHAR(30) NULL",
        'photo_1x1_url' => "VARCHAR(500) NULL",
        'signature_photo_url' => "VARCHAR(500) NULL",
        'qr_code_token' => "VARCHAR(255) NULL",
        'qr_code_image_url' => "VARCHAR(500) NULL",
        'admin_action_notes' => "TEXT NULL"
    ];
    foreach ($verifColumns as $col => $colDef) {
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM `citizen_verifications` LIKE '$col'")->fetch();
            if (!$checkCol) {
                $pdo->exec("ALTER TABLE `citizen_verifications` ADD COLUMN `$col` $colDef");
                echo "[MIGRATE] Added column `{$col}` to `citizen_verifications`\n";
            }
        } catch (Exception $e) {}
    }

    // Sync past UTC timestamps to Philippine Standard Time (+8 hours)
    try {
        $updated = $pdo->exec("
            UPDATE `citizen_verifications` 
            SET `submitted_at` = DATE_ADD(`submitted_at`, INTERVAL 8 HOUR) 
            WHERE `submitted_at` < '2026-10-10 00:00:00' AND `submitted_at` >= '2026-10-01 00:00:00'
        ");
        if ($updated > 0) {
            echo "[TIMEZONE] Synced {$updated} citizen application timestamps to Philippine Standard Time (UTC+8).\n";
        }
    } catch (Exception $e) {
        echo "[TIMEZONE NOTICE] " . $e->getMessage() . "\n";
    }

    // Verify created tables
    $pdo->exec("USE `citizen_verification`;");
    $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n[OK] Current tables in `citizen_verification`:\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "   - {$t} ({$count} records)\n";
    }

    echo "\n*** SUCCESS: Database is fully initialized and ready! ***\n";
} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
}

