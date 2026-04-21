CREATE DATABASE IF NOT EXISTS pfe;
USE pfe;

CREATE TABLE IF NOT EXISTS propositions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    titre_projet VARCHAR(255) NOT NULL,
    description TEXT,
    objectifs TEXT,
    technologies VARCHAR(255),
    date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);