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
  assigned_to BIGINT UNSIGNED NULL,
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

CREATE INDEX idx_messages_conversation_sent
  ON messages (conversation_id, sent_at);

CREATE INDEX idx_conversations_status_priority
  ON conversations (status_id, priority_id);
