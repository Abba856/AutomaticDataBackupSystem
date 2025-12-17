<?php
/**
 * General utility functions for Automatic Data Backup System
 */

function redirect($location)
{
    header("Location: $location");
    exit;
}

function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function logActivity($message, $level = 'INFO')
{
    $logFile = LOG_PATH . date('Y-m-d') . '_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A';
    
    $logEntry = "[$timestamp] [$level] [User: $userId] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function formatBytes($size, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function encryptFile($sourcePath, $destinationPath, $key = null)
{
    if (!$key) {
        $key = ENCRYPTION_KEY;
    }
    
    // Read the file contents
    $data = file_get_contents($sourcePath);
    if ($data === false) {
        return false;
    }
    
    // Encrypt the data
    $ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        return false;
    }
    
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
    $encryptedData = base64_encode($iv.$hmac.$ciphertext_raw);
    
    // Write encrypted data to destination
    return file_put_contents($destinationPath, $encryptedData) !== false;
}

function decryptFile($sourcePath, $destinationPath, $key = null)
{
    if (!$key) {
        $key = ENCRYPTION_KEY;
    }
    
    // Read the encrypted file
    $encryptedData = file_get_contents($sourcePath);
    if ($encryptedData === false) {
        return false;
    }
    
    $c = base64_decode($encryptedData);
    if ($c === false) {
        return false;
    }
    
    $ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");
    if (strlen($c) < $ivlen + 32) { // 32 = length of hmac (sha256)
        return false;
    }
    
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    if ($original_plaintext === false) {
        return false;
    }
    
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
    
    if (!hash_equals($hmac, $calcmac)) {
        return false;
    }
    
    // Write decrypted data to destination
    return file_put_contents($destinationPath, $original_plaintext) !== false;
}

function encryptData($data, $key = null)
{
    if (!$key) {
        $key = ENCRYPTION_KEY;
    }
    
    $ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        return false;
    }
    
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
    return base64_encode($iv.$hmac.$ciphertext_raw);
}

function decryptData($ciphertext, $key = null)
{
    if (!$key) {
        $key = ENCRYPTION_KEY;
    }
    
    $c = base64_decode($ciphertext);
    if ($c === false) {
        return false;
    }
    
    $ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");
    if (strlen($c) < $ivlen + 32) { // 32 = length of hmac (sha256)
        return false;
    }
    
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    if ($original_plaintext === false) {
        return false;
    }
    
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
    
    if (hash_equals($hmac, $calcmac)) {
        return $original_plaintext;
    }
    
    return false;
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isJson($string)
{
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

function cleanOldBackups()
{
    $retentionDays = MAX_BACKUP_RETENTION_DAYS;
    $cutoffDate = time() - ($retentionDays * 24 * 60 * 60);
    
    $files = glob(UPLOAD_PATH . "*");
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoffDate) {
            unlink($file);
        }
    }
}

?>