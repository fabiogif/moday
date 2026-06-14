<?php

/**
 * Suppress Dotenv deprecation warnings
 * This file should be included early in the application bootstrap
 */

// Suppress Dotenv deprecation warnings by default
$suppressWarnings = true;

// Check if .env file exists and has the setting
if (file_exists(__DIR__ . '/../../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../../.env');
    if (strpos($envContent, 'DOTENV_SUPPRESS_DEPRECATION_WARNINGS=false') !== false) {
        $suppressWarnings = false;
    }
}

if ($suppressWarnings) {
    // Set error handler to suppress specific deprecation warnings
    set_error_handler(function ($severity, $message, $file, $line) {
        // Check if it's a Dotenv or voku deprecation warning
        if ($severity === E_DEPRECATED && (
            strpos($message, 'Dotenv\\') === 0 || 
            strpos($message, 'voku\\helper\\ASCII') !== false
        )) {
            return true; // Suppress the warning
        }
        
        // Let other errors through
        return false;
    }, E_DEPRECATED);
}
