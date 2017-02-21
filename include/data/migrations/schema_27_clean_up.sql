ALTER TABLE pages RENAME owner TO committee_id;
ALTER TABLE pages ADD CONSTRAINT pages_committee_fkey 
	FOREIGN KEY (committee_id) REFERENCES commissies (id);


ALTER TABLE commissies RENAME page TO page_id;
ALTER TABLE commissies ADD CONSTRAINT commissies_page_fkey
	FOREIGN KEY (page_id) REFERENCES pages (id);

ALTER TABLE besturen RENAME page TO page_id;
ALTER TABLE besturen ADD CONSTRAINT besturen_page_fkey
	FOREIGN KEY (page_id) REFERENCES pages (id);

ALTER TABLE agenda RENAME commissie TO committee_id;
ALTER TABLE agenda ADD CONSTRAINT agenda_replacement_for_fkey
	FOREIGN KEY (replacement_for) REFERENCES agenda (id);

ALTER TABLE configuratie ADD CONSTRAINT configuratie_pkey
	PRIMARY KEY (key);

ALTER TABLE confirm ADD CONSTRAINT confirm_pkey
	PRIMARY KEY (key);

ALTER TABLE forum_group_member ADD CONSTRAINT forum_group_member_pkey
	PRIMARY KEY (id);

ALTER TABLE actieveleden RENAME TO committee_members;
ALTER TABLE committee_members RENAME lidid TO member_id;
ALTER TABLE committee_members RENAME commissieid TO committee_id;

ALTER TABLE foto_likes ADD CONSTRAINT foto_likes_pkey
	PRIMARY KEY (foto_id, lid_id);

DELETE FROM lid_fotos WHERE lid_id NOT IN (SELECT id FROM leden);
ALTER TABLE lid_fotos ADD CONSTRAINT lid_fotos_lid_id_fkey
	FOREIGN KEY (lid_id) REFERENCES leden (id)
		ON UPDATE CASCADE ON DELETE CASCADE;

DELETE FROM sessions WHERE member_id NOT IN (SELECT id FROM leden);
ALTER TABLE sessions ADD CONSTRAINT sessions_member_id_fkey
	FOREIGN KEY (member_id) REFERENCES leden (id)
		ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE sessions ADD CONSTRAINT sessions_override_member_id_fkey
	FOREIGN KEY (override_member_id) REFERENCES leden (id)
		ON UPDATE SET NULL ON DELETE SET NULL;