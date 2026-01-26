CREATE TABLE domain_caregiver_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_document_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_document_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_care_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_skill_level (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_contract_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_caregiver_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'active', 'Active'),
  (3, 'inactive', 'Inactive'),
  (4, 'blocked', 'Blocked');

INSERT INTO domain_document_type (id, code, label) VALUES
  (1, 'id', 'ID'),
  (2, 'cpf', 'CPF'),
  (3, 'address', 'Address'),
  (4, 'certificate', 'Certificate'),
  (5, 'other', 'Other');

INSERT INTO domain_document_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'verified', 'Verified'),
  (3, 'rejected', 'Rejected');

INSERT INTO domain_care_type (id, code, label) VALUES
  (1, 'idoso', 'Idoso'),
  (2, 'pcd', 'PCD'),
  (3, 'tea', 'TEA'),
  (4, 'pos_operatorio', 'Pos-operatorio');

INSERT INTO domain_skill_level (id, code, label) VALUES
  (1, 'basico', 'Basico'),
  (2, 'intermediario', 'Intermediario'),
  (3, 'avancado', 'Avancado');

INSERT INTO domain_contract_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'signed', 'Signed'),
  (3, 'active', 'Active'),
  (4, 'closed', 'Closed');

CREATE TABLE caregivers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  cpf VARCHAR(14) NULL UNIQUE,
  birth_date DATE NULL,
  email VARCHAR(255) NULL,
  city VARCHAR(128) NOT NULL,
  address_street VARCHAR(255) NULL,
  address_number VARCHAR(20) NULL,
  address_complement VARCHAR(100) NULL,
  address_neighborhood VARCHAR(128) NULL,
  address_zipcode VARCHAR(10) NULL,
  address_state VARCHAR(2) NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  experience_years INT UNSIGNED NOT NULL DEFAULT 0,
  profile_summary TEXT NULL,
  emergency_contact_name VARCHAR(255) NULL,
  emergency_contact_phone VARCHAR(32) NULL,
  recruitment_source VARCHAR(64) NULL,
  referred_by_caregiver_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_caregivers_status
    FOREIGN KEY (status_id) REFERENCES domain_caregiver_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  doc_type_id TINYINT UNSIGNED NOT NULL,
  file_url VARCHAR(512) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  verified_at DATETIME NULL,
  issued_at DATE NULL,
  expires_at DATE NULL,
  CONSTRAINT fk_caregiver_documents_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_documents_type
    FOREIGN KEY (doc_type_id) REFERENCES domain_document_type(id),
  CONSTRAINT fk_caregiver_documents_status
    FOREIGN KEY (status_id) REFERENCES domain_document_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_skills (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  care_type_id TINYINT UNSIGNED NOT NULL,
  level_id TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_caregiver_skills_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_skills_type
    FOREIGN KEY (care_type_id) REFERENCES domain_care_type(id),
  CONSTRAINT fk_caregiver_skills_level
    FOREIGN KEY (level_id) REFERENCES domain_skill_level(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_availability (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  CONSTRAINT fk_caregiver_availability_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_regions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  city VARCHAR(128) NOT NULL,
  neighborhood VARCHAR(128) NULL,
  CONSTRAINT fk_caregiver_regions_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_contracts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  contract_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  signed_at DATETIME NULL,
  CONSTRAINT fk_caregiver_contracts_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_contracts_status
    FOREIGN KEY (status_id) REFERENCES domain_contract_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_ratings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_caregiver_ratings_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_incident_severity (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  weight TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_leave_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_incident_severity (id, code, label, weight) VALUES
  (1, 'low', 'Leve', 1),
  (2, 'medium', 'Moderada', 2),
  (3, 'high', 'Grave', 3),
  (4, 'critical', 'Crítica', 5);

INSERT INTO domain_leave_type (id, code, label) VALUES
  (1, 'medical', 'Atestado Médico'),
  (2, 'vacation', 'Férias'),
  (3, 'personal', 'Licença Pessoal'),
  (4, 'maternity', 'Licença Maternidade'),
  (5, 'other', 'Outro');

CREATE TABLE caregiver_incidents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  incident_type VARCHAR(128) NOT NULL,
  severity_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NOT NULL,
  resolution_notes TEXT NULL,
  resolved_at DATETIME NULL,
  resolved_by VARCHAR(255) NULL,
  occurred_at DATETIME NOT NULL,
  CONSTRAINT fk_caregiver_incidents_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_incidents_severity
    FOREIGN KEY (severity_id) REFERENCES domain_incident_severity(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_training (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  course_name VARCHAR(255) NOT NULL,
  completed_at DATETIME NULL,
  CONSTRAINT fk_caregiver_training_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_status_history (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  changed_at DATETIME NOT NULL,
  CONSTRAINT fk_caregiver_status_history_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_status_history_status
    FOREIGN KEY (status_id) REFERENCES domain_caregiver_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_assignments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  started_at DATETIME NOT NULL,
  ended_at DATETIME NULL,
  hours_worked DECIMAL(6,2) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_caregiver_assignments_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_workload (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  week_start DATE NOT NULL,
  week_end DATE NOT NULL,
  hours_scheduled DECIMAL(6,2) NOT NULL DEFAULT 0,
  hours_worked DECIMAL(6,2) NOT NULL DEFAULT 0,
  hours_overtime DECIMAL(6,2) NOT NULL DEFAULT 0,
  assignments_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  clients_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_caregiver_workload_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  UNIQUE KEY uk_caregiver_workload_week (caregiver_id, week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_leaves (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  leave_type_id TINYINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NULL,
  document_url VARCHAR(512) NULL,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  approved_by VARCHAR(255) NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_caregiver_leaves_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id),
  CONSTRAINT fk_caregiver_leaves_type
    FOREIGN KEY (leave_type_id) REFERENCES domain_leave_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_references (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  relationship VARCHAR(128) NOT NULL,
  company VARCHAR(255) NULL,
  position VARCHAR(128) NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  verified_at DATETIME NULL,
  verification_notes TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_caregiver_references_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caregiver_settings (
  setting_key VARCHAR(64) PRIMARY KEY,
  value TEXT NOT NULL,
  description VARCHAR(255) NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_caregivers_phone_status
  ON caregivers (phone, status_id);

CREATE INDEX idx_caregivers_cpf
  ON caregivers (cpf);

CREATE INDEX idx_caregivers_city
  ON caregivers (city);

CREATE INDEX idx_caregiver_documents_expires
  ON caregiver_documents (expires_at);

CREATE INDEX idx_caregiver_documents_status
  ON caregiver_documents (caregiver_id, status_id);

CREATE INDEX idx_caregiver_skills_unique
  ON caregiver_skills (caregiver_id, care_type_id);

CREATE INDEX idx_caregiver_regions_city
  ON caregiver_regions (city);

CREATE INDEX idx_caregiver_availability_day
  ON caregiver_availability (caregiver_id, day_of_week);

CREATE INDEX idx_caregiver_contracts_status
  ON caregiver_contracts (caregiver_id, status_id);

CREATE UNIQUE INDEX uk_caregiver_rating_service
  ON caregiver_ratings (caregiver_id, service_id);

CREATE INDEX idx_caregiver_ratings_date
  ON caregiver_ratings (caregiver_id, created_at);

CREATE INDEX idx_caregiver_incidents_severity
  ON caregiver_incidents (caregiver_id, severity_id);

CREATE INDEX idx_caregiver_incidents_date
  ON caregiver_incidents (caregiver_id, occurred_at);

CREATE INDEX idx_caregiver_incidents_type
  ON caregiver_incidents (incident_type);

CREATE INDEX idx_caregiver_status_history_date
  ON caregiver_status_history (caregiver_id, changed_at);

CREATE INDEX idx_caregiver_assignments_caregiver
  ON caregiver_assignments (caregiver_id, started_at);

CREATE INDEX idx_caregiver_assignments_status
  ON caregiver_assignments (caregiver_id, status);

CREATE INDEX idx_caregiver_assignments_service
  ON caregiver_assignments (service_id);

CREATE INDEX idx_caregiver_workload_week
  ON caregiver_workload (week_start);

CREATE INDEX idx_caregiver_leaves_dates
  ON caregiver_leaves (caregiver_id, start_date, end_date);

CREATE INDEX idx_caregiver_leaves_period
  ON caregiver_leaves (start_date, end_date);
