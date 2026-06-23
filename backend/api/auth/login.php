<?php
// Login endpoint
require_once '../../config/db.php';
require_once '../../lib/response.php';
require_once '../../lib/auth.php';
require_once '../../lib/validators.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Only POST requests allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['email', 'password'];
$missing = Validator::checkRequired($data, $required);
if (!empty($missing)) {
    Response::validation(['missing_fields' => $missing]);
}

$email = Validator::sanitize($data['email']);
$password = $data['password'];

// Validate email format
if (!Validator::isValidEmail($email)) {
    Response::validation(['email' => 'Invalid email format']);
}

// Find student by email
$query = "SELECT id, name, email, password, yearBatch FROM ecotag_students WHERE email = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    Response::error('Database error: ' . $conn->error, 500);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    Response::error('Invalid email or password', 401);
}

// Verify password
if (!password_verify($password, $student['password'])) {
    Response::error('Invalid email or password', 401);
}

// Generate JWT token
$token = Auth::generateToken($student['id']);

// Return success with token
Response::success([
    'token' => $token,
    'student' => [
        'id' => $student['id'],
        'name' => $student['name'],
        'email' => $student['email'],
        'yearBatch' => $student['yearBatch']
    ]
], 'Login successful');