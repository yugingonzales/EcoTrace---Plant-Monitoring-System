-- EcoTrace Database Setup Script
-- Creates all necessary tables

-- Students Table
CREATE TABLE IF NOT EXISTS ecotag_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    yearBatch INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events Table
CREATE TABLE IF NOT EXISTS ecotag_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    treeCountPerStudent INT DEFAULT 1,
    targetYearBatch INT,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Plants Table
CREATE TABLE IF NOT EXISTS ecotag_plants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    locationAddress VARCHAR(255),
    plantedDate DATE,
    status ENUM('verified', 'unverified', 'dead') DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coordinates (latitude, longitude)
);

-- Tasks Table
CREATE TABLE IF NOT EXISTS ecotrace_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    eventId INT NOT NULL,
    plantId INT NOT NULL,
    studentId INT NOT NULL,
    status ENUM('pending', 'reserved', 'verified') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (eventId) REFERENCES ecotag_events(id) ON DELETE CASCADE,
    FOREIGN KEY (plantId) REFERENCES ecotag_plants(id) ON DELETE CASCADE,
    FOREIGN KEY (studentId) REFERENCES ecotag_students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task (eventId, plantId, studentId)
);

-- Reservations Table
CREATE TABLE IF NOT EXISTS ecotrace_reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plantId INT NOT NULL,
    studentId INT NOT NULL,
    expiresAt DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plantId) REFERENCES ecotag_plants(id) ON DELETE CASCADE,
    FOREIGN KEY (studentId) REFERENCES ecotag_students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reservation (plantId, studentId)
);

-- Verifications Table
CREATE TABLE IF NOT EXISTS ecotrace_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plantId INT NOT NULL,
    studentId INT NOT NULL,
    eventId INT,
    healthStatus ENUM('healthy', 'damaged', 'dead') NOT NULL,
    heightCm DECIMAL(8, 2),
    circumferenceCm DECIMAL(8, 2),
    plantStage ENUM('seedling', 'sapling', 'tree') NOT NULL,
    photoUrl VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plantId) REFERENCES ecotag_plants(id) ON DELETE CASCADE,
    FOREIGN KEY (studentId) REFERENCES ecotag_students(id) ON DELETE CASCADE,
    FOREIGN KEY (eventId) REFERENCES ecotag_events(id) ON DELETE CASCADE
);

-- Photos Table
CREATE TABLE IF NOT EXISTS ecotrace_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    verificationId INT NOT NULL,
    photoUrl VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (verificationId) REFERENCES ecotrace_verifications(id) ON DELETE CASCADE
);

-- Create indexes for better query performance
CREATE INDEX idx_event_tasks ON ecotrace_tasks(eventId);
CREATE INDEX idx_student_tasks ON ecotrace_tasks(studentId);
CREATE INDEX idx_student_reservations ON ecotrace_reservations(studentId);
CREATE INDEX idx_student_verifications ON ecotrace_verifications(studentId);
CREATE INDEX idx_event_verifications ON ecotrace_verifications(eventId);