CREATE TABLE domain_service_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_urgency_level (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_service_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_assignment_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_schedule_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_checklist_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_check_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_notification_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_emergency_severity (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_service_type (id, code, label) VALUES
  (1, 'horista', 'Horista'),
  (2, 'diario', 'Diario'),
  (3, 'mensal', 'Mensal');

INSERT INTO domain_urgency_level (id, code, label) VALUES
  (1, 'hoje', 'Hoje'),
  (2, 'semana', 'Semana'),
  (3, 'sem_data', 'Sem data');

INSERT INTO domain_service_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'scheduled', 'Scheduled'),
  (3, 'active', 'Active'),
  (4, 'completed', 'Completed'),
  (5, 'canceled', 'Canceled');

INSERT INTO domain_assignment_status (id, code, label) VALUES
  (1, 'assigned', 'Assigned'),
  (2, 'confirmed', 'Confirmed'),
  (3, 'replaced', 'Replaced'),
  (4, 'canceled', 'Canceled');

INSERT INTO domain_schedule_status (id, code, label) VALUES
  (1, 'planned', 'Planned'),
  (2, 'in_progress', 'In progress'),
  (3, 'done', 'Done'),
  (4, 'missed', 'Missed');

INSERT INTO domain_checklist_type (id, code, label) VALUES
  (1, 'start', 'Start'),
  (2, 'end', 'End');

INSERT INTO domain_check_type (id, code, label) VALUES
  (1, 'in', 'In'),
  (2, 'out', 'Out');

INSERT INTO domain_notification_status (id, code, label) VALUES
  (1, 'queued', 'Queued'),
  (2, 'sent', 'Sent'),
  (3, 'failed', 'Failed');

INSERT INTO domain_emergency_severity (id, code, label) VALUES
  (1, 'low', 'Low'),
  (2, 'medium', 'Medium'),
  (3, 'high', 'High'),
  (4, 'critical', 'Critical');

CREATE TABLE service_requests (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  urgency_id TINYINT UNSIGNED NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_service_requests_type
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id),
  CONSTRAINT fk_service_requests_urgency
    FOREIGN KEY (urgency_id) REFERENCES domain_urgency_level(id),
  CONSTRAINT fk_service_requests_status
    FOREIGN KEY (status_id) REFERENCES domain_service_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assignments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  service_request_id BIGINT UNSIGNED NOT NULL,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL,
  CONSTRAINT fk_assignments_request
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
  CONSTRAINT fk_assignments_status
    FOREIGN KEY (status_id) REFERENCES domain_assignment_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schedules (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assignment_id BIGINT UNSIGNED NOT NULL,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  shift_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_schedules_assignment
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
  CONSTRAINT fk_schedules_status
    FOREIGN KEY (status_id) REFERENCES domain_schedule_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE checklists (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  service_request_id BIGINT UNSIGNED NOT NULL,
  checklist_type_id TINYINT UNSIGNED NOT NULL,
  template_json JSON NOT NULL,
  CONSTRAINT fk_checklists_request
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
  CONSTRAINT fk_checklists_type
    FOREIGN KEY (checklist_type_id) REFERENCES domain_checklist_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE checklist_entries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  checklist_id BIGINT UNSIGNED NOT NULL,
  item_key VARCHAR(128) NOT NULL,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  CONSTRAINT fk_checklist_entries_checklist
    FOREIGN KEY (checklist_id) REFERENCES checklists(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE checkins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  schedule_id BIGINT UNSIGNED NOT NULL,
  check_type_id TINYINT UNSIGNED NOT NULL,
  timestamp DATETIME NOT NULL,
  location VARCHAR(255) NULL,
  CONSTRAINT fk_checkins_schedule
    FOREIGN KEY (schedule_id) REFERENCES schedules(id),
  CONSTRAINT fk_checkins_type
    FOREIGN KEY (check_type_id) REFERENCES domain_check_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  schedule_id BIGINT UNSIGNED NOT NULL,
  activities_json JSON NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_service_logs_schedule
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE substitutions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assignment_id BIGINT UNSIGNED NOT NULL,
  old_caregiver_id BIGINT UNSIGNED NOT NULL,
  new_caregiver_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_substitutions_assignment
    FOREIGN KEY (assignment_id) REFERENCES assignments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NULL,
  notif_type VARCHAR(64) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  sent_at DATETIME NULL,
  CONSTRAINT fk_notifications_status
    FOREIGN KEY (status_id) REFERENCES domain_notification_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE emergencies (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  service_request_id BIGINT UNSIGNED NOT NULL,
  severity_id TINYINT UNSIGNED NOT NULL,
  description TEXT NOT NULL,
  resolved_at DATETIME NULL,
  CONSTRAINT fk_emergencies_request
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
  CONSTRAINT fk_emergencies_severity
    FOREIGN KEY (severity_id) REFERENCES domain_emergency_severity(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_schedules_caregiver_date
  ON schedules (caregiver_id, shift_date);

CREATE INDEX idx_schedules_client_date
  ON schedules (client_id, shift_date);

CREATE TABLE domain_audit_action (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_audit_action (id, code, label) VALUES
  (1, 'schedule_created', 'Agendamento Criado'),
  (2, 'schedule_updated', 'Agendamento Atualizado'),
  (3, 'schedule_canceled', 'Agendamento Cancelado'),
  (4, 'checkin_performed', 'Check-in Realizado'),
  (5, 'checkout_performed', 'Check-out Realizado'),
  (6, 'assignment_created', 'Alocação Criada'),
  (7, 'assignment_confirmed', 'Alocação Confirmada'),
  (8, 'substitution_processed', 'Substituição Processada'),
  (9, 'emergency_created', 'Emergência Registrada'),
  (10, 'emergency_resolved', 'Emergência Resolvida'),
  (11, 'emergency_escalated', 'Emergência Escalonada'),
  (12, 'exception_approved', 'Exceção Aprovada'),
  (13, 'exception_rejected', 'Exceção Rejeitada'),
  (14, 'manual_override', 'Alteração Manual');

CREATE TABLE operational_audit_trail (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  action_id TINYINT UNSIGNED NOT NULL,
  entity_type VARCHAR(64) NOT NULL COMMENT 'Tipo da entidade: schedule, assignment, emergency, etc',
  entity_id BIGINT UNSIGNED NOT NULL COMMENT 'ID da entidade afetada',
  user_id BIGINT UNSIGNED NULL COMMENT 'Usuário que realizou a ação',
  user_type VARCHAR(32) NOT NULL DEFAULT 'system' COMMENT 'Tipo: system, operator, supervisor',
  old_values JSON NULL COMMENT 'Valores anteriores',
  new_values JSON NULL COMMENT 'Novos valores',
  reason VARCHAR(500) NULL COMMENT 'Motivo/justificativa',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_operational_audit_action
    FOREIGN KEY (action_id) REFERENCES domain_audit_action(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE operational_exceptions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  exception_type VARCHAR(64) NOT NULL COMMENT 'Tipo: late_checkin, early_checkout, schedule_change, etc',
  entity_type VARCHAR(64) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  requested_by BIGINT UNSIGNED NULL,
  description TEXT NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  approved_by BIGINT UNSIGNED NULL,
  approval_notes TEXT NULL,
  requested_at DATETIME NOT NULL,
  resolved_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE backup_caregivers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  region_code VARCHAR(32) NOT NULL,
  priority TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Alta, 2=Media, 3=Baixa',
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  available_from TIME NULL,
  available_until TIME NULL,
  service_types JSON NULL COMMENT 'Tipos de serviço que aceita',
  last_assignment_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_backup_caregiver_region (caregiver_id, region_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sla_metrics (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  metric_date DATE NOT NULL,
  metric_type VARCHAR(64) NOT NULL COMMENT 'allocation_time, checkin_punctuality, substitution_rate, etc',
  dimension VARCHAR(64) NULL COMMENT 'region, caregiver, service_type',
  dimension_value VARCHAR(64) NULL,
  target_value DECIMAL(10,2) NOT NULL,
  actual_value DECIMAL(10,2) NOT NULL,
  target_met TINYINT(1) NOT NULL DEFAULT 0,
  sample_size INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY sla_metrics_unique (metric_date, metric_type, dimension, dimension_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sla_alerts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sla_metric_id BIGINT UNSIGNED NULL,
  alert_type VARCHAR(64) NOT NULL COMMENT 'threshold_breach, trend_warning, critical',
  metric_type VARCHAR(64) NOT NULL,
  message TEXT NOT NULL,
  severity VARCHAR(16) NOT NULL DEFAULT 'warning' COMMENT 'info, warning, critical',
  is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
  acknowledged_by BIGINT UNSIGNED NULL,
  acknowledged_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_sla_alerts_metric
    FOREIGN KEY (sla_metric_id) REFERENCES sla_metrics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_service_requests_status_date
  ON service_requests (status_id, start_date);

CREATE INDEX idx_operational_audit_entity
  ON operational_audit_trail (entity_type, entity_id);

CREATE INDEX idx_operational_audit_user
  ON operational_audit_trail (user_id, created_at);

CREATE INDEX idx_operational_audit_created
  ON operational_audit_trail (created_at);

CREATE INDEX idx_operational_exceptions_status
  ON operational_exceptions (status, requested_at);

CREATE INDEX idx_operational_exceptions_entity
  ON operational_exceptions (entity_type, entity_id);

CREATE INDEX idx_backup_caregivers_region
  ON backup_caregivers (region_code, is_available, priority);

CREATE INDEX idx_sla_metrics_date
  ON sla_metrics (metric_date, target_met);

CREATE INDEX idx_sla_alerts_acknowledged
  ON sla_alerts (is_acknowledged, severity);
