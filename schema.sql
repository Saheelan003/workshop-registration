CREATE DATABASE IF NOT EXISTS workshop_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE workshop_registration;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','manager','staff') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS workshops (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  instructor VARCHAR(100) NOT NULL,
  start_at DATETIME NOT NULL,
  capacity INT UNSIGNED NOT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_workshop_capacity CHECK (capacity > 0),
  INDEX idx_workshop_start_status (start_at, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workshop_id INT UNSIGNED NOT NULL,
  attendee_name VARCHAR(100) NOT NULL,
  attendee_email VARCHAR(150) NOT NULL,
  status ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  registered_by INT UNSIGNED NOT NULL,
  registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_by INT UNSIGNED NULL,
  cancelled_at TIMESTAMP NULL,
  CONSTRAINT fk_registration_workshop FOREIGN KEY (workshop_id) REFERENCES workshops(id),
  CONSTRAINT fk_registration_registered_by FOREIGN KEY (registered_by) REFERENCES users(id),
  CONSTRAINT fk_registration_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id),
  INDEX idx_workshop_status (workshop_id, status),
  INDEX idx_registered_at (registered_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT UNSIGNED NULL,
  details JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

-- Optional sample workshops. Run after schema creation; workshop codes must be unique.
INSERT IGNORE INTO workshops (code,title,instructor,start_at,capacity,status) VALUES
('POT-101','Introduction to Pottery','Maya Perera',DATE_ADD(NOW(), INTERVAL 3 DAY),12,'scheduled'),
('COD-201','Web Development Basics','Nimal Fernando',DATE_ADD(NOW(), INTERVAL 5 DAY),20,'scheduled'),
('FIT-110','Weekend Fitness Session','Ayesha Silva',DATE_ADD(NOW(), INTERVAL 7 DAY),15,'scheduled');
