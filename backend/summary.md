# EcoTrace Backend - Features Summary

**Status:** Phase 1 Complete ✅  
**Framework:** PHP 7.4+ with MySQLi  
**API Type:** RESTful JSON API  
**Authentication:** JWT (7-day expiration)

---

## What This Backend Can Do

### 1. User Authentication
- **Login System**: Students authenticate with email/password
- **JWT Tokens**: Secure, stateless token-based authentication
- **Token Validation**: 7-day expiration with Bearer token verification
- **Endpoint**: `POST /auth/login.php`

### 2. Event Management
- **Create & Track Events**: Planting campaigns with date ranges
- **Progress Tracking**: Monitor verified plant count per student
- **Active Event Filtering**: Automatic filtering of current/completed campaigns
- **Endpoints**: 
  - `GET /events/index.php` - List all active events
  - `GET /events/detail.php?id=1` - Get event details

### 3. Geospatial Plant Discovery ⭐
- **Find Nearby Plants**: Search by GPS coordinates with configurable radius
- **Distance Calculation**: Haversine formula for accurate km measurements
- **Sorting**: Results automatically sorted by distance
- **Filtering**: Filter by verification status (verified/unverified)
- **Endpoint**: `GET /plants/nearby.php?latitude=X&longitude=Y&radius=5&limit=10`

### 4. Plant Reservation System
- **7-Day Locks**: Reserve plants to prevent duplicate verification work
- **Status Checking**: Check if plant is available or reserved
- **Reservation Release**: Cancel active reservations
- **Endpoints**:
  - `POST /reservations/create.php` - Reserve a plant
  - `GET /reservations/check.php?plant_id=1` - Check status
  - `POST /reservations/release.php` - Release reservation

### 5. Plant Verification & Health Tracking ⭐
- **Immutable Records**: All submissions are permanent and cannot be edited
- **Rich Data Capture**:
  - Health Status: healthy, damaged, or dead
  - Plant Stage: seedling, sapling, or tree
  - Height & Circumference measurements
  - Photo attachments
  - Additional notes
- **Auto-Updates**: Plant status automatically marked as "verified"
- **Endpoints**:
  - `POST /verifications/submit.php` - Submit verification
  - `GET /verifications/history.php?event_id=1` - View history

### 6. Plant Detail Retrieval
- **Complete Plant Info**: Location, GPS coordinates, planting date, status
- **Last Verification**: Latest health check with measurements
- **Reservation Status**: Shows if user has active reservation
- **Endpoint**: `GET /plants/detail.php?id=1`

### 7. Input Validation & Security
- **Email Validation**: FILTER_VALIDATE_EMAIL
- **GPS Validation**: Latitude (-90 to 90), Longitude (-180 to 180)
- **Health Status Validation**: healthy, damaged, dead
- **Plant Stage Validation**: seedling, sapling, tree
- **Password Requirements**: Minimum 6 characters
- **SQL Injection Protection**: Prepared statements
- **Input Sanitization**: htmlspecialchars on all text inputs

### 8. Standardized API Responses
- **Success Format**: `{success: true, message: "", data: {}}`
- **Error Format**: `{success: false, message: ""}`
- **Validation Errors**: Detailed field-level error messages (Status 422)
- **HTTP Status Codes**: 200 (success), 401 (unauthorized), 404 (not found), 405 (method not allowed), 409 (conflict), 422 (validation), 500 (server error)

---

## Database Schema (7 Tables)

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `ecotag_students` | User accounts | id, name, email, password, yearBatch |
| `ecotag_events` | Planting campaigns | id, title, startDate, endDate, treeCountPerStudent, status |
| `ecotag_plants` | Plant locations | id, latitude, longitude, locationAddress, plantedDate, status |
| `ecotrace_tasks` | Event assignments | eventId, plantId, studentId, status |
| `ecotrace_reservations` | 7-day locks | plantId, studentId, expiresAt |
| `ecotrace_verifications` | Health records (immutable) | plantId, studentId, eventId, healthStatus, heightCm, circumferenceCm, plantStage, photoUrl, notes |
| `ecotrace_photos` | Photo storage | verificationId, photoUrl |

---

## API Endpoints Summary

### Authentication
- `POST /auth/login.php` - Login with email/password → JWT token

### Events  
- `GET /events/index.php` - List active events (Auth required)
- `GET /events/detail.php?id=1` - Get event details (Auth required)

### Plants
- `GET /plants/nearby.php` - Find nearby plants by GPS (Auth required)
- `GET /plants/detail.php?id=1` - Get plant details (Auth required)

### Reservations
- `POST /reservations/create.php` - Reserve plant (Auth required)
- `GET /reservations/check.php?plant_id=1` - Check status (Auth required)
- `POST /reservations/release.php` - Release reservation (Auth required)

### Verifications
- `POST /verifications/submit.php` - Submit verification (Auth required)
- `GET /verifications/history.php?event_id=1` - View history (Auth required)

---

## Key Workflows

### Student Verification Process
1. Login → Get JWT token
2. View active events
3. Find nearby plants (GPS search)
4. Reserve plant (7-day lock)
5. Verify plant health (submit measurements/photos)
6. Plant marked "verified" automatically
7. Reservation deleted automatically
8. View verification history

### Plant Lifecycle
- Plant created with GPS coordinates (status: unverified)
- First verification submitted (status: updated to verified)
- Multiple verifications tracked with timestamps
- All records preserved permanently (immutable)
- Latest verification always accessible

---

## Configuration

```php
// Database
DB_HOST = 'localhost'
DB_USER = 'root'
DB_NAME = 'ecotrace_db'

// Authentication
JWT_SECRET = 'ecotrace_super_secret_key_2024'
TOKEN_EXPIRATION = 604800 seconds (7 days)

// Reservations
RESERVATION_LOCK_DURATION = 604800 seconds (7 days)

// File Uploads
MAX_FILE_SIZE = 5 MB
ALLOWED_EXTENSIONS = jpg, jpeg, png, gif
```

---

## Key Features Summary

✅ **JWT Authentication** - Secure, stateless token-based API access  
✅ **Geospatial Queries** - Find plants by GPS with distance sorting  
✅ **7-Day Reservation System** - Lock plants for verification  
✅ **Immutable Verifications** - Permanent audit trail of plant health  
✅ **Input Validation & Sanitization** - Prevent data corruption  
✅ **Prepared Statements** - SQL injection protection  
✅ **Error Handling** - Meaningful error messages & status codes  
✅ **Mobile-Friendly** - RESTful JSON API for any client  

---

## Setup & Access

**Base URL:** `http://localhost/repos/EcoTrace/backend/api/`

**Quick Test:**
```bash
# Login
curl -X POST http://localhost/repos/EcoTrace/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Use returned token for other endpoints
curl -X GET "http://localhost/repos/EcoTrace/backend/api/plants/nearby.php?latitude=12.533&longitude=124.872" \
  -H "Authorization: Bearer {TOKEN}"
```

**Documentation Files:**
- `README.md` - Setup instructions and detailed docs
- `TALEND_API_TESTING_GUIDE.md` - Step-by-step testing guide
- `BACKEND_FEATURES_SUMMARY.md` - Comprehensive feature documentation