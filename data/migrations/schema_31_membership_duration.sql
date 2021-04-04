ALTER TABLE leden
	ADD COLUMN member_from DATE DEFAULT NULL,
	ADD COLUMN member_till DATE DEFAULT NULL,
	ADD COLUMN donor_from DATE DEFAULT NULL,
	ADD COLUMN donor_till DATE DEFAULT NULL;

-- Todo after we "flipped the switch"
-- ALTER TABLE leden DROP COLUMN type;