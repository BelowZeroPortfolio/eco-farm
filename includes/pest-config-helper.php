<?php
/**
 * Pest Configuration Helper
 * Database-driven pest information retrieval
 */

require_once 'config/database.php';

/**
 * Get pest information from database
 * 
 * @param string $pestType The detected pest type
 * @return array ['severity' => string, 'actions' => string, 'description' => string, etc.]
 */
function getPestInfo($pestType)
{
    try {
        $pdo = getDatabaseConnection();
        
        // Normalize pest name for matching
        $pestLower = strtolower(trim($pestType));
        
        // Try exact match first
        $stmt = $pdo->prepare("
            SELECT * FROM pest_config 
            WHERE LOWER(pest_name) = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$pestLower]);
        $pest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no exact match, try partial match
        if (!$pest) {
            $stmt = $pdo->prepare("
                SELECT * FROM pest_config 
                WHERE LOWER(pest_name) LIKE ? AND is_active = 1
                ORDER BY LENGTH(pest_name) ASC
                LIMIT 1
            ");
            $stmt->execute(["%$pestLower%"]);
            $pest = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // If found in database, return it
        if ($pest) {
            return [
                'severity' => $pest['severity'],
                'actions' => $pest['suggested_actions'],
                'description' => $pest['description'],
                'economic_threshold' => $pest['economic_threshold'],
                'pest_type' => $pest['pest_type'],
                'remarks' => $pest['remarks'],
                'common_name' => $pest['common_name']
            ];
        }
        
        // Default for unknown pests
        return [
            'severity' => 'medium',
            'actions' => 'UNKNOWN PEST: Proper identification required. Contact agricultural extension service for identification. Document location, crop, damage type, and population level. Monitor daily until identified. Avoid broad-spectrum pesticides until pest confirmed.',
            'description' => 'Pest not found in database. Requires identification.',
            'economic_threshold' => 'Unknown - use caution',
            'pest_type' => 'unknown',
            'remarks' => 'Add this pest to the database for future reference.'
        ];
        
    } catch (Exception $e) {
        error_log("Error getting pest info: " . $e->getMessage());
        
        // Fallback on error
        return [
            'severity' => 'medium',
            'actions' => 'Database error. Contact system administrator.',
            'description' => 'Unable to retrieve pest information.',
            'economic_threshold' => 'Unknown',
            'pest_type' => 'unknown',
            'remarks' => ''
        ];
    }
}

/**
 * Get all active pests from database
 * 
 * @return array Array of pest configurations
 */
function getAllActivePests()
{
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->query("
            SELECT * FROM pest_config 
            WHERE is_active = 1
            ORDER BY severity DESC, pest_name ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting all pests: " . $e->getMessage());
        return [];
    }
}

/**
 * Get pest statistics
 * 
 * @return array Statistics about pest database
 */
function getPestStatistics()
{
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
            FROM pest_config
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting pest statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'active' => 0
        ];
    }
}
