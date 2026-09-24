<?php
// install.php
// ឯកសារដំឡើង Database Tables ដោយស្វ័យប្រវត្តិ
require_once __DIR__ . '/config/database.php';

try {
    $sql = file_get_contents(__DIR__ . '/install.sql');
    if (!$sql) {
        die("រកមិនឃើញឯកសារ install.sql ឡើយ!");
    }
    
    // ១. បង្កើត Tables ទាំងអស់
    $pdo->exec($sql);
    
    // ២. បង្កើតគណនី Admin ដំបូង
    $username = 'admin';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->execute([$username, $hash, 'Super Admin', 'admin']);
    }

    echo "<div style='font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; text-align: center;'>";
    echo "<h2 style='color: #16a34a; margin-top: 0;'>🎉 ដំឡើង Database ជោគជ័យ!</h2>";
    echo "<p style='color: #475569;'>តារាង (Tables) ទាំងអស់ក្នុង PostgreSQL ត្រូវបានបង្កើតរួចរាល់។</p>";
    echo "<div style='background: #f8fafc; padding: 15px; border-radius: 8px; text-align: left; margin: 20px 0;'>";
    echo "<p style='margin: 5px 0;'><strong>Username:</strong> <code>admin</code></p>";
    echo "<p style='margin: 5px 0;'><strong>Password:</strong> <code>admin123</code></p>";
    echo "</div>";
    echo "<a href='/login.php' style='display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;'>ចូលទៅកាន់ទំព័រ Login</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; color: red;'>";
    echo "<h2>កំហុសក្នុងការដំឡើង:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
