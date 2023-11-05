INSERT INTO profielen_privacy VALUES (10, 'nick');

ALTER TABLE leden
    ALTER COLUMN privacy TYPE bigint;