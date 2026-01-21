CREATE TABLE domain_page_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_form_target (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_urgency_level (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_service_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_legal_doc_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_page_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'published', 'Published'),
  (3, 'archived', 'Archived');

INSERT INTO domain_form_target (id, code, label) VALUES
  (1, 'cliente', 'Cliente'),
  (2, 'cuidador', 'Cuidador');

INSERT INTO domain_urgency_level (id, code, label) VALUES
  (1, 'hoje', 'Hoje'),
  (2, 'semana', 'Semana'),
  (3, 'sem_data', 'Sem data');

INSERT INTO domain_service_type (id, code, label) VALUES
  (1, 'horista', 'Horista'),
  (2, 'diario', 'Diario'),
  (3, 'mensal', 'Mensal');

INSERT INTO domain_legal_doc_type (id, code, label) VALUES
  (1, 'privacy', 'Privacy'),
  (2, 'terms', 'Terms');

CREATE TABLE site_pages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  seo_title VARCHAR(255) NULL,
  seo_description VARCHAR(512) NULL,
  content_json JSON NOT NULL,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_site_pages_status
    FOREIGN KEY (status_id) REFERENCES domain_page_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE page_sections (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  page_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(64) NOT NULL,
  content_json JSON NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_page_sections_page
    FOREIGN KEY (page_id) REFERENCES site_pages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE media_assets (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(128) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  storage_path VARCHAR(512) NOT NULL,
  checksum VARCHAR(128) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lead_forms (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  target_type_id TINYINT UNSIGNED NOT NULL,
  fields_json JSON NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_lead_forms_target
    FOREIGN KEY (target_type_id) REFERENCES domain_form_target(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE utm_campaigns (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  source VARCHAR(128) NOT NULL,
  medium VARCHAR(128) NOT NULL,
  campaign VARCHAR(128) NOT NULL,
  content VARCHAR(128) NULL,
  term VARCHAR(128) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE form_submissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  form_id BIGINT UNSIGNED NOT NULL,
  utm_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(255) NULL,
  city VARCHAR(128) NOT NULL,
  urgency_id TINYINT UNSIGNED NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  consent_at DATETIME NULL,
  payload_json JSON NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_form_submissions_form
    FOREIGN KEY (form_id) REFERENCES lead_forms(id),
  CONSTRAINT fk_form_submissions_utm
    FOREIGN KEY (utm_id) REFERENCES utm_campaigns(id),
  CONSTRAINT fk_form_submissions_urgency
    FOREIGN KEY (urgency_id) REFERENCES domain_urgency_level(id),
  CONSTRAINT fk_form_submissions_service
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE legal_documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  doc_type_id TINYINT UNSIGNED NOT NULL,
  version VARCHAR(32) NOT NULL,
  content LONGTEXT NOT NULL,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_legal_documents_type
    FOREIGN KEY (doc_type_id) REFERENCES domain_legal_doc_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE site_settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  setting_key VARCHAR(190) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE redirects (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  from_path VARCHAR(255) NOT NULL UNIQUE,
  to_url VARCHAR(512) NOT NULL,
  status_code INT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_form_submissions_phone_created
  ON form_submissions (phone, created_at);

CREATE INDEX idx_utm_campaigns_source_campaign
  ON utm_campaigns (source, campaign);
