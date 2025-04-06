<?php
namespace BaseballAnalytics\Database;

/**
 * Database Connection Manager
 * 
 * Handles database connections with connection pooling and error handling
 */
class Connection {
    private static $instance = null;
    private $connections = [];
    private $config;
    private $inTransaction = false;

    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $this->config = require_once __DIR__ . '/../../config/database.php';
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a connection from the pool
     */
    public function getConnection() {
        // Return existing connection if in transaction
        if ($this->inTransaction && !empty($this->connections)) {
            return reset($this->connections);
        }

        // Check for available connection in the pool
        foreach ($this->connections as $conn) {
            if (!$conn['in_use']) {
                $conn['in_use'] = true;
                return $conn['connection'];
            }
        }

        // Create new connection if under max limit
        if (count($this->connections) < $this->config['pool']['max_connections']) {
            return $this->createConnection();
        }

        // Wait for available connection
        $startTime = microtime(true);
        while (microtime(true) - $startTime < $this->config['pool']['wait_timeout']) {
            foreach ($this->connections as &$conn) {
                if (!$conn['in_use']) {
                    $conn['in_use'] = true;
                    return $conn['connection'];
                }
            }
            usleep(100000); // 100ms
        }

        throw new \RuntimeException('Could not get database connection: pool exhausted');
    }

    /**
     * Create a new database connection
     */
    private function createConnection() {
        $config = $this->config['default'];
        
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database']
            );

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );

            $connection = [
                'connection' => $pdo,
                'in_use' => true,
                'last_used' => time(),
            ];

            $this->connections[] = $connection;
            return $pdo;

        } catch (\PDOException $e) {
            // Log the error
            error_log("Database connection failed: " . $e->getMessage());
            
            // Throw a generic error for security
            throw new \RuntimeException('Could not connect to database');
        }
    }

    /**
     * Release a connection back to the pool
     */
    public function releaseConnection($connection) {
        foreach ($this->connections as &$conn) {
            if ($conn['connection'] === $connection) {
                $conn['in_use'] = false;
                $conn['last_used'] = time();
                break;
            }
        }
    }

    /**
     * Start a transaction
     */
    public function beginTransaction() {
        if ($this->inTransaction) {
            throw new \RuntimeException('Transaction already in progress');
        }
        
        $connection = $this->getConnection();
        $connection->beginTransaction();
        $this->inTransaction = true;
        
        return $connection;
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        if (!$this->inTransaction) {
            throw new \RuntimeException('No transaction in progress');
        }
        
        $connection = reset($this->connections)['connection'];
        $connection->commit();
        $this->inTransaction = false;
        $this->releaseConnection($connection);
    }

    /**
     * Rollback a transaction
     */
    public function rollback() {
        if (!$this->inTransaction) {
            throw new \RuntimeException('No transaction in progress');
        }
        
        $connection = reset($this->connections)['connection'];
        $connection->rollBack();
        $this->inTransaction = false;
        $this->releaseConnection($connection);
    }

    /**
     * Clean up idle connections
     */
    public function cleanup() {
        $now = time();
        foreach ($this->connections as $key => $conn) {
            if (!$conn['in_use'] && 
                ($now - $conn['last_used']) > $this->config['pool']['idle_timeout']) {
                unset($this->connections[$key]);
            }
        }
    }
} 