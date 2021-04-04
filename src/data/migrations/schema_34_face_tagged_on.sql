ALTER TABLE foto_faces ADD COLUMN tagged_on timestamp without time zone DEFAULT NULL;

CREATE TABLE foto_boeken_custom_visit (
    boek_id text NOT NULL,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    CONSTRAINT foto_boeken_custom_visit_pk PRIMARY KEY (boek_id, lid_id)
);
