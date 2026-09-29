<?php
/**
 * 1-Click Database Importer for Dokploy
 * Reads database/citizen_verification.sql and executes it against the live MySQL server
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

$sqlFile = __DIR__ . '/citizen_verification.sql';
if (!file_exists($sqlFile)) {
    die("[ERROR] citizen_verification.sql not found at {$sqlFile}\n");
}

$sql = file_get_contents($sqlFile);
if (empty($sql)) {
    die("[ERROR] citizen_verification.sql is empty\n");
}

echo "[INFO] Executing citizen_verification.sql (" . strlen($sql) . " bytes)...\n";

try {
    // Disable foreign key checks for clean drop/create
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Execute SQL script
    $pdo->exec($sql);
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "[OK] SQL script executed successfully!\n";
    
    // Verify created tables
    $pdo->exec("USE `citizen_verification`;");
    $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
    echo "[OK] Tables in `citizen_verification`:\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "   - {$t} ({$count} records)\n";
    }
    
    echo "\n*** SUCCESS: Database is now fully live and ready! ***\n";
} catch (Exception $e) {
    echo "[ERROR] SQL Execution failed: " . $e->getMessage() . "\n";
}
