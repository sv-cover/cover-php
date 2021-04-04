CREATE TABLE applications (
    key VARCHAR(255) NOT NULL PRIMARY KEY,
    name TEXT NOT NULL,
    secret TEXT NOT NULL
);

ALTER TABLE mailinglijsten_abonnementen
	DROP CONSTRAINT mailinglijsten_abonnementen_lid_id_fkey;

ALTER TABLE mailinglijsten_abonnementen
	ADD CONSTRAINT mailinglijsten_abonnementen_lid_id_fkey FOREIGN KEY (lid_id)
	REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE;