<?php
/**
 * Response Helper Functions
 * Standardizes JSON responses across the API
 */

class Response {
    // Send success response
    public static function success($data = [], $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    // Send error response
    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => null
        ]);
        exit;
    }
    
    // Send validation error
    public static function validation($errors = []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Send unauthorized error
    public static function unauthorized() {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }
    
    // Send not found error
    public static function notFound() {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Not found'
        ]);
        exit;
    }
}