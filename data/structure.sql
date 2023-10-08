--
-- PostgreSQL database dump
--

-- Dumped from database version 9.3.1
-- Dumped by pg_dump version 9.3.1
-- Started on 2014-02-12 16:35:48 CET

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 240 (class 3079 OID 12018)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;

-- A function that turns Jösé to Jose :)
CREATE EXTENSION unaccent;

--
-- TOC entry 2627 (class 0 OID 0)
-- Dependencies: 240
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;


--
-- Member table. Mostly filled through api.php when you create a new
-- member in Secretary.
--

CREATE TABLE leden (
    id integer NOT NULL PRIMARY KEY, -- determined by member administration
    voornaam character varying(255) NOT NULL,
    tussenvoegsel character varying(255),
    achternaam character varying(255) NOT NULL,
    adres character varying(255) NOT NULL,
    postcode character varying(7) NOT NULL,
    woonplaats character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    geboortedatum date DEFAULT NULL,
    geslacht character(1) NOT NULL,
    telefoonnummer character varying(20),
    privacy integer NOT NULL,
    type integer DEFAULT 1,
    machtiging smallint,
    beginjaar integer DEFAULT NULL,
    onderschrift character varying(200),
    avatar character varying(100),
    homepage character varying(255),
    nick character varying(50),
    taal character varying(10) DEFAULT 'en'::character varying,
    member_from DATE DEFAULT NULL,
    member_till DATE DEFAULT NULL,
    donor_from DATE DEFAULT NULL,
    donor_till DATE DEFAULT NULL
);

-- Passwords for members are stored separately so they don't get fetched, ever.

CREATE TABLE passwords (
    lid_id INTEGER NOT NULL PRIMARY KEY REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    password character varying (255) NOT NULL
);

-- Photos are also stored separately because they are large! And currently you 
-- can have multiple photos (although only the last is shown).

CREATE TABLE lid_fotos (
    id SERIAL PRIMARY KEY,
    lid_id integer REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    foto bytea,
    foto_mtime timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

--
-- Pages. Used by show.php, and many other places such as the committee
-- pages, previous board pages, and many little bits throughout the website.
--

CREATE TABLE pages (
    id SERIAL PRIMARY KEY,
    committee_id integer NOT NULL,
    titel character varying(100) NOT NULL,
    content text,
    content_en text,
    content_de text,
    cover_image_url character varying(255) DEFAULT NULL,
    last_modified timestamp without time zone DEFAULT NULL,
    slug character varying(100) DEFAULT NULL
    CONSTRAINT uk_slug UNIQUE(slug)
);

--
-- Committee and working group table. Also main permission group table
-- (committees are permission groups in many cases)
--

CREATE TABLE commissies (
    id SERIAL PRIMARY KEY,
    type integer NOT NULL DEFAULT 1, -- 1: committee, 2: working group, 3: other (hidden)
    naam character varying(25) NOT NULL,
    login character varying(50), -- mainly used for pretty urls these days
    website character varying(100),
    page_id integer REFERENCES pages (id),
    hidden integer NOT NULL DEFAULT 0, # Deactivated
    vacancies DATE DEFAULT NULL,
    CONSTRAINT commissies_login_key UNIQUE(login)
);

ALTER TABLE pages ADD CONSTRAINT pages_committee_fkey 
    FOREIGN KEY (committee_id) REFERENCES commissies (id);

-- 
-- committee memberships
--

CREATE TABLE committee_members (
    id SERIAL PRIMARY KEY,
    member_id smallint NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    committee_id smallint NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE,
    functie character varying(50) -- Todo: field still needs an English name
);


--
-- committee email addresses, used for mailing lists to determine whether they may send mail
--

CREATE TABLE committee_email (
    committee_id smallint NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE,
    email TEXT,
    CONSTRAINT committee_email_uniq UNIQUE (committee_id, email)
);

CREATE INDEX ON committee_email (committee_id);

--
-- Previous boards page table. Very similar to committees due to the
-- hacky nature of this implementation. Maybe someday they will be merged
-- into commissies. Would be a mayor improvement ;) TODO
--

CREATE TABLE besturen (
    id SERIAL PRIMARY KEY,
    naam character varying(25) NOT NULL,
    login character varying(50), -- mainly used for pretty urls these days
    website character varying(100),
    page_id integer REFERENCES pages (id),
    CONSTRAINT besturen_login_key UNIQUE(login)
);

--
-- Calendar event table
--

CREATE TABLE agenda (
    id SERIAL PRIMARY KEY,
    kop character varying(100) NOT NULL,
    beschrijving text,
    committee_id integer NOT NULL REFERENCES commissies (id),
    van timestamp with time zone NOT NULL,
    tot timestamp with time zone,
    locatie character varying(100),
    image_url character varying(255) DEFAULT NULL,
    private smallint DEFAULT 0, -- boolean
    extern smallint NOT NULL DEFAULT 0, -- boolean
    facebook_id character varying(20) DEFAULT NULL,
    replacement_for integer DEFAULT NULL -- refers to itself
);

-- Todo: add a separate column for agenda item approving. Currently new
-- agenda items that are pending approval are marked with replacement_for=0
-- which violates this constraint, obviously. Better to create a separate
-- field for approving.
-- ALTER TABLE agenda ADD CONSTRAINT agenda_replacement_for_fkey
--     FOREIGN KEY (replacement_for) REFERENCES agenda (id);

--
-- Configuration table allows you to override stuff from config.inc
-- using the interface in the website. Of course, these values are
-- only accessible once a database connection has been set up. So
-- not useful for everything.
-- 

CREATE TABLE configuratie (
    key character varying(100) NOT NULL PRIMARY KEY,
    value text NOT NULL
);


--
-- Currently not actively used. Queried by DataModelMember, but never filled.
-- Probably happened manually in the passed. Todo: accept this data in api.php.
--

CREATE TABLE studies (
    lidid integer NOT NULL,
    studie character varying(100)
);


-- 
-- All photo album stuff
--

CREATE TABLE foto_boeken (
    id SERIAL PRIMARY KEY,
    parent_id integer DEFAULT 0 NOT NULL,
    titel character varying(255) NOT NULL,
    fotograaf text,
    date date,
    last_update timestamp DEFAULT NULL,
    beschrijving text,
    visibility integer NOT NULL DEFAULT 0,
    sort_index integer DEFAULT NULL
);

CREATE INDEX ON foto_boeken (parent_id);


CREATE TABLE foto_boeken_visit (
    boek_id integer NOT NULL REFERENCES foto_boeken (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    CONSTRAINT foto_boeken_visit_pk PRIMARY KEY (boek_id, lid_id)
);

CREATE INDEX ON foto_boeken_visit (lid_id);

-- Photo albums that are not actually in the database, like your likes or the faces photo book
CREATE TABLE foto_boeken_custom_visit (
    boek_id text NOT NULL,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    CONSTRAINT foto_boeken_custom_visit_pk PRIMARY KEY (boek_id, lid_id)
);


CREATE TABLE fotos (
    id SERIAL PRIMARY KEY,
    boek integer NOT NULL REFERENCES foto_boeken (id) ON DELETE CASCADE ON UPDATE CASCADE,
    beschrijving character varying(255),
    filepath text,
    filehash character (8),
    width integer,
    height integer,
    created_on timestamp without time zone DEFAULT NULL,
    added_on timestamp without time zone DEFAULT NULL,
    sort_index integer DEFAULT NULL,
    hidden BOOLEAN DEFAULT 'f'
);

CREATE INDEX ON fotos (boek);


CREATE TABLE foto_reacties (
    id SERIAL PRIMARY KEY,
    foto integer NOT NULL REFERENCES fotos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    auteur integer NOT NULL,
    reactie text NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone
);

CREATE INDEX ON foto_reacties (foto);


CREATE TABLE foto_reacties_likes (
    id SERIAL PRIMARY KEY,
    reactie_id integer NOT NULL REFERENCES foto_reacties (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE foto_likes (
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    lid_id integer NOT NULL REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    liked_on timestamp without time zone DEFAULT NULL,
    CONSTRAINT foto_likes_pkey PRIMARY KEY (foto_id, lid_id)
);

CREATE TABLE foto_faces (
    id SERIAL PRIMARY KEY,
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    x real NOT NULL,
    y real NOT NULL,
    w real NOT NULL,
    h real NOT NULL,
    lid_id integer REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    deleted boolean NOT NULL DEFAULT FALSE,
    tagged_on timestamp without time zone DEFAULT NULL,
    tagged_by INTEGER REFERENCES "leden" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
    custom_label character varying (255),
    cluster_id INTEGER DEFAULT NULL
);

CREATE INDEX ON foto_faces (lid_id, deleted);

-- Note, this hidden table is for people wanting to hide photos from their
-- own tagged photos album. There is a 'hidden' field in the fotos table to
-- hide photos people complain about completely, from everywhere.

CREATE TABLE foto_hidden (
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    lid_id integer REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT foto_hidden_pkey PRIMARY KEY (foto_id, lid_id)
);


--
-- Available privacy properties for profiles
--

CREATE TABLE profielen_privacy (
    id integer NOT NULL PRIMARY KEY,
    field text NOT NULL
);

INSERT INTO profielen_privacy VALUES (0, 'naam');
INSERT INTO profielen_privacy VALUES (1, 'adres');
INSERT INTO profielen_privacy VALUES (2, 'postcode');
INSERT INTO profielen_privacy VALUES (3, 'woonplaats');
INSERT INTO profielen_privacy VALUES (4, 'geboortedatum');
INSERT INTO profielen_privacy VALUES (5, 'beginjaar');
INSERT INTO profielen_privacy VALUES (7, 'telefoonnummer');
INSERT INTO profielen_privacy VALUES (8, 'email');
INSERT INTO profielen_privacy VALUES (9, 'foto');

--
-- Log in sessions
-- 


CREATE TYPE session_type AS ENUM ('member', 'device');
CREATE TABLE sessions (
    session_id character(40) NOT NULL PRIMARY KEY,
    type session_type NOT NULL DEFAULT 'member',
    member_id integer REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    created_on timestamp with time zone,
    ip_address inet,
    last_active_on timestamp with time zone,
    timeout interval,
    application text,
    override_member_id integer DEFAULT NULL REFERENCES leden (id) ON UPDATE SET NULL ON DELETE SET NULL,
    override_committees varchar(255) DEFAULT NULL,
    device_enabled boolean NOT NULL DEFAULT FALSE,
    device_name varchar(255) DEFAULT NULL
);

--
-- Mailinglijsten
--

CREATE TABLE mailinglijsten (
    id SERIAL PRIMARY KEY,
    naam varchar(100) NOT NULL,
    adres varchar(255) NOT NULL UNIQUE,
    omschrijving text NOT NULL,
    type integer NOT NULL DEFAULT 1, -- default type is opt-in
    publiek boolean NOT NULL DEFAULT TRUE,
    toegang integer,
    commissie integer NOT NULL DEFAULT 0 REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET DEFAULT,
    tag varchar(100) NOT NULL DEFAULT 'Cover',
    has_members boolean NOT NULL DEFAULT TRUE, -- condition for opt-out lists
    has_contributors boolean NOT NULL DEFAULT FALSE, -- condition for opt-out lists
    has_starting_year integer DEFAULT NULL, -- condition for opt-out lists
    on_subscription_subject TEXT DEFAULT NULL,
    on_subscription_message TEXT DEFAULT NULL,
    on_first_email_subject TEXT DEFAULT NULL,
    on_first_email_message TEXT DEFAULT NULL
);

CREATE TABLE mailinglijsten_abonnementen (
    abonnement_id CHAR(40) NOT NULL PRIMARY KEY,
    mailinglijst_id integer NOT NULL REFERENCES mailinglijsten (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer DEFAULT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    naam VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    ingeschreven_op timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    opgezegd_op timestamp DEFAULT NULL
);

CREATE TABLE mailinglijsten_berichten (
    id SERIAL PRIMARY KEY,
    mailinglijst integer DEFAULT NULL REFERENCES mailinglijsten (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    commissie integer DEFAULT NULL REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    bericht TEXT NOT NULL,
    sender TEXT DEFAULT NULL,
    return_code integer NOT NULL,
    verwerkt_op timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE mailinglijsten_opt_out (
    id SERIAL PRIMARY KEY,
    mailinglijst_id integer NOT NULL REFERENCES mailinglijsten (id),
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    opgezegd_op timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE mailinglijsten_queue (
    id SERIAL PRIMARY KEY,
    destination varchar NOT NULL,
    destination_type varchar NOT NULL DEFAULT 'mailinglist',
    mailinglist_id integer DEFAULT NULL REFERENCES mailinglijsten (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
    message TEXT NOT NULL,
    status varchar NOT NULL DEFAULT 'waiting',
    queued_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    processing_on timestamp without time zone
);

--
-- The sticker map :)
-- (If only we had Postgis.. we would not have lat/lng fields)
--

CREATE TABLE stickers (
  id serial PRIMARY KEY,
  label text,
  omschrijving text NOT NULL DEFAULT '',
  lat double precision,
  lng double precision,
  toegevoegd_op date,
  toegevoegd_door integer DEFAULT NULL REFERENCES leden (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET DEFAULT,
  foto bytea DEFAULT NULL,
  foto_mtime timestamp without time zone
);

--
-- Announcements (simple posts)
-- 

CREATE TABLE announcements (
    id SERIAL PRIMARY KEY,
    committee_id INTEGER NOT NULL REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    created_on TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT ('now'::text)::timestamp(6) WITHOUT TIME ZONE,
    visibility integer NOT NULL DEFAULT 0
);

--
-- Temporarily stored registrations. Waiting area until their email address is
-- confirmed.
--

CREATE TABLE registrations (
    confirmation_code VARCHAR(255) NOT NULL PRIMARY KEY,
    data TEXT NOT NULL,
    registerd_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    confirmed_on timestamp without time zone DEFAULT NULL
);

--
-- Table with applications that are allowed access to (parts of) the api.
--

CREATE TABLE applications (
    key VARCHAR(255) NOT NULL PRIMARY KEY,
    name TEXT NOT NULL,
    secret TEXT NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE partners(
    id serial PRIMARY KEY,
    name character varying(255) NOT NULL,
    type integer NOT NULL,
    url character varying(255) NOT NULL,
    logo_url character varying(255) NOT NULL,
    logo_dark_url character varying(255) DEFAULT NULL,
    profile text,
    has_banner_visible integer NOT NULL DEFAULT 0, -- Not visible
    has_profile_visible integer NOT NULL DEFAULT 0, -- Not visible
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE vacancies(
    id serial PRIMARY KEY,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    type integer NOT NULL,
    url character varying(255),
    study_phase integer NOT NULL,
    partner_id integer DEFAULT NULL REFERENCES partners (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    partner_name character varying(255) DEFAULT NULL,
    created_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    updated_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    check ((partner_id IS NULL) != (partner_name IS NULL)) -- XOR on parnter id and name, name to be used for 
);


--
-- Temporarily stored password (re)set codes.
--

CREATE TABLE password_reset_tokens (
    key character (40) PRIMARY KEY,
    member_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    created_on timestamp without time zone NOT NULL
);

--
-- Temporarily stored email address confirmation codes
-- Note that these are only used when changing your email,
-- and not during registration. Those are stored in the registrations table.
-- Reason: member_id has to be known for this table to work.
--

CREATE TABLE email_confirmation_tokens (
    key character (40) PRIMARY KEY,
    member_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    email TEXT NOT NULL,
    created_on timestamp without time zone NOT NULL
);

--
-- Sign up forms
--

CREATE TABLE sign_up_forms (
    id SERIAL PRIMARY KEY,
    committee_id INTEGER REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE SET NULL,
    agenda_id INTEGER DEFAULT NULL REFERENCES agenda (id) ON UPDATE CASCADE ON DELETE SET NULL,
    created_on timestamp without time zone NOT NULL,
    open_on timestamp without time zone DEFAULT NULL,
    closed_on timestamp without time zone DEFAULT NULL,
    participant_limit INTEGER DEFAULT NULL,
);

CREATE TABLE sign_up_fields(
    id SERIAL PRIMARY KEY,
    form_id INTEGER NOT NULL REFERENCES sign_up_forms (id) ON UPDATE CASCADE ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    properties TEXT NOT NULL,
    sort_index INTEGER DEFAULT NULL,
    deleted BOOLEAN DEFAULT FALSE
);

CREATE INDEX sign_up_fields_form_id_idx ON public.sign_up_fields(form_id);

CREATE UNIQUE INDEX sign_up_fields_form_id_name_idx ON public.sign_up_fields(form_id, name);


CREATE TABLE sign_up_entries(
    id SERIAL PRIMARY KEY,
    form_id INTEGER NOT NULL REFERENCES sign_up_forms (id) ON UPDATE CASCADE ON DELETE CASCADE,
    member_id INTEGER DEFAULT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    created_on timestamp without time zone NOT NULL
);

CREATE TABLE sign_up_entry_values(
    entry_id INTEGER NOT NULL REFERENCES sign_up_entries (id) ON UPDATE CASCADE ON DELETE CASCADE,
    field_id INTEGER NOT NULL REFERENCES sign_up_fields (id) ON UPDATE CASCADE ON DELETE CASCADE,
    value TEXT NOT NULL,
    PRIMARY KEY (entry_id, field_id)
);


--
-- Polls
--
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
