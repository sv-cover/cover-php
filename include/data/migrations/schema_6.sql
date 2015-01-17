CREATE INDEX ON fotos (boek);

DELETE FROM foto_reacties WHERE id IN (
	SELECT fr.id FROM foto_reacties fr LEFT JOIN fotos f ON f.id = fr.foto WHERE f.id IS NULL GROUP BY fr.id);

ALTER TABLE "foto_reacties"
ADD FOREIGN KEY ("foto") REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE;
