CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 username VARCHAR(30) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 password_must_change TINYINT(1) NOT NULL DEFAULT 0,
 avatar_data MEDIUMBLOB NULL,
 avatar_mime VARCHAR(32) NULL,
 avatar_version INT UNSIGNED NOT NULL DEFAULT 0,
 role ENUM('employee','admin') NOT NULL DEFAULT 'employee',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS leave_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 leave_type ENUM('Annual','Sick','Emergency','Personal','Fast') NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 reason TEXT NOT NULL,
 status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
 remarks VARCHAR(1000) NOT NULL DEFAULT '',
 reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (reviewed_by) REFERENCES users(id),
 INDEX employee_dates (user_id,start_date,end_date),
 INDEX status_created (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS remember_tokens (
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 password_version CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 expires_at BIGINT UNSIGNED NOT NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX remember_user (user_id),
 INDEX remember_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_throttles (
 bucket_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL,
 window_start BIGINT UNSIGNED NOT NULL,
 INDEX throttle_expiry (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS leave_allowances (
 user_id INT UNSIGNED NOT NULL,
 year SMALLINT UNSIGNED NOT NULL,
 annual SMALLINT UNSIGNED NOT NULL DEFAULT 15,
 sick SMALLINT UNSIGNED NOT NULL DEFAULT 15,
 emergency SMALLINT UNSIGNED NOT NULL DEFAULT 7,
 PRIMARY KEY(user_id,year),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
