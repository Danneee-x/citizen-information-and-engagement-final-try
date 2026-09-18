<?php
// Function to load .env file into getenv/$_ENV
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/../.env');

/**
 * Global getDbConnection() function compatible with Dokploy MySQL internal mesh & instant local XAMPP
 */
function getDbConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $db = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'citizen_verification');
    $isLocal = (PHP_OS_FAMILY === 'Windows') || (!file_exists('/.dockerenv') && empty(getenv('DOKPLOY')) && empty(getenv('DOCKER')));

    if ($isLocal) {
        // Fast local candidate list (Zero delay on Windows / XAMPP)
        $candidates = [
            [
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: 3306,
                'user' => getenv('DB_USER') ?: 'root',
                'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
            ],
            [
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
            ],
            [
                'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
            ],
        ];
    } else {
        // Dokploy Cloud environment (Linux container mesh)
        $candidates = [
            [
                'host' => getenv('DB_HOST') ?: getenv('MYSQL_HOST'),
                'port' => getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: 3306,
                'user' => getenv('DB_USER') ?: getenv('MYSQL_USER'),
                'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : getenv('MYSQL_ROOT_PASSWORD')),
            ],
            [
                'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
            ],
            [
                'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
                'port' => 3306,
                'user' => 'mysql',
                'pass' => 'm68xnwxsqv3urvon',
            ],
            [
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'pass' => '',
            ],
        ];
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    $lastError = '';

    foreach ($candidates as $cand) {
        if (empty($cand['host']) || empty($cand['user'])) {
            continue;
        }

        $dsn = "mysql:host={$cand['host']};port={$cand['port']};dbname={$db};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $cand['user'], $cand['pass'], $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            // Only attempt database creation if the host is reachable but the database doesn't exist (1049)
            if (strpos($lastError, 'Unknown database') !== false || $e->getCode() == 1049) {
                try {
                    $noDbDsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
                    $tmpPdo = new PDO($noDbDsn, $cand['user'], $cand['pass'], $options);
                    $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo = new PDO($dsn, $cand['user'], $cand['pass'], $options);
                    return $pdo;
                } catch (PDOException $e2) {
                    $lastError = $e2->getMessage();
                }
            }
        }
    }

    throw new PDOException("Database Connection Error: " . $lastError);
}

/**
 * Dedicated database connection for the Certificate & Document Issuance module (civentral_certificates)
 */
function getCertificateDbConnection(): PDO {
    static $certPdo = null;
    if ($certPdo !== null) {
        return $certPdo;
    }

    $db = getenv('CERT_DB_NAME') ?: 'civentral_certificates';
    $isLocal = (PHP_OS_FAMILY === 'Windows') || (!file_exists('/.dockerenv') && empty(getenv('DOKPLOY')) && empty(getenv('DOCKER')));

    if ($isLocal) {
        $candidates = [
            ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
            ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'civentral_user', 'pass' => 'Civentral2026!'],
        ];
    } else {
        $candidates = [
            ['host' => getenv('DB_HOST') ?: getenv('MYSQL_HOST'), 'port' => getenv('DB_PORT') ?: 3306, 'user' => getenv('DB_USER') ?: getenv('MYSQL_USER'), 'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') ?: '')],
            ['host' => 'citizeninformationandengagement-citizenregistry-ffbtjn', 'port' => 3306, 'user' => 'civentral_user', 'pass' => 'Civentral2026!'],
            ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => '']
        ];
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 2,
    ];

    $lastError = '';
    foreach ($candidates as $cand) {
        if (empty($cand['host'])) continue;
        try {
            $dsn = "mysql:host={$cand['host']};port={$cand['port']};dbname={$db};charset=utf8mb4";
            $certPdo = new PDO($dsn, $cand['user'], $cand['pass'], $options);
            return $certPdo;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            if (strpos($lastError, 'Unknown database') !== false || $e->getCode() == 1049) {
                try {
                    $noDbDsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
                    $tmpPdo = new PDO($noDbDsn, $cand['user'], $cand['pass'], $options);
                    $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $certPdo = new PDO($dsn, $cand['user'], $cand['pass'], $options);
                    return $certPdo;
                } catch (PDOException $e2) {
                    $lastError = $e2->getMessage();
                }
            }
        }
    }

    // Fallback: use default getDbConnection()
    try {
        $fbPdo = getDbConnection();
        $fbPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; USE `{$db}`;");
        $certPdo = $fbPdo;
        return $certPdo;
    } catch (Exception $e) {
        return getDbConnection();
    }
}

class Database {
    private static $instance = null;
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function query($sql, $params = [], $ignoredMethodParam = null) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function exec($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function select($table, $filters = [], $columns = '*', $orderBy = '') {
        $sql = "SELECT {$columns} FROM `{$table}`";
        $where = [];
        $params = [];

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $where[] = "`{$key}` = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->query($sql, $params);
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $lastId = $this->pdo->lastInsertId();
        return $lastId ? (int)$lastId : true;
    }

    public function update($table, $data, $filters) {
        $set = [];
        $params = [];

        foreach ($data as $key => $value) {
            $set[] = "`{$key}` = :set_{$key}";
            $params["set_{$key}"] = $value;
        }

        $where = [];
        foreach ($filters as $key => $value) {
            $where[] = "`{$key}` = :where_{$key}";
            $params["where_{$key}"] = $value;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete($table, $filters) {
        $where = [];
        $params = [];

        foreach ($filters as $key => $value) {
            $where[] = "`{$key}` = :{$key}";
            $params[$key] = $value;
        }

        $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}

class DatabaseDB extends Database {}

$db = Database::getInstance();
?>
