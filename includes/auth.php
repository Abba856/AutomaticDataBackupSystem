<?php
/**
 * Authentication functions for Automatic Data Backup System
 */

function authenticateUser()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = "Username and password are required.";
            header('Location: ?action=login');
            exit;
        }
        
        try {
            $db = getDbConnection();
            
            // Query the user from database
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                header('Location: ?action=dashboard');
                exit;
            } else {
                $_SESSION['error'] = "Invalid username or password.";
                header('Location: ?action=login');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            $_SESSION['error'] = "Authentication failed. Please try again later.";
            header('Location: ?action=login');
            exit;
        }
    } else {
        header('Location: ?action=login');
        exit;
    }
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ?action=login');
        exit;
    }
}

function checkSessionTimeout()
{
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: ?action=login');
        exit;
    }
    
    if (isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time(); // Refresh session
    }
}

?>