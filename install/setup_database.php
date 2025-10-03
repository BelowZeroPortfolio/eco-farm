<?php
/**
 * Database Setup Script for IoT Farm Monitoring System
 * 
 * This script sets up the database schema and populates it with sample data.
 * Run this script once to initialize the database for the application.
 */

require_once __DIR__ . '/../config/database.php';

echo "IoT Farm Monitoring System - Database Setup\n";
echo "==========================================\n\n";

try {
    // Check if we can connect to the database
    echo "Checking database connection...\n";
    $pdo = getDatabaseConnection();
    echo "✓ Database connection successful\n\n";
    
    // Check if tables already exist
    echo "Checking existing tables...\n";
    if (checkDatabaseTables()) {
        echo "⚠ Database tables already exist. Do you want to recreate them? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo "Database setup cancelled.\n";
            exit(0);
        }
        
        echo "Dropping existing tables...\n";
        // Drop tables in correct order (respecting foreign keys)
        $dropTables = [
            'user_settings',
            'sensor_readings', 
            'pest_alerts',
            'sensors',
            'users'
        ];
        
        foreach ($dropTables as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                echo "✓ Dropped table: $table\n";
            } catch (PDOException $e) {
                echo "⚠ Could not drop table $table: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    // Initialize database
    echo "Setting up database schema and sample data...\n";
    if (initializeDatabase()) {
        echo "✓ Database schema created successfully\n";
        echo "✓ Sample data inserted successfully\n\n";
        
        // Verify setup
        echo "Verifying database setup...\n";
        if (checkDatabaseTables()) {
            echo "✓ All required tables created\n";
            
            // Count records in each table
            $tables = ['users', 'sensors', 'sensor_readings', 'pest_alerts', 'user_settings'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch()['count'];
                echo "✓ Table '$table': $count records\n";
            }
            
            echo "\n🎉 Database setup completed successfully!\n\n";
            
            // Display sample login credentials
            echo "Sample Login Credentials:\n";
            echo "========================\n";
            echo "Administrator:\n";
            echo "  Username: admin\n";
            echo "  Password: password\n";
            echo "  Email: admin@farm.com\n\n";
            echo "Farmer:\n";
            echo "  Username: farmer1\n";
            echo "  Password: password\n";
            echo "  Email: farmer1@farm.com\n\n";
            echo "Student:\n";
            echo "  Username: student1\n";
            echo "  Password: password\n";
            echo "  Email: student1@university.edu\n\n";
            echo "Note: All sample passwords are 'password' (hashed in database)\n";
            
        } else {
            throw new Exception("Database verification failed");
        }
        
    } else {
        throw new Exception("Database initialization failed");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
    exit(1);
}
?>