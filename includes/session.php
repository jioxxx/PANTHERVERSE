<?php
/**
 * PANTHERVERSE — Production-Safe Session Handler
 * Fixes Vercel serverless session issues (cold starts, secure cookies)
 */

// Prevent direct access
defined('BASE_PATH') or die('No script kiddies');

if (session_status() === PHP_SESSION_NONE) {
    // Detect Vercel/production environment (broader)
    $is_production = (isset($_SERVER['VERCEL']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'vercel.app') !== false)
        || strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Vercel') !== false;

    if ($is_production) {
        // Vercel: HTTPS-only, strict security
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '7200'); // 2hr
    } else {
        // Local dev (Laragon): Flexible
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_httponly', '0');
        ini_set('session.cookie_samesite', 'Lax');
    }

    // Common secure settings
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_path', '/');
    
    session_start();
    
    // Regenerate on login (already in login/register, but safety)
    if (isset($_SESSION['just_logged_in'])) {
        session_regenerate_id(true);
        unset($_SESSION['just_logged_in']);
    }
}
?>

