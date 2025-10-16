<?php
/**
 * Language Handler for AJAX Requests
 * 
 * Handles language switching and translation requests
 */

session_start();
require_once 'language.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle POST requests (language updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_language') {
        $language = $_POST['language'] ?? 'en';
        
        // Validate language
        $availableLanguages = array_keys(getAvailableLanguages());
        if (in_array($language, $availableLanguages)) {
            setUserLanguage($language);
            echo json_encode(['success' => true, 'language' => $language]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid language']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests (translation fetching)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $language = $_GET['lang'] ?? getCurrentLanguage();
    
    // Validate language
    $availableLanguages = array_keys(getAvailableLanguages());
    if (!in_array($language, $availableLanguages)) {
        $language = 'en';
    }
    
    $translations = getTranslations();
    $languageTranslations = $translations[$language] ?? $translations['en'];
    
    echo json_encode([
        'success' => true,
        'language' => $language,
        'translations' => $languageTranslations
    ]);
    exit;
}

// Invalid request method
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>