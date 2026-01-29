CREATE TABLE domain_channel (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_conversation_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_priority (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_message_direction (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_message_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_agent_role (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_incident_severity (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_webhook_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_support_level (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  escalation_minutes INT UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_loss_reason (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_incident_category (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_action_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_channel (id, code, label) VALUES
  (1, 'whatsapp', 'WhatsApp'),
  (2, 'email', 'Email'),
  (3, 'phone', 'Telefone');

INSERT INTO domain_conversation_status (id, code, label) VALUES
  (1, 'new', 'New'),
  (2, 'triage', 'Triage'),
  (3, 'proposal', 'Proposal'),
  (4, 'waiting', 'Waiting'),
  (5, 'active', 'Active'),
  (6, 'lost', 'Lost'),
  (7, 'closed', 'Closed');

INSERT INTO domain_priority (id, code, label) VALUES
  (1, 'low', 'Low'),
  (2, 'normal', 'Normal'),
  (3, 'high', 'High'),
  (4, 'urgent', 'Urgent');

INSERT INTO domain_message_direction (id, code, label) VALUES
  (1, 'inbound', 'Inbound'),
  (2, 'outbound', 'Outbound');

INSERT INTO domain_message_status (id, code, label) VALUES
  (1, 'queued', 'Queued'),
  (2, 'sent', 'Sent'),
  (3, 'delivered', 'Delivered'),
  (4, 'failed', 'Failed');

INSERT INTO domain_agent_role (id, code, label) VALUES
  (1, 'agent', 'Agent'),
  (2, 'supervisor', 'Supervisor'),
  (3, 'admin', 'Admin');

INSERT INTO domain_incident_severity (id, code, label) VALUES
  (1, 'low', 'Low'),
  (2, 'medium', 'Medium'),
  (3, 'high', 'High'),
  (4, 'critical', 'Critical');

INSERT INTO domain_webhook_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'processed', 'Processed'),
  (3, 'failed', 'Failed');

INSERT INTO domain_support_level (id, code, label, escalation_minutes) VALUES
  (1, 'n1', 'Nivel 1 - Atendimento', 15),
  (2, 'n2', 'Nivel 2 - Supervisao', 30),
  (3, 'n3', 'Nivel 3 - Gestao', 60);

INSERT INTO domain_loss_reason (id, code, label) VALUES
  (1, 'price', 'Preco acima do orcamento'),
  (2, 'competitor', 'Escolheu concorrente'),
  (3, 'no_response', 'Sem retorno do cliente'),
  (4, 'no_availability', 'Sem disponibilidade de cuidador'),
  (5, 'region', 'Regiao nao atendida'),
  (6, 'requirements', 'Requisitos nao atendidos'),
  (7, 'postponed', 'Cliente adiou a decisao'),
  (8, 'other', 'Outro motivo');

INSERT INTO domain_incident_category (id, code, label) VALUES
  (1, 'complaint', 'Reclamacao'),
  (2, 'delay', 'Atraso no atendimento'),
  (3, 'quality', 'Qualidade do servico'),
  (4, 'communication', 'Falha de comunicacao'),
  (5, 'billing', 'Problema de cobranca'),
  (6, 'caregiver', 'Problema com cuidador'),
  (7, 'emergency', 'Emergencia'),
  (8, 'suggestion', 'Sugestao'),
  (9, 'other', 'Outros');

INSERT INTO domain_action_type (id, code, label) VALUES
  (1, 'status_change', 'Mudanca de status'),
  (2, 'priority_change', 'Mudanca de prioridade'),
  (3, 'assignment', 'Atribuicao de atendente'),
  (4, 'escalation', 'Escalonamento'),
  (5, 'note', 'Anotacao interna'),
  (6, 'tag', 'Adicao de etiqueta'),
  (7, 'incident', 'Registro de incidente'),
  (8, 'closure', 'Encerramento');

CREATE TABLE contacts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL UNIQUE,
  email VARCHAR(255) NULL,
  city VARCHAR(128) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE agents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  role_id TINYINT UNSIGNED NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_agents_role
    FOREIGN KEY (role_id) REFERENCES domain_agent_role(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  contact_id BIGINT UNSIGNED NOT NULL,
  channel_id TINYINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  priority_id TINYINT UNSIGNED NOT NULL,
  support_level_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  assigned_to BIGINT UNSIGNED NULL,
  loss_reason_id TINYINT UNSIGNED NULL,
  loss_notes TEXT NULL,
  started_at DATETIME NULL,
  closed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_conversations_contact
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
  CONSTRAINT fk_conversations_channel
    FOREIGN KEY (channel_id) REFERENCES domain_channel(id),
  CONSTRAINT fk_conversations_status
    FOREIGN KEY (status_id) REFERENCES domain_conversation_status(id),
  CONSTRAINT fk_conversations_priority
    FOREIGN KEY (priority_id) REFERENCES domain_priority(id),
  CONSTRAINT fk_conversations_support_level
    FOREIGN KEY (support_level_id) REFERENCES domain_support_level(id),
  CONSTRAINT fk_conversations_loss_reason
    FOREIGN KEY (loss_reason_id) REFERENCES domain_loss_reason(id),
  CONSTRAINT fk_conversations_agent
    FOREIGN KEY (assigned_to) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  direction_id TINYINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  media_url VARCHAR(512) NULL,
  sent_at DATETIME NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_messages_direction
    FOREIGN KEY (direction_id) REFERENCES domain_message_direction(id),
  CONSTRAINT fk_messages_status
    FOREIGN KEY (status_id) REFERENCES domain_message_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tags (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversation_tags (
  conversation_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (conversation_id, tag_id),
  CONSTRAINT fk_conversation_tags_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_tags_tag
    FOREIGN KEY (tag_id) REFERENCES tags(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE message_templates (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  template_key VARCHAR(64) NOT NULL UNIQUE,
  body TEXT NOT NULL,
  language VARCHAR(16) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auto_rules (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  trigger_key VARCHAR(64) NOT NULL,
  template_id BIGINT UNSIGNED NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_auto_rules_template
    FOREIGN KEY (template_id) REFERENCES message_templates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sla_metrics (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  first_response_at DATETIME NULL,
  response_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
  resolved_at DATETIME NULL,
  CONSTRAINT fk_sla_metrics_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE incidents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  severity_id TINYINT UNSIGNED NOT NULL,
  category_id TINYINT UNSIGNED NOT NULL DEFAULT 9,
  notes TEXT NULL,
  resolution TEXT NULL,
  resolved_at DATETIME NULL,
  resolved_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_incidents_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_incidents_severity
    FOREIGN KEY (severity_id) REFERENCES domain_incident_severity(id),
  CONSTRAINT fk_incidents_category
    FOREIGN KEY (category_id) REFERENCES domain_incident_category(id),
  CONSTRAINT fk_incidents_resolved_by
    FOREIGN KEY (resolved_by) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE webhook_events (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  provider VARCHAR(64) NOT NULL,
  event_type VARCHAR(128) NOT NULL,
  payload_json JSON NOT NULL,
  received_at DATETIME NOT NULL,
  processed_at DATETIME NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_webhook_events_status
    FOREIGN KEY (status_id) REFERENCES domain_webhook_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversation_history (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  action_type_id TINYINT UNSIGNED NOT NULL,
  agent_id BIGINT UNSIGNED NULL,
  old_value VARCHAR(255) NULL,
  new_value VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_conversation_history_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_history_action
    FOREIGN KEY (action_type_id) REFERENCES domain_action_type(id),
  CONSTRAINT fk_conversation_history_agent
    FOREIGN KEY (agent_id) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sla_targets (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  priority_id TINYINT UNSIGNED NOT NULL,
  first_response_minutes INT UNSIGNED NOT NULL,
  resolution_minutes INT UNSIGNED NOT NULL,
  CONSTRAINT fk_sla_targets_priority
    FOREIGN KEY (priority_id) REFERENCES domain_priority(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sla_targets (priority_id, first_response_minutes, resolution_minutes) VALUES
  (1, 60, 480),
  (2, 30, 240),
  (3, 15, 120),
  (4, 5, 60);

CREATE TABLE triage_checklist (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_key VARCHAR(64) NOT NULL,
  item_label VARCHAR(255) NOT NULL,
  item_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  required TINYINT(1) NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO triage_checklist (item_key, item_label, item_order, required, active) VALUES
  ('patient_name', 'Nome do paciente', 1, 1, 1),
  ('patient_age', 'Idade do paciente', 2, 1, 1),
  ('care_type', 'Tipo de cuidado necessario', 3, 1, 1),
  ('location', 'Cidade/bairro do atendimento', 4, 1, 1),
  ('schedule', 'Horario/turno desejado', 5, 1, 1),
  ('start_date', 'Data de inicio pretendida', 6, 1, 1),
  ('special_needs', 'Necessidades especiais', 7, 0, 1),
  ('budget', 'Expectativa de valor', 8, 0, 1),
  ('decision_maker', 'Quem decide a contratacao', 9, 0, 1),
  ('how_found_us', 'Como conheceu a Carinho', 10, 0, 1);

CREATE TABLE conversation_triage (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  checklist_id BIGINT UNSIGNED NOT NULL,
  response TEXT NULL,
  completed_at DATETIME NULL,
  completed_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_conversation_triage_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_triage_checklist
    FOREIGN KEY (checklist_id) REFERENCES triage_checklist(id),
  CONSTRAINT fk_conversation_triage_agent
    FOREIGN KEY (completed_by) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE holidays (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  date DATE NOT NULL UNIQUE,
  description VARCHAR(128) NOT NULL,
  year_recurring TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO holidays (date, description, year_recurring) VALUES
  ('2026-01-01', 'Confraternizacao Universal', 1),
  ('2026-04-21', 'Tiradentes', 1),
  ('2026-05-01', 'Dia do Trabalho', 1),
  ('2026-09-07', 'Independencia do Brasil', 1),
  ('2026-10-12', 'Nossa Senhora Aparecida', 1),
  ('2026-11-02', 'Finados', 1),
  ('2026-11-15', 'Proclamacao da Republica', 1),
  ('2026-12-25', 'Natal', 1);

CREATE TABLE satisfaction_surveys (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NULL,
  feedback TEXT NULL,
  sent_at DATETIME NOT NULL,
  responded_at DATETIME NULL,
  CONSTRAINT fk_satisfaction_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_messages_conversation_sent
  ON messages (conversation_id, sent_at);

CREATE INDEX idx_conversations_status_priority
  ON conversations (status_id, priority_id);

CREATE INDEX idx_conversation_history_conversation
  ON conversation_history (conversation_id, created_at);

CREATE INDEX idx_conversation_triage_conversation
  ON conversation_triage (conversation_id);

CREATE INDEX idx_holidays_date
  ON holidays (date);

CREATE INDEX idx_incidents_category
  ON incidents (category_id, severity_id);

-- =============================================================================
-- Tabelas do Laravel Framework
-- =============================================================================

-- Tabela de migrations (controle de versão do banco)
CREATE TABLE migrations (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  migration VARCHAR(255) NOT NULL,
  batch INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabelas de filas (Laravel Horizon)
CREATE TABLE jobs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  KEY idx_jobs_queue (queue)
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

-- Tabela de tokens de acesso pessoal (Laravel Sanctum)
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
  updated_at DATETIME NULL,
  KEY idx_personal_access_tokens_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Tabelas do Spatie Activity Log
-- =============================================================================

CREATE TABLE activity_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  log_name VARCHAR(255) NULL,
  description TEXT NOT NULL,
  event VARCHAR(255) NULL,
  subject_type VARCHAR(255) NULL,
  subject_id BIGINT UNSIGNED NULL,
  causer_type VARCHAR(255) NULL,
  causer_id BIGINT UNSIGNED NULL,
  properties JSON NULL,
  batch_uuid CHAR(36) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_activity_log_subject (subject_type, subject_id),
  KEY idx_activity_log_causer (causer_type, causer_id),
  KEY idx_activity_log_log_name (log_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Tabelas do Spatie Permission
-- =============================================================================

CREATE TABLE permissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  guard_name VARCHAR(255) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_permissions_name_guard (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  guard_name VARCHAR(255) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_roles_name_guard (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE model_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  model_type VARCHAR(255) NOT NULL,
  model_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (permission_id, model_id, model_type),
  KEY idx_model_has_permissions_model (model_type, model_id),
  CONSTRAINT fk_model_has_permissions_permission
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE model_has_roles (
  role_id BIGINT UNSIGNED NOT NULL,
  model_type VARCHAR(255) NOT NULL,
  model_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, model_id, model_type),
  KEY idx_model_has_roles_model (model_type, model_id),
  CONSTRAINT fk_model_has_roles_role
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (permission_id, role_id),
  CONSTRAINT fk_role_has_permissions_permission
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_has_permissions_role
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Tabela de sessoes para SESSION_DRIVER=database (Laravel)
-- =============================================================================
CREATE TABLE sessions (
  id VARCHAR(255) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY sessions_user_id_index (user_id),
  KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
