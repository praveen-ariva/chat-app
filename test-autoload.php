<?php
require __DIR__ . '/vendor/autoload.php';

echo "Testing autoloading...\n";

try {
    if (class_exists(\App\Controllers\UserController::class)) {
        echo "UserController class exists!\n";
        $controller = new \App\Controllers\UserController();
        echo "UserController instantiated successfully!\n";
        
        if (method_exists($controller, 'create')) {
            echo "create() method exists!\n";
        } else {
            echo "create() method DOES NOT exist!\n";
        }
    } else {
        echo "UserController class DOES NOT exist!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
