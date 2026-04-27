-- ============================================================
-- GestPFE — Base de données MySQL
-- FST Sidi Bouzid — A.U. 2025-2026
-- Import : mysql -u root -p < gestpfe.sql
--       ou via phpMyAdmin > Importer
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestpfe
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestpfe;

-- ============================================================
-- TABLE: etudiants
-- ============================================================
CREATE TABLE IF NOT EXISTS etudiants (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    prenom           VARCHAR(100)  NOT NULL,
    nom              VARCHAR(100)  NOT NULL,
    email            VARCHAR(150)  NOT NULL UNIQUE COMMENT 'Doit être @fst-sbz.tn',
    cin              VARCHAR(8)    NOT NULL UNIQUE COMMENT '8 chiffres',
    filiere          VARCHAR(150)  NOT NULL,
    mot_de_passe     VARCHAR(255)  NOT NULL,
    date_inscription TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    est_actif        TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: enseignants
-- ============================================================
CREATE TABLE IF NOT EXISTS enseignants (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    prenom         VARCHAR(100) NOT NULL,
    nom            VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    departement    VARCHAR(100),
    telephone      VARCHAR(20),
    bureau         VARCHAR(50),
    disponibilites TEXT,
    est_actif      TINYINT(1)  DEFAULT 1,
    date_creation  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: projets
-- ============================================================
CREATE TABLE IF NOT EXISTS projets (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(255) NOT NULL,
    entreprise          VARCHAR(200),
    theme               ENUM('ia','web','mobile','reseau','data','agro','autre') NOT NULL,
    objectifs           TEXT NOT NULL,
    technologies        VARCHAR(300),
    resume              TEXT,
    mots_cles           VARCHAR(200),
    statut              ENUM(
                          'soumis','en_revision','valide','refuse',
                          'en_cours','rapport_depose','soutenu','archive'
                        ) DEFAULT 'soumis',
    etudiant_id         INT NOT NULL,
    tuteur_id           INT DEFAULT NULL,
    date_soumission     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_validation     TIMESTAMP NULL,
    date_soutenance     DATE NULL,
    salle_soutenance    VARCHAR(50),
    fichier_proposition VARCHAR(300),
    fichier_rapport     VARCHAR(300),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (tuteur_id)   REFERENCES enseignants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: comptes_rendus
-- ============================================================
CREATE TABLE IF NOT EXISTS comptes_rendus (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    projet_id          INT  NOT NULL,
    numero             INT  NOT NULL,
    travaux_realises   TEXT NOT NULL,
    travaux_a_venir    TEXT,
    problemes          TEXT,
    statut             ENUM('soumis','valide','rejete') DEFAULT 'soumis',
    commentaire_tuteur TEXT,
    date_soumission    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_validation    TIMESTAMP NULL,
    FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cr (projet_id, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: evaluations
-- ============================================================
CREATE TABLE IF NOT EXISTS evaluations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    projet_id        INT  NOT NULL UNIQUE,
    note_rapport     DECIMAL(4,2) CHECK (note_rapport    BETWEEN 0 AND 20),
    note_soutenance  DECIMAL(4,2) CHECK (note_soutenance BETWEEN 0 AND 20),
    note_suivi       DECIMAL(4,2) CHECK (note_suivi      BETWEEN 0 AND 20),
    note_finale      DECIMAL(4,2),
    mention          VARCHAR(30),
    commentaire      TEXT,
    jury_membres     VARCHAR(300),
    date_evaluation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id   INT          NOT NULL,
    titre         VARCHAR(200) NOT NULL,
    message       TEXT         NOT NULL,
    type          ENUM('info','success','warning','danger') DEFAULT 'info',
    est_lu        TINYINT(1)   DEFAULT 0,
    date_creation TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: jury
-- ============================================================
CREATE TABLE IF NOT EXISTS jury (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    projet_id     INT NOT NULL,
    enseignant_id INT NOT NULL,
    role          ENUM('tuteur','membre') DEFAULT 'membre',
    FOREIGN KEY (projet_id)     REFERENCES projets(id)     ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_jury (projet_id, enseignant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================

-- Enseignants
INSERT INTO enseignants (prenom, nom, email, departement, telephone, bureau, disponibilites) VALUES
('Mohamed', 'Ben Ali',    'm.benali@fst-sbz.tn',    'Informatique', '+216 97 000 001', 'B-204', 'Mar & Jeu : 10h–12h'),
('Fatma',   'Gharbi',     'f.gharbi@fst-sbz.tn',    'Informatique', '+216 97 000 002', 'B-206', 'Lun & Mer : 9h–11h'),
('Karim',   'Trabelsi',   'k.trabelsi@fst-sbz.tn',  'Réseaux',      '+216 97 000 003', 'A-115', 'Mer & Ven : 14h–16h'),
('Sonia',   'Mejri',      's.mejri@fst-sbz.tn',     'Agro-alimentaire', '+216 97 000 004', 'C-312', 'Lun & Jeu : 8h–10h');

-- Étudiant de test
-- Mot de passe : Test1234!
-- Pour générer un vrai hash : password_hash('Test1234!', PASSWORD_DEFAULT)
-- Remplacez le hash ci-dessous par le résultat de ce code PHP avant de l'utiliser
INSERT INTO etudiants (prenom, nom, email, cin, filiere, mot_de_passe) VALUES
(
  'Mohamed', 'Trabelsi',
  'm.trabelsi@fst-sbz.tn',
  '12345678',
  'Licence Sciences Informatique',
  '$2y$12$REMPLACEZ_PAR_UN_VRAI_HASH_GENERE_PAR_PHP'
);

-- ============================================================
-- COMMENT GÉNÉRER LE HASH DU MOT DE PASSE DE TEST
-- Créez un fichier hash.php à la racine de votre projet :
--
--   <?php echo password_hash('Test1234!', PASSWORD_DEFAULT); ?>
--
-- Ouvrez-le dans le navigateur via XAMPP (ex: localhost/gestpfe/hash.php)
-- Copiez le hash affiché et remplacez REMPLACEZ_PAR_UN_VRAI_HASH ci-dessus.
-- Supprimez hash.php ensuite.
-- ============================================================
