<?php
// Local XAMPP defaults. Set environment variables or edit these for hosting.
$getEnvVal = function(string $key, string $default = ''): string {
    $val = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);
    return (is_string($val) && $val !== '') ? $val : $default;
};

return [
    'host' => $getEnvVal('VERIDIAN_DB_HOST', '127.0.0.1'),
    'port' => $getEnvVal('VERIDIAN_DB_PORT', '3306'),
    'database' => $getEnvVal('VERIDIAN_DB_NAME', 'veridian'),
    'username' => $getEnvVal('VERIDIAN_DB_USER', 'root'),
    'password' => $getEnvVal('VERIDIAN_DB_PASSWORD', ''),
    'setup_key' => $getEnvVal('VERIDIAN_SETUP_KEY', ''),
];

