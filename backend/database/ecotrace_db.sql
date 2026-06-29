-- ============================================================================
-- EcoTrace Database Schema - Refactored Version
-- ============================================================================
-- Purpose: Improved database design with standardized naming, proper 
--          normalization, and enhanced data integrity
-- Changes:
--   1. Standardized table naming (consistent 'ecotrace_' prefix)
--   2. Improved column naming (camelCase → snake_case)
--   3. Enhanced normalization (separated concerns)
--   4. Added missing indexes for performance
--   5. Improved data types and constraints
--   6. Added audit fields consistently
-- ============================================================================

-- Drop existing tables in correct order (respecting foreign keys)
DROP TABLE IF EXISTS ecotrace_verification_photos;
DROP TABLE IF EXISTS ecotrace_plant_verifications;
DROP TABLE IF EXISTS ecotrace_plant_reservations;
DROP TABLE IF EXISTS ecotrace_event_tasks;
DROP TABLE IF EXISTS ecotrace_plants;
DROP TABLE IF EXISTS ecotrace_events;
DROP TABLE IF EXISTS ecotrace_students;

-- ============================================================================
-- CORE ENTITIES
-- ============================================================================

-- Students Table
-- Purpose: Store student user accounts and authentication data
CREATE TABLE ecotrace_students (
    student_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    year_batch YEAR NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_year_batch (year_batch),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events Table
-- Purpose: Store planting campaign events with date ranges and targets
CREATE TABLE ecotrace_events (
    event_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_title VARCHAR(255) NOT NULL,
    event_description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    trees_per_student INT UNSIGNED DEFAULT 1,
    target_year_batch YEAR NULL,
    event_status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_event_dates (start_date, end_date),
    INDEX idx_event_status (event_status),
    INDEX idx_target_batch (target_year_batch),
    
    CONSTRAINT chk_date_range CHECK (end_date >= start_date),
    CONSTRAINT chk_trees_positive CHECK (trees_per_student > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plants Table
-- Purpose: Store plant location and metadata
CREATE TABLE ecotrace_plants (
    plant_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    location_address VARCHAR(500),
    planted_date DATE,
    plant_species VARCHAR(255),
    plant_status ENUM('pending', 'verified', 'deceased') DEFAULT 'pending',
    verification_count INT UNSIGNED DEFAULT 0,
    last_verified_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_coordinates (latitude, longitude),
    INDEX idx_plant_status (plant_status),
    INDEX idx_planted_date (planted_date),
    INDEX idx_verification_count (verification_count),
    
    CONSTRAINT chk_latitude CHECK (latitude BETWEEN -90 AND 90),
    CONSTRAINT chk_longitude CHECK (longitude BETWEEN -180 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RELATIONSHIP & TRANSACTION TABLES
-- ============================================================================

-- Event Tasks Table
-- Purpose: Junction table linking students to plants within events
CREATE TABLE ecotrace_event_tasks (
    task_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_id INT UNSIGNED NOT NULL,
    plant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    task_status ENUM('assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES ecotrace_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (plant_id) REFERENCES ecotrace_plants(plant_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES ecotrace_students(student_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_event_plant_student (event_id, plant_id, student_id),
    INDEX idx_event_tasks (event_id),
    INDEX idx_plant_tasks (plant_id),
    INDEX idx_student_tasks (student_id),
    INDEX idx_task_status (task_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plant Reservations Table
-- Purpose: Temporary locks on plants to prevent concurrent verification attempts
CREATE TABLE ecotrace_plant_reservations (
    reservation_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    plant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    released_at TIMESTAMP NULL,
    
    FOREIGN KEY (plant_id) REFERENCES ecotrace_plants(plant_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES ecotrace_students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES ecotrace_events(event_id) ON DELETE SET NULL,
    
    INDEX idx_plant_reservations (plant_id, is_active),
    INDEX idx_student_reservations (student_id),
    INDEX idx_expiration (expires_at, is_active),
    INDEX idx_event_reservations (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plant Verifications Table
-- Purpose: Immutable record of plant health checks and measurements
CREATE TABLE ecotrace_plant_verifications (
    verification_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    plant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    health_status ENUM('healthy', 'stressed', 'damaged', 'deceased') NOT NULL,
    plant_stage ENUM('seed', 'seedling', 'sapling', 'young_tree', 'mature_tree') NOT NULL,
    height_cm DECIMAL(8, 2) UNSIGNED,
    circumference_cm DECIMAL(8, 2) UNSIGNED,
    canopy_diameter_cm DECIMAL(8, 2) UNSIGNED,
    leaf_condition ENUM('excellent', 'good', 'fair', 'poor') NULL,
    soil_condition ENUM('excellent', 'good', 'fair', 'poor') NULL,
    has_pests BOOLEAN DEFAULT FALSE,
    has_disease BOOLEAN DEFAULT FALSE,
    needs_water BOOLEAN DEFAULT FALSE,
    needs_fertilizer BOOLEAN DEFAULT FALSE,
    verification_notes TEXT,
    weather_condition VARCHAR(100),
    temperature_celsius DECIMAL(4, 1),
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plant_id) REFERENCES ecotrace_plants(plant_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES ecotrace_students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES ecotrace_events(event_id) ON DELETE SET NULL,
    
    INDEX idx_plant_verifications (plant_id, verified_at),
    INDEX idx_student_verifications (student_id),
    INDEX idx_event_verifications (event_id),
    INDEX idx_health_status (health_status),
    INDEX idx_plant_stage (plant_stage),
    INDEX idx_verified_date (verified_at),
    
    CONSTRAINT chk_height_positive CHECK (height_cm IS NULL OR height_cm >= 0),
    CONSTRAINT chk_circumference_positive CHECK (circumference_cm IS NULL OR circumference_cm >= 0),
    CONSTRAINT chk_canopy_positive CHECK (canopy_diameter_cm IS NULL OR canopy_diameter_cm >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification Photos Table
-- Purpose: Store multiple photos per verification (normalized from single photo field)
CREATE TABLE ecotrace_verification_photos (
    photo_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    verification_id INT UNSIGNED NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    photo_type ENUM('overview', 'leaves', 'trunk', 'roots', 'damage', 'other') DEFAULT 'overview',
    file_size_bytes INT UNSIGNED,
    mime_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (verification_id) REFERENCES ecotrace_plant_verifications(verification_id) ON DELETE CASCADE,
    
    INDEX idx_verification_photos (verification_id),
    INDEX idx_photo_type (photo_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- Active Plants View
-- Purpose: Quick access to plants that are alive and verified
CREATE OR REPLACE VIEW vw_active_plants AS
SELECT 
    p.plant_id,
    p.latitude,
    p.longitude,
    p.location_address,
    p.planted_date,
    p.plant_species,
    p.verification_count,
    p.last_verified_at,
    pv.health_status AS last_health_status,
    pv.plant_stage AS last_plant_stage,
    pv.height_cm AS last_height_cm
FROM ecotrace_plants p
LEFT JOIN ecotrace_plant_verifications pv ON pv.verification_id = (
    SELECT verification_id 
    FROM ecotrace_plant_verifications 
    WHERE plant_id = p.plant_id 
    ORDER BY verified_at DESC 
    LIMIT 1
)
WHERE p.plant_status = 'verified' AND p.plant_status != 'deceased';

-- Student Progress View
-- Purpose: Track student participation and verification counts
CREATE OR REPLACE VIEW vw_student_progress AS
SELECT 
    s.student_id,
    s.full_name,
    s.email,
    s.year_batch,
    COUNT(DISTINCT pv.verification_id) AS total_verifications,
    COUNT(DISTINCT pv.plant_id) AS unique_plants_verified,
    COUNT(DISTINCT pv.event_id) AS events_participated,
    MAX(pv.verified_at) AS last_verification_date
FROM ecotrace_students s
LEFT JOIN ecotrace_plant_verifications pv ON pv.student_id = s.student_id
WHERE s.is_active = TRUE
GROUP BY s.student_id, s.full_name, s.email, s.year_batch;

-- Event Progress View
-- Purpose: Track event completion status
CREATE OR REPLACE VIEW vw_event_progress AS
SELECT 
    e.event_id,
    e.event_title,
    e.start_date,
    e.end_date,
    e.event_status,
    e.trees_per_student AS target_per_student,
    COUNT(DISTINCT et.student_id) AS students_participating,
    COUNT(DISTINCT CASE WHEN et.task_status = 'completed' THEN et.task_id END) AS tasks_completed,
    COUNT(DISTINCT et.task_id) AS total_tasks,
    ROUND(COUNT(DISTINCT CASE WHEN et.task_status = 'completed' THEN et.task_id END) * 100.0 / NULLIF(COUNT(DISTINCT et.task_id), 0), 2) AS completion_percentage
FROM ecotrace_events e
LEFT JOIN ecotrace_event_tasks et ON et.event_id = e.event_id
GROUP BY e.event_id, e.event_title, e.start_date, e.end_date, e.event_status, e.trees_per_student;

-- ============================================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- ============================================================================

-- Procedure: Create Plant Reservation
DELIMITER //
CREATE PROCEDURE sp_create_plant_reservation(
    IN p_plant_id INT UNSIGNED,
    IN p_student_id INT UNSIGNED,
    IN p_event_id INT UNSIGNED,
    IN p_duration_days INT
)
BEGIN
    DECLARE reservation_exists INT;
    
    -- Check if plant is already reserved
    SELECT COUNT(*) INTO reservation_exists
    FROM ecotrace_plant_reservations
    WHERE plant_id = p_plant_id 
        AND is_active = TRUE 
        AND expires_at > NOW();
    
    IF reservation_exists > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Plant is already reserved';
    END IF;
    
    -- Create reservation
    INSERT INTO ecotrace_plant_reservations (
        plant_id, 
        student_id, 
        event_id, 
        expires_at
    ) VALUES (
        p_plant_id,
        p_student_id,
        p_event_id,
        DATE_ADD(NOW(), INTERVAL p_duration_days DAY)
    );
END //
DELIMITER ;

-- Procedure: Submit Plant Verification
DELIMITER //
CREATE PROCEDURE sp_submit_plant_verification(
    IN p_plant_id INT UNSIGNED,
    IN p_student_id INT UNSIGNED,
    IN p_event_id INT UNSIGNED,
    IN p_health_status VARCHAR(20),
    IN p_plant_stage VARCHAR(20),
    IN p_height_cm DECIMAL(8, 2),
    IN p_circumference_cm DECIMAL(8, 2),
    IN p_notes TEXT
)
BEGIN
    DECLARE new_verification_id INT UNSIGNED;
    
    START TRANSACTION;
    
    -- Insert verification
    INSERT INTO ecotrace_plant_verifications (
        plant_id, student_id, event_id, health_status, plant_stage,
        height_cm, circumference_cm, verification_notes
    ) VALUES (
        p_plant_id, p_student_id, p_event_id, p_health_status, p_plant_stage,
        p_height_cm, p_circumference_cm, p_notes
    );
    
    SET new_verification_id = LAST_INSERT_ID();
    
    -- Update plant status and counters
    UPDATE ecotrace_plants
    SET plant_status = 'verified',
        verification_count = verification_count + 1,
        last_verified_at = NOW()
    WHERE plant_id = p_plant_id;
    
    -- Release any active reservations
    UPDATE ecotrace_plant_reservations
    SET is_active = FALSE,
        released_at = NOW()
    WHERE plant_id = p_plant_id 
        AND student_id = p_student_id
        AND is_active = TRUE;
    
    -- Update task status if applicable
    UPDATE ecotrace_event_tasks
    SET task_status = 'completed',
        completed_at = NOW()
    WHERE plant_id = p_plant_id 
        AND student_id = p_student_id
        AND event_id = p_event_id
        AND task_status != 'completed';
    
    COMMIT;
    
    SELECT new_verification_id AS verification_id;
END //
DELIMITER ;

-- Procedure: Clean Expired Reservations
DELIMITER //
CREATE PROCEDURE sp_clean_expired_reservations()
BEGIN
    UPDATE ecotrace_plant_reservations
    SET is_active = FALSE,
        released_at = NOW()
    WHERE is_active = TRUE 
        AND expires_at < NOW();
END //
DELIMITER ;

-- ============================================================================
-- TRIGGERS FOR DATA INTEGRITY
-- ============================================================================

-- Trigger: Update student last login
DELIMITER //
CREATE TRIGGER tr_update_student_login
BEFORE UPDATE ON ecotrace_students
FOR EACH ROW
BEGIN
    IF NEW.last_login_at != OLD.last_login_at THEN
        SET NEW.updated_at = CURRENT_TIMESTAMP;
    END IF;
END //
DELIMITER ;

-- Trigger: Validate event dates
DELIMITER //
CREATE TRIGGER tr_validate_event_dates
BEFORE INSERT ON ecotrace_events
FOR EACH ROW
BEGIN
    IF NEW.end_date < NEW.start_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Event end date must be after start date';
    END IF;
END //
DELIMITER ;

-- ============================================================================
-- SCHEDULED EVENTS FOR MAINTENANCE
-- ============================================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Event: Auto-expire reservations every hour
CREATE EVENT IF NOT EXISTS evt_expire_reservations
ON SCHEDULE EVERY 1 HOUR
DO
    CALL sp_clean_expired_reservations();

-- Event: Auto-complete past events daily
CREATE EVENT IF NOT EXISTS evt_complete_past_events
ON SCHEDULE EVERY 1 DAY
DO
    UPDATE ecotrace_events
    SET event_status = 'completed'
    WHERE event_status = 'active' 
        AND end_date < CURDATE();

-- ============================================================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================================================

-- Insert sample student
INSERT INTO ecotrace_students (full_name, email, password_hash, year_batch, is_active, email_verified) 
VALUES ('John Doe', 'john.doe@example.com', '$2y$10$samplehashedpassword', 2024, TRUE, TRUE);

-- Insert sample event
INSERT INTO ecotrace_events (event_title, event_description, start_date, end_date, trees_per_student, event_status)
VALUES ('Campus Greening 2024', 'Annual tree planting event', '2024-03-01', '2024-03-31', 5, 'active');

-- ============================================================================
-- MIGRATION NOTES
-- ============================================================================
-- 
-- Key Changes from Original Schema:
-- 
-- 1. NAMING STANDARDIZATION:
--    - All tables now use 'ecotrace_' prefix consistently
--    - Column names changed from camelCase to snake_case
--    - More descriptive field names (e.g., 'full_name' vs 'name')
--
-- 2. NORMALIZATION IMPROVEMENTS:
--    - Separated photos into dedicated table (1-to-many relationship)
--    - Added proper junction table for event-plant-student relationships
--    - Removed redundant photoUrl from verifications table
--
-- 3. DATA INTEGRITY:
--    - Added CHECK constraints for data validation
--    - Enhanced ENUM values with more granular options
--    - Added is_active flags for soft deletes
--    - Added audit timestamps consistently
--
-- 4. PERFORMANCE OPTIMIZATION:
--    - Added strategic indexes for common queries
--    - Created views for frequently accessed data
--    - Added stored procedures for complex operations
--
-- 5. NEW FEATURES:
--    - Plant species tracking
--    - Enhanced health monitoring fields
--    - Weather and environmental data
--    - Photo categorization
--    - Email verification status
--    - Draft event status
--
-- 6. REMOVED INCONSISTENCIES:
--    - Standardized table prefixes (ecotag vs ecotrace)
--    - Unified date/time field naming
--    - Consistent use of UNSIGNED for IDs
--
-- ============================================================================