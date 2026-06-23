# EcoTrace Backend API

**Status:** Phase 1 ✅ Core API implementation complete  
**Base URL:** `http://localhost/repos/EcoTrace/backend/api/`  
**Framework:** PHP with MySQLi  
**Authentication:** JWT (JSON Web Tokens)

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Directory Structure](#directory-structure)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Documentation](#documentation)
- [Configuration](#configuration)
- [Security](#security)

## Overview

EcoTrace Backend is a PHP REST API designed for plant monitoring and verification. It provides core functionality for:
- User authentication with JWT tokens
- Plant location management with geospatial queries
- Event management for planting campaigns
- Plant reservation system (7-day locks)
- Plant verification and health tracking (immutable records)
- Photo upload and storage

## Quick Start

### Prerequisites
- XAMPP (Apache + MySQL) or similar PHP/MySQL stack
- PHP 7.4+
- MySQL 5.7+

### Setup Steps

1. **Clone/Copy Repository**
   ```
   Copy backend/ to C:\xampp\htdocs\repos\EcoTrace\backend\
   ```

2. **Start Services**
   - Start XAMPP (Apache + MySQL)
   - Verify Apache is running on port 80

3. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create new database: `ecotrace_db`
   - Ensure charset is `utf8mb4_unicode_ci`

4. **Import Database Schema**
   - In phpMyAdmin, select `ecotrace_db`
   - Go to Import tab
   - Upload `backend/database/ecotrace_db.sql`
   - Click Import
   - Verify 7 tables are created: ecotag_students, ecotag_events, ecotag_plants, ecotrace_tasks, ecotrace_reservations, ecotrace_verifications, ecotrace_photos

5. **Test Installation**
   - Open browser: `http://localhost/repos/EcoTrace/backend/api/auth/login.php`
   - Should see error message indicating backend is accessible

## Directory Structure

```
backend/
├── api/                          # API endpoints
│   ├── index.php                 # API router
│   ├── .htaccess                 # URL rewriting rules
│   ├── auth/
│   │   └── login.php             # Authentication endpoint
│   ├── events/
│   │   ├── index.php             # List events
│   │   └── detail.php            # Event details
│   ├── plants/
│   │   ├── nearby.php            # Geospatial query
│   │   └── detail.php            # Plant details
│   ├── reservations/
│   │   ├── create.php            # Create reservation
│   │   ├── check.php             # Check reservation status
│   │   └── release.php           # Release reservation
│   └── verifications/
│       ├── submit.php            # Submit verification
│       └── history.php           # Get verification history
├── config/
│   └── db.php                    # Database & API configuration
├── lib/
│   ├── auth.php                  # JWT token handler
│   ├── response.php              # Response formatting
│   ├── validators.php            # Input validation
│   └── geospatial.php            # Distance calculations
├── database/
│   └── ecotrace_db.sql           # Database schema & initial data
├── README.md                     # Backend documentation (this file)
├── TALEND_API_TESTING_GUIDE.md   # Testing guide with Talend API Tester
└── BACKEND_ANALYSIS.md           # Architecture analysis & verification
```

## API Endpoints (Phase 1)

### 1. Authentication

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/auth/login.php` | POST | ❌ | Login with email/password, returns JWT token |

**Request:**
```json
{
  "email": "test@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "student": {
      "id": 1,
      "name": "Student Name",
      "email": "test@example.com",
      "yearBatch": "2024"
    }
  }
}
```

### 2. Events

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/events/index.php` | GET | ✅ | List all active events |
| `/events/detail.php?id=1` | GET | ✅ | Get specific event details |

### 3. Plants

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/plants/nearby.php` | GET | ✅ | Get nearby plants (geospatial) |
| `/plants/detail.php?id=1` | GET | ✅ | Get plant details with verification history |

**Query Parameters for `nearby.php`:**
- `latitude` (required) - User's latitude
- `longitude` (required) - User's longitude
- `radius` (optional) - Search radius in km (default: 5, max: 100)
- `limit` (optional) - Result limit (default: 10, max: 100)
- `filter` (optional) - Filter by status: 'all', 'verified', 'unverified'

### 4. Reservations

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/reservations/create.php` | POST | ✅ | Reserve a plant (7-day lock) |
| `/reservations/check.php?plant_id=1` | GET | ✅ | Check reservation status |
| `/reservations/release.php` | POST | ✅ | Release a reservation |

### 5. Verifications

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/verifications/submit.php` | POST | ✅ | Submit plant verification (immutable) |
| `/verifications/history.php?event_id=1` | GET | ✅ | Get verification history |

## Authentication

All endpoints marked with ✅ require JWT token in Authorization header:

```
Authorization: Bearer {token}
```

**Token Details:**
- Valid for 7 days (604,800 seconds)
- Obtained from `/auth/login.php`
- Required format: `Bearer {token_value}`

**Protected Endpoint Error:**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```
Status Code: 401

## Database Schema

### Tables (7 Total)

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `ecotag_students` | User accounts | id, name, email, password, yearBatch |
| `ecotag_events` | Planting events | id, title, startDate, endDate, treeCountPerStudent |
| `ecotag_plants` | Plant locations | id, latitude, longitude, locationAddress, status |
| `ecotrace_tasks` | Event assignments | eventId, plantId, studentId |
| `ecotrace_reservations` | Reservation locks | plantId, studentId, expiresAt |
| `ecotrace_verifications` | Verification records | plantId, studentId, healthStatus, timestamp |
| `ecotrace_photos` | Photo storage | verificationId, photoUrl |

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description"
}
```

### Validation Error Response (Status 422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": "Error message"
  }
}
```

## Configuration

### Database Configuration

Edit `backend/config/db.php`:

```php
define('DB_HOST', 'localhost');    // MySQL host
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', '');             // MySQL password
define('DB_NAME', 'ecotrace_db');  // Database name
define('API_BASE_URL', 'http://localhost/repos/EcoTrace/backend/api');
```

### Security Configuration

```php
define('JWT_SECRET', 'ecotrace_super_secret_key_2024');  // ⚠️ CHANGE FOR PRODUCTION
define('TOKEN_EXPIRATION', 7 * 24 * 60 * 60);            // 7 days
define('RESERVATION_LOCK_DURATION', 7 * 24 * 60 * 60);   // 7 days
```

## Key Features

✅ **JWT-based Authentication** - Secure token-based API access  
✅ **Geospatial Queries** - Find plants by GPS coordinates  
✅ **7-day Reservation System** - Lock plants for verification  
✅ **Immutable Verifications** - Permanent verification records  
✅ **Input Validation & Sanitization** - Prevent data corruption  
✅ **Prepared Statements** - Protection against SQL injection  
✅ **Proper Error Handling** - Meaningful error messages  

## Testing

### Option 1: Talend API Tester (Recommended)
1. Install Talend API Tester Chrome extension
2. Open extension and set base URL: `http://localhost/repos/EcoTrace/backend/api/`
3. Follow **[TALEND_API_TESTING_GUIDE.md](./TALEND_API_TESTING_GUIDE.md)** for complete step-by-step testing instructions
4. Includes 6 complete test scenarios with expected responses

### Option 2: Postman

1. Create new Postman request
2. POST to `http://localhost/repos/EcoTrace/backend/api/auth/login.php`
3. Body (raw JSON):
   ```json
   {
     "email": "test@example.com",
     "password": "password"
   }
   ```
4. Copy token from response
5. Add to other requests: `Authorization: Bearer {token}`

### Option 3: cURL Command Line

```bash
# Login and get token
curl -X POST http://localhost/repos/EcoTrace/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Get nearby plants (replace TOKEN with actual token)
curl -X GET "http://localhost/repos/EcoTrace/backend/api/plants/nearby.php?latitude=12.533&longitude=124.872" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```
