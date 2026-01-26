CREATE TABLE domain_api_key_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_endpoint_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_event_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_delivery_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_job_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_api_key_status (id, code, label) VALUES
  (1, 'active', 'Active'),
  (2, 'revoked', 'Revoked');

INSERT INTO domain_endpoint_status (id, code, label) VALUES
  (1, 'active', 'Active'),
  (2, 'inactive', 'Inactive');

INSERT INTO domain_event_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'processing', 'Processing'),
  (3, 'done', 'Done'),
  (4, 'failed', 'Failed');

INSERT INTO domain_delivery_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'sent', 'Sent'),
  (3, 'failed', 'Failed');

INSERT INTO domain_job_status (id, code, label) VALUES
  (1, 'queued', 'Queued'),
  (2, 'running', 'Running'),
  (3, 'done', 'Done'),
  (4, 'failed', 'Failed');

CREATE TABLE api_keys (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  key_hash VARCHAR(255) NOT NULL,
  permissions_json JSON NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  last_used_at DATETIME NULL,
  CONSTRAINT fk_api_keys_status
    FOREIGN KEY (status_id) REFERENCES domain_api_key_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE webhook_endpoints (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  system_name VARCHAR(128) NOT NULL,
  url VARCHAR(512) NOT NULL,
  secret VARCHAR(255) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_webhook_endpoints_status
    FOREIGN KEY (status_id) REFERENCES domain_endpoint_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE integration_events (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_type VARCHAR(128) NOT NULL,
  source_system VARCHAR(128) NOT NULL,
  payload_json JSON NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_integration_events_status
    FOREIGN KEY (status_id) REFERENCES domain_event_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE event_mappings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_type VARCHAR(128) NOT NULL,
  target_system VARCHAR(128) NOT NULL,
  mapping_json JSON NOT NULL,
  version VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE webhook_deliveries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  endpoint_id BIGINT UNSIGNED NOT NULL,
  event_id BIGINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL,
  response_code INT NULL,
  CONSTRAINT fk_webhook_deliveries_endpoint
    FOREIGN KEY (endpoint_id) REFERENCES webhook_endpoints(id),
  CONSTRAINT fk_webhook_deliveries_event
    FOREIGN KEY (event_id) REFERENCES integration_events(id),
  CONSTRAINT fk_webhook_deliveries_status
    FOREIGN KEY (status_id) REFERENCES domain_delivery_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE retry_queue (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  next_retry_at DATETIME NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_retry_queue_event
    FOREIGN KEY (event_id) REFERENCES integration_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dead_letter (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  reason TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_dead_letter_event
    FOREIGN KEY (event_id) REFERENCES integration_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sync_jobs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  job_type VARCHAR(128) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  CONSTRAINT fk_sync_jobs_status
    FOREIGN KEY (status_id) REFERENCES domain_job_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rate_limits (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  window_start DATETIME NOT NULL,
  count INT UNSIGNED NOT NULL DEFAULT 0
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

CREATE INDEX idx_api_keys_name
  ON api_keys (name);

CREATE INDEX idx_webhook_endpoints_system
  ON webhook_endpoints (system_name);

CREATE INDEX idx_integration_events_status
  ON integration_events (status_id, created_at);

CREATE INDEX idx_integration_events_type_source
  ON integration_events (event_type, source_system);

CREATE INDEX idx_event_mappings_type_target
  ON event_mappings (event_type, target_system);

CREATE INDEX idx_webhook_deliveries_endpoint_status
  ON webhook_deliveries (endpoint_id, status_id);

CREATE INDEX idx_retry_queue_next_retry
  ON retry_queue (next_retry_at);

CREATE INDEX idx_sync_jobs_type_status
  ON sync_jobs (job_type, status_id);

CREATE INDEX idx_rate_limits_client_window
  ON rate_limits (client_id, window_start);
