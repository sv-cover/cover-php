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
