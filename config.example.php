<?php
// Local XAMPP defaults. Set environment variables or edit these for hosting.
return [
 'host' => getenv('VERIDIAN_DB_HOST') ?: '127.0.0.1',
 'port' => getenv('VERIDIAN_DB_PORT') ?: '3306',
 'database' => getenv('VERIDIAN_DB_NAME') ?: 'veridian',
 'username' => getenv('VERIDIAN_DB_USER') ?: 'root',
 'password' => getenv('VERIDIAN_DB_PASSWORD') ?: '',
 // Set a long random value before public deployment; required on the setup screen.
 'setup_key' => getenv('VERIDIAN_SETUP_KEY') ?: '',
];
