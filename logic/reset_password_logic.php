<?php

class ResetPasswordLogic
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
     * Validate reset token
     */
    public function validateToken($token)
    {
        try {
            // Set timezone to Philippine time
            date_default_timezone_set('Asia/Manila');
            $currentTime = date('Y-m-d H:i:s');
            
            // First check if token exists at all
            $checkStmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ?");
            $checkStmt->execute([$token]);
            $tokenData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenData) {
                error_log('Token found in DB: used=' . $tokenData['used'] . ', expires_at=' . $tokenData['expires_at'] . ', current_time=' . $currentTime);
            } else {
                error_log('Token NOT found in database: ' . substr($token, 0, 10) . '...');
            }
            
            $stmt = $this->db->prepare("
                SELECT pr.*, u.username, u.email 
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? 
                AND pr.used = 0 
                AND pr.expires_at > ?
            ");
            $stmt->execute([$token, $currentTime]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                error_log('Token validation failed for token: ' . substr($token, 0, 10) . '... at time: ' . $currentTime);
            }
            
            return $result !== false;
        } catch (PDOException $e) {
            error_log('Token validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process password reset
     */
    public function processReset($token)
    {
        try {
            // Set timezone to Philippine time
            date_default_timezone_set('Asia/Manila');
            $currentTime = date('Y-m-d H:i:s');
            
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate inputs
            if (empty($password) || empty($confirmPassword)) {
                $this->error = 'Please fill in all fields.';
                return false;
            }

            if (strlen($password) < 6) {
                $this->error = 'Password must be at least 6 characters long.';
                return false;
            }

            if ($password !== $confirmPassword) {
                $this->error = 'Passwords do not match.';
                return false;
            }

            // Get user ID from token
            $stmt = $this->db->prepare("
                SELECT user_id 
                FROM password_resets 
                WHERE token = ? 
                AND used = 0 
                AND expires_at > ?
            ");
            $stmt->execute([$token, $currentTime]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $this->error = 'Invalid or expired reset token.';
                return false;
            }

            // Hash new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Update user password
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $reset['user_id']]);

            // Mark token as used
            $stmt = $this->db->prepare("
                UPDATE password_resets 
                SET used = 1 
                WHERE token = ?
            ");
            $stmt->execute([$token]);

            $this->message = 'Password reset successful!';
            return true;
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            $this->error = 'An error occurred. Please try again.';
            return false;
        }
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
