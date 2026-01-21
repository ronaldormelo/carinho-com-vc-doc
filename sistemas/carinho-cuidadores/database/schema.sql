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
  email VARCHAR(255) NULL,
  city VARCHAR(128) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  experience_years INT UNSIGNED NOT NULL DEFAULT 0,
  profile_summary TEXT NULL,
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

CREATE TABLE caregiver_incidents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  incident_type VARCHAR(128) NOT NULL,
  notes TEXT NOT NULL,
  occurred_at DATETIME NOT NULL,
  CONSTRAINT fk_caregiver_incidents_caregiver
    FOREIGN KEY (caregiver_id) REFERENCES caregivers(id)
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

CREATE INDEX idx_caregivers_phone_status
  ON caregivers (phone, status_id);

CREATE INDEX idx_caregiver_regions_city
  ON caregiver_regions (city);

CREATE INDEX idx_caregiver_availability_day
  ON caregiver_availability (caregiver_id, day_of_week);
