<?php
/**
 * Database Connection File
 * Creates and manages database connection using MySQLi
 */

require_once 'db_config.php';

class Database {
    private $conn;

    public function __construct() {
        $this->connect();
    }

    /**
     * Establish database connection
     */
    public function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        // Check connection
        if ($this->conn->connect_error) {
            die('Connection failed: ' . $this->conn->connect_error);
        }

        // Set charset to UTF-8
        $this->conn->set_charset('utf8mb4');
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Escape string to prevent SQL injection
     */
    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Close database connection
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Create database instance
$database = new Database();
$conn = $database->getConnection();
?>
