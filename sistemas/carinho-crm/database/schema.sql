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
  phone TEXT NOT NULL COMMENT 'Criptografado',
  email TEXT NULL COMMENT 'Criptografado',
  city VARCHAR(128) NOT NULL,
  urgency_id TINYINT UNSIGNED NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  source VARCHAR(128) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  utm_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_leads_urgency
    FOREIGN KEY (urgency_id) REFERENCES domain_urgency_level(id) ON DELETE RESTRICT,
  CONSTRAINT fk_leads_service
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id) ON DELETE RESTRICT,
  CONSTRAINT fk_leads_status
    FOREIGN KEY (status_id) REFERENCES domain_lead_status(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at DATETIME NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sessions (
  id VARCHAR(255) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE jobs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE job_batches (
  id VARCHAR(255) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  total_jobs INT NOT NULL,
  pending_jobs INT NOT NULL,
  failed_jobs INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options MEDIUMTEXT NULL,
  cancelled_at INT NULL,
  created_at INT NOT NULL,
  finished_at INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE failed_jobs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  uuid VARCHAR(255) NOT NULL UNIQUE,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload LONGTEXT NOT NULL,
  exception LONGTEXT NOT NULL,
  failed_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT NULL,
  last_used_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clients (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  primary_contact VARCHAR(255) NOT NULL,
  phone TEXT NOT NULL COMMENT 'Criptografado',
  address TEXT NULL COMMENT 'Criptografado',
  city VARCHAR(128) NOT NULL,
  preferences_json JSON NULL,
  classification_id TINYINT UNSIGNED NULL,
  financial_contact_name VARCHAR(255) NULL,
  financial_contact_phone TEXT NULL COMMENT 'Criptografado',
  financial_contact_email TEXT NULL COMMENT 'Criptografado',
  financial_contact_cpf_cnpj VARCHAR(20) NULL,
  emergency_contact_name VARCHAR(255) NULL,
  emergency_contact_phone TEXT NULL COMMENT 'Criptografado',
  emergency_contact_relationship VARCHAR(64) NULL,
  review_frequency_id TINYINT UNSIGNED NULL,
  next_review_date DATE NULL,
  last_review_date DATE NULL,
  referred_by_client_id BIGINT UNSIGNED NULL,
  referral_source VARCHAR(128) NULL,
  internal_notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_clients_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_clients_classification
    FOREIGN KEY (classification_id) REFERENCES domain_client_classification(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_review_frequency
    FOREIGN KEY (review_frequency_id) REFERENCES domain_review_frequency(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_referred_by
    FOREIGN KEY (referred_by_client_id) REFERENCES clients(id) ON DELETE SET NULL,
  UNIQUE KEY uk_clients_lead (lead_id)
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

INSERT INTO pipeline_stages (id, name, stage_order, active) VALUES
  (1, 'Novo Lead', 1, 1),
  (2, 'Primeiro Contato', 2, 1),
  (3, 'Entendimento', 3, 1),
  (4, 'Proposta Enviada', 4, 1),
  (5, 'Negociação', 5, 1),
  (6, 'Fechamento', 6, 1);

CREATE TABLE deals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  stage_id BIGINT UNSIGNED NOT NULL,
  value_estimated DECIMAL(12,2) NOT NULL DEFAULT 0,
  probability TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Probabilidade de fechamento em %: 10, 25, 50, 75, 90',
  weighted_value DECIMAL(12,2) GENERATED ALWAYS AS (value_estimated * probability / 100) STORED COMMENT 'Valor ponderado para forecast',
  expected_close_date DATE NULL COMMENT 'Data prevista de fechamento',
  next_action VARCHAR(255) NULL COMMENT 'Próximo passo',
  next_action_date DATE NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_deals_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_deals_stage
    FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE RESTRICT,
  CONSTRAINT fk_deals_status
    FOREIGN KEY (status_id) REFERENCES domain_deal_status(id) ON DELETE RESTRICT
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
  renewal_alert_days SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Dias antes do vencimento para alertar',
  last_renewal_alert_at DATE NULL COMMENT 'Data do último alerta enviado',
  auto_renewal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Renovação automática',
  renewal_count SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de renovações realizadas',
  original_contract_id BIGINT UNSIGNED NULL COMMENT 'Contrato original para rastrear renovações',
  CONSTRAINT fk_contracts_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_contracts_proposal
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contracts_status
    FOREIGN KEY (status_id) REFERENCES domain_contract_status(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contracts_original
    FOREIGN KEY (original_contract_id) REFERENCES contracts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  consent_type VARCHAR(64) NOT NULL,
  granted_at DATETIME NOT NULL,
  source VARCHAR(64) NOT NULL,
  CONSTRAINT fk_consents_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY uk_consents_client_type (client_id, consent_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tasks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  due_at DATETIME NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  notes TEXT NULL,
  CONSTRAINT fk_tasks_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_assigned
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_tasks_status
    FOREIGN KEY (status_id) REFERENCES domain_task_status(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE interactions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  summary TEXT NOT NULL,
  occurred_at DATETIME NOT NULL,
  CONSTRAINT fk_interactions_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_interactions_channel
    FOREIGN KEY (channel_id) REFERENCES domain_interaction_channel(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE loss_reasons (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(128) NOT NULL,
  details TEXT NULL,
  CONSTRAINT fk_loss_reasons_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  UNIQUE KEY uk_loss_reasons_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_client_classification (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  description VARCHAR(255) NULL,
  priority TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordem de prioridade para ordenação'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_event_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  category VARCHAR(32) NOT NULL COMMENT 'Categoria: commercial, operational, financial, communication'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_review_frequency (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  days SMALLINT UNSIGNED NOT NULL COMMENT 'Intervalo em dias'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_client_classification (id, code, label, description, priority) VALUES
  (1, 'A', 'Cliente A', 'Alto valor/potencial - Prioridade máxima', 1),
  (2, 'B', 'Cliente B', 'Valor médio - Atenção regular', 2),
  (3, 'C', 'Cliente C', 'Valor baixo - Atendimento padrão', 3);

INSERT INTO domain_event_type (id, code, label, category) VALUES
  (1, 'lead_created', 'Lead Criado', 'commercial'),
  (2, 'lead_qualified', 'Lead Qualificado', 'commercial'),
  (3, 'proposal_sent', 'Proposta Enviada', 'commercial'),
  (4, 'proposal_accepted', 'Proposta Aceita', 'commercial'),
  (5, 'proposal_rejected', 'Proposta Recusada', 'commercial'),
  (6, 'deal_won', 'Negócio Fechado', 'commercial'),
  (7, 'deal_lost', 'Negócio Perdido', 'commercial'),
  (10, 'client_created', 'Cliente Cadastrado', 'operational'),
  (11, 'contract_created', 'Contrato Criado', 'operational'),
  (12, 'contract_signed', 'Contrato Assinado', 'operational'),
  (13, 'contract_activated', 'Contrato Ativado', 'operational'),
  (14, 'contract_renewed', 'Contrato Renovado', 'operational'),
  (15, 'contract_closed', 'Contrato Encerrado', 'operational'),
  (16, 'review_scheduled', 'Revisão Agendada', 'operational'),
  (17, 'review_completed', 'Revisão Realizada', 'operational'),
  (20, 'payment_received', 'Pagamento Recebido', 'financial'),
  (21, 'payment_overdue', 'Pagamento em Atraso', 'financial'),
  (22, 'invoice_sent', 'Fatura Enviada', 'financial'),
  (30, 'contact_whatsapp', 'Contato WhatsApp', 'communication'),
  (31, 'contact_phone', 'Contato Telefone', 'communication'),
  (32, 'contact_email', 'Contato E-mail', 'communication'),
  (33, 'complaint', 'Reclamação Registrada', 'communication'),
  (34, 'feedback_positive', 'Feedback Positivo', 'communication'),
  (35, 'feedback_negative', 'Feedback Negativo', 'communication'),
  (36, 'referral_made', 'Indicação Realizada', 'communication');

INSERT INTO domain_review_frequency (id, code, label, days) VALUES
  (1, 'monthly', 'Mensal', 30),
  (2, 'bimonthly', 'Bimestral', 60),
  (3, 'quarterly', 'Trimestral', 90),
  (4, 'semiannual', 'Semestral', 180),
  (5, 'annual', 'Anual', 365);

CREATE TABLE client_events (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  event_type_id TINYINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  metadata JSON NULL COMMENT 'Dados adicionais do evento em JSON',
  related_id BIGINT UNSIGNED NULL COMMENT 'ID da entidade relacionada (deal, contract, etc)',
  related_type VARCHAR(64) NULL COMMENT 'Tipo da entidade relacionada',
  created_by BIGINT UNSIGNED NULL COMMENT 'Usuário que criou o evento',
  occurred_at DATETIME NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_client_events_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_events_type
    FOREIGN KEY (event_type_id) REFERENCES domain_event_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE client_reviews (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  review_date DATE NOT NULL,
  satisfaction_score TINYINT UNSIGNED NULL COMMENT 'Nota de satisfação 1-5',
  service_quality_score TINYINT UNSIGNED NULL COMMENT 'Nota de qualidade do serviço 1-5',
  contract_renewal_intent TINYINT(1) NULL COMMENT 'Cliente pretende renovar?',
  observations TEXT NULL,
  action_items TEXT NULL COMMENT 'Ações identificadas na revisão',
  next_review_date DATE NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_client_reviews_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE client_referrals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  referrer_client_id BIGINT UNSIGNED NOT NULL COMMENT 'Cliente que indicou',
  referred_lead_id BIGINT UNSIGNED NULL COMMENT 'Lead indicado',
  referred_client_id BIGINT UNSIGNED NULL COMMENT 'Cliente convertido da indicação',
  referred_name VARCHAR(255) NOT NULL,
  referred_phone VARCHAR(32) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending, contacted, converted, lost',
  notes TEXT NULL,
  converted_at DATE NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_client_referrals_referrer
    FOREIGN KEY (referrer_client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_referrals_lead
    FOREIGN KEY (referred_lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_client_referrals_client
    FOREIGN KEY (referred_client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_sessions_user
  ON sessions (user_id);

CREATE INDEX idx_sessions_last_activity
  ON sessions (last_activity);

CREATE INDEX idx_jobs_queue
  ON jobs (queue);

CREATE INDEX idx_personal_access_tokens_tokenable
  ON personal_access_tokens (tokenable_type, tokenable_id);

CREATE INDEX idx_leads_status_city
  ON leads (status_id, city);

CREATE INDEX idx_leads_created
  ON leads (created_at);

CREATE INDEX idx_leads_source
  ON leads (source);

CREATE INDEX idx_clients_city
  ON clients (city);

CREATE INDEX idx_clients_classification
  ON clients (classification_id);

CREATE INDEX idx_clients_next_review
  ON clients (next_review_date);

CREATE INDEX idx_care_needs_patient_type
  ON care_needs (patient_type_id);

CREATE INDEX idx_pipeline_order
  ON pipeline_stages (stage_order, active);

CREATE INDEX idx_deals_stage_status
  ON deals (stage_id, status_id);

CREATE INDEX idx_deals_lead
  ON deals (lead_id);

CREATE INDEX idx_deals_created
  ON deals (created_at);

CREATE INDEX idx_deals_expected_close
  ON deals (expected_close_date);

CREATE INDEX idx_deals_probability
  ON deals (probability);

CREATE INDEX idx_proposals_deal
  ON proposals (deal_id);

CREATE INDEX idx_proposals_expires
  ON proposals (expires_at);

CREATE INDEX idx_contracts_client_status
  ON contracts (client_id, status_id);

CREATE INDEX idx_contracts_expiring
  ON contracts (end_date, status_id);

CREATE INDEX idx_consents_client_type
  ON consents (client_id, consent_type);

CREATE INDEX idx_tasks_assignee
  ON tasks (assigned_to, status_id, due_at);

CREATE INDEX idx_tasks_lead
  ON tasks (lead_id, status_id);

CREATE INDEX idx_tasks_due
  ON tasks (due_at, status_id);

CREATE INDEX idx_interactions_lead_time
  ON interactions (lead_id, occurred_at);

CREATE INDEX idx_interactions_channel_time
  ON interactions (channel_id, occurred_at);

CREATE INDEX idx_loss_reasons_reason
  ON loss_reasons (reason);

CREATE INDEX idx_client_events_timeline
  ON client_events (client_id, occurred_at);

CREATE INDEX idx_client_events_type
  ON client_events (event_type_id);

CREATE INDEX idx_client_events_related
  ON client_events (related_id, related_type);

CREATE INDEX idx_client_reviews_date
  ON client_reviews (client_id, review_date);

CREATE INDEX idx_referrals_status
  ON client_referrals (referrer_client_id, status);
