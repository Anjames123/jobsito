<?php
// Test script to create/verify admin user
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "Database connection successful!\n";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Admin user found:\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Is Admin: " . $admin['is_admin'] . "\n";
        echo "Password Hash: " . substr($admin['password'], 0, 20) . "...\n";
        
        // Test password verification
        if (password_verify('admin123', $admin['password'])) {
            echo "Password verification: SUCCESS\n";
        } else {
            echo "Password verification: FAILED\n";
            
            // Create new password hash
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $update_stmt->execute([$new_hash]);
            echo "Password updated with new hash\n";
        }
    } else {
        echo "Admin user not found. Creating admin user...\n";
        
        // Create admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $insert_stmt->execute(['admin', 'admin@jobportal.com', $password_hash, 'Admin', 'User', 1]);
        
        if ($result) {
            echo "Admin user created successfully!\n";
        } else {
            echo "Failed to create admin user\n";
        }
    }
    
    // List all users
    echo "\nAll users in database:\n";
    $all_users = $pdo->query("SELECT id, username, email, first_name, last_name, is_admin FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Admin: {$user['is_admin']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>