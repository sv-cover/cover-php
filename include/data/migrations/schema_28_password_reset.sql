CREATE TABLE password_reset_tokens (
	key character (40) PRIMARY KEY,
	member_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
	created_on timestamp without time zone NOT NULL
);