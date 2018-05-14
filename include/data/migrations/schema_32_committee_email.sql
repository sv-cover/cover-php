CREATE TABLE committee_email (
    committee_id smallint NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE,
    email TEXT,
    CONSTRAINT committee_email_uniq UNIQUE (committee_id, email)
);

CREATE INDEX ON committee_email (committee_id);