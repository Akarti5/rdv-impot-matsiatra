-- Create database (adjust if needed)
CREATE DATABASE IF NOT EXISTS rdv_impots_matsiatra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rdv_impots_matsiatra;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  nif VARCHAR(20) UNIQUE,
  type_user ENUM('client','agent') NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  agent_id INT,
  date_rdv DATE NOT NULL,
  heure_rdv TIME NOT NULL,
  motif VARCHAR(200) NOT NULL,
  status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  notes_client TEXT,
  notes_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Time slots
CREATE TABLE IF NOT EXISTS time_slots (
  id INT PRIMARY KEY AUTO_INCREMENT,
  agent_id INT NOT NULL,
  date DATE NOT NULL,
  heure_debut TIME NOT NULL,
  heure_fin TIME NOT NULL,
  is_available BOOLEAN DEFAULT TRUE,
  max_appointments INT DEFAULT 1,
  CONSTRAINT fk_slot_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Appointment types
CREATE TABLE IF NOT EXISTS appointment_types (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom_motif VARCHAR(150) NOT NULL,
  description TEXT,
  duree_estimee INT DEFAULT 30
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  type ENUM('email','sms','system') NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed appointment types
INSERT INTO appointment_types (nom_motif, description, duree_estimee) VALUES
('Déclaration d\'impôt', 'Assistance pour la déclaration', 30),
('Paiement d\'impôt', 'Informations sur le paiement', 20),
('Rectification', 'Rectification / contentieux', 40),
('Conseil fiscal', 'Consultation et conseils', 30)
ON DUPLICATE KEY UPDATE nom_motif = VALUES(nom_motif);
