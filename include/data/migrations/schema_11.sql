ALTER TABLE fotos
	ADD COLUMN filepath text,
	ADD COLUMN filehash character (8),
	ADD COLUMN created_on timestamp without time zone DEFAULT NULL,
	ADD COLUMN sort_index integer DEFAULT NULL;

ALTER TABLE fotos
	DROP COLUMN thumbwidth,
	DROP COLUMN thumbheight,
	DROP COLUMN url,
	DROP COLUMN thumburl;

ALTER TABLE foto_boeken
	ADD COLUMN sort_index integer DEFAULT NULL;