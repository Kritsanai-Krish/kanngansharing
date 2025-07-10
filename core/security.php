<?php

// IMPORTANT: This is a secret key for encrypting and decrypting data.
// You MUST change this to your own randomly generated, secret key.
// To generate a new key, you can run this PHP code once:
// echo base64_encode(openssl_random_pseudo_bytes(32));
// Store this key securely, for example, as an environment variable, NOT in the code.
define('ENCRYPTION_KEY', 'change-this-to-your-own-32-byte-secret-key!!');

define('ENCRYPTION_CIPHER', 'AES-256-GCM');

/**
 * Encrypts data using AES-256-GCM.
 * Provides both confidentiality and authenticity.
 * @param string $data The plaintext data to encrypt.
 * @return string|false The base64 encoded encrypted string, or false on failure.
 */
function encrypt_data($data) {
    if (empty($data)) {
        return '';
    }
    
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $ciphertext = openssl_encrypt(
        $data,
        ENCRYPTION_CIPHER,
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag // The authentication tag is generated and passed by reference.
    );

    if ($ciphertext === false) {
        return false;
    }

    // Prepend the IV and append the tag to the ciphertext for storage.
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypts data that was encrypted with encrypt_data().
 * @param string $encrypted_data The base64 encoded encrypted string.
 * @return string|false The original plaintext data, or false on failure (e.g., if tampered with).
 */
function decrypt_data($encrypted_data) {
    if (empty($encrypted_data)) {
        return '';
    }

    $decoded_data = base64_decode($encrypted_data);
    
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $tag_length = 16; // AES-GCM tag length is 16 bytes.
    
    $iv = substr($decoded_data, 0, $iv_length);
    $tag = substr($decoded_data, $iv_length, $tag_length);
    $ciphertext = substr($decoded_data, $iv_length + $tag_length);

    $decrypted_data = openssl_decrypt(
        $ciphertext,
        ENCRYPTION_CIPHER,
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $decrypted_data;
}

/**
 * Generates and stores a CSRF token in the session if one doesn't exist.
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 * Uses hash_equals for timing-attack-safe comparison.
 * The token is for one-time use.
 * @param string $submitted_token The token from the form submission.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token($submitted_token) {
    if (empty($submitted_token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    $is_valid = hash_equals($_SESSION['csrf_token'], $submitted_token);
    
    // Invalidate the token after its first use for added security.
    unset($_SESSION['csrf_token']); 

    return $is_valid;
}