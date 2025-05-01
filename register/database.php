<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'infinitytech';

// Create connection
$connection = new mysqli($host, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Set charset to ensure proper handling of special characters
$connection->set_charset("utf8mb4");