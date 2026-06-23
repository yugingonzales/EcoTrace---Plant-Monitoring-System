<?php
// Input validation helper functions

class Validator {
    // Check if email is valid
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Check if latitude/longitude are valid
    public static function isValidCoordinates($lat, $lon) {
        return is_numeric($lat) && is_numeric($lon) &&
               $lat >= -90 && $lat <= 90 &&
               $lon >= -180 && $lon <= 180;
    }
    
    // Sanitize input string
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate password
    public static function isValidPassword($password) {
        return strlen($password) >= 6;
    }
    
    // Validate health status
    public static function isValidHealthStatus($status) {
        $valid = ['healthy', 'dead', 'damaged'];
        return in_array(strtolower($status), $valid);
    }
    
    // Validate plant stage
    public static function isValidPlantStage($stage) {
        $valid = ['seedling', 'sapling', 'tree'];
        return in_array(strtolower($stage), $valid);
    }
    
    // Check required fields
    public static function checkRequired($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}