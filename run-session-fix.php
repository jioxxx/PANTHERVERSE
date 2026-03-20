<?php
// ONE-CLICK SESSION + DB FIX FOR ALL FILES
require_once 'includes/db.php';

$files = glob('*.php') + glob('admin/*.php') + glob('api/*.php') + glob('settings/*.php') + glob('*.sql');
$fixed = 0;

foreach ($files as $file) {
    if (strpos($file, 'run-session') !== false) continue;
    
    $content = file_get_contents($file);
    
    // Fix session_start()
    $content = preg_replace('/session_start\s*\(\s*\)\s*;[\r\n\s]*require_once\s+[\'\"]includes\/db\.php[\'\"]\s*;/', "require_once 'includes/session.php';", $content);
    
    // Fix is_active = 1 → $bool_true
    $content = preg_replace('/is_active\s*=\s*1/', 'is_active = $bool_true', $content);
    
    if (file_put_contents($file, $content)) {
        $fixed++;
    }
}

echo "Fixed $fixed files!\n";
echo "Test: http://pantherverse-simple.test/\n";
echo "Deploy: git add . && git commit -m 'fix: auto session/db' && git push && vercel --prod\n";
?>

