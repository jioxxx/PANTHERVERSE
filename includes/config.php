<?php
// ============================================================
// PANTHERVERSE — Configuration
// ============================================================

// Auto-detect base path from request
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '/';

// Get the directory of the script (e.g., /pantherverse-simple)
$base_path = dirname($script_name);
if ($base_path === '\\' || $base_path === '/') {
    $base_path = '';
}
// Remove any trailing slash
$base_path = rtrim($base_path, '/');

define('BASE_PATH', $base_path);

// Helper function to get base URL path
function base_path($path = ''): string {
    $base = BASE_PATH;
    if (empty($path)) {
        return $base;
    }
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

// Alias for compatibility
function base_url($path = ''): string {
    return base_path($path);
}

