<?php
// Turn on output buffering
ob_start();

// --- Core Path Constants ---
// Use DIRECTORY_SEPARATOR for cross-platform compatibility (e.g., Windows vs. Linux)
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);

// Define the absolute path to the project's root directory (kanngan-sharing)
defined('SITE_ROOT') ? null : define('SITE_ROOT', dirname(__DIR__));


// --- Error Reporting (Development vs. Production) ---
// For development, show all errors.
// For a live production server, you should change '1' to '0' and log errors to a file.
error_reporting(E_ALL);
ini_set('display_errors', '1');


// --- Load Core Files ---
// 1. Load the database connection first.
require_once(SITE_ROOT . DS . 'core' . DS . 'db_connect.php');

// 2. Load all helper functions.
require_once(SITE_ROOT . DS . 'core' . DS . 'functions.php');


// --- Start Session ---
// This function from functions.php will start a secure session on every page.
start_secure_session();