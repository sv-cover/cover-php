CREATE TABLE polls (
    id serial PRIMARY KEY,
    member_id integer DEFAULT NULL REFERENCES leden (id) ON DELETE SET DEFAULT,
    committee_id integer DEFAULT NULL REFERENCES commissies (id) ON DELETE SET DEFAULT,
    question text NOT NULL,
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    updated_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    closed_on timestamp without time zone DEFAULT NULL
);

CREATE TABLE poll_options (
    id serial PRIMARY KEY,
    poll_id integer NOT NULL REFERENCES polls (id) ON DELETE CASCADE,
    option character varying(255) NOT NULL
);

CREATE TABLE poll_votes (
    id serial PRIMARY KEY,
    poll_option_id integer NOT NULL REFERENCES poll_options (id) ON DELETE CASCADE,
    member_id integer DEFAULT NULL REFERENCES leden (id) ON DELETE SET DEFAULT, -- Preserve vote, even if we don't have member
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
    -- no updated_on, votes cannot be updated
);

CREATE TABLE poll_comments (
    id serial PRIMARY KEY,
    poll_id integer NOT NULL REFERENCES polls (id) ON DELETE CASCADE,
    member_id integer DEFAULT NULL REFERENCES leden (id) ON DELETE SET DEFAULT,  -- Preserve comment, even if we don't have member
    comment text NOT NULL,
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    updated_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE poll_likes (
    id serial PRIMARY KEY,
    poll_id integer NOT NULL REFERENCES polls (id) ON DELETE CASCADE,
    member_id integer DEFAULT NULL REFERENCES leden (id) ON DELETE SET DEFAULT, -- Preserve like, even if we don't have member
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
    -- no updated_on, likes cannot be updated
    CONSTRAINT poll_like_uniq UNIQUE (poll_id, member_id)
);

CREATE TABLE poll_comment_likes (
    id serial PRIMARY KEY,
    poll_comment_id integer NOT NULL REFERENCES poll_comments (id) ON DELETE CASCADE,
    member_id integer DEFAULT NULL REFERENCES leden (id) ON DELETE SET DEFAULT, -- Preserve like, even if we don't have member
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
    -- no updated_on, likes cannot be updated
    CONSTRAINT poll_commment_like_uniq UNIQUE (poll_comment_id, member_id)
);

-- Transfer old polls from forum to new datastructure
DO $$
DECLARE
    v_poll_forum_id integer;
    v_thread record;
    v_option record;
    v_message record;
    v_vote record;
    v_poll_id integer;
    v_poll_option_id integer;
BEGIN
    -- Get poll forum ID
    SELECT CAST(configuratie.value AS integer) INTO v_poll_forum_id
      FROM configuratie 
     WHERE configuratie.key = 'poll_forum';

    FOR v_thread IN
        SELECT ft.*
              ,fm.message
          FROM forum_threads AS ft
          LEFT JOIN (
                SELECT DISTINCT ON (thread_id)
                       thread_id
                      ,message
                  FROM forum_messages
                 ORDER BY thread_id, id
               ) AS fm ON ft.id = fm.thread_id
         WHERE ft.forum_id = v_poll_forum_id
           AND ft.poll = 1
    LOOP
        -- Create poll
        INSERT INTO polls (member_id, committee_id, question, created_on, closed_on)
            VALUES (
                CASE -- member_id
                    WHEN v_thread.author_type = 1 AND EXISTS (SELECT 1 FROM leden WHERE id = v_thread.author_id) THEN v_thread.author_id
                    ELSE NULL
                END,
                CASE -- committee_id
                    WHEN v_thread.author_type = 2 AND EXISTS (SELECT 1 FROM commissies WHERE id = v_thread.author_id) THEN v_thread.author_id
                    ELSE NULL
                END,
                CASE -- question
                    WHEN v_thread.subject NOT ILIKE v_thread.message THEN concat_ws(E'\n', v_thread.subject, v_thread.message)
                    ELSE v_thread.subject
                END,
                v_thread.date, -- created_on
                v_thread.date + INTERVAL '7 day' -- closed_on
            )
            RETURNING id INTO v_poll_id;
        
        -- Transfer options and votes
        FOR v_option IN
            SELECT *
              FROM pollopties
             WHERE pollid = v_thread.id
        LOOP
            INSERT INTO poll_options (poll_id, option)
                VALUES (v_poll_id, v_option.optie)
                RETURNING id INTO v_poll_option_id;

            FOR i IN 0..(v_option.stemmen - 1) LOOP
                INSERT INTO poll_votes (poll_option_id) VALUES (v_poll_option_id);
            END LOOP;
        END LOOP;

        -- Transfer comments
        FOR v_message IN
            SELECT *
              FROM forum_messages
             WHERE thread_id = v_thread.id
             ORDER BY id
            OFFSET 1 -- First message is part of question
        LOOP
            INSERT INTO poll_comments (poll_id, member_id, comment, created_on)
                VALUES (
                    v_poll_id, --poll_id
                    CASE -- member_id
                        WHEN v_message.author_type = 1 AND EXISTS (SELECT 1 FROM leden WHERE id = v_thread.author_id) THEN v_message.author_id
                        ELSE NULL
                    END,
                    v_message.message, -- comment
                    v_message.date -- created_on
                );
        END LOOP;
    END LOOP;
END $$;
