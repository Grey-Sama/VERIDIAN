-- Run once on an existing installation. Fresh installations use schema.sql.
ALTER TABLE users
 ADD COLUMN job_title VARCHAR(80) NOT NULL DEFAULT '',
 ADD COLUMN department VARCHAR(100) NOT NULL DEFAULT '';
