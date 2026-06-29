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
$query = "SELECT student_id, full_name, email, password_hash, year_batch FROM ecotrace_students WHERE email = ? AND is_active = TRUE";
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
if (!password_verify($password, $student['password_hash'])) {
    Response::error('Invalid email or password', 401);
}

// Update last login timestamp
$updateQuery = "UPDATE ecotrace_students SET last_login_at = NOW() WHERE student_id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("i", $student['student_id']);
$updateStmt->execute();

// Generate JWT token
$token = Auth::generateToken($student['student_id']);

// Return success with token
Response::success([
    'token' => $token,
    'student' => [
        'student_id' => $student['student_id'],
        'full_name' => $student['full_name'],
        'email' => $student['email'],
        'year_batch' => $student['year_batch']
    ]
], 'Login successful');
