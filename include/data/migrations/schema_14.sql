ALTER TABLE sessions
	ADD COLUMN override_member_id integer DEFAULT NULL,
    ADD COLUMN override_committees varchar(255) DEFAULT NULL;