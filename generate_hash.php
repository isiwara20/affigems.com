<?php
// Generate password hash and update admin
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Update the admin password with correct hash
$stmt = $conn->prepare("UPDATE admins SET password = :password WHERE email = :email");
$result = $stmt->execute([':password' => $hash, ':email' => 'info@arigato.lk']);

if ($result && $stmt->rowCount() > 0) {
    echo "<h2 style='color:green;'>Admin password updated successfully!</h2>";
    echo "<p><strong>Email:</strong> info@arigato.lk</p>";
    echo "<p><strong>Password:</strong> Admin@123</p>";
    echo "<p><strong>Hash:</strong> " . $hash . "</p>";
    echo "<br><p style='color:red;'><strong>DELETE this file now!</strong></p>";
    echo "<p><a href='/Arigato_Ecommerce/login.php'>Go to Login</a></p>";
} else {
    // Maybe admin doesn't exist yet, insert it
    $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->execute([
        ':name' => 'ARIGATO.LK Admin',
        ':email' => 'info@arigato.lk',
        ':password' => $hash,
        ':role' => 'super_admin'
    ]);
    echo "<h2 style='color:green;'>Admin account created!</h2>";
    echo "<p><strong>Email:</strong> info@arigato.lk</p>";
    echo "<p><strong>Password:</strong> Admin@123</p>";
    echo "<br><p style='color:red;'><strong>DELETE this file now!</strong></p>";
    echo "<p><a href='/Arigato_Ecommerce/login.php'>Go to Login</a></p>";
}
?>
