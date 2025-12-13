<?php
header('Content-Type: text/plain; charset=utf-8');

echo "PHP Function Existence Test\n";
echo "===========================\n\n";

$functions_to_check = [
    // Standard functions used in login process
    'password_verify',
    'password_hash',
    'json_encode',
    'json_decode',
    'hash_hmac',
    'bin2hex',
    'random_bytes',
    'hash_equals',
    'date',
    'file_get_contents',
    'file_put_contents',
    'filter_var',
    'session_start',
    'ob_start',

    // PDO class, which we already know should exist
    'PDO',
];

$all_found = true;

foreach ($functions_to_check as $function) {
    if (function_exists($function) || class_exists($function)) {
        echo "[ OK ] '{$function}' exists.\n";
    } else {
        echo "[ FAIL ] '{$function}' does NOT exist. This is the cause of the problem.\n";
        $all_found = false;
    }
}

echo "\n--- Conclusion ---\n";
if ($all_found) {
    echo "SUCCESS: All required standard functions and classes were found.\n";
    echo "The problem is not a missing PHP function. This is extremely unusual.\n";
} else {
    echo "FAILURE: One or more required PHP functions are missing.\n";
    echo "This indicates a problem with your PHP installation itself.\n";
    echo "You may need to reinstall XAMPP or ensure all standard extensions are enabled.\n";
}
?>
