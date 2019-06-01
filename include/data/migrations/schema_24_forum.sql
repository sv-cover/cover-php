ALTER TABLE forums DROP COLUMN type;

ALTER TABLE forum_acl RENAME uid TO author_id;
ALTER TABLE forum_acl RENAME type TO author_type;
ALTER TABLE forum_acl RENAME forumid TO forum_id;

ALTER TABLE forum_group_member RENAME guid TO group_id;
ALTER TABLE forum_group_member RENAME type to author_type;
ALTER TABLE forum_group_member RENAME uid TO author_id;

DROP TABLE forum_guestnames;

ALTER TABLE forum_lastvisits RENAME forum TO forum_id;

ALTER TABLE forum_messages RENAME thread TO thread_id;
ALTER TABLE forum_messages RENAME author TO author_id;

ALTER TABLE forum_sessionreads RENAME lid TO lid_id;
ALTER TABLE forum_sessionreads RENAME forum TO forum_id;
ALTER TABLE forum_sessionreads RENAME thread TO thread_id;

ALTER TABLE forum_visits RENAME lid TO lid_id;
ALTER TABLE forum_visits RENAME forum TO forum_id;

ALTER TABLE forum_threads RENAME forum TO forum_id;
ALTER TABLE forum_threads RENAME author TO author_id;
ALTER TABLE forum_threads ALTER COLUMN poll TYPE smallint;

-- Remove stale users
DELETE FROM forum_sessionreads WHERE lid_id NOT IN (SELECT id FROM leden);

ALTER TABLE forum_sessionreads
	DROP CONSTRAINT forum_sessionreads_lid_key,
	ADD CONSTRAINT forum_sessionreads_lid_key FOREIGN KEY (lid_id) REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
	ADD CONSTRAINT forum_sessionreads_pkey PRIMARY KEY (lid_id, forum_id, thread_id);

-- Remove stale users
DELETE FROM forum_visits WHERE lid_id NOT IN (SELECT id FROM leden);

-- Remove stale forums
DELETE FROM forum_visits WHERE forum_id NOT IN (SELECT id FROM forums);

-- Move old table out of the way
ALTER TABLE forum_visits RENAME TO forum_visits_old;

-- Remove sessionread temp stuff this one time
DELETE FROM forum_visits_old WHERE sessiondate IS NOT NULL;

-- Create a fresh one
CREATE TABLE forum_visits (
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    forum_id integer NOT NULL REFERENCES forums (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lastvisit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone,
    sessiondate timestamp without time zone,
    PRIMARY KEY (lid_id, forum_id)
);

-- Select all unique(!) entries
INSERT INTO forum_visits (lid_id, forum_id, lastvisit, sessiondate) SELECT lid_id, forum_id, lastvisit, NULL FROM (SELECT *, ROW_NUMBER() OVER (PARTITION BY lid_id, forum_id) as rwn FROM forum_visits_old) sq WHERE rwn = 1;

-- Drop the old table
DROP TABLE forum_visits_old;
