USE hamayesh_yar;

CREATE TABLE IF NOT EXISTS surveys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_surveys_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS survey_answers (
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

INSERT INTO surveys (title,is_active)
SELECT 'نظرسنجی عمومی همایش یار',1
WHERE NOT EXISTS (SELECT 1 FROM surveys LIMIT 1);
