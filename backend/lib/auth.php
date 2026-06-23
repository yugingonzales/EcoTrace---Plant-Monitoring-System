<?php
// JWT Authentication Helper Functions

class Auth {
    // Generate JWT Token
    public static function generateToken($userId) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [
            'userId' => $userId,
            'iat' => time(),
            'exp' => time() + TOKEN_EXPIRATION
        ];
        
        $header_encoded = base64_encode(json_encode($header));
        $payload_encoded = base64_encode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $header_encoded . '.' . $payload_encoded,
            JWT_SECRET,
            true
        );
        $signature_encoded = base64_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    // Verify JWT Token
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        $signature = hash_hmac(
            'sha256',
            $header_encoded . '.' . $payload_encoded,
            JWT_SECRET,
            true
        );
        $signature_new = base64_encode($signature);
        
        if ($signature_new !== $signature_encoded) {
            return null;
        }
        
        $payload = json_decode(base64_decode($payload_encoded), true);
        
        if ($payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    // Get token from headers
    public static function getToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $parts = explode(' ', $headers['Authorization']);
            if (count($parts) === 2 && $parts[0] === 'Bearer') {
                return $parts[1];
            }
        }
        
        return null;
    }
    
    // Get current user from token
    public static function getCurrentUser($conn) {
        $token = self::getToken();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        $userId = $payload['userId'];
        
        $query = "SELECT id, name, email, yearBatch FROM ecotag_students WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}