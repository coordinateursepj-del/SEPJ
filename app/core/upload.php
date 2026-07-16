<?php
/**
 * File Upload Handler - SEPJ Gabès
 * 
 * Secure file upload with MIME validation, extension whitelist, and safe filenames.
 */

/**
 * Get upload URL for a file with validation
 * 
 * @param string $filePath The file path stored in database
 * @return string The full URL to the image
 */
function get_image_url(string $filePath): string
{
    if (empty($filePath)) {
        return '';
    }
    
    // Generate the URL
    $url = upload_url($filePath);
    
    // Log for debugging if file doesn't exist physically
    $physicalPath = UPLOAD_PATH . '/' . ltrim($filePath, '/');
    if (!file_exists($physicalPath)) {
        error_log("WARNING: Image file not found - DB Path: {$filePath}, Physical: {$physicalPath}, URL: {$url}");
    }
    
    return $url;
}

/**
 * Upload a file to the uploads directory
 *
 * @param array $file $_FILES array element
 * @param string $subdirectory Subdirectory within uploads (e.g., 'content', 'gallery')
 * @return array ['success' => bool, 'path' => string, 'message' => string]
 */
function upload_file(array $file, string $subdirectory = 'general'): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'الملف كبير جداً. الحد الأقصى هو ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB.',
            UPLOAD_ERR_FORM_SIZE  => 'الملف كبير جداً.',
            UPLOAD_ERR_PARTIAL    => 'تم رفع جزء فقط من الملف.',
            UPLOAD_ERR_NO_FILE    => 'لم يتم رفع أي ملف.',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت غير موجود.',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف.',
            UPLOAD_ERR_EXTENSION  => 'تم إيقاف رفع الملف بواسطة إضافة.',
        ];
        $message = $errorMessages[$file['error']] ?? 'خطأ غير معروف في رفع الملف.';
        return ['success' => false, 'path' => '', 'message' => $message];
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $maxMB = MAX_UPLOAD_SIZE / 1024 / 1024;
        return [
            'success' => false,
            'path'    => '',
            'message' => "حجم الملف كبير جداً. الحد الأقصى هو {$maxMB}MB.",
        ];
    }
    
    // Validate MIME type using finfo
    $allowedMimes = unserialize(ALLOWED_MIME_TYPES);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        return [
            'success' => false,
            'path'    => '',
            'message' => 'نوع الملف غير مسموح به. الأنواع المسموحة: JPG, PNG, WebP.',
        ];
    }
    
    // Validate extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = explode(',', ALLOWED_EXTENSIONS);
    
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'path'    => '',
            'message' => 'امتداد الملف غير مسموح به. الامتدادات المسموحة: jpg, jpeg, png, webp.',
        ];
    }
    
    // Block dangerous files
    $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'shtml', 'cgi', 'pl', 'py', 'asp', 'aspx', 'exe', 'js', 'svg', 'html', 'htm'];
    if (in_array($extension, $dangerousExtensions)) {
        return [
            'success' => false,
            'path'    => '',
            'message' => 'هذا النوع من الملفات ممنوع لأسباب أمنية.',
        ];
    }
    
    // Generate safe filename
    $safeName = generate_safe_filename($extension);
    
    // Create subdirectory if needed
    $uploadDir = UPLOAD_PATH . '/' . $subdirectory;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("ERROR: Failed to create upload directory - {$uploadDir}");
            return [
                'success' => false,
                'path'    => '',
                'message' => 'فشل في إنشاء المجلد.',
            ];
        }
        // Set directory permissions
        chmod($uploadDir, 0755);
        error_log("INFO: Created upload directory - {$uploadDir}");
    }
    
    // Ensure a valid .htaccess in the uploads tree.
    // IMPORTANT: use Apache 2.4 syntax (Require, from mod_authz_core which is always
    // loaded). The old Order/Allow/Deny syntax needs mod_access_compat and returns a
    // 500 on servers without it (e.g. OVH), which made every uploaded image unviewable.
    // We also self-heal: any existing .htaccess still containing the legacy "Order"
    // directive is rewritten. All known subdirs are healed on every upload, so a single
    // successful upload repairs the whole tree.
    $htaccessContent = "# SEPJ Gabès - Upload Security\n"
        . "# Block script execution, allow images. Apache 2.4 (mod_authz_core).\n\n"
        . "Options -Indexes\n\n"
        . "<FilesMatch \"\\.(php|php\\d+|phtml|phar|shtml|cgi|pl|py|asp|aspx|exe|inc)$\">\n"
        . "    Require all denied\n"
        . "</FilesMatch>\n\n"
        . "<FilesMatch \"\\.(jpg|jpeg|png|webp|gif|ico)$\">\n"
        . "    Require all granted\n"
        . "</FilesMatch>\n";
    $healDirs = array_unique([
        UPLOAD_PATH,
        UPLOAD_PATH . '/content',
        UPLOAD_PATH . '/gallery',
        $uploadDir,
    ]);
    foreach ($healDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $htaccessFile = $dir . '/.htaccess';
        $needsWrite = !file_exists($htaccessFile)
            || strpos((string) @file_get_contents($htaccessFile), 'Order ') !== false;
        if ($needsWrite) {
            if (file_put_contents($htaccessFile, $htaccessContent)) {
                @chmod($htaccessFile, 0644);
                error_log("INFO: Wrote upload .htaccess (Apache 2.4 syntax) - {$htaccessFile}");
            } else {
                error_log("WARNING: Failed to write .htaccess file - {$htaccessFile}");
            }
        }
    }
    
    // Move file
    $destination = $uploadDir . '/' . $safeName;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Set proper file permissions for web server
        chmod($destination, 0644);
        
        $relativePath = $subdirectory . '/' . $safeName;
        
        // Verify file was actually saved and is accessible
        if (file_exists($destination)) {
            error_log("SUCCESS: File uploaded - Path: {$relativePath}, Size: " . filesize($destination) . " bytes, URL: " . upload_url($relativePath));
            return [
                'success' => true,
                'path'    => $relativePath,
                'message' => 'تم رفع الملف بنجاح.',
            ];
        } else {
            error_log("ERROR: File uploaded but verification failed - Destination: {$destination}");
            return [
                'success' => false,
                'path'    => '',
                'message' => 'فشل في التحقق من الملف المرفوع.',
            ];
        }
    }
    
    error_log("ERROR: move_uploaded_file failed - Source: {$file['tmp_name']}, Destination: {$destination}, Dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
    return [
        'success' => false,
        'path'    => '',
        'message' => 'فشل في حفظ الملف. الرجاء المحاولة مرة أخرى.',
    ];
}

/**
 * Upload multiple files at once
 *
 * @param array $files $_FILES array (with array structure for multiple files)
 * @param string $subdirectory Subdirectory within uploads
 * @return array Array of upload results
 */
function upload_multiple_files(array $files, string $subdirectory = 'gallery'): array
{
    $results = [];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        $singleFile = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        
        $results[] = upload_file($singleFile, $subdirectory);
    }
    
    return $results;
}

/**
 * Delete a file from the uploads directory
 *
 * @param string $relativePath Relative path from uploads directory
 * @return bool
 */
function delete_uploaded_file(string $relativePath): bool
{
    if (empty($relativePath)) {
        return false;
    }
    
    $fullPath = UPLOAD_PATH . '/' . ltrim($relativePath, '/');
    
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    
    return false;
}

/**
 * Generate a safe, unique filename
 *
 * @param string $extension File extension (without dot)
 * @return string
 */
function generate_safe_filename(string $extension): string
{
    $timestamp = date('Ymd_His');
    $random = bin2hex(random_bytes(8));
    return "{$timestamp}_{$random}.{$extension}";
}

/**
 * Get the full URL for an uploaded file
 *
 * @param string $relativePath
 * @return string
 */
function uploaded_file_url(string $relativePath): string
{
    if (empty($relativePath)) {
        return '';
    }
    return UPLOAD_URL . '/' . ltrim($relativePath, '/');
}

/**
 * Check if uploads directory is writable
 *
 * @return bool
 */
function is_uploads_writable(): bool
{
    $testFile = UPLOAD_PATH . '/_write_test.tmp';
    
    if (!is_dir(UPLOAD_PATH)) {
        return false;
    }
    
    if (file_put_contents($testFile, 'test') !== false) {
        unlink($testFile);
        return true;
    }
    
    return false;
}