USE byteme;

CREATE TABLE user_progress (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    language_name VARCHAR(50) NOT NULL,
    progress_percentage INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_language (user_id, language_name)
);
