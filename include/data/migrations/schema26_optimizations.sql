-- Delete duplicate rows
DELETE FROM pollvoters WHERE EXISTS (
	SELECT 'x'
	FROM pollvoters i
	WHERE i.lid = pollvoters.lid
	  AND i.poll = pollvoters.poll
	  AND i.ctid < pollvoters.ctid
);

-- Create a UNIQUE index on the lid-poll combination
CREATE UNIQUE INDEX ON pollvoters (lid, poll);

-- All queries referencing foto_faces either are on lid_id + deleted or foto_id + deleted
CREATE INDEX ON foto_faces (lid_id, deleted);

CREATE INDEX ON foto_reacties (foto);

CREATE INDEX ON foto_boeken_visit (lid_id);

DROP INDEX fotos_boek_idx;
CREATE INDEX ON fotos (boek, hidden);