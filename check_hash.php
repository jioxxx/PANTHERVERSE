<?php
$hash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "Hash matches 'password': " . (password_verify('password', $hash) ? 'YES' : 'NO') . "\n";
echo "Hash matches 'Admin@12345': " . (password_verify('Admin@12345', $hash) ? 'YES' : 'NO') . "\n";
