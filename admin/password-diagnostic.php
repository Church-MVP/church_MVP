<?php
/**
 * Password Diagnostic Tool
 * 
 * USE THIS TO DIAGNOSE PASSWORD ISSUES
 * DELETE THIS FILE AFTER USE - SECURITY RISK!
 */

// Start session
session_start();

// Include database connection
require_once '../includes/db.php';

$result = '';
$test_result = '';

// Handle password test
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Test 1: Verify existing password in database
    if (isset($_POST['test_login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $result .= "<div style='background:#e3f2fd;padding:15px;border-radius:8px;margin-bottom:15px;'>";
            $result .= "<h4>Database Info for '{$username}':</h4>";
            $result .= "<p><strong>User ID:</strong> {$admin['id']}</p>";
            $result .= "<p><strong>Stored Hash:</strong><br><code style='word-break:break-all;font-size:12px;'>{$admin['password']}</code></p>";
            $result .= "<p><strong>Hash Length:</strong> " . strlen($admin['password']) . " characters</p>";
            $result .= "<p><strong>Hash Format Valid:</strong> " . (substr($admin['password'], 0, 4) === '$2y$' ? '✅ Yes (bcrypt)' : '❌ No') . "</p>";
            $result .= "</div>";
            
            // Test password verification
            $result .= "<div style='background:" . (password_verify($password, $admin['password']) ? '#d4edda' : '#f8d7da') . ";padding:15px;border-radius:8px;margin-bottom:15px;'>";
            $result .= "<h4>Password Verification Test:</h4>";
            $result .= "<p><strong>Password Entered:</strong> " . htmlspecialchars($password) . "</p>";
            $result .= "<p><strong>Password Length:</strong> " . strlen($password) . " characters</p>";
            
            if (password_verify($password, $admin['password'])) {
                $result .= "<p style='color:#155724;font-size:18px;'><strong>✅ PASSWORD MATCHES!</strong></p>";
                $result .= "<p>The password you entered is correct. Login should work.</p>";
            } else {
                $result .= "<p style='color:#721c24;font-size:18px;'><strong>❌ PASSWORD DOES NOT MATCH!</strong></p>";
                $result .= "<p>The password you entered does not match the stored hash.</p>";
            }
            $result .= "</div>";
        } else {
            $result .= "<div style='background:#f8d7da;padding:15px;border-radius:8px;'>";
            $result .= "<p><strong>❌ User '{$username}' not found in database.</strong></p>";
            $result .= "</div>";
        }
    }
    
    // Test 2: Generate a new hash for a password
    if (isset($_POST['generate_hash'])) {
        $new_password = $_POST['new_password'];
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $test_result .= "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin-bottom:15px;'>";
        $test_result .= "<h4>Generated Hash for '{$new_password}':</h4>";
        $test_result .= "<p><strong>New Hash:</strong><br><code style='word-break:break-all;font-size:12px;'>{$new_hash}</code></p>";
        $test_result .= "<p><strong>Hash Length:</strong> " . strlen($new_hash) . " characters</p>";
        $test_result .= "<p><strong>Verification Test:</strong> " . (password_verify($new_password, $new_hash) ? '✅ Working' : '❌ Failed') . "</p>";
        $test_result .= "</div>";
    }
    
    // Test 3: Reset password directly
    if (isset($_POST['reset_password'])) {
        $username = $_POST['reset_username'];
        $new_password = $_POST['reset_new_password'];
        
        // Generate hash
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update database
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$new_hash, $username]);
        
        if ($stmt->rowCount() > 0) {
            $test_result .= "<div style='background:#d4edda;padding:15px;border-radius:8px;margin-bottom:15px;'>";
            $test_result .= "<h4>✅ Password Reset Successful!</h4>";
            $test_result .= "<p><strong>Username:</strong> {$username}</p>";
            $test_result .= "<p><strong>New Password:</strong> {$new_password}</p>";
            $test_result .= "<p><strong>New Hash:</strong><br><code style='word-break:break-all;font-size:12px;'>{$new_hash}</code></p>";
            $test_result .= "<p style='color:#155724;'><strong>You can now login with the new password!</strong></p>";
            $test_result .= "</div>";
        } else {
            $test_result .= "<div style='background:#f8d7da;padding:15px;border-radius:8px;'>";
            $test_result .= "<p><strong>❌ Failed to update password. User '{$username}' may not exist.</strong></p>";
            $test_result .= "</div>";
        }
    }
}

// Get all users for reference
$users = $pdo->query("SELECT id, username, email, LEFT(password, 30) as password_preview, LENGTH(password) as pwd_length FROM admins")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Diagnostic Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            padding: 2rem;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .card { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header { 
            background: #1a2b4a; 
            color: white; 
            padding: 1rem 1.5rem;
        }
        .card-header h2 { font-size: 1.25rem; }
        .card-body { padding: 1.5rem; }
        .warning-banner {
            background: #dc3545;
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: bold;
        }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e5eb;
            border-radius: 6px;
            font-size: 1rem;
        }
        input:focus { outline: none; border-color: #667eea; }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning-banner">
            ⚠️ SECURITY WARNING: DELETE THIS FILE AFTER USE! (password-diagnostic.php)
        </div>
        
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Current Admin Users</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Password Hash (preview)</th>
                            <th>Hash Length</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><code><?php echo $user['password_preview']; ?>...</code></td>
                            <td><?php echo $user['pwd_length']; ?> <?php echo $user['pwd_length'] == 60 ? '✅' : '❌'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> Valid bcrypt hashes are exactly 60 characters and start with <code>$2y$</code>
                </p>
            </div>
        </div>
        
        <?php if ($result): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-clipboard-check"></i> Test Results</h2>
            </div>
            <div class="card-body">
                <?php echo $result; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($test_result): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-tools"></i> Action Results</h2>
            </div>
            <div class="card-body">
                <?php echo $test_result; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Test 1: Verify Password -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-key"></i> Test 1: Verify Password</h2>
                </div>
                <div class="card-body">
                    <p style="margin-bottom: 1rem; color: #666;">Test if a password matches the stored hash</p>
                    <form method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="e.g., admin" required>
                        </div>
                        <div class="form-group">
                            <label>Password to Test</label>
                            <input type="text" name="password" placeholder="Enter password to test" required>
                        </div>
                        <button type="submit" name="test_login" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Test Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Test 2: Generate Hash -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-hashtag"></i> Test 2: Generate Hash</h2>
                </div>
                <div class="card-body">
                    <p style="margin-bottom: 1rem; color: #666;">Generate a bcrypt hash for a password</p>
                    <form method="POST">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" name="new_password" placeholder="Enter password to hash" required>
                        </div>
                        <button type="submit" name="generate_hash" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Generate Hash
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Test 3: Direct Password Reset -->
        <div class="card">
            <div class="card-header" style="background: #dc3545;">
                <h2><i class="fas fa-exclamation-triangle"></i> Test 3: Direct Password Reset (Emergency Fix)</h2>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 1rem; color: #666;">
                    <strong>Use this to directly reset a user's password in the database.</strong>
                    This bypasses the Edit User form completely.
                </p>
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="reset_username" placeholder="e.g., johndoe" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="text" name="reset_new_password" placeholder="e.g., NewPassword123" required>
                        </div>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-danger" onclick="return confirm('Are you sure you want to reset this password?')">
                        <i class="fas fa-sync-alt"></i> Reset Password Directly
                    </button>
                </form>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="login.php" class="btn btn-success">
                <i class="fas fa-sign-in-alt"></i> Go to Login Page
            </a>
        </div>
    </div>
</body>
</html>