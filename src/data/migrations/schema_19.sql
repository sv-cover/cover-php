CREATE TABLE foto_reacties_likes (
    id SERIAL NOT NULL,
    reactie_id integer NOT NULL REFERENCES foto_reacties (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE
);