<?php
/**
 * SEPJ Gabès - Admin Password Hash Generator
 * 
 * Run this script to generate a bcrypt hash for the default admin password.
 * Usage: php create_admin_hash.php
 * 
 * Copy the output hash and update it in the database:
 * UPDATE users SET password_hash = 'GENERATED_HASH' WHERE email = 'admin@sepj.local';
 */

$password = 'Admin12345!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "SEPJ Gabès - Admin Password Hash Generator\n";
echo "==============================================\n\n";
echo "Password: {$password}\n";
echo "Hash:     {$hash}\n\n";
echo "SQL to update:\n";
echo "UPDATE users SET password_hash = '{$hash}' WHERE email = 'admin@sepj.local';\n\n";
echo "PHP code to verify:\n";
echo "password_verify('{$password}', '{$hash}'); // returns true\n\n";
echo "----------------------------------------------\n";
echo "Save this hash! If you lose it, run this script again.\n";