<?php

class LoginLogic
{
    private $error = '';
    private $message = '';
    private $db;

    public function __construct()
    {
        global $pdo;

        if ($pdo === null) {
            throw new Exception("Database connection not available");
        }

        $this->db = $pdo;

        // Session should already be started by the calling page
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exception("Session not started. Please start session before using LoginLogic.");
        }
    }

    /**
     * Check if user is already logged in
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    /**
     * Process login form submission
     */
    public function processLogin()
    {
        try {
            // Get input
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $this->error = 'Please enter both username and password.';
                return false;
            }

            // Attempt authentication
            $user = $this->authenticateUser($username, $password);

            if ($user) {
                $this->createUserSession($user);
                return true;
            } else {
                $this->error = 'Invalid username or password.';
                return false;
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $this->error = 'An error occurred during login. Please try again.';
            return false;
        }
    }

    /**
     * Authenticate user credentials
     */
    private function authenticateUser($username, $password)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password, role, status, email, last_login
                FROM users 
                WHERE username = ? AND status = 'active'
            ");

            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $updateStmt = $this->db->prepare("
                    UPDATE users 
                    SET last_login = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$user['id']]);

                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log('Database error during authentication: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create user session after successful login
     */
    private function createUserSession($user)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }



    /**
     * Redirect user after successful login
     */
    public function redirectAfterLogin()
    {
        $redirectUrl = 'dashboard.php';

        // Check if there's a specific redirect URL
        if (isset($_GET['redirect'])) {
            $redirectUrl = $_GET['redirect'];
        }

        header('Location: ' . $redirectUrl);
        exit();
    }



    /**
     * Get current error message
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get current success message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set success message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
