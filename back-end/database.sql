-- ============================================
--  Base de données : gestion_etudiants
-- ============================================

CREATE DATABASE IF NOT EXISTS gestion_etudiants
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestion_etudiants;

-- Table des étudiants
CREATE TABLE IF NOT EXISTS etudiants (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prenom      VARCHAR(100)  NOT NULL,
  nom         VARCHAR(100)  NOT NULL,
  email       VARCHAR(255)  NOT NULL UNIQUE,
  matricule   VARCHAR(50)   NOT NULL UNIQUE,
  niveau      VARCHAR(50)   NOT NULL,
  filiere     VARCHAR(100)  NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,         -- stocké hashé (password_hash)
  actif       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Index utiles
CREATE INDEX idx_email     ON etudiants (email);
CREATE INDEX idx_matricule ON etudiants (matricule);
CREATE INDEX idx_niveau    ON etudiants (niveau);
CREATE INDEX idx_filiere   ON etudiants (filiere);
