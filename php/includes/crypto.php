<?php
require_once 'config.php';

function encryptData($plaintext)
{
    return openssl_encrypt($plaintext, 'AES-256-CBC', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

function decryptData($ciphertext)
{
    return openssl_decrypt($ciphertext, 'AES-256-CBC', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}
