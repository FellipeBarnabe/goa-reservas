-- ============================================================
-- GOA Reservas: criação do banco e das tabelas
-- Pode ser executado mais de uma vez sem erro.
-- ============================================================

CREATE DATABASE IF NOT EXISTS goa_reservas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE goa_reservas;

-- Hotéis do grupo
CREATE TABLE IF NOT EXISTS hoteis (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(120) NOT NULL,
  slug        VARCHAR(140) NOT NULL,              -- usado na URL: hotel.php?slug=...
  bandeira    VARCHAR(30)  NOT NULL,              -- AURA, ESSENCE, RESORT, CITY ou SMART
  cidade      VARCHAR(80)  NOT NULL,
  uf          CHAR(2)      NOT NULL,
  descricao   TEXT         NULL,
  imagem      VARCHAR(255) NULL,                  -- caminho da foto (enviada depois pela empresa)
  ativo       TINYINT(1)   NOT NULL DEFAULT 1,    -- 1 = aparece no site, 0 = oculto
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hoteis_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos de quarto de cada hotel (ex.: "Suíte Luxo", com 10 unidades)
CREATE TABLE IF NOT EXISTS tipos_quarto (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  hotel_id      INT UNSIGNED  NOT NULL,
  nome          VARCHAR(100)  NOT NULL,
  descricao     TEXT          NULL,
  capacidade    TINYINT UNSIGNED NOT NULL DEFAULT 2,   -- máximo de hóspedes
  preco_diaria  DECIMAL(10,2) NOT NULL,
  quantidade    SMALLINT UNSIGNED NOT NULL DEFAULT 1,  -- quantos quartos deste tipo existem
  imagem        VARCHAR(255)  NULL,
  ativo         TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_tipos_hotel (hotel_id),
  CONSTRAINT fk_tipos_hotel FOREIGN KEY (hotel_id)
    REFERENCES hoteis (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservas feitas pelos hóspedes
CREATE TABLE IF NOT EXISTS reservas (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo           CHAR(8)      NOT NULL,           -- código de consulta, ex.: GOA7K2QX
  tipo_quarto_id   INT UNSIGNED NOT NULL,
  hospede_nome     VARCHAR(120) NOT NULL,
  hospede_email    VARCHAR(150) NOT NULL,
  hospede_telefone VARCHAR(20)  NOT NULL,
  checkin          DATE         NOT NULL,
  checkout         DATE         NOT NULL,
  adultos          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criancas         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  valor_total      DECIMAL(10,2) NOT NULL,
  status           ENUM('pendente','confirmada','cancelada','concluida') NOT NULL DEFAULT 'pendente',
  observacoes      TEXT         NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reservas_codigo (codigo),
  KEY idx_reservas_disponibilidade (tipo_quarto_id, checkin, checkout),
  KEY idx_reservas_status (status),
  CONSTRAINT fk_reservas_tipo FOREIGN KEY (tipo_quarto_id)
    REFERENCES tipos_quarto (id) ON DELETE RESTRICT,   -- não apaga tipo de quarto que tem reservas
  CONSTRAINT ck_reservas_datas CHECK (checkout > checkin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Administradores do painel
CREATE TABLE IF NOT EXISTS usuarios (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  senha_hash  VARCHAR(255) NOT NULL,                -- nunca guardamos a senha, só o hash
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
