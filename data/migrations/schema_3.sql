ALTER TABLE commissies ADD COLUMN
	vacancies_ DATE DEFAULT NULL;

UPDATE commissies SET vacancies_ = '2022-12-22' WHERE vacancies = 1;

ALTER TABLE commissies DROP COLUMN vacancies;

ALTER TABLE commissies RENAME COLUMN vacancies_ TO vacancies;

UPDATE configuratie SET value = 3 WHERE key = 'schema_version';
