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
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_campaigns_channel_status
  ON campaigns (channel_id, status_id);

CREATE INDEX idx_campaign_metrics_date
  ON campaign_metrics (campaign_id, metric_date);
