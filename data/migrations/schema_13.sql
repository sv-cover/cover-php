ALTER TABLE agenda
	ADD COLUMN replacement_for integer DEFAULT NULL;

UPDATE agenda SET replacement_for = (SELECT overrideid FROM agenda_moderate WHERE agendaid = agenda.id);

DROP TABLE agenda_moderate;