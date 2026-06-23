# EcoTrace Backend API

PHP REST API for plant monitoring and verification. Phase 1 implementation with 10 core endpoints.

**Base URL:** `http://localhost/repos/EcoTrace/backend/api/`

## Documentation

- 📖 **[TALEND_API_TESTING_GUIDE.md](./TALEND_API_TESTING_GUIDE.md)** - Complete testing guide with step-by-step walkthroughs using Talend API Tester Chrome extension
- 🔗 **[BACKEND_ANALYSIS.md](./BACKEND_ANALYSIS.md)** - Architecture analysis showing how all files are connected and verified to use correct paths

## Quick Start

### Setup
1. Copy `backend/` to `C:\xampp\htdocs\EcoTrace\backend\`
2. Start XAMPP (Apache + MySQL)
3. Create database `ecotrace_db` in phpMyAdmin
4. Import `backend/database/ecotrace_db.sql`
5. Test: `http://localhost/repos/EcoTrace/backend/api/auth/login.php`

### Database Setup
- Open phpMyAdmin: http://localhost/phpmyadmin
- Create database: `ecotrace_db`
- Import SQL: `backend/database/setup.sql`
- All 7 tables will be created with proper indexes

## API Endpoints (Phase 1)

### Authentication
- **POST** `/auth/login.php` - Login with email/password, returns JWT token

### Events
- **GET** `/events/index.php` - List all active events
- **GET** `/events/detail.php?id=1` - Get event details

### Plants
- **GET** `/plants/nearby.php?latitude=12.533&longitude=124.872&radius=5&limit=10` - Get nearby plants
- **GET** `/plants/detail.php?id=1` - Get plant details and verification history

### Reservations
- **POST** `/reservations/create.php` - Reserve plant (7-day lock)
- **GET** `/reservations/check.php?plant_id=1` - Check reservation status
- **POST** `/reservations/release.php` - Release your reservation

### Verifications
- **POST** `/verifications/submit.php` - Submit plant verification (immutable)
- **GET** `/verifications/history.php?event_id=1` - Get verification history

## Authentication

All endpoints require JWT token in Authorization header:
```
Authorization: Bearer {token}
```

Token valid for 7 days. Get token from `/auth/login.php`.

## Database Tables

- `ecotag_students` - Student data (id, name, email, password, yearBatch)
- `ecotag_events` - Events (id, title, startDate, endDate, treeCountPerStudent)
- `ecotag_plants` - Plant locations (id, latitude, longitude, locationAddress, status)
- `ecotrace_tasks` - Task assignments (eventId, plantId, studentId)
- `ecotrace_reservations` - Reservation locks (plantId, studentId, expiresAt)
- `ecotrace_verifications` - Verification records (plantId, studentId, healthStatus, etc.)
- `ecotrace_photos` - Photo uploads (verificationId, photoUrl)

## Response Format

Success:
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

Error:
```json
{
  "success": false,
  "message": "Error description"
}
```

## Configuration

Edit `backend/config/db.php` to change:
- `DB_HOST` - MySQL host
- `DB_USER` - MySQL username
- `DB_PASS` - MySQL password
- `DB_NAME` - Database name
- `JWT_SECRET` - Change for production!

## Key Features

✅ JWT-based authentication  
✅ Geospatial queries (nearby plants)  
✅ 7-day reservation system  
✅ Immutable verification records  
✅ Input validation & sanitization  
✅ Prepared statements (no SQL injection)  
✅ Proper error handling  

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
