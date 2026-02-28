-- ============================================================
-- OJT DTR Monitoring Management System
-- Database: ojt_dtr_system
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ojt_dtr_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ojt_dtr_system`;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(120)        NOT NULL,
  `username`       VARCHAR(80)         NOT NULL UNIQUE,
  `email`          VARCHAR(180)        NOT NULL UNIQUE,
  `password`       VARCHAR(255)        NOT NULL,   -- bcrypt hash
  `role`           ENUM('admin','ojt') NOT NULL DEFAULT 'ojt',
  `required_hours`      INT UNSIGNED        NOT NULL DEFAULT 486,
  `training_supervisor` VARCHAR(150)                 DEFAULT NULL,
  `created_at`          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: attendance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `work_date`      DATE         NOT NULL,
  `time_in`        DATETIME     NOT NULL,
  `time_out`       DATETIME             DEFAULT NULL,
  `total_hours`    DECIMAL(5,2)         DEFAULT 0.00,
  `late_minutes`   INT                  DEFAULT 0,
  `status`         ENUM('On Time','Late','Overtime','Undertime','Pending')
                                        NOT NULL DEFAULT 'Pending',
  `last_edited_at` DATETIME             DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_date` (`user_id`, `work_date`),
  CONSTRAINT `fk_attendance_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: edit_logs  (audit trail)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `edit_logs` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attendance_id` INT UNSIGNED NOT NULL,
  `user_id`       INT UNSIGNED NOT NULL,           -- who made the edit
  `old_time_in`   DATETIME     NOT NULL,
  `new_time_in`   DATETIME     NOT NULL,
  `old_time_out`  DATETIME             DEFAULT NULL,
  `new_time_out`  DATETIME             DEFAULT NULL,
  `reason`        TEXT         NOT NULL,
  `edited_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_editlog_attendance`
    FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_editlog_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed: default admin account
-- Email   : admin@ojt.com
-- Password: Admin@1234   (bcrypt hash below)
-- ------------------------------------------------------------
INSERT IGNORE INTO `users` (`name`, `username`, `email`, `password`, `role`, `required_hours`)
VALUES (
  'System Admin',
  'sysadmin',
  'admin@ojt.com',
  '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWC', -- Admin@1234
  'admin',
  486
);
