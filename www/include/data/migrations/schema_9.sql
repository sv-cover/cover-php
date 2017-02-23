ALTER TABLE profielen
	ALTER COLUMN wachtwoord TYPE varchar(255),
	ALTER COLUMN wachtwoord DROP NOT NULL;
