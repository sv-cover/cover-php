-- Add a column to the registrations table to mark registrations as confirmed
-- but still pending to be inserted in Secretary. Previously registrations that
-- where confirmed were removed from the registrations table, even if Secretary
-- failed to add them to the database. By keeping them we can fix the problem
-- in the registration or in Secretary and try again without requiring to add
-- the registrations by hand into Secretary.
ALTER TABLE registrations
	ADD COLUMN confirmed_on timestamp without time zone DEFAULT NULL;