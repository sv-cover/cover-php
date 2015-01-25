DELETE FROM profielen WHERE id IN (751, 762, 816);
ALTER TABLE profielen DROP COLUMN id;
ALTER TABLE profielen ADD CONSTRAINT profielen_pkey PRIMARY KEY(lidid);