CREATE DATABASE IF NOT EXISTS fleetcare_ea
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE fleetcare_ea;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  workspace_name VARCHAR(120) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'KES',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE vehicles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  registration VARCHAR(20) NOT NULL,
  type VARCHAR(30) NOT NULL,
  odometer INT UNSIGNED NOT NULL DEFAULT 0,
  next_service_odometer INT UNSIGNED NULL,
  status ENUM('Active', 'In service', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehicle_user_registration (user_id, registration),
  KEY ix_vehicle_user (user_id),
  CONSTRAINT fk_vehicle_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE maintenance_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  service_type VARCHAR(100) NOT NULL,
  notes VARCHAR(500) NULL,
  service_date DATE NOT NULL,
  cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
  odometer INT UNSIGNED NULL,
  next_service_odometer INT UNSIGNED NULL,
  status ENUM('Scheduled', 'Completed') NOT NULL DEFAULT 'Scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_maintenance_user_status_date (user_id, status, service_date),
  KEY ix_maintenance_vehicle (vehicle_id),
  CONSTRAINT fk_maintenance_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fuel_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  filled_at DATE NOT NULL,
  litres DECIMAL(10, 2) NOT NULL,
  price_per_litre DECIMAL(10, 2) NOT NULL,
  odometer INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_fuel_user_date (user_id, filled_at),
  KEY ix_fuel_vehicle_odometer (vehicle_id, odometer),
  CONSTRAINT fk_fuel_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fuel_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vehicle_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  document_type ENUM('Insurance', 'Inspection', 'Permit', 'Other') NOT NULL,
  document_number VARCHAR(100) NULL,
  expiry_date DATE NOT NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_documents_user_expiry (user_id, expiry_date),
  KEY ix_documents_vehicle (vehicle_id),
  CONSTRAINT fk_documents_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_documents_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
) ENGINE=InnoDB;
