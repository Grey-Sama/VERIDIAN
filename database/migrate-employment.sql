-- Run once when upgrading. Existing employees remain unclassified until an administrator sets a category.
ALTER TABLE users ADD COLUMN employment_type ENUM('Full-Time','Part-Time','New Employee') NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS leave_category_history (
 user_id INT UNSIGNED NOT NULL,
 year SMALLINT UNSIGNED NOT NULL,
 employment_type ENUM('Full-Time','Part-Time','New Employee') NULL DEFAULT NULL,
 PRIMARY KEY(user_id,year),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Legacy leave_allowances records are retained but no longer used for new balance calculations.
