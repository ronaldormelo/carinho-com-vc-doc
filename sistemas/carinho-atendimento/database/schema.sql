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

INSERT INTO domain_channel (id, code, label) VALUES
  (1, 'whatsapp', 'WhatsApp'),
  (2, 'email', 'Email');

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

-- Tabela de domínio para níveis de suporte (N1, N2, N3)
CREATE TABLE domain_support_level (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  description VARCHAR(255) NULL,
  max_response_minutes INT UNSIGNED NOT NULL DEFAULT 5,
  max_resolution_minutes INT UNSIGNED NOT NULL DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_support_level (id, code, label, description, max_response_minutes, max_resolution_minutes) VALUES
  (1, 'n1', 'Nível 1 - Atendimento', 'Primeiro contato, triagem e informações básicas', 5, 30),
  (2, 'n2', 'Nível 2 - Suporte', 'Questões técnicas, propostas e negociação', 15, 120),
  (3, 'n3', 'Nível 3 - Especialista', 'Casos complexos, reclamações críticas e emergências', 30, 240);

-- Tabela de domínio para motivos de perda
CREATE TABLE domain_loss_reason (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  requires_notes TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_loss_reason (id, code, label, requires_notes) VALUES
  (1, 'price', 'Preço acima do orçamento', 0),
  (2, 'timing', 'Prazo não atendeu', 0),
  (3, 'competitor', 'Optou pela concorrência', 1),
  (4, 'no_caregiver', 'Não encontramos cuidador adequado', 0),
  (5, 'no_response', 'Cliente não respondeu', 0),
  (6, 'changed_mind', 'Cliente desistiu do serviço', 1),
  (7, 'location', 'Região não atendida', 0),
  (8, 'schedule', 'Horário incompatível', 0),
  (9, 'other', 'Outro motivo', 1);

-- Tabela de domínio para categorias de scripts/templates
CREATE TABLE domain_script_category (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_script_category (id, code, label) VALUES
  (1, 'greeting', 'Saudação'),
  (2, 'qualification', 'Qualificação'),
  (3, 'proposal', 'Proposta'),
  (4, 'objection', 'Objeção'),
  (5, 'closing', 'Fechamento'),
  (6, 'support', 'Suporte'),
  (7, 'emergency', 'Emergência'),
  (8, 'feedback', 'Feedback');

-- Tabela de domínio para tipos de ação (auditoria)
CREATE TABLE domain_action_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_action_type (id, code, label) VALUES
  (1, 'status_change', 'Mudança de Status'),
  (2, 'priority_change', 'Mudança de Prioridade'),
  (3, 'assignment', 'Atribuição de Agente'),
  (4, 'escalation', 'Escalonamento'),
  (5, 'note_added', 'Nota Adicionada'),
  (6, 'tag_added', 'Tag Adicionada'),
  (7, 'incident_created', 'Incidente Criado'),
  (8, 'sla_breach', 'Violação de SLA');

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
  support_level_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  max_concurrent_conversations INT UNSIGNED NOT NULL DEFAULT 5,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_agents_role
    FOREIGN KEY (role_id) REFERENCES domain_agent_role(id),
  CONSTRAINT fk_agents_support_level
    FOREIGN KEY (support_level_id) REFERENCES domain_support_level(id)
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
  CONSTRAINT fk_conversations_agent
    FOREIGN KEY (assigned_to) REFERENCES agents(id),
  CONSTRAINT fk_conversations_loss_reason
    FOREIGN KEY (loss_reason_id) REFERENCES domain_loss_reason(id)
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
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_incidents_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_incidents_severity
    FOREIGN KEY (severity_id) REFERENCES domain_incident_severity(id)
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

-- Tabela de histórico de ações (auditoria)
CREATE TABLE conversation_actions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  action_type_id TINYINT UNSIGNED NOT NULL,
  agent_id BIGINT UNSIGNED NULL,
  old_value VARCHAR(255) NULL,
  new_value VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_conversation_actions_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_actions_type
    FOREIGN KEY (action_type_id) REFERENCES domain_action_type(id),
  CONSTRAINT fk_conversation_actions_agent
    FOREIGN KEY (agent_id) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de checklist de triagem
CREATE TABLE triage_checklist_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL UNIQUE,
  question TEXT NOT NULL,
  field_type VARCHAR(32) NOT NULL DEFAULT 'text',
  options_json JSON NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Respostas do checklist por conversa
CREATE TABLE conversation_triage (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  checklist_item_id BIGINT UNSIGNED NOT NULL,
  answer TEXT NULL,
  answered_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_conversation_triage_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_triage_item
    FOREIGN KEY (checklist_item_id) REFERENCES triage_checklist_items(id),
  CONSTRAINT fk_conversation_triage_agent
    FOREIGN KEY (answered_by) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de scripts padronizados
CREATE TABLE communication_scripts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL UNIQUE,
  title VARCHAR(128) NOT NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  support_level_id TINYINT UNSIGNED NULL,
  body TEXT NOT NULL,
  variables_json JSON NULL,
  usage_hint TEXT NULL,
  display_order INT UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_scripts_category
    FOREIGN KEY (category_id) REFERENCES domain_script_category(id),
  CONSTRAINT fk_scripts_support_level
    FOREIGN KEY (support_level_id) REFERENCES domain_support_level(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configurações de SLA por prioridade e nível de suporte
CREATE TABLE sla_configurations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  priority_id TINYINT UNSIGNED NOT NULL,
  support_level_id TINYINT UNSIGNED NOT NULL,
  max_first_response_minutes INT UNSIGNED NOT NULL,
  max_resolution_minutes INT UNSIGNED NOT NULL,
  warning_threshold_percent INT UNSIGNED NOT NULL DEFAULT 80,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_sla_config (priority_id, support_level_id),
  CONSTRAINT fk_sla_config_priority
    FOREIGN KEY (priority_id) REFERENCES domain_priority(id),
  CONSTRAINT fk_sla_config_support_level
    FOREIGN KEY (support_level_id) REFERENCES domain_support_level(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alertas de SLA
CREATE TABLE sla_alerts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  alert_type VARCHAR(32) NOT NULL,
  threshold_minutes INT UNSIGNED NOT NULL,
  actual_minutes INT UNSIGNED NOT NULL,
  notified_at DATETIME NULL,
  acknowledged_by BIGINT UNSIGNED NULL,
  acknowledged_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_sla_alerts_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_sla_alerts_agent
    FOREIGN KEY (acknowledged_by) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notas internas da conversa
CREATE TABLE conversation_notes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  agent_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  is_private TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_conversation_notes_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_conversation_notes_agent
    FOREIGN KEY (agent_id) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico de escalonamentos
CREATE TABLE escalation_history (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  from_level_id TINYINT UNSIGNED NOT NULL,
  to_level_id TINYINT UNSIGNED NOT NULL,
  from_agent_id BIGINT UNSIGNED NULL,
  to_agent_id BIGINT UNSIGNED NULL,
  reason TEXT NULL,
  escalated_at DATETIME NOT NULL,
  CONSTRAINT fk_escalation_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  CONSTRAINT fk_escalation_from_level
    FOREIGN KEY (from_level_id) REFERENCES domain_support_level(id),
  CONSTRAINT fk_escalation_to_level
    FOREIGN KEY (to_level_id) REFERENCES domain_support_level(id),
  CONSTRAINT fk_escalation_from_agent
    FOREIGN KEY (from_agent_id) REFERENCES agents(id),
  CONSTRAINT fk_escalation_to_agent
    FOREIGN KEY (to_agent_id) REFERENCES agents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados padronizados para SLA
INSERT INTO sla_configurations (priority_id, support_level_id, max_first_response_minutes, max_resolution_minutes, warning_threshold_percent, active, created_at) VALUES
  (1, 1, 15, 120, 80, 1, NOW()),
  (1, 2, 30, 240, 80, 1, NOW()),
  (1, 3, 60, 480, 80, 1, NOW()),
  (2, 1, 5, 60, 80, 1, NOW()),
  (2, 2, 15, 120, 80, 1, NOW()),
  (2, 3, 30, 240, 80, 1, NOW()),
  (3, 1, 3, 30, 70, 1, NOW()),
  (3, 2, 10, 60, 70, 1, NOW()),
  (3, 3, 20, 120, 70, 1, NOW()),
  (4, 1, 2, 15, 50, 1, NOW()),
  (4, 2, 5, 30, 50, 1, NOW()),
  (4, 3, 10, 60, 50, 1, NOW());

-- Checklist padrão de triagem
INSERT INTO triage_checklist_items (code, question, field_type, options_json, is_required, display_order, active, created_at) VALUES
  ('patient_name', 'Nome do paciente/idoso', 'text', NULL, 1, 1, 1, NOW()),
  ('patient_age', 'Idade do paciente', 'number', NULL, 1, 2, 1, NOW()),
  ('patient_condition', 'Condição/diagnóstico principal', 'text', NULL, 1, 3, 1, NOW()),
  ('mobility_level', 'Nível de mobilidade', 'select', '["Independente","Parcial","Acamado","Cadeirante"]', 1, 4, 1, NOW()),
  ('care_type', 'Tipo de cuidado desejado', 'select', '["Horista","Diária (12h)","Pernoite","24h"]', 1, 5, 1, NOW()),
  ('schedule_days', 'Dias da semana', 'multiselect', '["Segunda","Terça","Quarta","Quinta","Sexta","Sábado","Domingo"]', 1, 6, 1, NOW()),
  ('schedule_hours', 'Horário desejado', 'text', NULL, 1, 7, 1, NOW()),
  ('address_city', 'Cidade', 'text', NULL, 1, 8, 1, NOW()),
  ('address_neighborhood', 'Bairro', 'text', NULL, 1, 9, 1, NOW()),
  ('urgency', 'Urgência do início', 'select', '["Imediato (hoje/amanhã)","Esta semana","Próxima semana","Sem urgência"]', 1, 10, 1, NOW()),
  ('caregiver_preference_gender', 'Preferência de gênero do cuidador', 'select', '["Feminino","Masculino","Indiferente"]', 0, 11, 1, NOW()),
  ('special_requirements', 'Requisitos especiais (experiência com Alzheimer, etc)', 'textarea', NULL, 0, 12, 1, NOW()),
  ('contact_relationship', 'Relação do contato com o paciente', 'select', '["Filho(a)","Cônjuge","Neto(a)","Sobrinho(a)","Outro familiar","Próprio paciente","Outro"]', 1, 13, 1, NOW()),
  ('budget_range', 'Faixa de orçamento mensal', 'select', '["Até R$3.000","R$3.000 a R$5.000","R$5.000 a R$8.000","Acima de R$8.000","A definir"]', 0, 14, 1, NOW());

CREATE INDEX idx_messages_conversation_sent
  ON messages (conversation_id, sent_at);

CREATE INDEX idx_conversations_status_priority
  ON conversations (status_id, priority_id);

CREATE INDEX idx_conversations_support_level
  ON conversations (support_level_id);

CREATE INDEX idx_conversation_actions_conversation
  ON conversation_actions (conversation_id, created_at);

CREATE INDEX idx_sla_alerts_conversation
  ON sla_alerts (conversation_id, created_at);

CREATE INDEX idx_escalation_history_conversation
  ON escalation_history (conversation_id, escalated_at);
