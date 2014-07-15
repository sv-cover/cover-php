ALTER TABLE ONLY configuratie
    ADD CONSTRAINT configuratie_pkey PRIMARY KEY (key);

ALTER TABLE ONLY configuratie
	DROP CONSTRAINT configuratie_key_key;

INSERT INTO configuratie (key, value) VALUES ('schema_version', 1);

CREATE TABLE announcements (
    id SERIAL NOT NULL,
    committee INTEGER NOT NULL REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    created_on TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT ('now'::text)::timestamp(6) WITHOUT TIME ZONE,
    visibility integer NOT NULL DEFAULT 0,
    CONSTRAINT announcements_pk PRIMARY KEY (id)
);

TRUNCATE TABLE announcements;

INSERT INTO announcements (committee, subject, created_on, message)
SELECT
	CASE
		WHEN forum_threads.author_type = 2 THEN forum_threads.author
		ELSE 0 -- bestuur
	END,
	forum_threads.subject,
	forum_threads.date,
	forum_messages.message
FROM
	forum_threads
LEFT JOIN forum_messages ON
	forum_messages.thread = forum_threads.id
	AND forum_messages.id IN (
		SELECT
			MIN(forum_messages.id)
		FROM
			forum_messages
		WHERE
			forum_messages.thread = forum_threads.id
		GROUP BY
			forum_messages.thread
	)
RIGHT JOIN commissies ON
	commissies.id = CASE
		WHEN forum_threads.author_type = 2 THEN forum_threads.author
		ELSE 0 -- bestuur
	END
WHERE
	forum_threads.forum IN (SELECT TO_NUMBER(value, '9999') FROM configuratie WHERE key = 'news_forum')
ORDER BY
	forum_threads.date ASC;

