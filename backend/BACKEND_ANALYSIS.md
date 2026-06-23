# EcoTrace Backend Architecture Analysis

## Overview
This document provides a comprehensive analysis of the EcoTrace backend structure, how all files are connected, and verification that they use the correct path: `http://localhost/repos/EcoTrace/backend/`

---

## Verified Configuration

### Primary Configuration File
**File:** `backend/config/db.php`

```php
// API Base URL (VERIFIED)
define('API_BASE_URL', 'http://localhost/repos/EcoTrace/backend/api');

// Database Connection
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecotrace_db');

// Security
define('JWT_SECRET', 'ecotrace_super_secret_key_2024');
define('TOKEN_EXPIRATION', 7 * 24 * 60 * 60);
```

**Status:** ✅ **CORRECT** - Uses proper base path

---

## Backend Directory Structure

```
backend/
├── config/
│   └── db.php                    # Database & API configuration
├── lib/
│   ├── auth.php                  # JWT authentication handler
│   ├── response.php              # Response formatting
│   ├── validators.php            # Input validation
│   └── geospatial.php            # Geospatial calculations
├── api/
│   ├── index.php                 # API router
│   ├── .htaccess                 # URL rewriting
│   ├── auth/
│   │   └── login.php             # Authentication endpoint
│   ├── events/
│   │   ├── index.php             # List events
│   │   └── detail.php            # Event details
│   ├── plants/
│   │   ├── nearby.php            # Find nearby plants
│   │   └── detail.php            # Plant details
│   ├── reservations/
│   │   ├── create.php            # Reserve plant
│   │   ├── check.php             # Check reservation
│   │   └── release.php           # Release reservation
│   └── verifications/
│       ├── submit.php            # Submit verification
│       └── history.php           # Verification history
├── database/
│   └── ecotrace_db.sql           # Database schema & seed data
├── TALEND_API_TESTING_GUIDE.md   # API testing guide (UPDATED)
└── README.md                      # Backend documentation
```

---

## File Connection Map

### Configuration Chain
```
Every API endpoint → requires config/db.php
    ↓
db.php provides:
    - Database connection ($conn)
    - API_BASE_URL constant
    - JWT_SECRET constant
    - TOKEN_EXPIRATION constant
    - UPLOAD settings
```

### Example: Login Endpoint Connection
**File:** `backend/api/auth/login.php`

```php
// 1. Load configuration
require_once '../../config/db.php';           // Gets $conn, API_BASE_URL, JWT_SECRET
require_once '../../lib/response.php';        // Response formatting
require_once '../../lib/auth.php';            // JWT token generation
require_once '../../lib/validators.php';      // Input validation

// 2. Use database connection from db.php
$stmt = $conn->prepare($query);               // Uses $conn from db.php

// 3. Use constants from db.php
$token = Auth::generateToken($student['id']); // Uses JWT_SECRET from db.php

// 4. Return formatted response
Response::success([...], 'Login successful');
```

**Status:** ✅ **CONNECTED** - All dependencies properly linked

---

## Library Dependencies

### auth.php (JWT Token Handler)
**Requires:** `config/db.php`
- Uses: `JWT_SECRET` constant
- Uses: `TOKEN_EXPIRATION` constant
- Provides: Token generation and verification
- Used by: All protected endpoints

**Methods:**
```php
Auth::generateToken($userId)       // Creates JWT token
Auth::verifyToken($token)          // Validates JWT token
Auth::getToken()                   // Extracts token from headers
Auth::getCurrentUser($conn)        // Gets user from token
```

### response.php (Response Formatting)
**Provides:** Consistent response format for all endpoints

**Methods:**
```php
Response::success($data, $message)     // 200 OK response
Response::error($message, $code)       // Error response
Response::validation($errors)          // 422 validation errors
Response::unauthorized()               // 401 unauthorized
```

### validators.php (Input Validation)
**Provides:** Input sanitization and validation

**Methods:**
```php
Validator::checkRequired($data, $fields)      // Check required fields
Validator::sanitize($input)                   // Sanitize strings
Validator::isValidEmail($email)               // Email validation
Validator::isValidCoordinates($lat, $lon)     // Coordinate validation
```

### geospatial.php (Distance Calculations)
**Provides:** Geographic calculations for nearby plants

**Methods:**
```php
Geospatial::calculateDistance($lat1, $lon1, $lat2, $lon2)  // Distance between points
```

---

## API Endpoints (Verified)

### Authentication
| Endpoint | Method | URL | Auth Required | Status |
|----------|--------|-----|---|---|
| Login | POST | `http://localhost/repos/EcoTrace/backend/api/auth/login.php` | No | ✅ |

### Events
| Endpoint | Method | URL | Auth Required | Status |
|----------|--------|-----|---|---|
| List Events | GET | `http://localhost/repos/EcoTrace/backend/api/events/index.php` | Yes | ✅ |
| Event Details | GET | `http://localhost/repos/EcoTrace/backend/api/events/detail.php?id=1` | Yes | ✅ |

### Plants
| Endpoint | Method | URL | Auth Required | Status |
|----------|--------|-----|---|---|
| Nearby Plants | GET | `http://localhost/repos/EcoTrace/backend/api/plants/nearby.php?latitude=12.533&longitude=124.872` | Yes | ✅ |
| Plant Details | GET | `http://localhost/repos/EcoTrace/backend/api/plants/detail.php?id=1` | Yes | ✅ |

### Reservations
| Endpoint | Method | URL | Auth Required | Status |
|----------|--------|-----|---|---|
| Create Reservation | POST | `http://localhost/repos/EcoTrace/backend/api/reservations/create.php` | Yes | ✅ |
| Check Reservation | GET | `http://localhost/repos/EcoTrace/backend/api/reservations/check.php?plant_id=1` | Yes | ✅ |
| Release Reservation | POST | `http://localhost/repos/EcoTrace/backend/api/reservations/release.php` | Yes | ✅ |

### Verifications
| Endpoint | Method | URL | Auth Required | Status |
|----------|--------|-----|---|---|
| Submit Verification | POST | `http://localhost/repos/EcoTrace/backend/api/verifications/submit.php` | Yes | ✅ |
| Verification History | GET | `http://localhost/repos/EcoTrace/backend/api/verifications/history.php?event_id=1` | Yes | ✅ |

---

## Database Connection Flow

```
1. User makes HTTP request to endpoint
   ↓
2. API endpoint (e.g., plants/nearby.php) loads
   ↓
3. Requires config/db.php
   ↓
4. db.php creates $conn (mysqli connection)
   ↓
5. db.php defines API_BASE_URL and security constants
   ↓
6. Endpoint uses $conn for database queries
   ↓
7. Endpoint uses Auth class for token validation
   ↓
8. Endpoint returns Response::success() or Response::error()
```

---

## Security Implementation

### Authentication
- ✅ JWT tokens with 7-day expiration
- ✅ Token verification on protected endpoints
- ✅ Token extracted from Authorization header
- ✅ Bearer token format required

### Database Security
- ✅ Prepared statements (prevents SQL injection)
- ✅ Parameter binding with type specification
- ✅ Input sanitization via Validator class
- ✅ Character set encoding (UTF-8)

### Input Validation
- ✅ Required field checking
- ✅ Email format validation
- ✅ Coordinate validation
- ✅ Radius and limit constraints
- ✅ File upload restrictions

---

## Testing URLs (Correct Path Verified)

### Base Path
```
http://localhost/repos/EcoTrace/backend/api/
```

### Test with Talend API Tester

**1. Login First**
```
Method: POST
URL: http://localhost/repos/EcoTrace/backend/api/auth/login.php
Headers: Content-Type: application/json
Body: {
  "email": "test@example.com",
  "password": "password"
}
```

**2. Use Token for Protected Endpoints**
```
Headers:
- Authorization: Bearer {TOKEN_FROM_LOGIN}
- Content-Type: application/json
```

**3. Example Protected Request**
```
Method: GET
URL: http://localhost/repos/EcoTrace/backend/api/plants/nearby.php?latitude=12.533&longitude=124.872
Headers:
- Authorization: Bearer {TOKEN}
- Content-Type: application/json
```

---

## Connection Verification Checklist

- ✅ `config/db.php` defines correct API_BASE_URL
- ✅ `config/db.php` establishes database connection
- ✅ All API endpoints require `config/db.php`
- ✅ All library files properly imported
- ✅ `lib/auth.php` uses JWT_SECRET from config
- ✅ Database connection passed to endpoints
- ✅ Response formatting consistent across endpoints
- ✅ Input validation applied to all endpoints
- ✅ Protected endpoints verify authentication
- ✅ All paths use `http://localhost/repos/EcoTrace/backend/`

---

## Summary

**Status: ✅ ALL BACKEND FILES PROPERLY CONNECTED**

The EcoTrace backend is correctly configured with:
1. **Central configuration** in `config/db.php`
2. **Proper database connection** with MySQLi
3. **Correct API path** set to `http://localhost/repos/EcoTrace/backend/api`
4. **Modular library system** for auth, responses, validation, and geospatial functions
5. **Well-organized API endpoints** following consistent patterns
6. **Security measures** including JWT authentication and input validation
7. **Complete documentation** and testing guide

All files are interconnected and use the verified path structure.

---

**Last Verified:** 2026-06-23
**Analysis Status:** ✅ COMPLETE