ALTER TABLE leden
	ADD COLUMN member_from DATE DEFAULT NULL,
	ADD COLUMN member_till DATE DEFAULT NULL,
	ADD COLUMN donor_from DATE DEFAULT NULL,
	ADD COLUMN donor_till DATE DEFAULT NULL;