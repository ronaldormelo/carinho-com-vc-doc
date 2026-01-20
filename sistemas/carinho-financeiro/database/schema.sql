CREATE TABLE domain_payment_method (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_account_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_invoice_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_payment_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_payout_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_service_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_owner_type (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_reconciliation_status (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_payment_method (id, code, label) VALUES
  (1, 'pix', 'Pix'),
  (2, 'boleto', 'Boleto'),
  (3, 'card', 'Card');

INSERT INTO domain_account_status (id, code, label) VALUES
  (1, 'active', 'Active'),
  (2, 'inactive', 'Inactive');

INSERT INTO domain_invoice_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'paid', 'Paid'),
  (3, 'overdue', 'Overdue'),
  (4, 'canceled', 'Canceled');

INSERT INTO domain_payment_status (id, code, label) VALUES
  (1, 'pending', 'Pending'),
  (2, 'paid', 'Paid'),
  (3, 'failed', 'Failed'),
  (4, 'refunded', 'Refunded');

INSERT INTO domain_payout_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'paid', 'Paid'),
  (3, 'canceled', 'Canceled');

INSERT INTO domain_service_type (id, code, label) VALUES
  (1, 'horista', 'Horista'),
  (2, 'diario', 'Diario'),
  (3, 'mensal', 'Mensal');

INSERT INTO domain_owner_type (id, code, label) VALUES
  (1, 'client', 'Client'),
  (2, 'caregiver', 'Caregiver'),
  (3, 'company', 'Company');

INSERT INTO domain_reconciliation_status (id, code, label) VALUES
  (1, 'open', 'Open'),
  (2, 'closed', 'Closed');

CREATE TABLE billing_accounts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  payment_method_id TINYINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_billing_accounts_method
    FOREIGN KEY (payment_method_id) REFERENCES domain_payment_method(id),
  CONSTRAINT fk_billing_accounts_status
    FOREIGN KEY (status_id) REFERENCES domain_account_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoices (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  contract_id BIGINT UNSIGNED NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_invoices_status
    FOREIGN KEY (status_id) REFERENCES domain_invoice_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  invoice_id BIGINT UNSIGNED NOT NULL,
  service_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  qty DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_invoice_items_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  invoice_id BIGINT UNSIGNED NOT NULL,
  method_id TINYINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status_id TINYINT UNSIGNED NOT NULL,
  paid_at DATETIME NULL,
  external_id VARCHAR(128) NULL,
  CONSTRAINT fk_payments_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_payments_method
    FOREIGN KEY (method_id) REFERENCES domain_payment_method(id),
  CONSTRAINT fk_payments_status
    FOREIGN KEY (status_id) REFERENCES domain_payment_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payouts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  caregiver_id BIGINT UNSIGNED NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_payouts_status
    FOREIGN KEY (status_id) REFERENCES domain_payout_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payout_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payout_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  commission_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_payout_items_payout
    FOREIGN KEY (payout_id) REFERENCES payouts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE price_plans (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  service_type_id TINYINT UNSIGNED NOT NULL,
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_price_plans_service
    FOREIGN KEY (service_type_id) REFERENCES domain_service_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE price_rules (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  plan_id BIGINT UNSIGNED NOT NULL,
  rule_type VARCHAR(64) NOT NULL,
  value DECIMAL(12,2) NOT NULL DEFAULT 0,
  conditions_json JSON NULL,
  CONSTRAINT fk_price_rules_plan
    FOREIGN KEY (plan_id) REFERENCES price_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bank_accounts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  owner_type_id TINYINT UNSIGNED NOT NULL,
  owner_id BIGINT UNSIGNED NOT NULL,
  bank_name VARCHAR(128) NOT NULL,
  account_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_bank_accounts_owner
    FOREIGN KEY (owner_type_id) REFERENCES domain_owner_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reconciliations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  period VARCHAR(32) NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  notes TEXT NULL,
  CONSTRAINT fk_reconciliations_status
    FOREIGN KEY (status_id) REFERENCES domain_reconciliation_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fiscal_documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  invoice_id BIGINT UNSIGNED NOT NULL,
  doc_number VARCHAR(64) NOT NULL,
  issued_at DATETIME NOT NULL,
  file_url VARCHAR(512) NOT NULL,
  CONSTRAINT fk_fiscal_documents_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_invoices_client_status
  ON invoices (client_id, status_id);

CREATE INDEX idx_payments_status_paid
  ON payments (status_id, paid_at);

CREATE INDEX idx_payouts_caregiver_status
  ON payouts (caregiver_id, status_id);
