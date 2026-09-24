CREATE DATABASE IF NOT EXISTS hamayesh_yar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hamayesh_yar;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS survey_answers;
DROP TABLE IF EXISTS surveys;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS article_reviewers;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('author','reviewer','secretariat','admin') NOT NULL DEFAULT 'author',
    expertise VARCHAR(255) NULL,
    organization VARCHAR(255) NULL,
    bio VARCHAR(200) NULL,
    avatar VARCHAR(255) NULL,
    status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    status ENUM('draft','upcoming','ongoing','finished','cancelled') NOT NULL DEFAULT 'draft',
    start_date DATE NULL,
    end_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    duration VARCHAR(50) NULL,
    location VARCHAR(255) NULL,
    address VARCHAR(500) NULL,
    organizer VARCHAR(255) NULL,
    description TEXT NULL,
    long_description TEXT NULL,
    image VARCHAR(255) NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    capacity INT UNSIGNED NULL,
    submission_deadline DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE articles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(500) NOT NULL,
    abstract TEXT NOT NULL,
    keywords VARCHAR(1000) NULL,
    file_path VARCHAR(500) NOT NULL,
    revision_file_path VARCHAR(500) NULL,
    status ENUM('pending','reviewing','revision','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
    final_decision ENUM('accept','reject','revision') NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revision_submitted_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_articles_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_articles_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_articles_author (author_id),
    INDEX idx_articles_event (event_id),
    INDEX idx_articles_status (status)
) ENGINE=InnoDB;

CREATE TABLE article_reviewers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned','in_progress','completed') NOT NULL DEFAULT 'assigned',
    UNIQUE KEY uq_article_reviewer (article_id, reviewer_id),
    CONSTRAINT fk_ar_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ar_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    suggestion ENUM('accept','reject','revision') NOT NULL,
    reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (article_id, reviewer_id),
    CONSTRAINT chk_review_score CHECK (score BETWEEN 0 AND 100),
    CONSTRAINT fk_reviews_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    schedule_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    title VARCHAR(255) NOT NULL,
    speaker VARCHAR(255) NULL,
    hall VARCHAR(255) NULL,
    type VARCHAR(100) NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_schedule_event_date_time (event_id, schedule_date, start_time, sort_order),
    CONSTRAINT fk_schedules_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_schedule_event (event_id)
) ENGINE=InnoDB;

CREATE TABLE registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    organization VARCHAR(255) NULL,
    message VARCHAR(1000) NULL,
    status ENUM('registered','cancelled','attended') NOT NULL DEFAULT 'registered',
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_email (event_id, email),
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE surveys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_surveys_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE survey_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    rate TINYINT UNSIGNED NOT NULL,
    satisfaction ENUM('very-satisfied','satisfied','neutral','dissatisfied','very-dissatisfied') NOT NULL,
    message VARCHAR(500) NOT NULL,
    newsletter TINYINT(1) NOT NULL DEFAULT 0,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_survey_rate CHECK (rate BETWEEN 1 AND 5),
    CONSTRAINT fk_answers_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE SET NULL,
    INDEX idx_answers_survey (survey_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

-- حساب‌های اولیه برای توسعه؛ رمزها باید با password_hash() تولید شوند.
-- INSERT نمونه را عمداً اینجا قرار نمی‌دهیم تا رمز خام داخل SQL ذخیره نشود.
