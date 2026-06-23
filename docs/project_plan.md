# 🌱 EcoTrace Project Prompt

## PROJECT OVERVIEW

---

## 📋 Project Name
**EcoTrace** - Plant Monitoring & Verification System

---

## 🎯 Project Purpose

**EcoTrace** is a mobile application that allows students to monitor and verify the health status of trees planted by the **EcoTag legacy program** at the University of Eastern Philippines.

### Why This Project?

The EcoTag program plants trees as graduating students' final contribution to the university. But there's no system to track if those trees are still alive and healthy.

**EcoTrace solves this by:**
- ✅ Allowing students to verify plant health
- ✅ Creating a digital record of plant status
- ✅ Tracking who verified which plants
- ✅ Preventing duplicate verifications (1-week reservation lock)
- ✅ Providing real-time data on tree survival rates

---

## 🏗️ What We're Building

### Three Main Components:

#### 1. **Mobile App (Android)**
- Platform: Android (Java/Kotlin)
- User Interface for students
- Offline capability
- GPS location tracking
- Camera integration for photo proof
- Local data caching with Room database

#### 2. **Web Backend (PHP)**
- API endpoints for mobile app
- Runs on XAMPP (local server)
- Connects mobile app to database
- Handles all business logic
- Processes verifications
- Manages reservations

#### 3. **Database (MySQL)**
- Stores all data
- XAMPP includes MySQL
- Tables for: students, plants, events, tasks, reservations, verifications, photos

---

## 💻 Tech Stack

```
Frontend (Mobile)        Backend (Server)        Database
├─ Java                  ├─ PHP                  └─ MySQL
├─ Kotlin                ├─ JavaScript (if       (via XAMPP)
├─ Android Studio        │  needed)
├─ Retrofit (API)        └─ XAMPP
├─ Room (Local DB)           (Apache + PHP)
└─ Google Maps
```

---

## 🔄 How Everything Works Together

```
STUDENT (Mobile App)
    ↓ (Student clicks "Login")
    
ANDROID APP
    ├─ Shows login screen
    ├─ Student enters email/password
    ├─ Sends HTTP request to PHP backend
    │
    ↓
    
PHP BACKEND (on your computer)
    ├─ Receives request
    ├─ Validates email/password
    ├─ Queries MySQL database
    ├─ Checks if student exists
    ├─ Creates JWT token
    └─ Sends JSON response back
    
    ↓
    
MYSQL DATABASE
    ├─ Stores student data
    ├─ Stores plant coordinates
    ├─ Stores events
    ├─ Stores reservations
    ├─ Stores verifications
    └─ Stores photos
    
    ↓
    
ANDROID APP (receives response)
    ├─ Parses JSON
    ├─ Stores token
    ├─ Shows main screen
    ├─ Student can now use app
    └─ Token used for future requests
```

---

## 📱 Main Features

### 1. **Authentication**
- Student login with email/password
- JWT token-based security
- Persistent login (token stored locally)

### 2. **Event Management**
- Admin creates events (e.g., "June 2024 Verification Event")
- Events have: title, dates, tree count, target year batch
- Students see active events relevant to them

### 3. **Map View**
- Shows all plants with GPS coordinates
- Displays plant status (verified/unverified)
- Filter by: status, distance
- Click plant to see details

### 4. **Tasks List**
- Shows plants assigned to student
- Shows verification progress
- Each task shows: plant location, reservation status

### 5. **Reservation System**
- Student reserves a plant (1-week lock)
- Lock prevents other students from verifying same plant
- Lock expires after 7 days if not verified
- Lock released immediately after verification

### 6. **Verification Submission**
- Student goes to plant location (GPS verification)
- Takes photo as proof
- Enters plant health status: healthy/dead/damaged
- Enters measurements: height, circumference
- Enters plant stage: seedling/sapling/tree
- Submits - record is locked (immutable)

### 7. **Nearby Plants**
- Shows plants closest to student's current location
- Sorted by distance
- User can set radius (1-5km) and limit (1-50 plants)
- Filters: verified/unverified/all

### 8. **Event Tracking**
- Shows which events are open/closed
- Shows event requirements
- Tracks progress: X of Y plants verified
- Shows deadline for each event

---

## 🎓 User Workflow

### Student's Day with EcoTrace:

```
1. MORNING: Student opens app
   └─ Logs in with email/password
   └─ Sees dashboard with active events

2. CHECKS EVENTS
   └─ Sees: "June 2024 Event - Verify 3 plants"
   └─ Sees progress: "1 of 3 verified"

3. VIEWS MAP
   └─ Sees plants near campus
   └─ Filters: "Show unverified plants within 2km"
   └─ Sees 5 plants nearby

4. RESERVES PLANT
   └─ Clicks plant
   └─ Clicks "Reserve"
   └─ Gets 1-week lock

5. NAVIGATES TO PLANT
   └─ Uses GPS navigation
   └─ Goes to plant location

6. VERIFIES PLANT
   └─ Takes photo
   └─ Enters: healthy, 45cm height, 12cm circumference, sapling
   └─ Submits

7. APP CONFIRMS
   └─ Shows: "✓ Verified"
   └─ Progress updates: "2 of 3 verified"
   └─ Plant disappears from TODO

8. REPEATS
   └─ Reserves next plant
   └─ Navigates to location
   └─ Verifies
   └─ Continues until 3 plants done

9. EVENT COMPLETION
   └─ All plants verified
   └─ Event marked complete
   └─ Student has fulfilled their responsibility
```

---

## 🗄️ Database Structure

### Key Tables:

**ecotag_students**
- id, name, email, yearBatch

**ecotag_events**
- id, title, startDate, endDate, treeCountPerStudent, targetYearBatch

**ecotag_plants**
- id, latitude, longitude, locationAddress, plantedDate, status

**ecotrace_tasks**
- id, eventId, plantId, studentId, status

**ecotrace_reservations**
- id, plantId, studentId, expiresAt (reservation lock)

**ecotrace_verifications**
- id, plantId, studentId, healthStatus, heightCm, circumferenceCm, plantStage, photoUrl

**ecotrace_photos**
- id, verificationId, photoUrl

---

## 🔌 Backend API Endpoints

The PHP backend creates endpoints that Android app calls:

### Authentication
- `POST /api/auth/login.php` - Login with email/password → get JWT token

### Events
- `GET /api/events/index.php` - List all active events
- `GET /api/events/detail.php?id=1` - Get single event details

### Plants
- `GET /api/plants/nearby.php?latitude=12.533&longitude=124.872&radius=5&limit=10` - Get nearby plants
- `GET /api/plants/detail.php?id=1` - Get plant details

### Reservations
- `POST /api/reservations/create.php` - Reserve a plant (creates 1-week lock)
- `GET /api/reservations/check.php?plant_id=1` - Check if plant is locked
- `POST /api/reservations/release.php?id=1` - Release reservation

### Verifications
- `POST /api/verifications/submit.php` - Submit verification with photo
- `GET /api/verifications/history.php?event_id=1` - Get verification history

---

## 🚀 Development Approach

### Two Separate Teams

**Backend Team (You):**
- Build PHP API endpoints
- Handle database queries
- Process verifications
- Manage reservations
- Create JSON responses

**Frontend Team (Mobile Dev):**
- Build Android screens
- Call your API endpoints
- Display data in UI
- Handle GPS/camera
- Manage offline caching

### Teams Work Independently

- Backend team creates endpoints
- Frontend team integrates them
- Same database, separate code
- Easy to test each component separately

---

## 📊 Development Phases

### Phase 1: Foundation (Week 1)
```
Backend:
✅ Set up XAMPP
✅ Create database & tables
✅ Create helper functions
✅ Build: Login endpoint
✅ Build: Events endpoints
✅ Build: Plants nearby endpoint

Frontend:
✅ Create LoginActivity
✅ Create MainActivity with tabs
✅ Create MapFragment
✅ Create TasksFragment
✅ Create EventsFragment
```

### Phase 2: Core Features (Week 2-3)
```
Backend:
✅ Build: Reservation endpoints
✅ Build: Verification submit endpoint
✅ Implement: 1-week lock logic
✅ Implement: Immutable records

Frontend:
✅ Integrate: Login API
✅ Integrate: Events API
✅ Integrate: Plants nearby API
✅ Add: GPS location tracking
✅ Add: Camera integration
```

### Phase 3: Testing & Polish (Week 4-5)
```
Backend:
✅ Test all endpoints
✅ Test error handling
✅ Test database edge cases
✅ Optimize queries

Frontend:
✅ End-to-end testing
✅ Test with actual backend
✅ Handle network errors
✅ Optimize performance
```

### Phase 4: Deployment (Week 6)
```
Backend:
✅ Deploy to university server
✅ Set up backups
✅ Monitor performance

Frontend:
✅ Build release APK
✅ Submit to Play Store (if desired)
✅ User testing
```

---

## 🎯 Success Criteria

### Backend Working When:
✅ All 4 endpoints respond correctly  
✅ Database queries work properly  
✅ Errors handled gracefully  
✅ JWT tokens generated and verified  
✅ Reservations lock correctly  
✅ Verifications immutable  
✅ Tests pass  

### Frontend Working When:
✅ Can login to backend  
✅ Can see events from backend  
✅ Can see plants on map  
✅ Can reserve plants  
✅ Can submit verifications  
✅ Can upload photos  
✅ Can track progress  

### Overall Success When:
✅ Student can complete full workflow  
✅ Data persists in database  
✅ No data loss  
✅ App runs smoothly  
✅ Photos upload correctly  
✅ GPS works reliably  

---

## 🛠️ Tools & Setup

### Your Machine
- **Visual Studio Code** - Edit PHP files
- **XAMPP** - Run Apache + PHP + MySQL locally
- **Postman** - Test API endpoints
- **Git** - Version control
- **Browser** - Test PHP, access phpMyAdmin

### Development Workflow
```
1. Edit PHP file in VS Code
2. Save file
3. Test endpoint with Postman or browser
4. Check database in phpMyAdmin
5. Commit to Git when working
```

---

## 📋 High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│                   ANDROID APP                        │
│  ┌──────────────────────────────────────────────┐  │
│  │ Activities (Screens)                         │  │
│  │ ├─ LoginActivity                             │  │
│  │ ├─ MainActivity (4 tabs)                     │  │
│  │ │  ├─ HomeFragment                           │  │
│  │ │  ├─ MapFragment                            │  │
│  │ │  ├─ TasksFragment                          │  │
│  │ │  └─ EventsFragment                         │  │
│  │ ├─ ViewModels (State management)             │  │
│  │ ├─ Repositories (Data access)                │  │
│  │ ├─ API Client (Retrofit)                     │  │
│  │ └─ Local Database (Room)                     │  │
│  └──────────────────────────────────────────────┘  │
└──────────────────┬──────────────────────────────────┘
                   │ HTTP Request/Response
                   │ JSON
                   ↓
┌──────────────────────────────────────────────────────┐
│         PHP BACKEND (on your computer)               │
│  ┌──────────────────────────────────────────────┐  │
│  │ API Endpoints                                │  │
│  │ ├─ /api/auth/login.php                       │  │
│  │ ├─ /api/events/...                           │  │
│  │ ├─ /api/plants/nearby.php                    │  │
│  │ ├─ /api/reservations/...                     │  │
│  │ └─ /api/verifications/...                    │  │
│  │                                              │  │
│  │ Helper Functions                             │  │
│  │ ├─ Database connection (config/db.php)      │  │
│  │ ├─ Validation (lib/validators.php)          │  │
│  │ ├─ JWT Auth (lib/auth.php)                  │  │
│  │ ├─ Geospatial (lib/geospatial.php)          │  │
│  │ └─ JSON Response (lib/response.php)         │  │
│  └──────────────────────────────────────────────┘  │
└──────────────────┬──────────────────────────────────┘
                   │ SQL Queries
                   ↓
┌──────────────────────────────────────────────────────┐
│            MYSQL DATABASE (XAMPP)                    │
│  ┌──────────────────────────────────────────────┐  │
│  │ Tables                                       │  │
│  │ ├─ ecotag_students                          │  │
│  │ ├─ ecotag_events                            │  │
│  │ ├─ ecotag_plants                            │  │
│  │ ├─ ecotrace_tasks                           │  │
│  │ ├─ ecotrace_reservations                    │  │
│  │ ├─ ecotrace_verifications                   │  │
│  │ └─ ecotrace_photos                          │  │
│  └──────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
```

---

## 🔐 Security Considerations

### What We're Protecting
- Student credentials (email/password)
- JWT tokens (sent with each request)
- Verification data (immutable once submitted)
- Photo uploads (store securely)

### Security Measures
- JWT for authentication (not storing passwords)
- Prepared statements (prevent SQL injection)
- Input validation (check all data)
- Error handling (don't expose sensitive info)
- Immutable verifications (can't change data)

---

## 📈 Scalability

### Current Design
- Works for: 1000s of students
- Works for: 10,000s of plants
- Works for: Multiple events

### Future Enhancements
- Add analytics (success rates)
- Add photo analysis (tree health scoring)
- Add web dashboard (admin view)
- Add data export (reports)
- Add notifications (push alerts)

---

## 🎯 Project Goals

### Primary Goal
**Enable systematic monitoring of planted trees** so the university can track if EcoTag legacy trees survive.

### Secondary Goals
**Engage students** in environmental conservation  
**Create data** for future analysis  
**Build mobile app** from scratch  
**Learn backend development** with PHP  
**Practice teamwork** (backend + frontend)

---

## 📚 What You'll Learn

### By Building This Project:

**Backend Skills:**
- REST API design
- PHP programming
- MySQL database design
- Server-side validation
- JWT authentication
- Error handling
- Geospatial queries

**Mobile Skills (for frontend team):**
- Android development
- API integration
- GPS/location services
- Camera integration
- Offline data caching
- UI/UX design

**Team Skills:**
- Git version control
- API contracts (agreeing on endpoints)
- Testing & QA
- Deployment
- Documentation

---

## ✅ Deliverables

### By End of Project:

1. **Working Mobile App**
   - Can login
   - Can see events
   - Can view plants on map
   - Can reserve plants
   - Can submit verifications
   - Can view history

2. **Working Backend**
   - All endpoints operational
   - Database populated
   - Error handling robust
   - Performance optimized

3. **Documentation**
   - API documentation
   - Database schema
   - Setup instructions
   - User guide

4. **Code**
   - Clean, readable code
   - Commented where complex
   - Organized file structure
   - Git history with good commits

---

## 🚀 Getting Started

### Immediate Next Steps:

**Week 1 - Backend Setup:**
1. Follow BACKEND_STEP_BY_STEP.md
2. Complete all 22 steps
3. Get 4 endpoints working
4. Test with Postman

**Week 1 - Frontend Setup:**
1. Create Android project structure
2. Create LoginActivity
3. Create MainActivity with 4 tabs
4. Set up Retrofit for API calls

**Week 2+:**
1. Integrate backend and frontend
2. Test together
3. Add more endpoints
4. Add more UI screens
5. Optimize and polish

---

## 📞 Questions You Might Have

**Q: Can we use cloud hosting?**
A: No - use local XAMPP for development, university server for deployment

**Q: Can we use a different database?**
A: No - stick with MySQL (already in XAMPP)

**Q: Can we use Node.js instead of PHP?**
A: No - project uses PHP + MySQL XAMPP

**Q: Can we skip testing?**
A: No - testing ensures it actually works

**Q: What if the database has errors?**
A: Use phpMyAdmin to view/fix data directly

**Q: How do we handle offline?**
A: Android app caches data locally with Room database

---

## 🎯 Summary

**EcoTrace** = Plant monitoring system  
**Built with** = Android (frontend) + PHP (backend) + MySQL (database)  
**Purpose** = Students verify tree health  
**Process** = Login → See events → View plants → Reserve → Navigate → Verify → Upload photo  
**Result** = Database of verified trees with photos  

**Your job** = Build the PHP backend that processes all requests  
**Their job** = Build the Android frontend that students use  
**Together** = Complete ecosystem for tree monitoring  

---

**This is the project. This is what we're building. Ready?** 🚀

