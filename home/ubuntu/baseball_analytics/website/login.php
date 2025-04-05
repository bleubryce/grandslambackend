<?php
// Authentication configuration
$users = [
    'admin' => [
        'password' => password_hash('baseball_admin_2025', PASSWORD_DEFAULT),
        'role' => 'admin',
        'name' => 'Administrator'
    ]
    // Additional users can be added here
];

// Start session
session_start();

// Check if user is already logged in
$is_logged_in = isset($_SESSION['user']) && !empty($_SESSION['user']);

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        // Login successful
        $_SESSION['user'] = [
            'username' => $username,
            'role' => $users[$username]['role'],
            'name' => $users[$username]['name']
        ];
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        // Login failed
        $error = 'Invalid username or password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baseball Analytics System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: var(--primary-color);
        }
        
        .login-title {
            text-align: center;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }
        
        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <h2 class="login-title">Baseball Analytics</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Sign in</button>
        </form>
        <div class="login-footer">
            <p>© 2025 Baseball Analytics. All rights reserved.</p>
            <p>For access, please contact your system administrator.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
