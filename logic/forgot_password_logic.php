<?php

class ForgotPasswordLogic
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
    }

    /**
     * Process forgot password request
     */
    public function processRequest()
    {
        try {
            // Set timezone to Philippine time
            date_default_timezone_set('Asia/Manila');
            
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $this->error = 'Please enter your email address.';
                return false;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error = 'Please enter a valid email address.';
                return false;
            }

            // Check if user exists
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                // Don't reveal if email exists for security
                $this->message = 'If an account exists with this email, you will receive a password reset link shortly.';
                return true;
            }

            // Check cooldown period (60 seconds)
            $cooldownCheck = $this->checkCooldown($user['id']);
            if ($cooldownCheck !== true) {
                $this->error = $cooldownCheck; // Contains the error message with remaining time
                return false;
            }

            // Generate reset token
            $token = $this->generateResetToken($user['id']);
            
            if ($token) {
                // Store token data in session for EmailJS to use
                $_SESSION['reset_email_data'] = [
                    'to_email' => $email,
                    'to_name' => $user['username'],
                    'reset_link' => $this->getResetLink($token),
                    'token' => $token
                ];
                
                $this->message = 'Password reset link has been prepared. Please check your email.';
                return true;
            } else {
                $this->error = 'Failed to generate reset token. Please try again.';
                return false;
            }
        } catch (Exception $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $this->error = 'An error occurred. Please try again later.';
            return false;
        }
    }

    /**
     * Check if user is in cooldown period
     * Returns true if allowed, or error message if in cooldown
     */
    private function checkCooldown($userId)
    {
        try {
            $currentTime = date('Y-m-d H:i:s');
            $cooldownSeconds = 60; // 60 seconds cooldown
            
            // Get the most recent reset request for this user
            $stmt = $this->db->prepare("
                SELECT created_at 
                FROM password_resets 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $lastRequest = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastRequest) {
                $lastRequestTime = strtotime($lastRequest['created_at']);
                $currentTimestamp = strtotime($currentTime);
                $timeDiff = $currentTimestamp - $lastRequestTime;
                
                if ($timeDiff < $cooldownSeconds) {
                    $remainingTime = $cooldownSeconds - $timeDiff;
                    return "Please wait {$remainingTime} seconds before requesting another reset link.";
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log('Cooldown check error: ' . $e->getMessage());
            return true; // Allow request if check fails
        }
    }

    /**
     * Get user by email
     */
    private function getUserByEmail($email)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate password reset token
     */
    private function generateResetToken($userId)
    {
        try {
            // Set timezone to Philippine time
            date_default_timezone_set('Asia/Manila');
            
            // Generate secure random token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Create table if not exists
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_token (token),
                    INDEX idx_expires (expires_at)
                )
            ");

            // Mark old unused tokens as used (invalidate them)
            $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
            $stmt->execute([$userId]);

            // Insert new token
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$userId, $token, $expiry])) {
                return $token;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Token generation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reset link URL
     */
    private function getResetLink($token)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        return $protocol . '://' . $host . $path . '/reset_password.php?token=' . $token;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
