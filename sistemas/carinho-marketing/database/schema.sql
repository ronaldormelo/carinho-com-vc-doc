CREATE TABLE domain_channel_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_content_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_asset_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_campaign_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_creative_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_landing_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_channel_status (id, code, label) VALUES
  (1, 'active', 'Active'),
  (2, 'inactive', 'Inactive');

INSERT INTO domain_content_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'scheduled', 'Scheduled'),
  (3, 'published', 'Published'),
  (4, 'canceled', 'Canceled');

INSERT INTO domain_asset_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'approved', 'Approved'),
  (3, 'published', 'Published');

INSERT INTO domain_campaign_status (id, code, label) VALUES
  (1, 'planned', 'Planned'),
  (2, 'active', 'Active'),
  (3, 'paused', 'Paused'),
  (4, 'finished', 'Finished');

INSERT INTO domain_creative_type (id, code, label) VALUES
  (1, 'image', 'Image'),
  (2, 'video', 'Video'),
  (3, 'text', 'Text');

INSERT INTO domain_landing_status (id, code, label) VALUES
  (1, 'draft', 'Draft'),
  (2, 'published', 'Published'),
  (3, 'archived', 'Archived');

CREATE TABLE marketing_channels (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_marketing_channels_status
    FOREIGN KEY (status_id) REFERENCES domain_channel_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE social_accounts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  channel_id BIGINT UNSIGNED NOT NULL,
  handle VARCHAR(128) NOT NULL,
  profile_url VARCHAR(512) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_social_accounts_channel
    FOREIGN KEY (channel_id) REFERENCES marketing_channels(id),
  CONSTRAINT fk_social_accounts_status
    FOREIGN KEY (status_id) REFERENCES domain_channel_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE content_calendar (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  channel_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  scheduled_at DATETIME NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  owner_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_content_calendar_channel
    FOREIGN KEY (channel_id) REFERENCES marketing_channels(id),
  CONSTRAINT fk_content_calendar_status
    FOREIGN KEY (status_id) REFERENCES domain_content_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE content_assets (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  calendar_id BIGINT UNSIGNED NOT NULL,
  asset_type_id TINYINT UNSIGNED NOT NULL,
  asset_url VARCHAR(512) NOT NULL,
  caption TEXT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_content_assets_calendar
    FOREIGN KEY (calendar_id) REFERENCES content_calendar(id),
  CONSTRAINT fk_content_assets_type
    FOREIGN KEY (asset_type_id) REFERENCES domain_creative_type(id),
  CONSTRAINT fk_content_assets_status
    FOREIGN KEY (status_id) REFERENCES domain_asset_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE campaigns (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  channel_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  objective VARCHAR(255) NOT NULL,
  budget DECIMAL(12,2) NOT NULL DEFAULT 0,
  start_date DATE NULL,
  end_date DATE NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  external_id VARCHAR(128) NULL,
  approval_required TINYINT(1) NOT NULL DEFAULT 0,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_campaigns_channel
    FOREIGN KEY (channel_id) REFERENCES marketing_channels(id),
  CONSTRAINT fk_campaigns_status
    FOREIGN KEY (status_id) REFERENCES domain_campaign_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ad_groups (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  targeting_json JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_ad_groups_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE creatives (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  ad_group_id BIGINT UNSIGNED NOT NULL,
  creative_type_id TINYINT UNSIGNED NOT NULL,
  headline VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  media_url VARCHAR(512) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_creatives_group
    FOREIGN KEY (ad_group_id) REFERENCES ad_groups(id),
  CONSTRAINT fk_creatives_type
    FOREIGN KEY (creative_type_id) REFERENCES domain_creative_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE utm_links (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  source VARCHAR(128) NOT NULL,
  medium VARCHAR(128) NOT NULL,
  campaign VARCHAR(128) NOT NULL,
  content VARCHAR(128) NULL,
  term VARCHAR(128) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE landing_pages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  utm_default_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_landing_pages_status
    FOREIGN KEY (status_id) REFERENCES domain_landing_status(id),
  CONSTRAINT fk_landing_pages_utm
    FOREIGN KEY (utm_default_id) REFERENCES utm_links(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversion_events (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  event_key VARCHAR(128) NOT NULL,
  target_url VARCHAR(512) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE campaign_metrics (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  metric_date DATE NOT NULL,
  impressions INT UNSIGNED NOT NULL DEFAULT 0,
  clicks INT UNSIGNED NOT NULL DEFAULT 0,
  spend DECIMAL(12,2) NOT NULL DEFAULT 0,
  leads INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_campaign_metrics_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  UNIQUE KEY uk_campaign_metrics_date (campaign_id, metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE brand_assets (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(32) NOT NULL,
  file_url VARCHAR(512) NOT NULL,
  description TEXT NULL,
  metadata JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lead_sources (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lead_id VARCHAR(64) NOT NULL,
  utm_source VARCHAR(128) NULL,
  utm_medium VARCHAR(128) NULL,
  utm_campaign VARCHAR(128) NULL,
  utm_content VARCHAR(128) NULL,
  utm_term VARCHAR(128) NULL,
  campaign_id BIGINT UNSIGNED NULL,
  landing_page_id BIGINT UNSIGNED NULL,
  referrer VARCHAR(512) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  extra_data JSON NULL,
  captured_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_lead_sources_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  CONSTRAINT fk_lead_sources_landing
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_approval_status (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_partnership_type (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_partnership_status (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_approval_status (id, code, label) VALUES
  (1, 'pending', 'Pendente'),
  (2, 'approved', 'Aprovado'),
  (3, 'rejected', 'Rejeitado');

INSERT INTO domain_partnership_type (id, code, label) VALUES
  (1, 'clinic', 'Clínica'),
  (2, 'hospital', 'Hospital'),
  (3, 'caregiver', 'Cuidador'),
  (4, 'condominium', 'Condomínio'),
  (5, 'pharmacy', 'Farmácia'),
  (6, 'other', 'Outro');

INSERT INTO domain_partnership_status (id, code, label) VALUES
  (1, 'active', 'Ativo'),
  (2, 'inactive', 'Inativo'),
  (3, 'pending', 'Pendente');

CREATE TABLE campaign_approvals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  requested_budget DECIMAL(12,2) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  requested_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  justification TEXT NULL,
  approval_notes TEXT NULL,
  requested_at DATETIME NOT NULL,
  decided_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_campaign_approvals_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_campaign_approvals_status
    FOREIGN KEY (status_id) REFERENCES domain_approval_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE budget_limits (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NULL COMMENT 'null = limite global',
  daily_limit DECIMAL(12,2) NULL,
  monthly_limit DECIMAL(12,2) NULL,
  total_limit DECIMAL(12,2) NULL,
  auto_pause_enabled TINYINT(1) NOT NULL DEFAULT 0,
  alert_threshold_70 TINYINT NOT NULL DEFAULT 1,
  alert_threshold_90 TINYINT NOT NULL DEFAULT 1,
  alert_threshold_100 TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_budget_limits_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  UNIQUE KEY uk_budget_limits_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE budget_alerts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NULL,
  budget_limit_id BIGINT UNSIGNED NOT NULL,
  threshold_percent TINYINT NOT NULL,
  current_spend DECIMAL(12,2) NOT NULL,
  limit_value DECIMAL(12,2) NOT NULL,
  period_type VARCHAR(32) NOT NULL,
  period_date DATE NOT NULL,
  acknowledged TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_budget_alerts_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_budget_alerts_limit
    FOREIGN KEY (budget_limit_id) REFERENCES budget_limits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE campaign_audit_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(64) NOT NULL,
  field_name VARCHAR(64) NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_campaign_audit_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE marketing_partnerships (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type_id TINYINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  contact_name VARCHAR(255) NULL,
  contact_phone VARCHAR(32) NULL,
  contact_email VARCHAR(255) NULL,
  address VARCHAR(512) NULL,
  city VARCHAR(128) NULL,
  state VARCHAR(2) NULL,
  notes TEXT NULL,
  commission_percent DECIMAL(5,2) NULL,
  referral_code VARCHAR(32) NULL UNIQUE,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_partnerships_type
    FOREIGN KEY (type_id) REFERENCES domain_partnership_type(id),
  CONSTRAINT fk_partnerships_status
    FOREIGN KEY (status_id) REFERENCES domain_partnership_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE partnership_referrals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  partnership_id BIGINT UNSIGNED NOT NULL,
  lead_id VARCHAR(64) NOT NULL,
  lead_name VARCHAR(255) NULL,
  lead_phone VARCHAR(32) NULL,
  converted TINYINT(1) NOT NULL DEFAULT 0,
  contract_value DECIMAL(12,2) NULL,
  commission_value DECIMAL(12,2) NULL,
  commission_paid TINYINT(1) NOT NULL DEFAULT 0,
  referred_at DATETIME NOT NULL,
  converted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_partnership_referrals_partnership
    FOREIGN KEY (partnership_id) REFERENCES marketing_partnerships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_referrals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  referrer_customer_id VARCHAR(64) NOT NULL,
  referrer_name VARCHAR(255) NOT NULL,
  referrer_phone VARCHAR(32) NULL,
  referred_lead_id VARCHAR(64) NULL,
  referred_name VARCHAR(255) NULL,
  referred_phone VARCHAR(32) NULL,
  referral_code VARCHAR(32) NOT NULL UNIQUE,
  converted TINYINT(1) NOT NULL DEFAULT 0,
  contract_value DECIMAL(12,2) NULL,
  benefit_type VARCHAR(64) NULL,
  benefit_value DECIMAL(12,2) NULL,
  benefit_applied TINYINT(1) NOT NULL DEFAULT 0,
  referred_at DATETIME NULL,
  converted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE referral_program_config (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  benefit_type VARCHAR(64) NOT NULL DEFAULT 'discount',
  referrer_benefit_value DECIMAL(12,2) NOT NULL DEFAULT 50.00,
  referred_benefit_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  min_contract_value INT NOT NULL DEFAULT 0,
  max_referrals_per_month INT NOT NULL DEFAULT 10,
  terms TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_campaigns_channel_status
  ON campaigns (channel_id, status_id);

CREATE INDEX idx_campaign_metrics_date
  ON campaign_metrics (campaign_id, metric_date);

CREATE INDEX idx_brand_assets_type
  ON brand_assets (type, is_active);

CREATE INDEX idx_lead_sources_utm
  ON lead_sources (utm_source, utm_medium, captured_at);

CREATE INDEX idx_lead_sources_lead_id
  ON lead_sources (lead_id);

CREATE INDEX idx_campaign_approvals_campaign
  ON campaign_approvals (campaign_id, status_id);

CREATE INDEX idx_budget_alerts_campaign
  ON budget_alerts (campaign_id, period_date);

CREATE INDEX idx_campaign_audit_campaign
  ON campaign_audit_log (campaign_id, created_at);

CREATE INDEX idx_campaign_audit_user
  ON campaign_audit_log (user_id, created_at);

CREATE INDEX idx_partnerships_type_status
  ON marketing_partnerships (type_id, status_id);

CREATE INDEX idx_partnerships_city
  ON marketing_partnerships (city);

CREATE INDEX idx_partnership_referrals_status
  ON partnership_referrals (partnership_id, converted);

CREATE INDEX idx_partnership_referrals_lead
  ON partnership_referrals (lead_id);

CREATE INDEX idx_customer_referrals_referrer
  ON customer_referrals (referrer_customer_id, converted);

CREATE INDEX idx_customer_referrals_code
  ON customer_referrals (referral_code);

CREATE INDEX idx_content_calendar_channel_status
  ON content_calendar (channel_id, status_id, scheduled_at);

CREATE INDEX idx_utm_links_source_medium
  ON utm_links (source, medium, campaign);

CREATE INDEX idx_conversion_events_key
  ON conversion_events (event_key);
