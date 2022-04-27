CREATE TABLE committee_battle_scores (
    id SERIAL NOT NULL PRIMARY KEY,
    committee_id integer NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE,
    points integer,
    awarded_for text default '',
    awarded_on timestamp without time zone
);

INSERT INTO pages (owner, titel) VALUES (0, 'committee_battle');