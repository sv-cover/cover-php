CREATE TABLE email_confirmation_tokens (
    key character (40) PRIMARY KEY,
    member_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    email TEXT NOT NULL,
    created_on timestamp without time zone NOT NULL
);

DROP TABLE confirm;