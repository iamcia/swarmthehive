<?php
/**
 * Helper functions for handling media files
 */

/**
 * Ensures the announcement_media directory exists
 * @return bool True if directory exists or was created, false otherwise
 */
function ensureMediaDirectoryExists() {
    $uploadDir = __DIR__ . '/announcement_media/';
    if (!file_exists($uploadDir)) {
        return mkdir($uploadDir, 0755, true);
    }
    return is_dir($uploadDir);
}

/**
 * Builds the correct path for a media file
 * @param string $path The file path
 * @return string The corrected path
 */
function getCorrectMediaPath($path) {
    if (empty($path)) {
        return '';
    }
    
    // Normalize path separators
    $path = str_replace('\\', '/', $path);
    
    // If path is already a URL, return it as is
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    
    // If path doesn't start with announcement_media/
    if (strpos($path, 'announcement_media/') !== 0) {
        // If path already has a directory component
        if (strpos($path, '/') !== false) {
            // Just return as is - it might be a legacy path
            return $path;
        } else {
            // It's just a filename, prefix with the correct directory
            return 'announcement_media/' . $path;
        }
    }
    
    // Path already has the correct prefix
    return $path;
}

/**
 * Sanitizes a filename to make it safe for storage
 * @param string $filename The original filename
 * @return string The sanitized filename
 */
function sanitizeFilename($filename) {
    // Remove unwanted characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    
    // Ensure filename is unique by adding timestamp
    $parts = pathinfo($filename);
    return $parts['filename'] . '_' . time() . '.' . $parts['extension'];
}

/**
 * Saves an uploaded file to the announcement_media directory
 * @param array $fileData The $_FILES array element for this file
 * @return array Success/error status and file path
 */
function saveUploadedMedia($fileData) {
    // Ensure directory exists
    if (!ensureMediaDirectoryExists()) {
        return [
            'success' => false,
            'message' => 'Failed to create upload directory'
        ];
    }
    
    // Check for upload errors
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error: ' . $fileData['error']
        ];
    }
    
    // Sanitize filename
    $safeFilename = sanitizeFilename($fileData['name']);
    
    // Build destination path
    $destination = __DIR__ . '/announcement_media/' . $safeFilename;
    
    // Move the uploaded file
    if (move_uploaded_file($fileData['tmp_name'], $destination)) {
        return [
            'success' => true,
            'path' => 'announcement_media/' . $safeFilename,
            'filename' => $safeFilename
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file'
        ];
    }
}
?>
