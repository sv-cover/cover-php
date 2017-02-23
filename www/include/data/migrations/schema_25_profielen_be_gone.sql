ALTER TABLE leden 
	ADD COLUMN onderschrift character varying(200),
	ADD COLUMN avatar character varying(100),
	ADD COLUMN homepage character varying(255),
	ADD COLUMN nick character varying(50),
	ADD COLUMN taal character varying(10) DEFAULT 'en'::character varying;

UPDATE leden
SET
	onderschrift = p.onderschrift,
	avatar = p.avatar,
	homepage = p.homepage,
	nick = p.nick,
	taal = p.taal
FROM profielen p WHERE leden.id = p.lidid;

CREATE TABLE passwords (
    lid_id INTEGER NOT NULL PRIMARY KEY REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    password character varying (255) NOT NULL
);

INSERT INTO passwords (lid_id, password) SELECT lidid, wachtwoord FROM profielen WHERE wachtwoord IS NOT NULL;

DROP TABLE profielen;
