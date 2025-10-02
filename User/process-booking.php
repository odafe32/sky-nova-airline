<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: book-flight.php");
    exit();
}

// Get form data
$flight_id = $_POST['flight_id'] ?? '';
$passengers = $_POST['passengers'] ?? '';
$class = $_POST['class'] ?? '';
$total_price = $_POST['total_price'] ?? '';
$trip_type = $_POST['trip_type'] ?? '';
$return_date = $_POST['return_date'] ?? '';

// Store booking data in session for checkout page
$_SESSION['booking_data'] = [
    'flight_id' => $flight_id,
    'passengers' => $passengers,
    'class' => $class,
    'total_price' => $total_price,
    'trip_type' => $trip_type,
    'return_date' => $return_date
];

// Simple redirect to checkout
header("Location: checkout.php");
exit();
?>