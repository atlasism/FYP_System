<?php

function save_profile_picture(mysqli $conn, int $user_id, array $file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => ''];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['filename' => null, 'error' => 'Unable to upload the profile picture.'];
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['filename' => null, 'error' => 'Profile picture must be 2 MB or smaller.'];
    }

    $image_info = @getimagesize($file['tmp_name']);
    $mime = $image_info['mime'] ?? '';
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$image_info || !isset($extensions[$mime])) {
        return ['filename' => null, 'error' => 'Only JPG, PNG or WEBP profile pictures are allowed.'];
    }

    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        return ['filename' => null, 'error' => 'The profile picture folder could not be created.'];
    }

    $filename = 'user_' . $user_id . '_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        return ['filename' => null, 'error' => 'The profile picture could not be saved.'];
    }

    $statement = $conn->prepare('SELECT profile_picture FROM users WHERE id = ?');
    $statement->bind_param('i', $user_id);
    $statement->execute();
    $old_filename = $statement->get_result()->fetch_assoc()['profile_picture'] ?? '';

    $update = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    $update->bind_param('si', $filename, $user_id);
    if (!$update->execute()) {
        @unlink($directory . DIRECTORY_SEPARATOR . $filename);
        return ['filename' => null, 'error' => 'The profile picture could not be linked to the account.'];
    }

    if ($old_filename !== '' && preg_match('/^user_' . preg_quote((string) $user_id, '/') . '_[a-f0-9]+\.(jpg|png|webp)$/', $old_filename)) {
        @unlink($directory . DIRECTORY_SEPARATOR . $old_filename);
    }

    return ['filename' => $filename, 'error' => ''];
}
