<?php
/**
 * 1-Click ID Issuance Database Importer
 * Executes database/id_issuance.sql against the live MySQL server
 */
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

echo "=== Civentral ID Issuance Database Importer ===\n";

try {
    $pdo = getCertificateDbConnection();
    echo "[OK] Connected to MySQL successfully.\n";
} catch (Exception $e) {
    die("[ERROR] Could not connect: " . $e->getMessage() . "\n");
}

$sqlFile = __DIR__ . '/id_issuance.sql';
if (!file_exists($sqlFile)) {
    die("[ERROR] id_issuance.sql not found at {$sqlFile}\n");
}

$sql = file_get_contents($sqlFile);
if (empty($sql)) {
    die("[ERROR] id_issuance.sql is empty\n");
}

echo "[INFO] Executing id_issuance.sql (" . strlen($sql) . " bytes)...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "[OK] SQL script executed successfully!\n\n";

    // Verify tables
    $pdo->exec("USE `civentral_certificates`;");
    $tables = ['id_issuance_applications', 'id_cards_issued', 'id_issuance_audit_logs'];
    echo "[OK] Tables verified in `civentral_certificates`:\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "   - {$t}: {$count} records\n";
    }

    echo "\n*** SUCCESS: ID Issuance database is fully live and connected to Admin & Citizen App! ***\n";
} catch (Exception $e) {
    echo "[ERROR] SQL Execution failed: " . $e->getMessage() . "\n";
}
