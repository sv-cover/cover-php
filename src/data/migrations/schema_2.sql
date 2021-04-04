ALTER TABLE fotos ADD COLUMN
	added_on timestamp without time zone DEFAULT NULL;

CREATE TABLE foto_boeken_visit (
    boek_id integer NOT NULL REFERENCES foto_boeken (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    CONSTRAINT foto_boeken_visit_pk PRIMARY KEY (boek_id, lid_id)
);

UPDATE configuratie SET value = 2 WHERE key = 'schema_version';
