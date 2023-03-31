CREATE TYPE session_type AS ENUM ('member', 'device');
ALTER TABLE sessions ADD COLUMN type session_type NOT NULL DEFAULT 'member';
ALTER TABLE sessions ADD COLUMN device_enabled boolean NOT NULL DEFAULT FALSE;
ALTER TABLE sessions ADD COLUMN device_name varchar(255) DEFAULT NULL;
