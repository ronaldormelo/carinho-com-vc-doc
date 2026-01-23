CREATE TABLE domain_doc_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_owner_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_document_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_signer_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_signature_method (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_access_action (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_request_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_request_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_consent_subject_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_doc_type (id, code, label) VALUES
  (1, 'contrato_cliente', 'Contrato cliente'),
  (2, 'contrato_cuidador', 'Contrato cuidador'),
  (3, 'termos', 'Termos'),
  (4, 'privacidade', 'Privacidade');

INSERT INTO domain_owner_type (id, code, label) VALUES
  (1, 'client', 'Client'),
  (2, 'caregiver', 'Caregiver'),
  (3, 'company', 'Company');

INSERT INTO domain_document_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'signed', 'Signed'),
  (3, 'archived', 'Archived');

INSERT INTO domain_signer_type (id, code, label) VALUES
  (1, 'client', 'Client'),
  (2, 'caregiver', 'Caregiver'),
  (3, 'company', 'Company');

INSERT INTO domain_signature_method (id, code, label) VALUES
  (1, 'otp', 'OTP'),
  (2, 'click', 'Click'),
  (3, 'certificate', 'Certificate');

INSERT INTO domain_access_action (id, code, label) VALUES
  (1, 'view', 'View'),
  (2, 'download', 'Download'),
  (3, 'sign', 'Sign'),
  (4, 'delete', 'Delete');

INSERT INTO domain_request_type (id, code, label) VALUES
  (1, 'export', 'Export'),
  (2, 'delete', 'Delete'),
  (3, 'update', 'Update');

INSERT INTO domain_request_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'in_progress', 'In progress'),
  (3, 'done', 'Done'),
  (4, 'rejected', 'Rejected');

INSERT INTO domain_consent_subject_type (id, code, label) VALUES
  (1, 'client', 'Client'),
  (2, 'caregiver', 'Caregiver');

CREATE TABLE document_templates (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  doc_type_id TINYINT UNSIGNED NOT NULL,
  version VARCHAR(32) NOT NULL,
  content LONGTEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_document_templates_type
    FOREIGN KEY (doc_type_id) REFERENCES domain_doc_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  owner_type_id TINYINT UNSIGNED NOT NULL,
  owner_id BIGINT UNSIGNED NOT NULL,
  template_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_documents_owner_type
    FOREIGN KEY (owner_type_id) REFERENCES domain_owner_type(id),
  CONSTRAINT fk_documents_template
    FOREIGN KEY (template_id) REFERENCES document_templates(id),
  CONSTRAINT fk_documents_status
    FOREIGN KEY (status_id) REFERENCES domain_document_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE document_versions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT UNSIGNED NOT NULL,
  version VARCHAR(32) NOT NULL,
  file_url VARCHAR(512) NOT NULL,
  checksum VARCHAR(128) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_document_versions_document
    FOREIGN KEY (document_id) REFERENCES documents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE signatures (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT UNSIGNED NOT NULL,
  signer_type_id TINYINT UNSIGNED NOT NULL,
  signer_id BIGINT UNSIGNED NOT NULL,
  signed_at DATETIME NOT NULL,
  method_id TINYINT UNSIGNED NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  CONSTRAINT fk_signatures_document
    FOREIGN KEY (document_id) REFERENCES documents(id),
  CONSTRAINT fk_signatures_signer
    FOREIGN KEY (signer_type_id) REFERENCES domain_signer_type(id),
  CONSTRAINT fk_signatures_method
    FOREIGN KEY (method_id) REFERENCES domain_signature_method(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  subject_type_id TINYINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  consent_type VARCHAR(64) NOT NULL,
  granted_at DATETIME NOT NULL,
  source VARCHAR(64) NOT NULL,
  ip_address VARCHAR(64) NULL COMMENT 'Endereco IP no momento do registro',
  user_agent VARCHAR(512) NULL COMMENT 'User agent do navegador/app',
  revoked_at DATETIME NULL,
  revocation_reason VARCHAR(64) NULL COMMENT 'Motivo formal da revogacao',
  revocation_source VARCHAR(64) NULL COMMENT 'Canal/sistema de origem da revogacao',
  CONSTRAINT fk_consents_subject
    FOREIGN KEY (subject_type_id) REFERENCES domain_consent_subject_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_consents_revocation
  ON consents (revoked_at, revocation_reason);

CREATE TABLE access_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  document_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  action_id TINYINT UNSIGNED NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_access_logs_document
    FOREIGN KEY (document_id) REFERENCES documents(id),
  CONSTRAINT fk_access_logs_action
    FOREIGN KEY (action_id) REFERENCES domain_access_action(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE retention_policies (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  doc_type_id TINYINT UNSIGNED NOT NULL,
  retention_days INT UNSIGNED NOT NULL,
  CONSTRAINT fk_retention_policies_type
    FOREIGN KEY (doc_type_id) REFERENCES domain_doc_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE data_requests (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  subject_type_id TINYINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  request_type_id TINYINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  requested_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  CONSTRAINT fk_data_requests_subject
    FOREIGN KEY (subject_type_id) REFERENCES domain_consent_subject_type(id),
  CONSTRAINT fk_data_requests_type
    FOREIGN KEY (request_type_id) REFERENCES domain_request_type(id),
  CONSTRAINT fk_data_requests_status
    FOREIGN KEY (status_id) REFERENCES domain_request_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_documents_owner
  ON documents (owner_type_id, owner_id);

CREATE INDEX idx_signatures_document_time
  ON signatures (document_id, signed_at);

CREATE INDEX idx_access_logs_document_time
  ON access_logs (document_id, created_at);
