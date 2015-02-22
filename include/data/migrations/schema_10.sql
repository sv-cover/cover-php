ALTER TABLE foto_boeken
	ADD COLUMN last_update TIMESTAMP DEFAULT NULL;

UPDATE foto_boeken SET last_update = (SELECT MAX(fotos.added_on) FROM fotos WHERE fotos.boek = foto_boeken.id)
