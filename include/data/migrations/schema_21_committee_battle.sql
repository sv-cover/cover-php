ALTER TABLE committee_battle_scores RENAME TO committee_battle_scores_old;

CREATE TABLE committee_battle_scores (
    id SERIAL NOT NULL PRIMARY KEY,
    points integer,
    awarded_for text default '',
    awarded_on timestamp without time zone
);

CREATE TABLE committee_battle_committees (
    id SERIAL NOT NULL PRIMARY KEY,
    score_id integer NOT NULL REFERENCES committee_battle_scores (id) ON UPDATE CASCADE ON DELETE CASCADE,
    committee_id integer NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE committee_battle_users (
    id SERIAL NOT NULL PRIMARY KEY,
    score_id integer NOT NULL REFERENCES committee_battle_scores (id) ON UPDATE CASCADE ON DELETE CASCADE,
    member_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE
);

DO $$
DECLARE
    v_row record;
    v_score_id integer;
    v_committee_id integer;
BEGIN
    FOR v_row IN
        SELECT   
            array_agg(committee_id) as committee_ids,
            points,
            awarded_for,
            DATE(awarded_on) as awarded_on
        FROM committee_battle_scores_old
        GROUP BY
            points,
            awarded_for,
            DATE(awarded_on)
    LOOP
        INSERT INTO committee_battle_scores (points, awarded_for, awarded_on)
            VALUES (v_row.points, v_row.awarded_for, v_row.awarded_on)
            RETURNING id INTO v_score_id;

        FOREACH v_committee_id IN ARRAY v_row.committee_ids LOOP
            INSERT INTO committee_battle_committees (score_id, committee_id) VALUES (v_score_id, v_committee_id);
        END LOOP;
    END LOOP;
END $$;