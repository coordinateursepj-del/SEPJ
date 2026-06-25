<?php
/**
 * Delete User - Handles user deletion via POST or redirect to main page
 */
require_once dirname(__DIR__, 2) . '/app/config/app.php';
require_once ROOT_PATH . '/app/core/db.php';
require_once ROOT_PATH . '/app/core/auth.php';
require_once ROOT_PATH . '/app/core/csrf.php';
require_once ROOT_PATH . '/app/core/helpers.php';
session_start_secure();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== $_SESSION['user_id']) {
        db()->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);
        log_audit($_SESSION['user_id'], 'delete', 'user', $id);
    }
    set_flash('success', current_lang() === 'ar' ? 'تم حذف المستخدم.' : (current_lang() === 'fr' ? 'Utilisateur supprimé.' : 'User deleted.'));
    redirect('index.php');
}

// GET request - redirect to main page
redirect('index.php');