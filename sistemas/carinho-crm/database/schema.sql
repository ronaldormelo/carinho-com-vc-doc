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

CREATE TABLE domain_lead_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_deal_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_contract_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_interaction_channel (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_patient_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_task_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_urgency_level (id, code, label) VALUES
  (1, 'hoje', 'Hoje'),
  (2, 'semana', 'Semana'),
  (3, 'sem_data', 'Sem data');

INSERT INTO domain_service_type (id, code, label) VALUES
  (1, 'horista', 'Horista'),
  (2, 'diario', 'Diario'),
  (3, 'mensal', 'Mensal');

INSERT INTO domain_lead_status (id, code, label) VALUES
  (1, 'new', 'New'),
  (2, 'triage', 'Triage'),
  (3, 'proposal', 'Proposal'),
  (4, 'active', 'Active'),
  (5, 'lost', 'Lost');

INSERT INTO domain_deal_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'won', 'Won'),
  (3, 'lost', 'Lost');

INSERT INTO domain_contract_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'signed', 'Signed'),
  (3, 'active', 'Active'),
  (4, 'closed', 'Closed');

INSERT INTO domain_interaction_channel (id, code, label) VALUES
  (1, 'whatsapp', 'WhatsApp'),
  (2, 'email', 'Email'),
  (3, 'phone', 'Phone');

INSERT INTO domain_patient_type (id, code, label) VALUES
  (1, 'idoso', 'Idoso'),
  (2, 'pcd', 'PCD'),
  (3, 'tea', 'TEA'),
  (4, 'pos_operatorio', 'Pos-operatorio');

INSERT INTO domain_task_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'done', 'Done'),
  (3, 'canceled', 'Canceled');

CREATE TABLE leads (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(255) NULL,
  city VARCHAR(128) NOT NULL,
  urgency_id TINYINT UNSIGNED NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  source VARCHAR(128) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  utm_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_leads_urgency
    FOREIGN KEY (urgency_id) REFERENCES domain_urgency_level(id),
  CONSTRAINT fk_leads_service
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id),
  CONSTRAINT fk_leads_status
    FOREIGN KEY (status_id) REFERENCES domain_lead_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clients (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  primary_contact VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(128) NOT NULL,
  preferences_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_clients_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE care_needs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  patient_type_id TINYINT UNSIGNED NOT NULL,
  conditions_json JSON NULL,
  notes TEXT NULL,
  CONSTRAINT fk_care_needs_client
    FOREIGN KEY (client_id) REFERENCES clients(id),
  CONSTRAINT fk_care_needs_patient
    FOREIGN KEY (patient_type_id) REFERENCES domain_patient_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pipeline_stages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  stage_order INT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE deals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  stage_id BIGINT UNSIGNED NOT NULL,
  value_estimated DECIMAL(12,2) NOT NULL DEFAULT 0,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_deals_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id),
  CONSTRAINT fk_deals_stage
    FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id),
  CONSTRAINT fk_deals_status
    FOREIGN KEY (status_id) REFERENCES domain_deal_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proposals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  deal_id BIGINT UNSIGNED NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  expires_at DATETIME NULL,
  CONSTRAINT fk_proposals_deal
    FOREIGN KEY (deal_id) REFERENCES deals(id),
  CONSTRAINT fk_proposals_service
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contracts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  proposal_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  signed_at DATETIME NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  CONSTRAINT fk_contracts_client
    FOREIGN KEY (client_id) REFERENCES clients(id),
  CONSTRAINT fk_contracts_proposal
    FOREIGN KEY (proposal_id) REFERENCES proposals(id),
  CONSTRAINT fk_contracts_status
    FOREIGN KEY (status_id) REFERENCES domain_contract_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  consent_type VARCHAR(64) NOT NULL,
  granted_at DATETIME NOT NULL,
  source VARCHAR(64) NOT NULL,
  CONSTRAINT fk_consents_client
    FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tasks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  due_at DATETIME NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  notes TEXT NULL,
  CONSTRAINT fk_tasks_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id),
  CONSTRAINT fk_tasks_status
    FOREIGN KEY (status_id) REFERENCES domain_task_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE interactions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  summary TEXT NOT NULL,
  occurred_at DATETIME NOT NULL,
  CONSTRAINT fk_interactions_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id),
  CONSTRAINT fk_interactions_channel
    FOREIGN KEY (channel_id) REFERENCES domain_interaction_channel(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE loss_reasons (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(128) NOT NULL,
  details TEXT NULL,
  CONSTRAINT fk_loss_reasons_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_leads_phone_status_city
  ON leads (phone, status_id, city);

CREATE INDEX idx_deals_stage_status
  ON deals (stage_id, status_id);

CREATE INDEX idx_interactions_lead_time
  ON interactions (lead_id, occurred_at);
