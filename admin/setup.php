<?php
/**
 * Admin Setup Script
 * Run this ONCE in browser to create the default admin account
 * URL: http://localhost/admin/setup.php
 * 
 * DELETE THIS FILE after running it!
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed. Make sure MySQL is running.");
}

// Create admins table if not exists
$conn->exec("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Check if admin already exists
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = :email");
$stmt->execute([':email' => 'info@arigato.lk']);
$existing = $stmt->fetch();

if ($existing) {
    echo "<h2>Admin account already exists!</h2>";
    echo "<p><strong>Email:</strong> info@arigato.lk</p>";
    echo "<p><strong>Password:</strong> Admin@123</p>";
    echo "<p><a href='/login.php'>Go to Login</a></p>";
} else {
    // Create admin with password: Admin@123
    $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $result = $stmt->execute([
        ':name' => 'ARIGATO.LK Admin',
        ':email' => 'info@arigato.lk',
        ':password' => $hashedPassword,
        ':role' => 'super_admin'
    ]);

    if ($result) {
        echo "<h2 style='color: green;'>Admin account created successfully!</h2>";
        echo "<p><strong>Email:</strong> info@arigato.lk</p>";
        echo "<p><strong>Password:</strong> Admin@123</p>";
        echo "<p style='color: red; margin-top: 20px;'><strong>IMPORTANT:</strong> Delete this file (admin/setup.php) after use!</p>";
        echo "<p><a href='/login.php'>Go to Login</a></p>";
    } else {
        echo "<h2 style='color: red;'>Failed to create admin account.</h2>";
    }
}
