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
  cost_center VARCHAR(64) NULL COMMENT 'Centro de custo para análise gerencial',
  approval_status_id TINYINT UNSIGNED NULL,
  approval_id BIGINT UNSIGNED NULL,
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
  approval_status_id TINYINT UNSIGNED NULL,
  approval_id BIGINT UNSIGNED NULL,
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

CREATE TABLE setting_categories (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(500) NULL,
  display_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id TINYINT UNSIGNED NOT NULL,
  setting_key VARCHAR(64) NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(500) NULL,
  value TEXT NOT NULL,
  value_type VARCHAR(32) NOT NULL DEFAULT 'string',
  unit VARCHAR(32) NULL,
  default_value TEXT NULL,
  validation_rules TEXT NULL,
  is_editable TINYINT(1) NOT NULL DEFAULT 1,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  display_order SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_settings_category_key (category_id, setting_key),
  CONSTRAINT fk_settings_category
    FOREIGN KEY (category_id) REFERENCES setting_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE setting_history (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  setting_id BIGINT UNSIGNED NOT NULL,
  old_value TEXT NULL,
  new_value TEXT NOT NULL,
  changed_by VARCHAR(128) NULL,
  change_reason VARCHAR(500) NULL,
  changed_at DATETIME NOT NULL,
  CONSTRAINT fk_setting_history_setting
    FOREIGN KEY (setting_id) REFERENCES settings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_transaction_type (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_financial_category (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL,
  type ENUM('revenue', 'expense', 'both') NOT NULL DEFAULT 'both'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_approval_status (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE domain_payable_status (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO domain_transaction_type (id, code, label) VALUES
  (1, 'receipt', 'Recebimento'),
  (2, 'payment', 'Pagamento'),
  (3, 'transfer', 'Transferência'),
  (4, 'adjustment', 'Ajuste'),
  (5, 'fee', 'Taxa'),
  (6, 'refund', 'Reembolso');

INSERT INTO domain_financial_category (id, code, label, type) VALUES
  (1, 'service_revenue', 'Receita de Serviços', 'revenue'),
  (2, 'cancellation_fee', 'Taxa de Cancelamento', 'revenue'),
  (3, 'late_fee', 'Juros e Multas', 'revenue'),
  (4, 'other_revenue', 'Outras Receitas', 'revenue'),
  (10, 'caregiver_payout', 'Repasse Cuidadores', 'expense'),
  (11, 'gateway_fee', 'Taxa Gateway', 'expense'),
  (12, 'transfer_fee', 'Taxa Transferência', 'expense'),
  (13, 'refund_expense', 'Reembolso Cliente', 'expense'),
  (14, 'operational', 'Despesa Operacional', 'expense'),
  (15, 'administrative', 'Despesa Administrativa', 'expense'),
  (16, 'tax', 'Impostos e Tributos', 'expense'),
  (17, 'other_expense', 'Outras Despesas', 'expense');

INSERT INTO domain_approval_status (id, code, label) VALUES
  (1, 'pending', 'Pendente'),
  (2, 'approved', 'Aprovado'),
  (3, 'rejected', 'Rejeitado'),
  (4, 'auto_approved', 'Aprovado Automático');

INSERT INTO domain_payable_status (id, code, label) VALUES
  (1, 'open', 'Em Aberto'),
  (2, 'scheduled', 'Agendado'),
  (3, 'paid', 'Pago'),
  (4, 'canceled', 'Cancelado');

CREATE TABLE cash_transactions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  transaction_date DATE NOT NULL,
  competence_date DATE NULL COMMENT 'Data de competência contábil',
  type_id TINYINT UNSIGNED NOT NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  direction ENUM('in', 'out') NOT NULL COMMENT 'in=entrada, out=saída',
  reference_type VARCHAR(64) NULL COMMENT 'invoice, payment, payout, payable',
  reference_id BIGINT UNSIGNED NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  external_reference VARCHAR(128) NULL COMMENT 'ID externo (Stripe, etc)',
  notes TEXT NULL,
  created_by VARCHAR(128) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_cash_transactions_type
    FOREIGN KEY (type_id) REFERENCES domain_transaction_type(id),
  CONSTRAINT fk_cash_transactions_category
    FOREIGN KEY (category_id) REFERENCES domain_financial_category(id),
  CONSTRAINT fk_cash_transactions_bank
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payables (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  status_id TINYINT UNSIGNED NOT NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  supplier_name VARCHAR(255) NOT NULL,
  supplier_document VARCHAR(20) NULL COMMENT 'CPF/CNPJ',
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  interest_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  competence_date DATE NULL,
  paid_at DATETIME NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  payment_method VARCHAR(32) NULL,
  document_number VARCHAR(64) NULL COMMENT 'Nº nota/documento',
  barcode VARCHAR(128) NULL COMMENT 'Código de barras boleto',
  notes TEXT NULL,
  reference_type VARCHAR(64) NULL,
  reference_id BIGINT UNSIGNED NULL,
  created_by VARCHAR(128) NULL,
  paid_by VARCHAR(128) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_payables_status
    FOREIGN KEY (status_id) REFERENCES domain_payable_status(id),
  CONSTRAINT fk_payables_category
    FOREIGN KEY (category_id) REFERENCES domain_financial_category(id),
  CONSTRAINT fk_payables_bank
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE provisions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  period VARCHAR(7) NOT NULL COMMENT 'Formato: YYYY-MM',
  type VARCHAR(32) NOT NULL COMMENT 'pcld, other',
  calculated_amount DECIMAL(12,2) NOT NULL COMMENT 'Valor calculado pelo sistema',
  adjusted_amount DECIMAL(12,2) NULL COMMENT 'Valor ajustado manualmente',
  used_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Valor utilizado/baixado',
  calculation_base JSON NULL COMMENT 'Dados usados no cálculo',
  notes TEXT NULL,
  created_by VARCHAR(128) NULL,
  adjusted_by VARCHAR(128) NULL,
  adjusted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_provisions_period_type (period, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE approvals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  status_id TINYINT UNSIGNED NOT NULL,
  operation_type VARCHAR(64) NOT NULL COMMENT 'discount, refund, payout, payable',
  operation_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  threshold_amount DECIMAL(12,2) NOT NULL COMMENT 'Limite que disparou aprovação',
  requested_by VARCHAR(128) NOT NULL,
  request_reason TEXT NULL,
  requested_at DATETIME NOT NULL,
  decided_by VARCHAR(128) NULL,
  decision_reason TEXT NULL,
  decided_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_approvals_status
    FOREIGN KEY (status_id) REFERENCES domain_approval_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO setting_categories (id, code, name, description, display_order) VALUES
  (1, 'payment', 'Pagamento', 'Configurações de prazo e cobrança', 1),
  (2, 'cancellation', 'Cancelamento', 'Políticas de cancelamento e reembolso', 2),
  (3, 'commission', 'Comissões', 'Percentuais de comissão por tipo de serviço', 3),
  (4, 'pricing', 'Precificação', 'Valores base e adicionais', 4),
  (5, 'margin', 'Margem', 'Margens e viabilidade financeira', 5),
  (6, 'payout', 'Repasses', 'Configurações de repasse aos cuidadores', 6),
  (7, 'fiscal', 'Fiscal', 'Configurações fiscais e tributárias', 7),
  (8, 'limits', 'Limites', 'Limites e alertas do sistema', 8),
  (9, 'bonus', 'Bônus', 'Bônus por avaliação e tempo de casa', 9),
  (10, 'approval', 'Aprovações', 'Configurações de limites e workflow de aprovação', 10);

ALTER TABLE invoices
  ADD CONSTRAINT fk_invoices_approval_status
    FOREIGN KEY (approval_status_id) REFERENCES domain_approval_status(id);

ALTER TABLE invoices
  ADD CONSTRAINT fk_invoices_approval
    FOREIGN KEY (approval_id) REFERENCES approvals(id);

ALTER TABLE payouts
  ADD CONSTRAINT fk_payouts_approval_status
    FOREIGN KEY (approval_status_id) REFERENCES domain_approval_status(id);

ALTER TABLE payouts
  ADD CONSTRAINT fk_payouts_approval
    FOREIGN KEY (approval_id) REFERENCES approvals(id);

CREATE INDEX idx_invoices_client_status
  ON invoices (client_id, status_id);

CREATE INDEX idx_settings_key
  ON settings (setting_key);

CREATE INDEX idx_setting_history_setting
  ON setting_history (setting_id, changed_at);

CREATE INDEX idx_cash_transactions_date
  ON cash_transactions (transaction_date, direction);

CREATE INDEX idx_cash_transactions_competence
  ON cash_transactions (competence_date, category_id);

CREATE INDEX idx_cash_transactions_reference
  ON cash_transactions (reference_type, reference_id);

CREATE INDEX idx_payables_status_due
  ON payables (status_id, due_date);

CREATE INDEX idx_payables_competence
  ON payables (competence_date, category_id);

CREATE INDEX idx_approvals_status_type
  ON approvals (status_id, operation_type);

CREATE INDEX idx_approvals_requested
  ON approvals (requested_by, status_id);

CREATE INDEX idx_payments_status_paid
  ON payments (status_id, paid_at);

CREATE INDEX idx_payouts_caregiver_status
  ON payouts (caregiver_id, status_id);
