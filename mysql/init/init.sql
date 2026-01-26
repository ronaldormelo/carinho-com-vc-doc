-- =============================================================================
-- Script de Inicialização MariaDB - Carinho com Você
-- =============================================================================
-- Este script cria todos os bancos de dados necessários para os sistemas
-- Execute automaticamente ao iniciar o container MariaDB pela primeira vez
-- =============================================================================

-- Criar usuário se não existir
CREATE USER IF NOT EXISTS 'carinho'@'%' IDENTIFIED BY 'carinho';
GRANT ALL PRIVILEGES ON carinho_*.* TO 'carinho'@'%';
FLUSH PRIVILEGES;

-- Criar bancos de dados
CREATE DATABASE IF NOT EXISTS carinho_atendimento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_cuidadores CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_documentos_lgpd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_operacao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_site CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS carinho_integracoes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Garantir permissões
GRANT ALL PRIVILEGES ON carinho_atendimento.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_cuidadores.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_documentos_lgpd.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_operacao.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_site.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_crm.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_marketing.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_financeiro.* TO 'carinho'@'%';
GRANT ALL PRIVILEGES ON carinho_integracoes.* TO 'carinho'@'%';

FLUSH PRIVILEGES;
