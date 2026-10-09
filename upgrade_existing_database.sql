USE workshop_registration;

-- Safe to run on the existing database. Does not delete or recreate users,
-- workshops, or registrations.
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

-- Add demo workshops only if their unique codes are not already present.
INSERT IGNORE INTO workshops (code, title, instructor, start_at, capacity, status) VALUES
('POT-101', 'Introduction to Pottery', 'Maya Perera', DATE_ADD(NOW(), INTERVAL 3 DAY), 12, 'scheduled'),
('COD-201', 'Web Development Basics', 'Nimal Fernando', DATE_ADD(NOW(), INTERVAL 5 DAY), 20, 'scheduled'),
('FIT-110', 'Weekend Fitness Session', 'Ayesha Silva', DATE_ADD(NOW(), INTERVAL 7 DAY), 15, 'scheduled');
