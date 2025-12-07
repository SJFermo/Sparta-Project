<?php
// config.php - Database Configuration
// This file handles the connection to MySQL database

// Database credentials
$host = 'localhost';        // MySQL server (usually localhost)
$dbname = 'websys_db';         // Database name from your SQL script
$username = 'root';         // Default MySQL username in XAMPP/WAMP
$password = '';             // Default password (empty for XAMPP/WAMP)

// Set character set to UTF-8
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options for better error handling and security
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
];


try {
    // Create PDO instance (database connection)
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Optional: Uncomment below line to test connection
    // echo "Database connected successfully!";
    
} catch (PDOException $e) {
    // If connection fails, show error
    die("Database connection failed: " . $e->getMessage());
}
?>