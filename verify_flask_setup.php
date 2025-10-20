<?php
/**
 * Flask Setup Verification Script
 * Run this to verify everything is configured correctly
 */

echo "========================================\n";
echo "Flask Setup Verification\n";
echo "========================================\n\n";

$allGood = true;

// Test 1: Check if YOLODetector2.php exists
echo "Test 1: YOLODetector2.php file\n";
if (file_exists('YOLODetector2.php')) {
    echo "✓ YOLODetector2.php found\n";
} else {
    echo "✗ YOLODetector2.php NOT found\n";
    $allGood = false;
}
echo "\n";

// Test 2: Check if yolo_detect2.py exists
echo "Test 2: yolo_detect2.py file\n";
if (file_exists('yolo_detect2.py')) {
    echo "✓ yolo_detect2.py found\n";
} else {
    echo "✗ yolo_detect2.py NOT found\n";
    $allGood = false;
}
echo "\n";

// Test 3: Check if best.pt model exists
echo "Test 3: YOLO model file\n";
if (file_exists('best.pt')) {
    $size = filesize('best.pt');
    $sizeMB = round($size / 1024 / 1024, 2);
    echo "✓ best.pt found ($sizeMB MB)\n";
} else {
    echo "✗ best.pt NOT found\n";
    $allGood = false;
}
echo "\n";

// Test 4: Check if Flask service is running
echo "Test 4: Flask service health\n";
require_once 'YOLODetector2.php';
$detector = new YOLODetector2('http://127.0.0.1:5000');

if ($detector->isHealthy()) {
    echo "✓ Flask service is running and healthy\n";
    
    // Get model info
    try {
        $info = $detector->getModelInfo();
        echo "  - Model Type: " . $info['model_type'] . "\n";
        echo "  - Classes: " . $info['num_classes'] . "\n";
    } catch (Exception $e) {
        echo "  - Could not get model info\n";
    }
} else {
    echo "✗ Flask service is NOT running\n";
    echo "  Please run: start_yolo_service.bat\n";
    $allGood = false;
}
echo "\n";

// Test 5: Check if pest_detection2.php exists
echo "Test 5: pest_detection2.php file\n";
if (file_exists('pest_detection2.php')) {
    echo "✓ pest_detection2.php found\n";
    
    // Check if it references pest_detection2.php (not pest_detection.php)
    $content = file_get_contents('pest_detection2.php');
    $count = substr_count($content, "fetch('pest_detection2.php");
    echo "  - Found $count fetch calls to pest_detection2.php\n";
    
    if (strpos($content, "fetch('pest_detection.php") !== false) {
        echo "  ⚠ WARNING: Still has references to pest_detection.php\n";
    }
} else {
    echo "✗ pest_detection2.php NOT found\n";
    $allGood = false;
}
echo "\n";

// Test 6: Check temp directory
echo "Test 6: Temp directory\n";
if (file_exists('temp')) {
    if (is_writable('temp')) {
        echo "✓ temp/ directory exists and is writable\n";
    } else {
        echo "⚠ temp/ directory exists but is NOT writable\n";
    }
} else {
    echo "⚠ temp/ directory does not exist (will be created automatically)\n";
}
echo "\n";

// Test 7: Performance test (if service is running)
if ($detector->isHealthy()) {
    echo "Test 7: Performance test\n";
    
    // Find a test image
    $testImages = ['test.jpg', 'includes/pest.jpg', 'includes/realtime.jpg'];
    $testImage = null;
    
    foreach ($testImages as $img) {
        if (file_exists($img)) {
            $testImage = $img;
            break;
        }
    }
    
    if ($testImage) {
        echo "  Using test image: $testImage\n";
        
        try {
            $startTime = microtime(true);
            $detections = $detector->detectPests($testImage);
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            echo "✓ Detection completed in {$duration}ms\n";
            echo "  - Found " . count($detections) . " detection(s)\n";
            
            if ($duration < 1000) {
                echo "  ✓ Performance is GOOD (< 1 second)\n";
            } else {
                echo "  ⚠ Performance is SLOW (> 1 second)\n";
            }
        } catch (Exception $e) {
            echo "✗ Detection failed: " . $e->getMessage() . "\n";
            $allGood = false;
        }
    } else {
        echo "  ⚠ No test image found, skipping performance test\n";
    }
    echo "\n";
}

// Final summary
echo "========================================\n";
if ($allGood) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "========================================\n\n";
    echo "You can now use pest_detection2.php\n";
    echo "URL: http://localhost/eco-farm/pest_detection2.php\n";
} else {
    echo "✗ SOME TESTS FAILED\n";
    echo "========================================\n\n";
    echo "Please fix the issues above before using the system.\n";
}
