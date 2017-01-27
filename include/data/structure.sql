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
-- TOC entry 172 (class 1259 OID 24129)
-- Name: agenda_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE agenda_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 173 (class 1259 OID 24131)
-- Name: agenda; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE agenda (
    id integer DEFAULT nextval('agenda_id_seq'::regclass) NOT NULL,
    kop character varying(100) NOT NULL,
    beschrijving text,
    commissie smallint NOT NULL,
    van timestamp with time zone NOT NULL,
    tot timestamp with time zone,
    locatie character varying(100),
    private smallint DEFAULT 0,
    extern smallint NOT NULL DEFAULT 0,
    facebook_id character varying(20) DEFAULT NULL,
    replacement_for integer DEFAULT NULL
);

--
-- TOC entry 182 (class 1259 OID 24175)
-- Name: besturen_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE besturen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 183 (class 1259 OID 24177)
-- Name: besturen; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE besturen (
    id smallint NOT NULL PRIMARY KEY,
    theme character varying(100),
    page integer
);


CREATE TABLE cache (
    key character(40) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL,
    expires integer NOT NULL
);

CREATE TABLE commissies (
    id SERIAL NOT NULL PRIMARY KEY,
    type integer NOT NULL DEFAULT 1,
    naam character varying(25) NOT NULL,
    login character varying(50),
    website character varying(100),
    nocaps text,
    page integer,
    hidden integer NOT NULL DEFAULT 0,
    vacancies DATE DEFAULT NULL,
    CONSTRAINT commissies_login_key UNIQUE(login)
);

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

--
-- TOC entry 188 (class 1259 OID 24203)
-- Name: configuratie; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE configuratie (
    key character varying(100) NOT NULL,
    value text NOT NULL
);

--
-- TOC entry 189 (class 1259 OID 24209)
-- Name: confirm; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE confirm (
    key character(32) NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone,
    value text NOT NULL,
    type text DEFAULT ''::text NOT NULL
);

--
-- TOC entry 190 (class 1259 OID 24217)
-- Name: forum_acl_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_acl_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 191 (class 1259 OID 24219)
-- Name: forum_acl; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_acl (
    id integer DEFAULT nextval('forum_acl_id_seq'::regclass) NOT NULL,
    forumid integer NOT NULL,
    type smallint,
    uid integer,
    permissions integer
);

--
-- TOC entry 192 (class 1259 OID 24223)
-- Name: forum_group_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 193 (class 1259 OID 24225)
-- Name: forum_group; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_group (
    id integer DEFAULT nextval('forum_group_id_seq'::regclass) NOT NULL,
    name character varying(50)
);

--
-- TOC entry 194 (class 1259 OID 24229)
-- Name: forum_group_member_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_group_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 195 (class 1259 OID 24231)
-- Name: forum_group_member; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_group_member (
    id integer DEFAULT nextval('forum_group_member_id_seq'::regclass) NOT NULL,
    guid integer,
    type smallint,
    uid integer
);

--
-- TOC entry 196 (class 1259 OID 24235)
-- Name: forum_guestnames; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_guestnames (
    thread integer NOT NULL,
    reply integer NOT NULL,
    naam character varying(100) NOT NULL,
    email character varying(100)
);

--
-- TOC entry 197 (class 1259 OID 24238)
-- Name: forum_header_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_header_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 198 (class 1259 OID 24240)
-- Name: forum_header; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_header (
    id integer DEFAULT nextval('forum_header_id_seq'::regclass) NOT NULL,
    name character varying(150),
    "position" integer
);

--
-- TOC entry 199 (class 1259 OID 24244)
-- Name: forum_lastvisits; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_lastvisits (
    lid integer NOT NULL,
    forum integer NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone
);

--
-- TOC entry 200 (class 1259 OID 24248)
-- Name: forum_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 201 (class 1259 OID 24250)
-- Name: forum_messages; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_messages (
    id integer DEFAULT nextval('forum_messages_id_seq'::regclass) NOT NULL,
    thread integer NOT NULL,
    author integer NOT NULL,
    message text NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone NOT NULL,
    author_type smallint DEFAULT 1
);

--
-- TOC entry 202 (class 1259 OID 24259)
-- Name: forum_sessionreads; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_sessionreads (
    lid integer NOT NULL,
    forum integer NOT NULL,
    thread integer NOT NULL
);

--
-- TOC entry 203 (class 1259 OID 24262)
-- Name: forum_threads_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forum_threads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 204 (class 1259 OID 24264)
-- Name: forum_threads; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_threads (
    id integer DEFAULT nextval('forum_threads_id_seq'::regclass) NOT NULL,
    forum integer NOT NULL,
    author integer NOT NULL,
    subject character varying(250) NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone NOT NULL,
    author_type smallint DEFAULT 1,
    poll integer DEFAULT 0 NOT NULL
);

--
-- TOC entry 205 (class 1259 OID 24271)
-- Name: forum_visits; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forum_visits (
    lid integer NOT NULL,
    forum integer NOT NULL,
    lastvisit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone,
    sessiondate timestamp without time zone
);

--
-- TOC entry 206 (class 1259 OID 24275)
-- Name: forums_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE forums_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 207 (class 1259 OID 24277)
-- Name: forums; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE forums (
    id integer DEFAULT nextval('forums_id_seq'::regclass) NOT NULL,
    name character varying(50) NOT NULL,
    description character varying(255) NOT NULL,
    type smallint DEFAULT 0,
    "position" integer DEFAULT 0
);

--
-- TOC entry 217 (class 1259 OID 24336)
-- Name: leden; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE leden (
    id integer NOT NULL PRIMARY KEY,
    voornaam character varying(255) NOT NULL,
    tussenvoegsel character varying(255),
    achternaam character varying(255) NOT NULL,
    adres character varying(255) NOT NULL,
    postcode character varying(7) NOT NULL,
    woonplaats character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    geboortedatum date NOT NULL,
    geslacht character(1) NOT NULL,
    telefoonnummer character varying(20),
    privacy integer NOT NULL,
    type integer DEFAULT 1,
    machtiging smallint,
    beginjaar integer DEFAULT date_part('year'::text, now())
);

CREATE TABLE studies (
    lidid integer NOT NULL,
    studie character varying(100)
);

CREATE TABLE actieveleden (
    id SERIAL NOT NULL,
    lidid smallint NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    commissieid smallint NOT NULL REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE CASCADE,
    functie character varying(50)
);

--
-- TOC entry 208 (class 1259 OID 24283)
-- Name: foto_boeken_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE foto_boeken_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 209 (class 1259 OID 24285)
-- Name: foto_boeken; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE foto_boeken (
    id integer DEFAULT nextval('foto_boeken_id_seq'::regclass) NOT NULL,
    parent integer DEFAULT 0 NOT NULL,
    titel character varying(255) NOT NULL,
    fotograaf text,
    date date,
    last_update timestamp DEFAULT NULL,
    beschrijving text,
    visibility integer NOT NULL DEFAULT 0,
    sort_index integer DEFAULT NULL,
    CONSTRAINT foto_boeken_pkey PRIMARY KEY (id)
);

CREATE INDEX ON foto_boeken (parent);

CREATE TABLE foto_boeken_visit (
    boek_id integer NOT NULL REFERENCES foto_boeken (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    CONSTRAINT foto_boeken_visit_pk PRIMARY KEY (boek_id, lid_id)
);


--
-- TOC entry 211 (class 1259 OID 24300)
-- Name: foto_reacties_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE foto_reacties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 212 (class 1259 OID 24302)
-- Name: foto_reacties; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE foto_reacties (
    id integer DEFAULT nextval('foto_reacties_id_seq'::regclass) NOT NULL,
    foto integer NOT NULL REFERENCES fotos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    auteur integer NOT NULL,
    reactie text NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone
);

CREATE TABLE foto_reacties_likes (
    id SERIAL NOT NULL,
    reactie_id integer NOT NULL REFERENCES foto_reacties (id) ON UPDATE CASCADE ON DELETE CASCADE,
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE
);

--
-- TOC entry 213 (class 1259 OID 24310)
-- Name: fotos_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE fotos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 214 (class 1259 OID 24312)
-- Name: fotos; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE fotos (
    id integer DEFAULT nextval('fotos_id_seq'::regclass) NOT NULL,
    boek integer NOT NULL REFERENCES foto_boeken (id) ON DELETE CASCADE ON UPDATE CASCADE,
    beschrijving character varying(255),
    filepath text,
    filehash character (8),
    width integer,
    height integer,
    created_on timestamp without time zone DEFAULT NULL,
    added_on timestamp without time zone DEFAULT NULL,
    sort_index integer DEFAULT NULL,
    hidden BOOLEAN DEFAULT 'f',
    CONSTRAINT fotos_pkey PRIMARY KEY (id)
);

CREATE INDEX ON fotos (boek);

CREATE TABLE foto_likes (
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    lid_id integer NOT NULL REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    liked_on timestamp without time zone DEFAULT NULL
);

CREATE TABLE foto_faces (
    id SERIAL NOT NULL,
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    x real NOT NULL,
    y real NOT NULL,
    w real NOT NULL,
    h real NOT NULL,
    lid_id integer REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    deleted boolean NOT NULL DEFAULT FALSE,
    tagged_by INTEGER REFERENCES "leden" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
    custom_label character varying (255),
    CONSTRAINT foto_faces_pkey PRIMARY KEY (id)
);

CREATE TABLE foto_hidden (
    foto_id integer NOT NULL REFERENCES "fotos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    lid_id integer REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT foto_hidden_pkey PRIMARY KEY (foto_id, lid_id)
);

--
-- TOC entry 218 (class 1259 OID 24341)
-- Name: lid_fotos_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE lid_fotos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 219 (class 1259 OID 24343)
-- Name: lid_fotos; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE lid_fotos (
    id integer DEFAULT nextval('lid_fotos_id_seq'::regclass) NOT NULL,
    lid_id integer,
    foto bytea,
    foto_mtime timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

--
-- TOC entry 224 (class 1259 OID 24369)
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 225 (class 1259 OID 24371)
-- Name: pages; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE pages (
    id integer DEFAULT nextval('pages_id_seq'::regclass) NOT NULL,
    owner integer NOT NULL,
    titel character varying(100) NOT NULL,
    content text,
    content_en text,
    content_de text
);

--
-- TOC entry 226 (class 1259 OID 24385)
-- Name: pollopties_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE pollopties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 227 (class 1259 OID 24387)
-- Name: pollopties; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE pollopties (
    id integer DEFAULT nextval('pollopties_id_seq'::regclass) NOT NULL,
    pollid integer NOT NULL REFERENCES forum_threads (id) ON DELETE CASCADE ON UPDATE CASCADE,
    optie character varying(150) NOT NULL,
    stemmen smallint DEFAULT 0 NOT NULL
);

--
-- TOC entry 228 (class 1259 OID 24392)
-- Name: pollvoters; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE pollvoters (
    lid integer NOT NULL REFERENCES leden (id) ON DELETE CASCADE ON UPDATE CASCADE,
    poll integer NOT NULL REFERENCES forum_threads (id) ON DELETE CASCADE ON UPDATE CASCADE
);

--
-- TOC entry 229 (class 1259 OID 24395)
-- Name: profielen_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE profielen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- TOC entry 230 (class 1259 OID 24397)
-- Name: profielen; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE profielen (
    lidid integer NOT NULL REFERENCES "leden" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    wachtwoord character varying(255),
    onderschrift character varying(200),
    avatar character varying(100),
    homepage character varying(255),
    msn character varying(100),
    icq character varying(15),
    nick character varying(50),
    taal character varying(10) DEFAULT 'nl'::character varying
);

--
-- TOC entry 231 (class 1259 OID 24405)
-- Name: profielen_privacy; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE profielen_privacy (
    id integer NOT NULL,
    field text NOT NULL
);

--
-- TOC entry 232 (class 1259 OID 24414)
-- Name: sessions; Type: TABLE; Schema: public; Owner: webcie; Tablespace: 
--

CREATE TABLE sessions (
    session_id character(40) NOT NULL,
    member_id integer,
    created_on timestamp with time zone,
    ip_address inet,
    last_active_on timestamp with time zone,
    timeout interval,
    application text,
    override_member_id integer DEFAULT NULL,
    override_committees varchar(255) DEFAULT NULL
);

--
-- TOC entry 2551 (class 0 OID 24125)
-- Dependencies: 171
-- Data for Name: actieveleden; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO actieveleden VALUES (746, 709, 26, 'Algemeen Lid', NULL);
INSERT INTO actieveleden VALUES (837, 709, 13, 'Voorzitter', NULL);
INSERT INTO actieveleden VALUES (864, 709, 0, 'Secretaris', NULL);
INSERT INTO actieveleden VALUES (890, 709, 1, 'Penningmeester', NULL);


--
-- TOC entry 2628 (class 0 OID 0)
-- Dependencies: 170
-- Name: actieveleden_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('actieveleden_id_seq', 919, true);


--
-- TOC entry 2553 (class 0 OID 24131)
-- Dependencies: 173
-- Data for Name: agenda; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2629 (class 0 OID 0)
-- Dependencies: 172
-- Name: agenda_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('agenda_id_seq', 1928, true);


--
-- TOC entry 2563 (class 0 OID 24177)
-- Dependencies: 183
-- Data for Name: besturen; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO besturen VALUES (1, 'Bestuur I', 'bestuur1', '', 'bestuur i', 76);
INSERT INTO besturen VALUES (2, 'Bestuur II', 'bestuur2', '', 'bestuur ii', 75);
INSERT INTO besturen VALUES (3, 'Bestuur III', 'bestuur3', '', 'bestuur iii', 74);
INSERT INTO besturen VALUES (4, 'Bestuur IV', 'bestuur4', '', 'bestuur iv', 73);
INSERT INTO besturen VALUES (5, 'Bestuur V', 'bestuur5', '', 'bestuur v', 72);
INSERT INTO besturen VALUES (6, 'Bestuur VI', 'bestuur6', '', 'bestuur vi', 71);
INSERT INTO besturen VALUES (7, 'Bestuur VII', 'bestuur7', '', 'bestuur vii', 70);
INSERT INTO besturen VALUES (8, 'Bestuur VIII', 'bestuur8', '', 'bestuur viii', 69);
INSERT INTO besturen VALUES (9, 'Bestuur IX', 'bestuur9', '', 'betuur ix', 68);
INSERT INTO besturen VALUES (10, 'Bestuur X', 'bestuur10', '', 'bestuur x', 67);
INSERT INTO besturen VALUES (11, 'Bestuur XI', 'bestuur11', '', 'bestuur xi', 66);
INSERT INTO besturen VALUES (12, 'Bestuur XII', 'bestuur12', '', 'bestuur xii', 65);
INSERT INTO besturen VALUES (13, 'Bestuur XIII', 'bestuur13', '', 'bestuur xiii', 64);
INSERT INTO besturen VALUES (14, 'Bestuur XIV', 'bestuur14', '', 'bestuur xiv', 63);
INSERT INTO besturen VALUES (15, 'Bestuur XV', 'bestuur15', '', 'bestuur xv', 62);
INSERT INTO besturen VALUES (16, 'Bestuur XVI', 'bestuur16', '', 'bestuur xvi', 61);
INSERT INTO besturen VALUES (17, 'Bestuur XVII', 'bestuur17', '', 'bestuur xvii', 60);
INSERT INTO besturen VALUES (18, 'Bestuur XVIII', 'bestuur18', '', 'bestuur xviii', 59);
INSERT INTO besturen VALUES (19, 'Bestuur XIX', 'bestuur19', '', 'bestuur xix', 58);
INSERT INTO besturen VALUES (20, 'Bestuur XX', 'bestuur20', '', 'bestuur xx', 57);
INSERT INTO besturen VALUES (21, 'Bestuur XXI', 'bestuur21', NULL, 'bestuur xxi', 82);
INSERT INTO besturen VALUES (22, 'Bestuur XXII', 'bestuur22', NULL, 'bestuur xxii', 83);


--
-- TOC entry 2633 (class 0 OID 0)
-- Dependencies: 182
-- Name: besturen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('besturen_id_seq', 22, true);


--
-- TOC entry 2564 (class 0 OID 24184)
-- Dependencies: 184
-- Data for Name: boeken; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO boeken VALUES (14, 3, '(CS) Net Computing', 'Distributed Systems: Concepts and Design', 66.83, 1, 'G. Coulouris, J. Dollimore, T. Kindberg');
INSERT INTO boeken VALUES (10, 3, '(CS) Net Computing', 'Distributed Systems: Principles and Paradigms', 71.06, 1, 'M. van Steen, A.S. Tanenbaum');
INSERT INTO boeken VALUES (13, 2, '(AI) Onderzoeksmethodologie', 'Empirical Methods in Artificial Intelligence', 69.51, 1, 'P.R. Cohen');
INSERT INTO boeken VALUES (11, 1, '(CS) Computer Architectures and Networks', 'Structured Computer Organization', 63.01, 1, 'T. Austin, A.S. Tanenbaum');
INSERT INTO boeken VALUES (9, 1, '(AI + CS) Algorithms and Datastructures in C', 'The C Programming Language', 45.81, 1, 'B.W. Kernighan, D.M. Ritchie');
INSERT INTO boeken VALUES (8, 2, '(CS) Gevorderde Algoritmen en Datastructuren', 'Algorithm Design', 56.07, 1, 'M.T. Goodrich, R. Tamassia');
INSERT INTO boeken VALUES (15, 2, '(CS) Software Engineering 1', 'Software Engineering: Principles and Practice', 47.72, 1, 'H. van Vliet');
INSERT INTO boeken VALUES (16, 1, '(AI) Cognition: Exploring the Science of the Mind', 'Cognitive Psychology', 47.15, 1, 'D. Reisberg');
INSERT INTO boeken VALUES (19, 2, '(AI) Filosofie van WTS', 'An Introduction to Science and Technological Studies', 25.74, 1, 'J.A. Harbers');
INSERT INTO boeken VALUES (21, 1, '(CS) Business Intelligence', 'Decision Enhancement Services: Rehearsing the Future for Decisions that Matter', 43.08, 1, 'P.G.W. Keen, H.G. Sol');
INSERT INTO boeken VALUES (17, 2, '(AI) Filosofie van WTS', 'Pandora''s Hope: Essays on the Reality of Science Studies', 25.74, 1, 'B. Latour');
INSERT INTO boeken VALUES (12, 3, '(AI) Kunstmatige Intelligentie 2', 'Artificial Intelligence: A Modern Approach', 65.88, 1, 'P. Norvig, S. Russell');
INSERT INTO boeken VALUES (22, 2, '(AI) Cognition and Attention', 'Attention: Theory and Practice', 84.99, 1, 'A. Johnson, R.W. Proctor');
INSERT INTO boeken VALUES (20, 3, '(AI + CS) Computer Graphics', 'Fundamentals of Computer Graphics', 78.29, 1, 'S. Marschner, P. Shirley');


--
-- TOC entry 2565 (class 0 OID 24188)
-- Dependencies: 185
-- Data for Name: boeken_categorie; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO boeken_categorie VALUES (1, 'Eerstejaars');
INSERT INTO boeken_categorie VALUES (2, 'Tweedejaars');
INSERT INTO boeken_categorie VALUES (3, 'Derdejaars');
INSERT INTO boeken_categorie VALUES (4, 'Ma-AI');
INSERT INTO boeken_categorie VALUES (5, 'Ma-MMC');
INSERT INTO boeken_categorie VALUES (6, 'Keuzevakken Bachelor');


--
-- TOC entry 2567 (class 0 OID 24196)
-- Dependencies: 187
-- Data for Name: commissies; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO commissies VALUES (16, 'SympoCie', 'sympocie', NULL, NULL, 19, 0);
INSERT INTO commissies VALUES (15, 'Foetsie', 'foetsie', NULL, NULL, 15, 0);
INSERT INTO commissies VALUES (13, 'McCie', 'mccie', '/~mccie/', NULL, 13, 0);
INSERT INTO commissies VALUES (11, 'StudCie', 'studcie', NULL, 'studcie', 11, 0);
INSERT INTO commissies VALUES (8, 'IntroCie', 'introcie', NULL, 'introcie', 8, 0);
INSERT INTO commissies VALUES (6, 'ExCie', 'excie', NULL, 'excie', 6, 0);
INSERT INTO commissies VALUES (3, 'BoekCie', 'boekcie', NULL, 'boekcie', 3, 0);
INSERT INTO commissies VALUES (2, 'Actie', 'actie', NULL, 'actie', 2, 0);
INSERT INTO commissies VALUES (24, 'Promotie', 'promotie', '', 'promotie', 48, 0);
INSERT INTO commissies VALUES (25, 'MeisCie', 'meiscie', '', 'meiscie', 49, 0);
INSERT INTO commissies VALUES (1, 'WebCie', 'webcie', NULL, 'webcie', 1, 0);
INSERT INTO commissies VALUES (20, 'Conditie', 'conditie', 'http://conditie.svcover.nl', 'conditie', 44, 0);
INSERT INTO commissies VALUES (0, 'Bestuur', 'board', NULL, 'board', 0, 1);
INSERT INTO commissies VALUES (27, 'BHVcie', 'bhvcie', 'http://bhvcie.svcover.nl', 'bhvcie', 81, 0);
INSERT INTO commissies VALUES (18, 'SLACKcie', 'slackcie', NULL, 'slackcie', 42, 0);
INSERT INTO commissies VALUES (7, 'FotoCie', 'fotocie', NULL, 'fotocie', 7, 0);
INSERT INTO commissies VALUES (17, 'EerstejaarsCie', 'eerstejaarscie', 'http://www.ai.rug.nl/~eerstejaarscie/', 'eerstejaarscie', 39, 0);
INSERT INTO commissies VALUES (21, 'LanCie', 'lancie', 'lancie.svcover.nl', 'lancie', 45, 0);
INSERT INTO commissies VALUES (22, 'PCie', 'pcie', 'pcie.svcover.nl', 'pcie', 46, 0);
INSERT INTO commissies VALUES (12, 'KasCie', 'kascie', NULL, NULL, 12, 0);
INSERT INTO commissies VALUES (14, 'Raad van Advies', 'rva', NULL, NULL, 14, 0);
INSERT INTO commissies VALUES (5, 'Brainstorm', 'brainstorm', NULL, 'brainstorm', 5, 0);
INSERT INTO commissies VALUES (26, 'PRcie', 'prcie', 'prcie.svcover.nl', 'prcie', 50, 0);
INSERT INTO commissies VALUES (4, 'AlmanakCie', 'almanakcie', NULL, 'almanakcie', 4, 0);
INSERT INTO commissies VALUES (9, 'LustrumCie', 'lustrumcie', NULL, 'lustrumcie', 9, 0);


--
-- TOC entry 2634 (class 0 OID 0)
-- Dependencies: 186
-- Name: commissies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('commissies_id_seq', 27, true);


--
-- TOC entry 2568 (class 0 OID 24203)
-- Dependencies: 188
-- Data for Name: configuratie; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO configuratie VALUES ('weblog_forum', '26');
INSERT INTO configuratie VALUES ('poll_forum', '28');
INSERT INTO configuratie VALUES ('spam_count', '619924');
INSERT INTO configuratie VALUES ('boeken_bestellen', '0');


--
-- TOC entry 2569 (class 0 OID 24209)
-- Dependencies: 189
-- Data for Name: confirm; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2571 (class 0 OID 24219)
-- Dependencies: 191
-- Data for Name: forum_acl; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO forum_acl VALUES (1, 9, 2, 2, 15);
INSERT INTO forum_acl VALUES (2, 10, 2, 4, 15);
INSERT INTO forum_acl VALUES (3, 11, 2, 0, 15);
INSERT INTO forum_acl VALUES (4, 12, 2, 3, 15);
INSERT INTO forum_acl VALUES (5, 13, 2, 5, 15);
INSERT INTO forum_acl VALUES (6, 14, 2, 1, 15);
INSERT INTO forum_acl VALUES (7, 15, 2, 6, 15);
INSERT INTO forum_acl VALUES (8, 16, 2, 15, 15);
INSERT INTO forum_acl VALUES (9, 17, 2, 7, 15);
INSERT INTO forum_acl VALUES (10, 18, 2, 8, 15);
INSERT INTO forum_acl VALUES (11, 19, 2, 12, 15);
INSERT INTO forum_acl VALUES (12, 20, 2, 9, 15);
INSERT INTO forum_acl VALUES (13, 21, 2, 13, 15);
INSERT INTO forum_acl VALUES (16, 24, 2, 11, 15);
INSERT INTO forum_acl VALUES (17, 25, 2, 16, 15);
INSERT INTO forum_acl VALUES (18, 26, 2, 0, 15);
INSERT INTO forum_acl VALUES (19, 26, -1, -1, 5);
INSERT INTO forum_acl VALUES (22, 28, -1, -1, 13);
INSERT INTO forum_acl VALUES (23, 28, 2, -1, 5);
INSERT INTO forum_acl VALUES (24, 3, 2, -1, 15);
INSERT INTO forum_acl VALUES (26, 29, 2, -1, 15);
INSERT INTO forum_acl VALUES (27, 8, -1, -1, 15);
INSERT INTO forum_acl VALUES (28, 30, 2, 17, 15);
INSERT INTO forum_acl VALUES (25, 29, -1, -1, 5);
INSERT INTO forum_acl VALUES (48, 28, 2, 1, 15);
INSERT INTO forum_acl VALUES (1, 9, 2, 2, 15);
INSERT INTO forum_acl VALUES (2, 10, 2, 4, 15);
INSERT INTO forum_acl VALUES (3, 11, 2, 0, 15);
INSERT INTO forum_acl VALUES (4, 12, 2, 3, 15);
INSERT INTO forum_acl VALUES (5, 13, 2, 5, 15);
INSERT INTO forum_acl VALUES (6, 14, 2, 1, 15);
INSERT INTO forum_acl VALUES (7, 15, 2, 6, 15);
INSERT INTO forum_acl VALUES (8, 16, 2, 15, 15);
INSERT INTO forum_acl VALUES (9, 17, 2, 7, 15);
INSERT INTO forum_acl VALUES (10, 18, 2, 8, 15);
INSERT INTO forum_acl VALUES (11, 19, 2, 12, 15);
INSERT INTO forum_acl VALUES (12, 20, 2, 9, 15);
INSERT INTO forum_acl VALUES (13, 21, 2, 13, 15);
INSERT INTO forum_acl VALUES (16, 24, 2, 11, 15);
INSERT INTO forum_acl VALUES (17, 25, 2, 16, 15);
INSERT INTO forum_acl VALUES (18, 26, 2, 0, 15);
INSERT INTO forum_acl VALUES (19, 26, -1, -1, 5);
INSERT INTO forum_acl VALUES (22, 28, -1, -1, 13);
INSERT INTO forum_acl VALUES (23, 28, 2, -1, 5);
INSERT INTO forum_acl VALUES (24, 3, 2, -1, 15);
INSERT INTO forum_acl VALUES (26, 29, 2, -1, 15);
INSERT INTO forum_acl VALUES (27, 8, -1, -1, 15);
INSERT INTO forum_acl VALUES (28, 30, 2, 17, 15);
INSERT INTO forum_acl VALUES (25, 29, -1, -1, 5);
INSERT INTO forum_acl VALUES (48, 28, 2, 1, 15);
INSERT INTO forum_acl VALUES (1, 9, 2, 2, 15);
INSERT INTO forum_acl VALUES (2, 10, 2, 4, 15);
INSERT INTO forum_acl VALUES (3, 11, 2, 0, 15);
INSERT INTO forum_acl VALUES (4, 12, 2, 3, 15);
INSERT INTO forum_acl VALUES (5, 13, 2, 5, 15);
INSERT INTO forum_acl VALUES (6, 14, 2, 1, 15);
INSERT INTO forum_acl VALUES (7, 15, 2, 6, 15);
INSERT INTO forum_acl VALUES (8, 16, 2, 15, 15);
INSERT INTO forum_acl VALUES (9, 17, 2, 7, 15);
INSERT INTO forum_acl VALUES (10, 18, 2, 8, 15);
INSERT INTO forum_acl VALUES (11, 19, 2, 12, 15);
INSERT INTO forum_acl VALUES (12, 20, 2, 9, 15);
INSERT INTO forum_acl VALUES (13, 21, 2, 13, 15);
INSERT INTO forum_acl VALUES (16, 24, 2, 11, 15);
INSERT INTO forum_acl VALUES (17, 25, 2, 16, 15);
INSERT INTO forum_acl VALUES (18, 26, 2, 0, 15);
INSERT INTO forum_acl VALUES (19, 26, -1, -1, 5);
INSERT INTO forum_acl VALUES (22, 28, -1, -1, 13);
INSERT INTO forum_acl VALUES (23, 28, 2, -1, 5);
INSERT INTO forum_acl VALUES (24, 3, 2, -1, 15);
INSERT INTO forum_acl VALUES (26, 29, 2, -1, 15);
INSERT INTO forum_acl VALUES (27, 8, -1, -1, 15);
INSERT INTO forum_acl VALUES (28, 30, 2, 17, 15);
INSERT INTO forum_acl VALUES (25, 29, -1, -1, 5);
INSERT INTO forum_acl VALUES (48, 28, 2, 1, 15);
INSERT INTO forum_acl VALUES (49, 32, 2, 18, 15);
INSERT INTO forum_acl VALUES (51, 33, 2, 22, 15);
INSERT INTO forum_acl VALUES (53, 34, 2, 21, 15);
INSERT INTO forum_acl VALUES (57, 3, -1, -1, 15);
INSERT INTO forum_acl VALUES (32, 9, 2, 0, 0);
INSERT INTO forum_acl VALUES (32, 9, 2, 0, 0);
INSERT INTO forum_acl VALUES (32, 9, 2, 0, 0);
INSERT INTO forum_acl VALUES (35, 13, 2, 0, 0);
INSERT INTO forum_acl VALUES (35, 13, 2, 0, 0);
INSERT INTO forum_acl VALUES (35, 13, 2, 0, 0);
INSERT INTO forum_acl VALUES (33, 10, 2, 0, 0);
INSERT INTO forum_acl VALUES (33, 10, 2, 0, 0);
INSERT INTO forum_acl VALUES (33, 10, 2, 0, 0);
INSERT INTO forum_acl VALUES (34, 12, 2, 0, 0);
INSERT INTO forum_acl VALUES (34, 12, 2, 0, 0);
INSERT INTO forum_acl VALUES (34, 12, 2, 0, 0);
INSERT INTO forum_acl VALUES (37, 30, 2, 0, 0);
INSERT INTO forum_acl VALUES (37, 30, 2, 0, 0);
INSERT INTO forum_acl VALUES (37, 30, 2, 0, 0);
INSERT INTO forum_acl VALUES (38, 15, 2, 0, 0);
INSERT INTO forum_acl VALUES (38, 15, 2, 0, 0);
INSERT INTO forum_acl VALUES (38, 15, 2, 0, 0);
INSERT INTO forum_acl VALUES (39, 16, 2, 0, 0);
INSERT INTO forum_acl VALUES (39, 16, 2, 0, 0);
INSERT INTO forum_acl VALUES (39, 16, 2, 0, 0);
INSERT INTO forum_acl VALUES (40, 17, 2, 0, 0);
INSERT INTO forum_acl VALUES (40, 17, 2, 0, 0);
INSERT INTO forum_acl VALUES (40, 17, 2, 0, 0);
INSERT INTO forum_acl VALUES (41, 18, 2, 0, 0);
INSERT INTO forum_acl VALUES (41, 18, 2, 0, 0);
INSERT INTO forum_acl VALUES (41, 18, 2, 0, 0);
INSERT INTO forum_acl VALUES (54, 34, 2, 0, 0);
INSERT INTO forum_acl VALUES (43, 20, 2, 0, 0);
INSERT INTO forum_acl VALUES (43, 20, 2, 0, 0);
INSERT INTO forum_acl VALUES (43, 20, 2, 0, 0);
INSERT INTO forum_acl VALUES (44, 21, 2, 0, 0);
INSERT INTO forum_acl VALUES (44, 21, 2, 0, 0);
INSERT INTO forum_acl VALUES (44, 21, 2, 0, 0);
INSERT INTO forum_acl VALUES (52, 33, 2, 0, 0);
INSERT INTO forum_acl VALUES (45, 22, 2, 0, 0);
INSERT INTO forum_acl VALUES (45, 22, 2, 0, 0);
INSERT INTO forum_acl VALUES (45, 22, 2, 0, 0);
INSERT INTO forum_acl VALUES (50, 32, 2, 0, 0);
INSERT INTO forum_acl VALUES (46, 24, 2, 0, 0);
INSERT INTO forum_acl VALUES (46, 24, 2, 0, 0);
INSERT INTO forum_acl VALUES (46, 24, 2, 0, 0);
INSERT INTO forum_acl VALUES (47, 25, 2, 0, 0);
INSERT INTO forum_acl VALUES (47, 25, 2, 0, 0);
INSERT INTO forum_acl VALUES (47, 25, 2, 0, 0);
INSERT INTO forum_acl VALUES (56, 35, 2, 0, 0);
INSERT INTO forum_acl VALUES (58, 36, 2, 20, 15);
INSERT INTO forum_acl VALUES (59, 37, 2, 25, 15);
INSERT INTO forum_acl VALUES (60, 38, 2, 24, 15);
INSERT INTO forum_acl VALUES (61, 22, 2, 26, 15);
INSERT INTO forum_acl VALUES (36, 14, 2, 0, 0);
INSERT INTO forum_acl VALUES (36, 14, 2, 0, 0);
INSERT INTO forum_acl VALUES (36, 14, 2, 0, 0);
INSERT INTO forum_acl VALUES (71, 23, 2, 14, 15);
INSERT INTO forum_acl VALUES (74, 39, -1, -1, 15);
INSERT INTO forum_acl VALUES (75, 39, 2, -1, 15);
INSERT INTO forum_acl VALUES (78, 42, 3, 6, 15);
INSERT INTO forum_acl VALUES (81, 43, 3, 9, 15);
INSERT INTO forum_acl VALUES (83, 44, 3, 8, 15);
INSERT INTO forum_acl VALUES (85, 45, 3, 7, 15);
INSERT INTO forum_acl VALUES (86, 45, 2, 9, 7);
INSERT INTO forum_acl VALUES (87, 46, 3, 10, 15);
INSERT INTO forum_acl VALUES (88, 46, 2, 9, 7);
INSERT INTO forum_acl VALUES (89, 43, 2, 9, 7);
INSERT INTO forum_acl VALUES (80, 42, 2, 9, 7);
INSERT INTO forum_acl VALUES (84, 44, 2, 9, 7);
INSERT INTO forum_acl VALUES (90, 47, 3, 11, 15);
INSERT INTO forum_acl VALUES (91, 47, 2, 9, 7);
INSERT INTO forum_acl VALUES (92, 48, 3, 12, 15);
INSERT INTO forum_acl VALUES (93, 48, 2, 9, 7);


--
-- TOC entry 2635 (class 0 OID 0)
-- Dependencies: 190
-- Name: forum_acl_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_acl_id_seq', 93, true);


--
-- TOC entry 2573 (class 0 OID 24225)
-- Dependencies: 193
-- Data for Name: forum_group; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO forum_group VALUES (5, 'temp');
INSERT INTO forum_group VALUES (6, 'Aardbeienbavarois');
INSERT INTO forum_group VALUES (7, 'R.A.M.M.');
INSERT INTO forum_group VALUES (8, 'H.U.N.T.E.R.S.');
INSERT INTO forum_group VALUES (9, 'Ballenbakbal');
INSERT INTO forum_group VALUES (10, 'RoddelCie');
INSERT INTO forum_group VALUES (11, 'Tochgeentijdvoor!');
INSERT INTO forum_group VALUES (12, 'We win or we booze');


--
-- TOC entry 2636 (class 0 OID 0)
-- Dependencies: 192
-- Name: forum_group_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_group_id_seq', 12, true);


--
-- TOC entry 2575 (class 0 OID 24231)
-- Dependencies: 195
-- Data for Name: forum_group_member; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO forum_group_member VALUES (35, 11, 1, 709);


--
-- TOC entry 2637 (class 0 OID 0)
-- Dependencies: 194
-- Name: forum_group_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_group_member_id_seq', 42, true);


--
-- TOC entry 2576 (class 0 OID 24235)
-- Dependencies: 196
-- Data for Name: forum_guestnames; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2578 (class 0 OID 24240)
-- Dependencies: 198
-- Data for Name: forum_header; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO forum_header VALUES (3, 'Algemeen', 7);
INSERT INTO forum_header VALUES (2, 'Cover', 1);
INSERT INTO forum_header VALUES (4, 'Website', 12);
INSERT INTO forum_header VALUES (1, 'Commissies', 16);
INSERT INTO forum_header VALUES (8, 'Scavenger hunt', 42);


--
-- TOC entry 2638 (class 0 OID 0)
-- Dependencies: 197
-- Name: forum_header_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_header_id_seq', 8, true);

--
-- TOC entry 2639 (class 0 OID 0)
-- Dependencies: 200
-- Name: forum_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_messages_id_seq', 10861, true);

--
-- TOC entry 2640 (class 0 OID 0)
-- Dependencies: 203
-- Name: forum_threads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forum_threads_id_seq', 1277, true);


--
-- TOC entry 2587 (class 0 OID 24277)
-- Dependencies: 207
-- Data for Name: forums; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO forums VALUES (31, 'Informatica', 'Alles over nieuwtjes en onderzoek over informatica.', 0, 6);
INSERT INTO forums VALUES (8, 'Algemeen', 'Hier moet je zijn voor alles wat niet ergens anders hoort.', 0, 8);
INSERT INTO forums VALUES (5, 'Kunstmatige Intelligentie', 'Alles over nieuwtjes, onderzoek en ethische vragen over Kunstmatige Intelligentie.', 1, 5);
INSERT INTO forums VALUES (4, 'De studie', 'Klachten, vragen en opmerkingen over KI of informatica aan de RuG.', 0, 4);
INSERT INTO forums VALUES (3, 'Cover', 'Tips voor de vereniging? Activiteit was geweldig? Bespreek het hier.', 0, 2);
INSERT INTO forums VALUES (29, 'Activiteiten', 'Bespreek hier alle uitjes, borrels en andere activiteiten binnen Cover.', 0, 3);
INSERT INTO forums VALUES (6, 'Marktkraam', 'Verhandel hier alles, van kamers en boeken tot meubilair en je oma.', 0, 10);
INSERT INTO forums VALUES (7, 'Roddelhoek', 'De roddels die iedereen moet weten.', 0, 11);
INSERT INTO forums VALUES (28, 'Cover polls', 'Wil je weten wat andere mensen over een bepaald onderwerp vinden, plaats dan hier een leuke poll. Deze polls komen ook op de voorpagina te staan. In dit forum kun je elke 14 dagen een nieuwe poll plaatsen.', 0, 13);
INSERT INTO forums VALUES (26, 'Weblog', 'Weblog van het Bestuur', 0, 15);
INSERT INTO forums VALUES (9, 'Commissie: Actie', 'Privéforum voor de Actie', 0, 17);
INSERT INTO forums VALUES (10, 'Commissie: AlmanakCie', 'Privéforum voor de AlmanakCie', 0, 18);
INSERT INTO forums VALUES (11, 'Commissie: Bestuur', 'Privéforum voor het Bestuur', 0, 19);
INSERT INTO forums VALUES (12, 'Commissie: BoekCie', 'Privéforum voor de BoekCie', 0, 20);
INSERT INTO forums VALUES (13, 'Commissie: Brainstorm', 'Privéforum voor de Brainstorm', 0, 21);
INSERT INTO forums VALUES (36, 'Commissie: Conditie', 'Privéforum voor de Conditie', 0, 22);
INSERT INTO forums VALUES (30, 'Commissie: Eerstejaarscie', 'Privéforum voor de Eerstejaarscie', 0, 23);
INSERT INTO forums VALUES (15, 'Commissie: ExCie', 'Privéforum voor de ExCie', 0, 24);
INSERT INTO forums VALUES (16, 'Commissie: Foetsie', 'Privéforum voor de Foetsie', 0, 25);
INSERT INTO forums VALUES (17, 'Commissie: FotoCie', 'Privéforum voor de FotoCie', 0, 26);
INSERT INTO forums VALUES (18, 'Commissie: IntroCie', 'Privéforum voor de IntroCie', 0, 27);
INSERT INTO forums VALUES (19, 'Commissie: KasCie', 'Privéforum voor de KasCie', 0, 28);
INSERT INTO forums VALUES (34, 'Commissie: LanCie', 'Privéforum voor de LanCie', 0, 29);
INSERT INTO forums VALUES (20, 'Commissie: LustrumCie', 'Privéforum voor de LustrumCie', 0, 30);
INSERT INTO forums VALUES (21, 'Commissie: McCie', 'Privéforum voor de McCie', 0, 31);
INSERT INTO forums VALUES (37, 'Commissie: MeisCie', 'Privéforum voor de MeisCie', 0, 32);
INSERT INTO forums VALUES (33, 'Commissie: PCie', 'Privéforum voor de PCie', 0, 33);
INSERT INTO forums VALUES (22, 'Commissie: PRcie', 'Privéforum voor de PRcie', 0, 34);
INSERT INTO forums VALUES (38, 'Commissie: Promotie', 'Privéforum voor de Promotie', 0, 35);
INSERT INTO forums VALUES (23, 'Commissie: Raad van Advies', 'Privéforum voor de Raad van Advies', 0, 36);
INSERT INTO forums VALUES (32, 'Commissie: SLACKcie', 'Privéforum voor de SLACKcie', 0, 37);
INSERT INTO forums VALUES (24, 'Commissie: StudCie', 'Privéforum voor de StudCie', 0, 38);
INSERT INTO forums VALUES (25, 'Commissie: SympoCie', 'Privéforum voor de SympoCie', 0, 39);
INSERT INTO forums VALUES (35, 'Commissie: ToetsCie', 'Privéforum voor de ToetsCie', 0, 40);
INSERT INTO forums VALUES (14, 'Commissie: WebCie', 'Privéforum voor de WebCie', 0, 41);
INSERT INTO forums VALUES (39, 'Externe Activiteiten', 'Is er iets leuks te doen buiten Cover en geen plek op de agenda? Lees het hier!', 0, 9);
INSERT INTO forums VALUES (42, 'Aardbeienbavarois', 'Forum voor team Aardbeienbavarois', 0, 43);
INSERT INTO forums VALUES (43, 'Ballenbakbal', 'Forum voor team Ballenbakbal', 0, 44);
INSERT INTO forums VALUES (44, 'H.U.N.T.E.R.S.', 'Forum voor team H.U.N.T.E.R.S.', 0, 45);
INSERT INTO forums VALUES (45, 'R.A.M.M.', 'Forum voor team R.A.M.M.', 0, 47);
INSERT INTO forums VALUES (46, 'RoddelCie', 'Forum voor team RoddelCie', 0, 48);
INSERT INTO forums VALUES (47, 'Tochgeentijdvoor!', 'Forum voor team Tochgeentijdvoor!', 0, 49);
INSERT INTO forums VALUES (48, 'We win or we booze', 'Forum voor team We win or we booze', 0, 46);


--
-- TOC entry 2641 (class 0 OID 0)
-- Dependencies: 206
-- Name: forums_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('forums_id_seq', 48, true);


--
-- TOC entry 2589 (class 0 OID 24285)
-- Dependencies: 209
-- Data for Name: foto_boeken; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2642 (class 0 OID 0)
-- Dependencies: 208
-- Name: foto_boeken_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('foto_boeken_id_seq', 995, true);


--
-- TOC entry 2592 (class 0 OID 24302)
-- Dependencies: 212
-- Data for Name: foto_reacties; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2643 (class 0 OID 0)
-- Dependencies: 211
-- Name: foto_reacties_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('foto_reacties_id_seq', 5890, true);


--
-- TOC entry 2594 (class 0 OID 24312)
-- Dependencies: 214
-- Data for Name: fotos; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2644 (class 0 OID 0)
-- Dependencies: 213
-- Name: fotos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('fotos_id_seq', 38004, true);


--
-- TOC entry 2597 (class 0 OID 24336)
-- Dependencies: 217
-- Data for Name: leden; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO leden VALUES (709, 'Jelmer', 'van der', 'Linde', '', '', '', 'user@example.com', '1970-01-01', 'm', '', 1059361359, 1, NULL, 2008);


--
-- TOC entry 2599 (class 0 OID 24343)
-- Dependencies: 219
-- Data for Name: lid_fotos; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2646 (class 0 OID 0)
-- Dependencies: 218
-- Name: lid_fotos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('lid_fotos_id_seq', 795, true);

--
-- TOC entry 2605 (class 0 OID 24371)
-- Dependencies: 225
-- Data for Name: pages; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO pages VALUES (0, 0, 'Bestuurspagina', '[H1]Coverbestuur XXII (2013/2014)[/H1]

[commissie_foto]

[center][ [url=mailto:bestuur@svcover.nl]E-mail[/url] ][/center]

[commissie_leden]

[commissie_agenda]
', '[H1]Board XXII of Cover (2013/2014)[/H1]

[commissie_foto]

[center][ [url=mailto:bestuur@svcover.nl]E-mail[/url] ][/center]

[commissie_leden]

[commissie_agenda]
', NULL);
INSERT INTO pages VALUES (2, 2, 'Commissiepagina Actie', '[samenvatting]Actie staat voor activiteitencommissie. De Actie organiseert 1 keer per maand op de eerste woensdag een borrel. Verder probeert de Actie minimaal 1 activiteit te organiseren per maand![/samenvatting]
[h1]Actie[/h1]
[commissie_foto]
[center][ [commissie_email] ][/center]

Actie staat voor activiteitencommissie. Wij hebben als doel om iedere eerste woensdag van de maand een borrel te houden in café ''t Pleidooi. Verder proberen we elke maand minimaal één grote activiteit te organiseren, hierbij geeft de Actie vaak korting. We proberen ook elke maand een spellenavond en een filmavond te organiseren.

Kijk voor meer info op onze [url=http://actie.svcover.nl]website[/url]!
[commissie_leden]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (3, 3, 'Commissiepagina Boekcie', '[samenvatting]Als je lid bent van Cover dan kun je je boeken bij ons bestellen. [/samenvatting]
[H1]Boekcie[/H1][center][[url=mailto:boekcie@svcover.nl]E-Mail[/url]][/center]

[commissie_foto]

Als je lid bent van Cover dan kun je je boeken bij ons bestellen. Op de Engelstalige boeken krijg je dan sowieso 11% korting op de verkoopprijs van onze leverancier! Elke periode kun je via de website je bestelling doorgeven van de boeken die jij denkt nodig te hebben in die periode.
 
De boekenverkoop is eens [b]per periode[/b]. Al je boeken kun je via deze [b][url=http://www.svcover.nl/boeken.php]website[/url] [/b] bestellen. Voor vragen hierover kun je mailen met de BoekCie. [url=mailto:boekcie@svcover.nl]boekcie@svcover.nl[/url]

Naast de boeken die je via deze site kunt bestellen is het voor Coverleden ook mogelijk om met behulp van hun lidmaatschapspas zelf boeken af te halen bij onze boekenleverancier Selexyz. Meer informatie hierover kun je bij het bestuur krijgen: [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url]

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (4, 4, 'Commissiepagina Almanakcie', '[samenvatting]Ieder jaar brengt de AlmanakCie een almanak uit. Deze wordt uitgereikt op de jaarlijkse Almanakborrel. [/samenvatting]
[H1]AlmanakCie[/H1][center][[url=mailto:almanak@svcover.nl]E-Mail[/url]][/center]

[commissie_foto]

Ook dit jaar zal er een almanak uitgebracht worden. De totstandkoming van dit boekwerk ligt in handen van de AlmanakCie. De verwachting is dat de almanak tegen het einde van het academisch jaar klaar zal zijn. Het thema is nog een verrassing.

Mocht je nog een oude almanak willen hebben, er zijn nog een aantal exemplaren van de laatste paar jaar te vinden in de Coverkamer! 

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (5, 5, 'Commissiepagina Brainstorm', '[h1]Brainstorm[/h1][samenvatting]Tenminste drie keer per jaar ons geliefde verenigingsblad "Brainstorm" uitbrengen, dat is de missie van de Brainstormcommissie.[/samenvatting]
[commissie_foto]
Tenminste drie keer per jaar ons geliefde verenigingsblad "Brainstorm" uitbrengen, dat is de missie van de Brainstormcommissie.

[h2]De commissie[/h2]
[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (8, 8, 'Commissiepagina Introcie', '[samenvatting]De Introductie Commissie heeft de taak om de toekomstige KI-ers e informatici te introduceren bij elkaar, ouderejaars, staf en Cover.[/samenvatting]
[H1]IntroCie[/H1]
[commissie_foto]
[center][ [commissie_email] ][/center]
De Introductie Commissie heeft de taak om de toekomstige KI-ers en informatici te introduceren bij elkaar, ouderejaars, staf en Cover. Hiervoor organiseren ze jaarlijks een introductiedag in Groningen, en een drie dagen durend kamp. Het thema van komend jaar staat nog niet vast. Zodra er meer informatie bekend is, komt deze op www.introcie.nl te staan!

[commissie_leden]
[commissie_agenda]

', '[samenvatting]It is the job of the Introduction Committee of Cover to introduce the new AI and CS students to each other, Cover and staff.[/samenvatting]
[H1]Introduction Committee[/H1]
[commissie_foto]
[center][ [commissie_email] ][/center]
It is the job of the Introduction Committee of Cover to introduce the new AI and CS students to each other, Cover and staff. To do this, they organise an introduction day in Groningen and a weekend called Introcamp. The theme of this year''s Introcamp has yet to be revealed! For more information go to introcie.nl!

[commissie_leden]
[commissie_agenda]
', NULL);
INSERT INTO pages VALUES (9, 9, 'Commissiepagina Lustrumcie', '[samenvatting]De LustrumCie organiseert de festiviteiten uit de Lustrumweek. Het eerstvolgende (4de) lustrum zal gevierd worden in 2013.[/samenvatting]
[H1]LustrumCie[/H1]
[commissie_foto]

Op 20 september 2013 wordt Cover alweer 20 jaar! Om deze prachtige verjaardag op gepaste wijze te vieren organiseert de LustrumCie van 16 t/m 20 september een geweldige lustrumweek met als thema: Lust, Rum & Rock ''n Roll! Wil je contact met ons opnemen, dan kun je mailen naar: lustrumcie@svcover.nl
Voor meer informatie over het lustrum, kijk op:
http://lustrumandrocknroll.svcover.nl

In 2008 bestond Cover 15 jaar. 15 t/m 19 september is daarom een prachtig lustrum neergezet. Als je wil weten wat er allemaal is gebeurd tijdens dit lustrum, kijk dan op:
http://de-opkomst.svcover.nl

Om te zien wat er tijdens het tweede lustrum is gebeurd, kijk op:
http://www.ai.rug.nl/~lustrum/
[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (83, 0, 'Bestuur XXII', '[samenvatting]2013/2014
"Enlightenment"[/samenvatting]
[h1]Bestuur XXII: Enlightenment[/h1]
____BESTUURSFOTO____

[h2]Leden[/h2]
Voorzitter: Harmke Alkemade
Secretaris: Jelmer van der Linde
Penningmeester: Martijn Luinstra
Commissaris Intern: Davey Schilling
Commissaris Extern: Sybren Römer
', NULL, NULL);
INSERT INTO pages VALUES (11, 11, 'Commissiepagina Studcie', '[samenvatting]De StudCie organiseert regelmatig studiegerelateerde activiteiten, zoals lezingen en excursies naar KI- en Informatica gerelateerde bedrijven.[/samenvatting]

[H1]StudCie[/H1]
[commissie_foto]

[center] [ [url=mailto:studcie@svcover.nl]E-Mail[/url] ][/center]


[b]De Studiegerelateerde activiteiten commissie (StudCie) organiseert regelmatig studiegerelateerde activiteiten, zoals lezingen, excursies naar KI- en Informatica gerelateerde bedrijven en natuurlijk jaarlijks een reis naar een buitenlandse stad.[/b]

Als Informatica- of KI-student kom je regelmatig in aanraking met onderzoek: de docenten van je vakken (en andere wetenschappers) zijn enthousiast over hun werk. De kans is echter groot dat je na het behalen van je bul geen onderzoeksbaan krijgt (of wilt) en daarom is het belangrijk om te weten wat je verder met je opleiding kunt doen.

Hierom probeert de StudCie iedere twee weken een studiegerelateerde activiteit te organiseren. Op deze manier blijf je op de hoogte van de huidige ontwikkelingen binnen Informatica en KI, kom je te weten waar je interesses liggen en waar je uiteindelijk zou willen werken na je afstuderen. Kom dus eens naar een van onze activiteiten en laat je verrassen door de toekomstperspectieven die KI en Informatica bieden!

[commissie_leden]
[commissie_agenda]', '[samenvatting]The Study Activities Commission regularly arranges study-related activities such as lectures and trips to AI- and CS-related companies and institutes.[/samenvatting]

[H1]StudCie[/H1]
[commissie_foto]

[center] [ [url=mailto:studcie@svcover.nl]E-Mail[/url] ][/center]


[b]The Study Activities Commission (StudCie) regularly arranges study-related activities such as lectures, trips to AI- and CS-related companies and institutes, and of course a yearly trip to a city in a foreign country.[/b]

As a student of the course Computer Science or Artificial Intelligence you will regularly come in contact with research. Your courses'' teachers are all scientists and very enthusiastic about their research. However, many of us will not (want to) get a job at a university after graduation, which is why it is important to know what else you can do with your degree.

This is why the StudCie organizes a study-related activity every one to two weeks. This way, you get to know about what''s currently going on in the fields of AI and CS, which parts interest you, and where you might eventually want to start working after you graduate. So by all means come take a look at one of these activities and be surprised by all the things you can do with AI and CS.

[commissie_leden]
[commissie_agenda]', NULL);
INSERT INTO pages VALUES (12, 12, 'Commissiepagina Kascie', '[samenvatting]De Kascommissie controleert de financieen van Cover. Hiervan doen ze op de ALVs verslag aan de leden.[/samenvatting]
[H1]KasCie[/H1]

De Kascommissie controleert de financieen van Cover. Hiervan doen ze op de ALVs verslag aan de leden.

De leden van de Kascommissie worden door de jaarlijkse ALV gekozen, en kunnen worden voorgedragen door 10 leden van de ALV of de KasCie zelf. Om een goede controle en objectiviteit naar de leden toe te kunnen waarborgen, blijven deze leden voor minimaal 1 jaar commissielid. De leden hebben meestal veel ervaring met financieen, en zijn vaak voormalig penningmeesters van Cover.
[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (13, 13, 'Commissiepagina Mccie', '[samenvatting]De megaexcursiecommissie (kort: McCie) organiseert grote studiereizen buiten Europa. [/samenvatting]
[H1]McCie[/H1][center]

[ [commissie_email] ][/center]


De megaexcursiecommissie (kort: McCie) organiseert grote studiereizen buiten Europa. Op het moment hebben we een megaexcursiecommissie die een reis organiseert naar een nog onbekende datum.

Hier vind je meer informatie over de McCie reis uit 2004. [b]BIG: Boston - New York 2004[/b]
www.ai.rug.nl/~mccie [i](site)[/i]
www.ai.rug.nl/~mccie/mccie04/report/BIG2004.pdf [i](wetenschappelijk en dagverslag)[/i]

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (14, 14, 'Commissiepagina RvA', '[samenvatting] De Raad van Advies adviseert het bestuur, zodat zij het wiel niet opnieuw uit hoeven vinden.[/samenvatting]
[H1]Raad van Advies[/H1] [center][ [url=mailto:rva@svcover.nl] Email [/url]][/center]
[commissie_foto]

Elk jaar wisselt Cover van bestuur. En elk jaar komt er weer een oud-bestuur met heel veel ervaring bij. Om dit nieuwe bestuur te ondersteunen en om de kennis van de oude besturen niet verloren te laten gaan, bestaat er de Raad van Advies. Hierin nemen oud-bestuursleden plaats en zij stellen zich ten doel het bestuur daar waar nodig te voorzien van zowel gevraagd als ongevraagd advies.

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (16, 0, 'Actief worden', '[h1]Actief worden bij Cover[/h1]

[h3]Organiseer meer[/h3]
Ben jij enthousiast, organisatorisch, creatief, reislustig, gezellig, journalistiek of initiatiefrijk? Of wil je dat juist worden? Doe dan meer met je talent en ontwikkel jezelf en de vereniging. Word teammember van een van deze commissies:

- [url=http://www.svcover.nl/show.php?id=%2015] Foetsie [/url]
De Foetsie zorgt ervoor dat er voor de Coverleden genoeg te knabbelen en te drinken valt. Wil je af en toe naar de Makro? Houd je van shoppen? Dan is dit misschien een commissie voor jou!

- [url=http://www.svcover.nl/show.php?id=%2019] SympoCie [/url]
Er wordt weer een SympoCie gezocht voor een groot symposium in 2011. Er wordt hiervoor gezocht naar een compleet nieuwe commissie, waarbij enkele huidige SympoCieleden ook al interesse hebben getoond. Er worden hier dus veel mensen voor gezocht, wat het een leuke commissie maakt om te doen als je al een grote groep om je heen hebt die ook actief willen worden! Bovendien staat het erg goed op je CV. Misschien is dit wel een commissie voor jou!

- [url=http://www.svcover.nl/show.php?id=%2013] McCie [/url]
De McCie is een commissie die een Mega-Excursie organiseert naar het buitenland. Hierbij kun je denken aan landen zoals Japan of Brazilië. Eerder is al een reis naar Amerika geweest. Op dit moment is de commissie leeg, maar het is natuurlijk altijd leuk als deze commissie weer gevuld wordt. Lijkt het je leuk om zo''n gigantische reis te organiseren? Dan is dit misschien een commissie voor jou!

Naast deze commissies waar mensen voor gezocht worden, zijn er natuurlijk nog veel meer commissies waar je eventueel deel vanuit kan maken. [url=http://www.svcover.nl/commissies.php] Bekijk ze hier allemaal! [/url]

Als je eerst meer wilt weten over Cover, een commissie of actief worden, kun je natuurlijk altijd het [url=http://www.svcover.nl/show.php?id=0] bestuur[/url] vragen stellen. Kom een keer langs de Coverkamer, spreek ons aan bij activiteiten of stuur een mailtje naar [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url].', NULL, NULL);
INSERT INTO pages VALUES (18, 0, 'Lidmaatschap en Donateurschap', '[H1]Lidmaatschap en Donateurschap[/H1]
[H2]Hoe kun je...[/H2]
[H3]Lid worden:[/H3]
[ul]
[li]Vul het [url=lidworden.php] online lidmaatschapsformulier[/url] in.[/li]
[li]Kom langs bij de Coverkamer, zodat je het lidmaatschapsformulier kunt invullen. Kijk bij [url=http://www.svcover.nl/show.php?id=17] Contact [/url] voor de nodige gegevens. [/li]
[li] Download het [url=documenten/lidmaatschapsformulier20100902.pdf] lidmaatschapsformulier [/url] en stuur het op naar:

Studievereniging Cover
Postbus 407 
9700 AK Groningen

[/ul]

Het lidmaatschap bedraagt 10 euro per jaar. Word je na 1 februari lid, dan betaal je voor het resterende deel van het verenigingsjaar 5 euro.

[H3]Lid-af worden:[/H3]
[ul]
[li]Wanneer je afstudeert zorgen wij ervoor dat je uitgeschreven wordt.[/li]
[li]In alle andere gevallen dien je minimaal een maand voor het einde van het verenigingsjaar te melden dat je je lidmaatschap op wilt zeggen. Dit kan door een mail te sturen naar [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url] of een brief te sturen naar:

Studievereniging Cover 
Postbus 407
9700 AK Groningen

Het verenigingsjaar loopt tot 1 september[/li]
[/ul]

Let wel, onze ledenadministratie staat geheel los van die van KI en informatica.

[H3]Donateur worden:[/H3]
Als donateur steun je Cover door middel van een jaarlijkse gift van een zelf bepaalde hoogte. In ruil hiervoor ontvang je ons verenigingsblad, de Brainstorm.
Wil je donateur worden? Download dan [url=documenten/Donateurschap.pdf]dit[/url] formulier en stuur het op naar Studievereniging Cover, Postbus 407, 9700 AK Groningen of leg het in ons postvakje op de derde verdieping van de Bernoulliborg.

', NULL, NULL);
INSERT INTO pages VALUES (20, 0, 'English', '[h1]Welcome foreign visitor[/h1]

[b]Welcome to the website of student union CoVer. On this part of the site you can find more information about:
- CoVer
- Artificial Intelligence at the Dutch University of Groningen
- some interesting links
- contact information
[/b]

[h2]CoVer[/h2]
CoVer is the student union of the Artificial Intelligence department at the Dutch University of Groningen. It organizes many study-related and social events. CoVer introduces and supports their student members, sells them books with discount, and also makes and maintains contact with companies to become acquainted with the aspects of Artificial Intelligence. 

An important activity is organizing excursions. These excursions have two main goals: The first is to make the participants more aware of the research and commercial activities in the area of Artificial Intelligence and the second goal is to give them an opportunity to get in touch with universities or companies that offer graduating or working opportunities. Indirectly, it helps them in the choice which aspect they want to focus on within Artificial Intelligence.

CoVer has visited many other universities and companies all over the world. Some of the places weve been with our member-students are; Rome, Boston, Edinburgh, Berlin, Prague, New York and Florida. Universities and companies like IBM, MIT (Medialab and CSAIL), Harvard, Edinburgh University, Scansoft and Kennedy Space Centre made an effort in presenting their research to our students, possible future colleagues . A more exhaustive list and some travel reports can be found [url=http://www.ai.rug.nl/~mccie]here[/url] (most of them are in Dutch).

This year, as any year, we will visit new countries and other companies and universities. More information about the coming journeys can be found [url=http://www.ai.rug.nl/~excie]here[/url].

Would you like us to visit your company, university or research centre? Or would you like to visit the department of Artificial Intelligence at the University in Groningen? Then we would be our pleasure to arrange a meeting or excursion. The section How to contact will provide you with the information needed to reach the board of the student union of Artificial Intelligence; CoVer.

[h2]Artificial Intelligence[/h2]
The Artificial Intelligence department at the Dutch University of Groningen was found in 1993. Originally it was named TCW: Technical Cognitive Science. As this name suggests our educational and research program isnt focused on classical, logical and statistical based Artificial Intelligence. Instead we are interested in Cognitive Science and focus on it from a Technical Perspective. Therefore we use robotics, physics and computer models to learn more about intelligence.

The research is divided in four programmes: Autonomous Perceptive Systems, Multi-Agent Systems, Language and Speech Technology and Cognitive Modelling.  If you want to read more about the research at our university, you can visit the website of the [url=http://www.ai.rug.nl/alice]ALICE research group[/url]. Some of the research is done in close collaboration with the [url=http://www.rug.nl/bcn]School of Behavioural and Cognitive Neurosciences[/url].

Students are involved in Bachelor and Master programs. The Bachelor program is called Artificial Intelligence and takes 3 years. Students learn about AI, searching in algorithms, logic and statistics, physics, cognitive science, language and speech technology and robotics. During their last year they follow intensive practical courses on robotics, expert systems, human factors or language and speech technology. Next to this they conduct a small research.

The University of Groningen has two related Masters, both are a 2 year program. In the Master Artificial Intelligence students follow more courses and conduct research in the fields of robotics and multi agent systems. In the Master Man Machine Interaction students follow courses and conduct research in the fields of cognitive science, cognitive modelling, language and speech technology and interface design. Toward graduation they conduct a Master Thesis research of 6 months. All Master Thesiss can be found [url= http://www.ai.rug.nl/nl/colloquia/]here[/url]. 


[h2]Links[/h2]

[h3]Our university[/h3]
[url=http://www.rug.nl/corporate/index?lang=en]Dutch University of Groningen[/url]
[url=http://www.rug.nl/ai/index?lang=en]Artificial Intelligence, Dutch University Groningen[/url]
[url=http://www.ai.rug.nl/alice/]Research institute ALICE[/url]
[url=www.rug.nl/bcn]School Behavioral and Cognitive Neuroscience[/url]

[h3]CoVer[/h3]
[url=www.amigro.nl]CoVers international Symposium: The ISB Event 2006: AmIGro (March 16th in Groningen)[/url]
[url=www.ai.rug.nl/~mccie]CoVers past excursions abroad[/url]
[url=www.ai.rug.nl/~excie]CoVers Excursion Committee[/url]
[url=http://www.ai.rug.nl/~cover/show.php?id=11]CoVers Students Activity Committee (in Dutch)[/url]

[h3]Other[/h3]
[url=www.groningen.nl]Groningen city[/url]
[url=www.ns.nl]The Dutch Railway companie[/url]

[h2]Contact[/h2]
[h3]Contact information[/h3]
If you would like more information about CoVer or the Artificial Intelligence department, please contact the board of the student union:
[b]
Postal adress:
Student union CoVer
Grote Kruisstraat 2/1
9712 TS Groningen
The Netherlands

e-mail: cover@ai.rug.nl
Bank account:	4383199 
Chamber of Commerce registration number: 40026707

Our visiting address:
Groote Appelstraat 23
Groningen
[/b]

[h3]Directions[/h3]
[h3]By public transport[/h3]
Take the train toward Zwolle/Groningen (visit [url=www.ns.nl]The website of the Dutch Railway Companie[/url] for route planning).
When you leave Groningen Central station, walk across the museum bridge, past the Museum of Groningen. For approximately the next 15 minutes you walk straight ahead. You will walk through the Ubbo Emmiusstraat, cross the Zuiderdiep, walk through the Folkingestraat, cross the Vismarkt, walk through the Stoeldraaiersstraat, through the Oude Kijk in `t Jatstraat, cross a bridge and finally walk into the Nieuwe Kijk in `t Jatstraat. The Groote Appelstraat is the fourth street to your left. CoVer can be found at number 23.

[h3]By car:[/h3]
When you are coming from the direction of Amsterdam, follow directions toward Assen and Groningen. (these directions are not included). When you are near Assen, stay on road A28. You wil reach a bug crossing with traffic lights called Julianaplein'', go straight ahead into the centre. When you arrive at the next traffic lights, go to the left. Follow this road (Emmasingel, Eeldersingel) for about 600 meters.
At the next traffic light, you take a right, crossing a bridge. At the end you then take a right and an immediate left, driving into the Westersingel.
At the next traffic lights you go straight ahead. After the bridge you take a left. You will drive along the Noorderplantsoen (a park at your right side).
Take the first street to your right: the Oranjesingel. After about 300 meters, take the first street to your right. This is the Kerklaan, which will move over into the Grote Kruisstraat. Take the first street to the left (Nieuwe Kijk in t Jatstraat) and the Groote Appelstraat is the first street to your left. You can park your car on the square behind the green fence. The student union is located at number 23.
', NULL, NULL);
INSERT INTO pages VALUES (21, 0, 'Startpagina', '[h1]Studievereniging Cover[/h1]

Cover is de studievereniging voor Kunstmatige Intelligentie en Informatica aan de Rijksuniversiteit Groningen. De studievereniging telt ongeveer 450 leden.

Wil je weten wat Cover allemaal doet binnenkort, kijk dan op de [url=./agenda.php]agenda[/url].', '[h1]Study Association Cover[/h1]

Cover is the study association for Artificial Intelligence and Computer Science at the University of Groningen. The study association counts almost 450 members.

Are you interested in the upcoming activities of Cover? Take a look at [url=./agenda.php]our agenda[/url].', NULL);
INSERT INTO pages VALUES (84, 0, 'Oudbesturenpagina', '[h1]Vorige besturen van Cover[/h1]
Dit is de trotse geschiedenis van onze vereniging.', '[h1]Previous boards of Cover[/h1]
These are our previous boards.', NULL);
INSERT INTO pages VALUES (23, 2, 'De Studie', '[h1]De studie[/h1]
Bij KI/RuG houden we ons bezig met zowel menselijke als kunstmatige intelligentie. Aan de ene kant wordt gebruik gemaakt van computermodellen om meer kennis te krijgen van hoe de menselijke geest werkt. Aan de andere kant wordt gebruik gemaakt van de kennis die we al hebben over de menselijke geest om intelligente en gebruiksvriendelijke programma''s te ontwikkelen.


[ [url=http://www.rug.nl/bachelors/artificial-intelligence/]Informatie over de studie[/url] ]

[ [url=http://www.rug.nl/fwn/roosters/2013/ki/]roosters 2013-2014[/url] ]', '[h1]The Study[/h1]
At Artificial Intelligence at the RUG we focus on both human as well as artificial intelligence. On one hand we use computer models to gain more knowledge of how the human mind works. On the other hand we use the knowledge of the human mind we already have to develop intelligent and usable computer programs.

For more information about the study go to http://www.rug.nl/bachelors/artificial-intelligence/

[url=http://www.rug.nl/fwn/roosters/2013/ki/]Schedules 2013-2014[/url]', NULL);
INSERT INTO pages VALUES (15, 15, 'Commissiepagina Foetsie', '[samenvatting]De Foetsie zorgt ervoor dat er vaak iets te eten en drinken is in de Coverkamer. [/samenvatting]

[h1]Foetsie[/h1]

[commissie_foto]

Tegenwoordig zijn de kruisjes duurder, maar de service is nog steeds net zo goed!

[commissie_leden]', '[samenvatting]The Foetsie provides food and drinks in the Cover room. [/samenvatting]

[h1]Foetsie[/h1]

[commissie_foto]

The credits are more expensive these days, but we try to maintain the quality of our service!

[commissie_leden]', NULL);
INSERT INTO pages VALUES (24, 0, 'Alumni', '[h1]Alumni[/h1]

[h2]Kunstmatige Intelligentie[/h2]
Sinds 2005 heeft de studie Kunstmatige Intelligentie (voorheen Technische Cognitiewetenschappen) een zelfstandige alumnivereniging genaamd Axon.

Ben jij alumnus van Kunstmatige Intelligentie of Technische Cognitiewetenschappen en ben je benieuwd naar het reilen en zeilen van de studie en je oud-studiegenoten? Wordt dan nu lid van Axon. De lidmaatschapskosten bedragen slechts 5 euro per jaar.

Voor meer informatie, kijk op onze site [url=http://www.axonline.nl]http://www.axonline.nl[/url] of mail naar [url=mailto:info@axonline.nl]info@axonline.nl[/url].

[h2]Alumni Informatica[/h2]
Binnenkort wordt de alumnivereniging voor informatici genaamd [url=http://invariant.nl/]Invariant[/url] opgericht.', '[h1]Alumni[/h1]

[h2]Artificial Intelligence[/h2]
Since 2005 the study Artificial Intelligence (previously named Technische Cognitiewetenschappen) an independent alumni association named Axon.

Are you an alumnus of Artificial Intelligence or Technische Cognitiewetenschappen and interested in the ins and outs of the study and your fellow old students? Become a member of Axon. The membership fee is only 5 euros per year.

For more information go to our website [url=http://www.axonline.nl]http://www.axonline.nl[/url] or send an email to [url=mailto:info@axonline.nl]info@axonline.nl[/url].

[h2]Computer Science[/h2]
Currently a group of alumni is working on setting up an association for computer science alumni named [url=http://invariant.nl/]Invariant[/url].', NULL);
INSERT INTO pages VALUES (25, 0, 'ALV', '[h1]ALV[/h1]
Drie a vier keer per jaar vindt er een een Algemene Ledenvergadering (ALV) plaats. Op een ALV verantwoorden alle commissies en het bestuur zich aan de leden. Deze vergadering informeert de leden over het gevoerde beleid en geeft de leden inspraak bij besluiten en voorstellen. Het is dus erg belangrijk dat je als Coverlid de ALV''s bezoekt. Wanneer je verhinderd bent, bestaat er altijd de mogelijkheid om je te laten machtigen.

Alle relevante stukken van een ALV zijn minstens een week voor een ALV te vinden op deze website, onder ''[url=show.php?id=30]Documenten[/url]''. Ook liggen er minimaal twee inkijkexemplaren in de Coverkamer. Een machtigingsformulier zit bij de uitnodiging die elk lid over de post ontvangt.

[b]NB[/b]: Op ALV''s wordt iedereen altijd gratis voorzien van gepaste drankjes en koekjes!

[h2]Overgangsboekjaar (2012)[/h2]
Tijdens de ALV in mei worden de begrotingen voor het komende boekjaar besproken.

[h2]Vanaf augustus 2012[/h2]
In oktober vindt er een overdrachts-ALV plaats, tijdens deze ledenvergadering wordt er van bestuur gewisseld. Daarnaast wordt er een afrekening gepresenteerd, presenteert het bestuur haar eindverslag en de commissies een halfjaarverslag. Tijdens deze ALV komt ook de CUOS-lijst ter stemming, zoals dat vanaf 2012 moet.

Ergens rond de jaarwisselingen is er een ALV waarin het bestuur haar beleidsplan presenteert, eventueel kan op deze ALV ook gestemd worden over een herziene begroting.

In maart is er een ALV waarop zowel de commissies als het bestuur halfjaarverslagen presenteren. Tijdens deze ALV wordt ook een financieel halfjaarverslag gepresenteerd.

Eind mei, begin juni is er een ALV om begrotingen voor het komende boekjaar goed te keuren, dan presenteren commissies ook hun jaarplanning.', NULL, NULL);
INSERT INTO pages VALUES (26, 3, 'Boeken bestellen', '[h1]Order books[/h1]
Welcome to the book webstore. Please indicate the books you wish to buy from the list and click on the ''order'' button. You can change your order until the deadline has passed. 

As soon as the books are delivered, you will receive an e-mail from the BoekCie that specifies when and where you can pick up your books. You can pay by debit card, cash or by direct debit.

Is the book that you need not on the list? Then please send an e-mail to the [[commissie(BoekCie)]] on boekcie@svcover.nl They might be able to arrange something for you. For further questions, you can send an email to boekcie@svcover.nl or bestuur@svcover.nl .

[b] Do note: you may not need every book that is listed for your year. Because this has caused problems in the past, we have indicated which degree programme requires which book: (AI) indicates the book is for Artificial Intelligence and (CS) indicates the book is for Computing Sciences. Despite these abbreviations, the BoekCie still assumes that you think about the books that you will be needing yourself. [/b]', '[h1]Order books[/h1]
Welcome to the book webstore. Please indicate the books you wish to buy from the list and click on the ''order'' button. You can change your order until the deadline has passed. 

As soon as the books are delivered, you will receive an e-mail from the BoekCie that specifies when and where you can pick up your books. You can pay by debit card, cash or by direct debit.

Is the book that you need not on the list? Then please send an e-mail to the [[commissie(BoekCie)]] on boekcie@svcover.nl They might be able to arrange something for you. For further questions, you can send an email to boekcie@svcover.nl or bestuur@svcover.nl .

[b] Do note: you may not need every book that is listed for your year. Because this has caused problems in the past, we have indicated which degree programme requires which book: (AI) indicates the book is for Artificial Intelligence and (CS) indicates the book is for Computing Sciences. Despite these abbreviations, the BoekCie still assumes that you think about the books that you will be needing yourself. [/b]', NULL);
INSERT INTO pages VALUES (27, 0, 'Studieondersteuning', NULL, NULL, NULL);
INSERT INTO pages VALUES (50, 26, 'Commissiepagina PRcie', '[samenvatting]De belangrijkste taak van de PRcie is het leggen van contacten met bedrijven. [/samenvatting]

[H1]PRcie[/H1][center][ [commissie_email] ][/center]


De belangrijkste taak van de PRcie is het leggen van contacten met bedrijven. Dit is erg belangrijk, omdat we er graag voor willen zorgen dat er een goede keus aan stageplaatsen is en dat er sponsoring gevonden wordt.

De PRcie is een samenwerkingsverband tussen de belanghebbende commissies en de Commissaris Extern. De PRcie is te bereiken via prcie@svcover.nl.


[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (51, 0, 'Bedrijvenpagina', '[h1]Bedrijven[/h1]

[table noborder]

|| [h2]Capgemini[/h2] ||
|| [url=http://www.nl.capgemini.com/] [img=/images/Capgemini.jpg] [/url]
||Capgemini is marktleider op het gebied van technology, outsourcing en consultancy. Onze
overtuiging is dat duurzame resultaten alleen haalbaar zijn door intensieve en innovatieve
vormen van samenwerking met toonaangevende klanten, business partners en collega''s.
Werken bij Capgemini staat dan ook voor samenwerken in teams van ondernemende
professionals, die resultaatgerichtheid combineren met onconventioneel denken. Een
inspirerende omgeving met uitdagende opdrachten/projecten waarin je gewoon jezelf kunt
zijn en waarin je jouw talenten optimaal kunt inzetten en ontwikkelen.

[url=http://www.svcover.nl/show.php?id=79]Lees meer over Capgemini[/url] ||


|| [h2]Ortec[/h2] ||
|| [url=http://www.ortec.nl/] [img=/images/ortec.jpg] [/url]
|| ORTEC bestaat uit experts op het gebied van complexe optimalisatievraagstukken in
diverse sectoren. Wij zorgen ervoor dat medicijnen op tijd in de apotheek liggen; dat grote
supermarktketens miljoenen besparen op hun logistieke netwerken; dat de melk die jij koopt
vers is; dat vliegtuigen op tijd vertrekken; dat het beste staal ter wereld nog altijd uit Nederland
komt; dat e-commerce bedrijven de optimale prijs kunnen vragen voor hun producten. En
misschien nog wel het allerbelangrijkste: er is geen bedrijf dat zoveel CO2 bespaart als wij.
Wij zorgen niet alleen nu voor onze klanten, maar wij zorgen ook voor de toekomst van onze
klanten. Dat doen we wereldwijd met vestigingen op vijf continenten.

[url=http://www.svcover.nl/show.php?id=80]Lees meer over Ortec[/url] ||


|| [h2]KPN Consulting[/h2] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
||KPN Consulting is hét ICT-adviesbedrijf van KPN, Nederlands marktleider in geïntegreerde IT- en telecommunicatiediensten. Onze visie is dat ICT veel meer is dan de inzet van technologie. Het vergroten van de daadkracht van mens en organisatie staat bij ons voorop. Al decennia identificeren we nieuwe technologieën en vertalen deze naar toekomstbestendige en mensgerichte oplossingen. Oplossingen die helpen ambities en doelen te realiseren.

[url=http://www.svcover.nl/show.php?id=77]Lees meer over KPN Consulting[/url] ||


|| [h2]Procam ICT[/h2] ||
|| [url=http://www.procam.nl/] [img=/images/Bedrijvenpagina_Procam.png][/url] ||Procam is een carrièreadviesbureau voor afgestudeerde academici en HBO''ers. Onze missie is ze te laten groeien in een omgeving die aansluit op hun persoonlijkheid, hun capaciteiten en ambities. Na een selectieprocedure en matchingsproces start je in een traineeship bij één van de topwerkgevers van Nederland. Je wordt intensief begeleid door een ervaren coach en volgt trainingen. Zo werk je vanaf de start van jouw loopbaan bewust aan je eigen ontwikkeling. Succes is geen toeval maar een keuze! Kijk op www.procam.nl. ||


|| [h2]TNO-ICT[/h2] ||
|| [url=http://werkenbijtno.nl/] [img=/images/Bedrijvenpagina_TNO.png][/url] || Een bombestendige vuilnisbak ontwikkelen of intelligente sportkleding? Dé oplossing bedenken voor dichtslibbende wegen of voor overgewicht bij kinderen? 

Werken bij TNO betekent: werken in teams aan boeiende opdrachten voor multinationals, midden- en kleinbedrijf en de overheid. Je draagt direct bij aan innovatie en de ontwikkeling en toepassing van kennis; je bent rechtstreeks betrokken bij nieuwe ontwikkelingen. De projecten lopen uiteen van contractresearch tot consultancy, van beleidsstudies tot certificeren en testen. Saaie routine kun je schrappen uit je woordenboek. 

TNO biedt stage- en afstudeermogelijkheden. We zeggen het eerlijk: met alleen goede studieresultaten ben je er niet. TNO zoekt gedreven medewerkers met een sterke persoonlijkheid. Kun je goed met mensen omgaan, ben je resultaat- en klantgericht, ondernemend én denk je in mogelijkheden? Kijk op www.werkenbijTNO.nl! ||


|| [h2]Topicus[/h2] ||
|| [url=http://www.topicus.nl/] [img=/images/topicus.png][/url] || Topicus is een ICT dienstverlener met meer dan 300 medewerkers gevestigd in het centrum van Deventer en werken in units van maximaal 25 medewerkers. Wij bouwen volledig webgebaseerde, complexe ICT-systemen voor de sectoren zorg, onderwijs en finance in JAVA of .NET. Bij het realiseren van deze systemen krijgen we te maken met vraagstukken over security, privacy, architectuur, high performance, implementatie, miljoenen regels code en maken daarbij gebruik van de nieuwste technologieën.

Dankzij de sterke technische basis en uitgebreide domeinkennis is Topicus in staat om interessante projecten te verwerven die in Deventer worden uitgevoerd. Bijvoorbeeld de ontwikkeling van elektronische patiëntendossiers, leerlingenadministratiesystemen of op internettechnologie gebaseerde administraties van grote banken en financiële tussenpersonen. [url=http://www.svcover.nl/show.php?id=55] meer... [/url]||

[/table]', '', '');
INSERT INTO pages VALUES (85, 0, 'Oprichting Cover', '[h1]Oprichting Cover[/h1]
20-09-1993: Cover werd opgericht.', '[h1]Founding of Cover[/h1]
20-09-1993: Cover was founded.', NULL);
INSERT INTO pages VALUES (86, 0, 'Vacature KPN Consulting', '[h1]KPN Consulting[/h1]
[h2]Young Professional Technology[/h2]
[h3]Wat ga je doen als Young Professional Technology?[/h3]
Het Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

Alle kennis die je tijdens de introductie hebt opgedaan, kun je direct toepassen bij onze klanten in technische ICT-projecten. KPN Consulting werkt samen met de 400 grootste bedrijven van Nederland zoals Shell, Nuon, Rijkswaterstaat, NS en ING. Bovendien stippel je samen met een persoonlijke coach een carrièrepad uit aan de hand van jouw kennis, ervaring en ambitie. Gedurende het jaar volg je naast je projecten nog enkele trainingen. Ter afsluiting van het traject voer je samen met je mede Young Professionalcollega''s een praktijkgerichte case op het gebied van techniek uit, die je daarna presenteert aan onze directie.

Na het eerste jaar ontwikkel je je richting techniek of architectuur. Je kunt daarbij een vliegende start maken door als High Potential door te stromen naar een vervolgopleidingstraject, waar je in korte tijd de kennis, kunde en ervaring opdoet voor de rest van je loopbaan. Dit geeft je de mogelijkheid om uit te groeien tot een gewaardeerde Technisch Consultant die organisaties met concrete adviezen naar een hoger niveau tilt.

[h3]Wat heb jij ons te bieden als Young Professional Technology?[/h3]
- Afgeronde HBO- of masteropleiding in ICT, Techniek of een andere Bèta studierichting;
- Aantoonbare affiniteit met en kennis van ICT;
- Goede communicatievaardigheden;
- Helikopterview, proactief, resultaatgericht en pragmatisch;
- Goede beheersing van de Nederlandse taal;
- Je staat voor kwaliteit en je doet net iets meer dan afgesproken.

[h3]Wat bieden wij jou?[/h3]
Wij bieden jou een carrière bij de nummer 1 in ICT-consultancy. Daarbij horen natuurlijk dingen als een marktconform salaris, een leaseauto, een telefoon, laptop, bonusregeling en goede secundaire arbeidsvoorwaarden. Veel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien, zodoende denken we met je mee in een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!

Daarnaast bieden wij jou een carrière bij dé kennisleider in ICT. Inventief in infrastructuren en opinieleider in leidende standaarden gaan bij ons namelijk hand in hand. Je werkt samen met ervaren en professionele collega''s op uitdagende opdrachten. Je vindt aansluiting bij collega''s rondom jouw expertise waarbij je volop de ruimte en verantwoordelijkheid krijgt voor persoonlijke ontwikkeling en kennisdeling.


[h3]Over KPN Consulting[/h3]
KPN Consulting is de nummer 1 in ICT-consultancy! Wij begeleiden de top van het Nederlandse bedrijfsleven bij het implementeren van vooruitstrevende informatietechnologie. Onze expertise is gebaseerd op ervaring en we ontdoen ICT van hypes en vaagheden. We maken technologie toepasbaar. Dit levert inventieve en waardevolle toepassingen en ervaringen op. 

Mensen en organisaties vinden steeds nieuwe mogelijkheden om met elkaar in contact te komen, samen te werken, plezier te hebben, van elkaar te leren en zaken te doen. Het is ons vak en onze passie om hiervoor de optimale infrastructuur te bieden. We analyseren de impact van veranderingen op de business, organisatie, mensen, systemen en middelen. Onze professionals zijn herkenbaar door ervaringskennis en passie voor ICT en de menselijke maat.

We zijn momenteel ruim 1100 man groot. We zien onze mensen als meer dan werknemer en investeren daar ook in. KPN Consulting stelt zich tot doel de meest aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Werken" geïmplementeerd, dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance. Daarnaast organiseren we ook dingen als de donderdagmiddagborrel, een nieuwjaarsfeest en het jaarlijkse strandfeest, gewoon omdat we dat leuk vinden.

[h3]Interesse?[/h3]
Herken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Pamela van Winterswijk, pamela.vanwinterswijk@kpn.com   06-12872367 of Mandy Klemann, Mandy.Klemann@kpn.com 06-13444246.

Screening is onderdeel van het sollicitatieproces van KPN Consulting. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv.
', NULL, NULL);
INSERT INTO pages VALUES (87, 0, 'Vacature KPN Consulting', '[h1]KPN Consulting[/h1]
[h2]Het Management Traineeship bij KPN IT Solutions[/h2]
Je bent een assertieve starter met je Master op zak. Je staat te trappelen om het bedrijfsleven te overtuigen van je talenten en bent gedreven om bij de absolute top te horen. Ontwikkelingen in de IT dienstverlening vind je boeiende materie. Daarom werk jij straks als Management Trainee bij KPN IT Solutions. Vandaag high potential? Binnen de kortste tijd groei jij uit tot een beslisser binnen onze organisatie!

[h3]Jij in de rol van Management Trainee[/h3]
Je maakt deel uit van een select team dat onder begeleiding van het hoger management wordt voorgesorteerd voor een sturende rol binnen KPN IT Solutions. Je ontdekt in 18 maanden de veelzijdigheid van onze IT dienstverlening, maar vooral ook je eigen talenten en toegevoegde waarde. Je vervult opdrachten binnen verschillende disciplines van onze organisatie. Doordat je een stevig intern netwerk opbouwt, pak je de kans om zelf leidend te zijn in de invulling van je traineeship.

Je start met een uitgebreid introductietraject van een maand met meerdere cursussen als ITIL, PRINCE2 en Presenteren. Na een maand vervul je binnen KPN IT Solutions achtereenvolgens 3 opdrachten van ongeveer 6 maanden. Uiteraard aangevuld met meer trainingen (zoals verandermanagement) en intervisie-sessies. Je krijgt te maken met (inter)nationale klanten en partners van KPN, zoals ING, NS, Rabobank, IBM, diverse Ministeries, Achmea en Microsoft.

[h3]Jouw kwaliteiten[/h3]
Je bent een topperformer met ambitie! In dit maatwerk traineeship pak jij elke kans die je krijgt om het beste uit jezelf te halen. Door je overtuigingskracht krijg je mensen met je mee. Projecten blijven bij jou niet bij vage plannen. Door je resultaatgerichte instelling innoveer, ontwikkel en implementeer je jouw opdrachten met als doel het best mogelijke resultaat. Je hebt:

- het VWO afgerond en beschikt over een universitair Masterdiploma op het gebied van IT, Bedrijfskunde of Techniek
- aantoonbare affiniteit met IT
- een bovengemiddeld vermogen van systematisch denken
- behoefte aan afwisseling en een snelle ontwikkeling

[h3]Wat bieden wij jou?[/h3]
Kansen! Jij krijgt de kans om je maximaal te ontwikkelen en snel verder te groeien. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen! Je manager denkt met je mee bij het opstellen en naleven van een persoonlijk ontwikkelplan. Ook je inhoudelijke begeleiding is in goede handen. Je krijgt bij elke opdracht, een inhoudelijk begeleider aangewezen. Je werkt nauw samen met je collega-trainees. Je daagt elkaar uit en kunt bij elkaar terecht. Natuurlijk zijn een marktconform salaris, een leaseauto, laptop, telefoon, bonusregeling en goede secundaire arbeidsvoorwaarden bij de functie van Management Trainee inbegrepen.

NB. In het First Employers 2013 onderzoek van Memory Magazine is KPN uitgeroepen tot 1 van de 5 beste werkgevers in de IT/Telecom branche om je loopbaan te starten.

[h3]Over KPN IT Solutions[/h3]
KPN IT Solutions is marktleider in het ontwerpen, implementeren en beheren van vooruitstrevende IT infrastructuur diensten. We zorgen dat onze klanten altijd en overal op een veilige manier over hun bedrijfsinformatie kunnen beschikken. Daarom heeft ons werk vaak een grote maatschappelijke impact.

We zien onze mensen als meer dan werknemer en investeren daar ook in. KPN IT Solutions stelt zich tot doel een aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Leven en Werken" geïmplementeerd. Dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance.

[h3]Meer informatie of solliciteren?[/h3]
Ben jij de management trainee die wij zoeken? Upload dan direct jouw motivatie en cv op deze pagina. Heb je vragen over de sollicitatieprocedure of het traineeship, dan kun je contact opnemen met corporate recruiter Jotte Tromp via jotte.tromp@kpn.com of Yvonne Pribnow via yvonne.pribnow@kpn.com
', NULL, NULL);
INSERT INTO pages VALUES (88, 0, 'Vacature KPN Consulting', '[h1]KPN Consulting[/h1]
[h2]Trainee Techniek[/h2]
[h3]Wat ga je doen?[/h3]
Binnen KPN draait IT, naast bits, bytes en netwerken, ook om aansturen en regisseren. Daarvoor hebben we professionals nodig die snappen dat IT een middel is en beslist geen doel. Professionals die begrijpen hoe je wensen vanuit de klant kunt vertalen naar IT oplossingen. Die ervan houden om initiatief te nemen en te werken aan pittige projecten.

Als "Trainee Techniek" maak je deel uit van een select team dat, met begeleiding vanuit het hoger management, kennismaakt met de verschillende facetten van onze vooruitstrevende IT dienstverlening in een dynamische business-to-business markt. Je krijgt te maken met (inter)nationale klanten en partners van KPN, zoals ING, NS, Rabobank, IBM, diverse Ministeries, Achmea en Microsoft. Je krijgt de kans om concreet en inhoudelijk bij te dragen aan onze doelstelling om het beste IT servicebedrijf van Nederland te worden.

Je start met een uitgebreid introductietraject met meerdere cursussen op het gebied van kennis en soft skills (o.a. ITIL, PRINCE2, Windows 7 en Klantgericht Communiceren). In de loop van je Traineeship volg je, steeds samen met jouw mede-Trainees, vervolgens nog enkele specialistische trainingen op technisch gebied en zal je regelmatig deelnemen aan intervisie-sessies.

In een periode van één jaar werk je aan verschillende opdrachten, waarbij je het laatste half jaar actief zult zijn in het team waarbinnen je je ook ná het Traineeship verder zult bekwamen. Bij het uitvoeren van de opdrachten, zal je altijd zowel je technische kennis als je commerciële en communicatieve vaardigheden in moeten zetten. Op die manier lever jij je concrete bijdrage aan ons vakgebied: het op efficiënte wijze innoveren, ontwikkelen, bouwen, implementeren en beheren van IT services en onderliggende infrastructuren voor onze klanten.

[h3]Wat heb jij ons te bieden als Trainee Techniek? [/h3]
- Je beschikt over een HBO- of WO-diploma op het gebied van Informatica, Technische Bedrijfskunde of Bedrijfskundige Informatica.
- Je bent in staat om klantprocessen te begrijpen en te vertalen naar technische oplossingen.
- Doordat je geïnteresseerd bent in je vakgebied houd je jezelf continu op de hoogte van de ontwikkelingen in de ICT.
- Je wilt hard werken aan het vinden van een optimale aansluiting van onze organisatie en ons portfolio op de wensen van de klant.
- Je wilt uitgroeien tot een top-performer in een topfunctie binnen onze organisatie.
- Je kunt goed samenwerken, zoekt anderen op en bent communicatief vaardig.
- Je kunt goed analyseren en gestructureerd denken en bewaart het overzicht.


[h3]Wat bieden wij?[/h3]
Natuurlijk zijn een marktconform salaris, een leaseauto, laptop, telefoon, bonusregeling en goede secundaire arbeidsvoorwaarden bij de functie van Trainee Techniek inbegrepen.
Veel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien. Zodoende denken we met je mee met het opstellen en naleven van een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!

[h3]Interesse?[/h3]
Herken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Jotte Tromp via jotte.tromp@kpn.com
Screening is onderdeel van het sollicitatieproces van KPN Corporate Market. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv
', NULL, NULL);
INSERT INTO pages VALUES (28, 0, 'Zusterverenigingen', '[h1]Zusterverenigingen[/h1]
[table noborder]
||[url=http://www.uscki.nl][/url]||[h3][url=http://www.uscki.nl]Uscki incognito[/url] ([url=mailto:incognito@uscki.nl]e-mail[/url])[/h3]

Cognitieve Kunstmatige Intelligentie
Universiteit Utrecht||
||[url=http://www.kun.nl/cognac][/url]||[h3][url=http://www.kun.nl/cognac]CognAC[/url] ([url=mailto:cognac@cogsci.kun.nl]e-mail[/url])[/h3]

Kunstmatige Intelligentie
Katholieke Universiteit Nijmegen||
||[url=http://www.storm.vu.nl][/url]||[h3][url=http://www.storm.vu.nl]Storm[/url] ([url=mailto:storm@cs.vu.nl]e-mail[/url])[/h3]

Wiskunde & Informatica
Vrije Universiteit Amsterdam||
||[url=http://www.svia.nl][/url]||[h3][url=http://www.svia.nl]via[/url] ([url=mailto:via@science.uva.nl]e-mail[/url])[/h3]

Informatiewetenschappen
Universiteit van Amsterdam||
||[url=http://www.studver.unimaas.nl/incognito/][/url]||[h3][url=http://www.studver.unimaas.nl/incognito/]MSV Incognito[/url] ([url=mailto:incognito@facburfdaw.unimaas.nl]e-mail[/url])[/h3]

Kennistechnologie
Universiteit Maastricht||

||[url=http://www.deleidscheflesch.nl/][/url]||[h3][url=http://www.deleidscheflesch.nl/]De Leidsche Flesch[/url] ([url=mailto:bestuur@deleidscheflesch.nl]e-mail[/url])[/h3]

Natuurkunde, Sterrenkunde, Wiskunde en Informatica
Universiteit Leiden||

||[url=http://ch.twi.tudelft.nl/][/url]||[h3][url=http://ch.twi.tudelft.nl/]Christiaan Huygens[/url] ([url=mailto:bestuur@ch.tudelft.nl]e-mail[/url])[/h3]

Wiskunde en Informatica
Technische Universiteit Delft||

||[url=http://www.stickyutrecht.nl/][/url]||[h3][url=http://www.stickyutrecht.nl/]St!cky[/url] ([url=mailto:info@stickyutrecht.nl]e-mail[/url])[/h3]

Informatica en Informatiekunde
Universiteit Utrecht||

||[url=http://www.a-eskwadraat.nl/][/url]||[h3][url=http://www.a-eskwadraat.nl/]A-Eskwadraat[/url] ([url=mailto:bestuur@a-eskwadraat.nl]e-mail[/url])[/h3]

Wiskunde, Informatica, Informatiekunde en Natuur- & Sterrenkunde
Universiteit Utrecht||

||[url=http://www.gewis.nl/][/url]||[h3][url=http://www.gewis.nl/]GEWIS[/url] ([url=mailto:bestuur@gewis.nl]e-mail[/url])[/h3]

Wiskunde en Informatica
Technische Universiteit Eindhoven||

||[url=http://www.thalia.nu/][/url]||[h3][url=http://www.thalia.nu/]Thalia[/url] ([url=mailto:info@thalia.nu]e-mail[/url])[/h3]

Informatica en Informatiekunde
Radboud Universiteit Nijmegen||

||[url=http://www.inter-actief.net/][/url]||[h3][url=http://www.inter-actief.net/]Inter-Actief[/url] ([url=mailto:contact@inter-actief.net]e-mail[/url])[/h3]

Informatica, Bedrijfsinformatietechnologie en Telematica
Technische Universiteit Twente||

||[url=http://www.fmf.nl/][/url]||[h3][url=http://www.fmf.nl/]FMF[/url] ([url=mailto:bestuur@fmf.nl]e-mail[/url])[/h3]

wiskunde, natuurkunde, informatica en sterrenkunde
Rijksuniversiteit Groningen||

||[url=http://www.realtime-online.nl/][/url]||[h3][url=http://www.realtime-online.nl/]Realtime[/url] ([url=mailto:bestuur@realtime-online.nl]e-mail[/url])[/h3]

ICT Hanzehogeschool Groningen||

||[url=http://www.nsvki.nl/][/url]||[h3][url=http://www.nsvki.nl/]NSVKI[/url] ([url=mailto:bestuur@nsvki.nl]e-mail[/url])[/h3]

De Nederlandse StudieVereniging Kunstmatige Intelligentie is een overkoepelende organisatie voor KI verenigingen||
[/table]', NULL, NULL);
INSERT INTO pages VALUES (29, 1, 'Markup test pagina', '[h1]Markup test pagina[/h1]

http://www.icecrew.nl
', '', NULL);
INSERT INTO pages VALUES (30, 0, 'Documenten', '[h1]Documenten[/h1]
Hier vind je verschillende belangrijke documenten van Cover zoals reglementen en ALV stukken.

 [h2]Reglementen[/h2] 
1. Statuten ([url=http://sd.svcover.nl/Verenigingsdocumenten/Statuten.pdf]pdf[/url]) 
2. Huishoudelijk reglement ([url=http://sd.svcover.nl/Verenigingsdocumenten/Huishoudelijk_reglement.pdf]pdf[/url]) 
 3. Richtlijnen commissies ([url=http://sd.svcover.nl/Verenigingsdocumenten/Richtlijnen_commissies.pdf]pdf[/url])

 [h2]Nieuwsbrieven[/h2] 
Alle nieuwsbrieven vind je [url=http://standaarddocumenten.svcover.nl/Nieuwsbrieven]hier[/url].

[h2]Jaarplanning[/h2] 
De jaarplanning voor dit jaar vind je [url=http://sd.svcover.nl/Documenten/jaarplanning%2020132014.pdf]hier[/url].
Een overzicht van alle jaarplanningen vind je [url=http://standaarddocumenten.svcover.nl/Archief/Jaarplanningen]hier[/url].

[h2]Begroting[/h2]
Je kan de huidige begroting van Cover [url=http://www.svcover.nl/agenda.php?agenda_id=1514]hier[/url] vinden. Let op: je moet wel ingelogd zijn.

[h2]Afrekening[/h2]
De afrekening van 2012 zoals hij op de ALV goedgekeurd is kun je [url=http://www.svcover.nl/agenda.php?agenda_id=1611]hier[/url] vinden. Let op: je moet wel ingelogd zijn.

[h2]ALV-documenten[/h2]
De ALV-documenten van afgelopen ALV''s zijn terug te vinden [url=http://sd.svcover.nl/Archief/General%20Assemblies/]op de SD[/url]. Hiervoor dien je wel ingelogd te zijn. Wens je de documenten te ontvangen zonder in te loggen? Mail dan naar bestuur@svcover.nl.

', '[h1]Documents[/h1]
Here you can find important documents of Cover such as the regulations and General Assembly documents.

[h2]Regulations[/h2]
1. Articles of Association (in Dutch) ([url=http://sd.svcover.nl/Verenigingsdocumenten/Statuten.pdf]pdf[/url]) 
2. Rules and regulations (in Dutch) ([url=http://sd.svcover.nl/Verenigingsdocumenten/Huishoudelijk_reglement.pdf]pdf[/url]) 
3. Committee guidelines (in Dutch) ([url=http://sd.svcover.nl/Verenigingsdocumenten/Richtlijnen_commissies.pdf]pdf[/url])

[h2]Newsletters[/h2] 
A history of the newsletters can be found [url=http://sd.svcover.nl/Nieuwsbrieven]on the SD[/url].

[h2]Year schedule[/h2] 
The year schedule of this year can be found [url=http://sd.svcover.nl/Documenten/jaarplanning%2020132014.pdf]here[/url].
A history of the year schedules of past years can be found [url=http://sd.svcover.nl/Archief/Jaarplanningen]here[/url].

[h2]Budget[/h2]
You can find the current budget of Cover [url=http://sd.svcover.nl/Archief/Budgets/Begroting_1314.pdf]here[/url].

[h2]Accounts[/h2]
The financial account of 2012 can be found [url=http://sd.svcover.nl/Archief/Accounts/2012-10-11_afrekening.pdf]here[/url].

[h2]General Assemblies[/h2]
The documents of the past General Assemblies can be found [url=http://sd.svcover.nl/Archief/General%20Assemblies/]on the SD[/url]. To access these you need to log in. If you wish to access them without loggin in, please email the board at bestuur@svcover.nl
', NULL);
INSERT INTO pages VALUES (31, 10, 'Afstudeerplaatsen', '[h1]Afstudeerplaatsen[/h1]
Welkom op de afstudeer en bedrijfsstage bedrijvenpagina! Ben jij op zoek naar een stageplaats, neem dan contact op met het bestuur (bestuur@svcover.nl). Zij kunnen jou vertellen welke bedrijven je kan benaderen voor een stageplaats. 

Kijk voor meer informatie ook in de studiegids of op de site van de RUG.
', NULL, NULL);
INSERT INTO pages VALUES (38, 2, 'Test in opera', 'Test in opera .. nieuwe pagina aanmaken voor de actie', NULL, NULL);
INSERT INTO pages VALUES (39, 17, 'Commissiepagina Eerstejaarscie', '[samenvatting]De EerstejaarsCie organiseert leuke activiteiten voor en door de eerstejaars onder jullie![/samenvatting]

[H1]EerstejaarsCie[/H1][center][[url=mailto:eerstejaarscie@svcover.nl]E-Mail[/url]][/center]

De EerstejaarsCie organiseert leuke activiteiten voor en door de eerstejaars van Cover!
[commissie_leden]
[b]Voor contact met de EerstejaarsCie, stuur ons een mailtje![/b]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (41, 0, 'De studie', '[h1]Informatica[/h1]

Geen wetenschap is een groter drijfveer achter maatschappelijke veranderingen dan informatica. Er is bijna geen sector te bedenken waar informatica geen belangrijke, vernieuwende rol speelt. Ben je geïnteresseerd in informatica, maar ook andere vakken, dan kun je als je informatica gestudeerd hebt altijd het vak combineren met één of meer van je andere interesses. Zeker met de flexibele bachelor structuur die we in Groningen recent hebben ingevoerd, waardoor je veel mogelijkheden krijgt om al tijdens je studie aandacht te besteden aan andere vakken.

Kijk voor meer informatie op: http://www.rug.nl/bachelors/computing-science/

[url=http://www.rug.nl/fwn/roosters/2013/in/]Roosters 2013-2014[/url]', '[h1]Computer Science[/h1]

No science other than computer science is a greater driving force behind social changes. It is difficult to think of a field where computer science does not play an important and innovative role.

If you are interested in computer science, but also in other occupations then you can often combine your computer science study with one or more of your other interests. Especially with the flexibel bachelor fase that has been recently introduced at the University of Groningen which will grant you the ability to also focus on other areas of interest.

For more information go to http://www.rug.nl/bachelors/computing-science/

[url=http://www.rug.nl/fwn/roosters/2013/in/]Schedules 2013-2014[/url]', NULL);
INSERT INTO pages VALUES (42, 18, 'Commissiepagina SLACKCie', '[samenvatting] Wij hebben ID 42 en zorgen voor de Super Leuke Aangename Cover Kamer[/samenvatting]
[H1]SLACKcie[/H1][center][ [commissie_email] ][/center]

De Super Leuke Actieve Cover KamerCommissie zorgt ervoor dat elke dat de Super Leuke A...(maak af tot een positief woord) opengehouden wordt. Daarnaast gaan we na onze eerstvolgende vergadering de wereld over nemen.

[commissie_leden]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (44, 20, 'Commissiepagina Conditie', '[samenvatting]Het doel van ''de conditie'' is om de conditie van coverleden wat op peil te houden doormiddel van een sportieve doch gezellige activiteiten. [/samenvatting]

[H1]Conditie[/H1][center][ conditie@svcover.nl ][/center]
[commissie_foto]
Het doel van ''de conditie'' is om de conditie van coverleden wat op peil te houden doormiddel van een sportieve doch gezellige activiteiten. 

Een van de activiteiten zal zijn het deelnemen aan de batavierenrace met een team van Cover.

[commissie_leden]
', NULL, NULL);
INSERT INTO pages VALUES (45, 21, 'Commissiepagina LanCie', '[samenvatting]De LanCie organiseert elk jaar twee enorm toffe LAN-party''s![/samenvatting]
[H1]LanCie[/H1][center][/center]
[commissie_foto]

De LanCie organiseert elk jaar twee enorm toffe LAN-party''s voor relatief weinig geld.

[commissie_leden]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (46, 22, 'Commissiepagina PCie', '[samenvatting]De PCie beheert de hardware in de SLACK. Zij zorgen ervoor dat alle pc''s soepel draaien en vervangen worden waar nodig.[/samenvatting]
[H1]PCie[/H1][center][ [url=mailto:pcie@svcover.nl]E-Mail[/url] ][/center]
[commissie_foto]

De PCie beheert de hardware in de SLACK. Zij zorgen ervoor dat alle pc''s soepel draaien en vervangen worden waar nodig.

[commissie_leden]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (49, 25, 'Commissiepagina MeisCie', '[samenvatting]De MeisCie is de meidencommissie van Cover. Zij verzorgen de vrouwelijke kant van de vereniging[/samenvatting]
[H1]MeisCie[/H1][center][/center][center][ [url=mailto:meiscie@svcover.nl] Email [/url]][/center]
[commissie_foto]

De MeisCie is onze jongste en vrouwelijkste commissie en zij zorgen dat ook alle meiden van de vereniging voldoende leuke activiteiten voorgeschoteld krijgen. Dat zijn dus leuke activiteiten met veel chocola, wijn en bij voorkeur kussentjes!

[commissie_leden]
[commissie_agenda]', NULL, NULL);
INSERT INTO pages VALUES (52, 0, 'Sogeti', '[h1]Sogeti[/h1]

[h2]Over Sogeti Nederland[/h2]

Met ruim 3.300 medewerkers bundelen we meer dan 35 jaar ICT-kennis en -expertise in één bedrijf. Het ontwerpen, realiseren, implementeren, testen en beheren van ICT-oplossingen behoort tot onze core-business. Eén van onze specialismen is het bouwen en beheren van informatiesystemen die 7*24 draaien en betrouwbaar moeten zijn. Sogeti-methodieken als TMap®, TPI®, DYA® en Regatta® zijn uitgegroeid tot internationale standaarden.

[h2]Passie voor je vak[/h2]

Vakmanschap is een van de belangrijkste pijlers van onze cultuur. Ook in de structuur van onze divisies komt dit tot uiting. Onze medewerkers zijn gegroepeerd rondom expertises. Binnen een expertisegroep worden regelmatig bijeenkomsten georganiseerd. Dit biedt jou de mogelijkheid om met collega''s over het vakgebied te praten, kennis op te doen en te delen wat weer bijdraagt aan je persoonlijke ontwikkeling. Daarnaast kom je zo op informele wijze met elkaar in contact. Plezier vinden we namelijk een voorwaarde om je werk goed te kunnen uitvoeren.

[h2]Het heft in eigen hand[/h2]

Jij krijgt volop mogelijkheden om je te ontwikkelen op één van onze expertises, waarbij wij voortdurend aandacht hebben voor jouw loopbaanontwikkeling. Dat doen we onder andere door middel van coaching, opleidingen, technische meetings, congressen en beurzen. Certificering op ons vakgebied vinden wij belangrijk en hierbij faciliteren en stimuleren we je zoveel mogelijk.

Zelf zit je wel aan het stuur om jouw uiteindelijke bestemming binnen Sogeti en jouw vakgebied te bereiken. Wij vinden eigen verantwoordelijkheid erg belangrijk. Daarnaast verwachten we van jou dat je beschikt over goede communicatieve eigenschappen om met onze klanten te sparren. Gedrevenheid, enthousiasme en plezier in en trots voor het vakgebied.

[h2]Young Professionals[/h2]

Wij zoeken young professionals met een ICT-gerelateerde opleiding op HBO-/WO-niveau. Young Professionals starten met een opleiding van minimaal twee maanden. De eerste maand is een basisopleiding, in samenwerking met de Ohio Universtiy. Je studeert drie weken in de Verenigde Staten en verblijft op de campus van de Ohio University. Hier leer je de laatste stand van zaken op het gebied van bedrijfskunde en ICT en je werkt daarnaast aan je persoonlijke ontwikkeling. Het tweede deel van de basisopleiding is de specialisatiemaand, waarin de vaktechnische specialisatie plaatsvindt. Na afloop van de basisopleiding ben je inzetbaar bij de klanten van één van onze divisies.

Kijk op [url=http://www.werkenbijsogeti.nl]werkenbijSogeti.nl[/url] voor meer informatie over Sogeti, onze vacatures en de sollicitatieprocedure.', '', '');
INSERT INTO pages VALUES (53, 0, 'FINAN', '[h1]Welkom bij Finan![/h1]
Deze tekst gaat over jouw eerste baan. We hopen dat je deze bij Finan invult. Wij zijn een middengroot Nederlands softwarebedrijf in Zwolle waar bedrijfseconomen en informatici samen werken aan innovatieve oplossingen rond financieringsvraagstukken. Zoals je de laatste tijd in de media hebt gemerkt, is dat een zeer dynamisch onderwerp met complexe vraagstukken en maatschappelijke relevantie.

Topicus Finan BV is een dochteronderneming van Topicus. Wij zijn een toonaangevende leverancier van software voor financiële analyse en credit risk management. De zakelijke kredietketen is enorm in beweging en Finan is daarin een belangrijke innovatieve kracht. Wij bieden standaard- en maatwerkoplossingen aan banken, financiële adviseurs, accountants, aansprekende ondernemingen en onderwijsinstellingen. Finan kenmerkt zich door een hoge mate van commerciële professionaliteit, innovatieve producten en diensten en begeleidt de klanten in de projecten van begin tot eind.

[h2]ICT bij Finan[/h2]

Er is bij ons geen functionele scheiding tussen ontwerpers en software engineers; wel zien we dat sommige mensen zich vooral thuis voelen in de techniek en anderen vooral graag ontwerpen. Vooral medewerkers die zich initieel op de combinatie van beide disciplines storten, blijken zich snel te ontwikkelen. In de rol van software engineer werk je bij Finan met een J2EE ontwikkelstraat, grotendeels op basis van open source componenten. Applicaties die we bouwen zijn complex van aard en daarom maken we gebruik van domeinspecifieke talen en slim geparameteriseerde logica. Wat betreft analyse en ontwerp hanteren we een informele aanpak die wordt aangepast aan het project. Maatwerk wordt bij voorkeur iteratief volgens een risk-first aanpak vormgegeven.

 

[h2]Werken bij Finan[/h2]
- Werken binnen een zeer snel groeiende organisatie;

- Grote mate van zelfstandigheid;

- Een prettige informele werksfeer met leuke collega''s en ''korte lijntjes'';

- Coaching en extra training waar nodig;

- Werken op projectbasis;

- Prima arbeidsvoorwaarden;

- Doorgroeimogelijkheden binnen Finan. In je carrière begin je breed en breng je steeds meer focus aan. De bedoeling is dat je na enkele jaren een duidelijke keuze kunt maken tussen ''harde'' software engineering (met toenemende specialisatie), de functionele kant (functioneel of technisch ontwerp) of de leidinggevende kant (als project- of teamleider).

Finan is continu op zoek naar slimme starters op HBO en WO niveau. Relevante opleidingen zijn: - Bedrijfseconomie (WO) - Informatica, informatiekunde (WO en HBO functies beschikbaar) - Toegepaste wiskunde (WO) - Econometrie (WO) - Interaction

We bieden daarnaast stageplaatsen en afstudeeropdrachten aan, mits onze begeleidingscapaciteit dat toestaat. Gemiddeld voeren twee studenten bij ons opdrachten uit, die zij meestal zelf binnen ons vakgebied definiëren aan de hand van hun eigen interesses. Heb je interesse of wil je graag meer informatie? Neem contact op met Marloes Bulthuis via telefoonnummer 088 77 88 990 of mail naar m.bulthuis@finan.nl.

Kijk voor actuele vacatures op: http://www.finan.nl/index.php?option=com_content&task=blogcategory&id=26&Itemid=123', '', '');
INSERT INTO pages VALUES (54, 0, 'Vacatures', '[h1]Vacatures[/h1]

[table noborder]

|| [h2]KPN Consulting[/h2] ||
|| [h3]Young Professional Project/Proces[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Het Young Professional traject van KPN Consulting duurt in totaal een jaar en is een goede mix tussen trainingen, cursussen en het op doen van praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als YP start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende, vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

[url=http://www.svcover.nl/show.php?id=78]Bekijk de volledige vacature[/url] ||


|| [h3]Young Professional Technology[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Het Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

[url=http://www.svcover.nl/show.php?id=86]Bekijk de volledige vacature[/url] ||


|| [h3]Management Traineeship[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Je bent een assertieve starter met je Master op zak. Je staat te trappelen om het bedrijfsleven te overtuigen van je talenten en bent gedreven om bij de absolute top te horen. Ontwikkelingen in de IT dienstverlening vind je boeiende materie. Daarom werk jij straks als Management Trainee bij KPN IT Solutions. Vandaag high potential? Binnen de kortste tijd groei jij uit tot een beslisser binnen onze organisatie!

[url=http://www.svcover.nl/show.php?id=87]Bekijk de volledige vacature[/url] ||


|| [h3]Trainee Techniek[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Binnen KPN draait IT, naast bits, bytes en netwerken, ook om aansturen en regisseren. Daarvoor hebben we professionals nodig die snappen dat IT een middel is en beslist geen doel. Professionals die begrijpen hoe je wensen vanuit de klant kunt vertalen naar IT oplossingen. Die ervan houden om initiatief te nemen en te werken aan pittige projecten.

[url=http://www.svcover.nl/show.php?id=88]Bekijk de volledige vacature[/url]


[url=http://www.svcover.nl/show.php?id=77]Lees meer over KPN Consulting[/url] ||

[/table]', '[h1]Vacancies[/h1]

(Note: these vacancies are currently targeted at Dutch speaking students)

[table noborder]

|| [h2]KPN Consulting[/h2] ||
|| [h3]Young Professional Project/Proces[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Het Young Professional traject van KPN Consulting duurt in totaal een jaar en is een goede mix tussen trainingen, cursussen en het op doen van praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als YP start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende, vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

[url=http://www.svcover.nl/show.php?id=78]Bekijk de volledige vacature[/url] ||


|| [h3]Young Professional Technology[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Het Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

[url=http://www.svcover.nl/show.php?id=86]Bekijk de volledige vacature[/url] ||


|| [h3]Management Traineeship[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Je bent een assertieve starter met je Master op zak. Je staat te trappelen om het bedrijfsleven te overtuigen van je talenten en bent gedreven om bij de absolute top te horen. Ontwikkelingen in de IT dienstverlening vind je boeiende materie. Daarom werk jij straks als Management Trainee bij KPN IT Solutions. Vandaag high potential? Binnen de kortste tijd groei jij uit tot een beslisser binnen onze organisatie!

[url=http://www.svcover.nl/show.php?id=87]Bekijk de volledige vacature[/url] ||


|| [h3]Trainee Techniek[/h3] ||
|| [url=http://www.kpn.com/consulting] [img=/images/KPN_Consulting.jpg] [/url]
|| Binnen KPN draait IT, naast bits, bytes en netwerken, ook om aansturen en regisseren. Daarvoor hebben we professionals nodig die snappen dat IT een middel is en beslist geen doel. Professionals die begrijpen hoe je wensen vanuit de klant kunt vertalen naar IT oplossingen. Die ervan houden om initiatief te nemen en te werken aan pittige projecten.

[url=http://www.svcover.nl/show.php?id=88]Bekijk de volledige vacature[/url]


[url=http://www.svcover.nl/show.php?id=77]Lees meer over KPN Consulting[/url] ||

[/table]', '');
INSERT INTO pages VALUES (55, 0, 'Topicus', '[h1]Bedrijfsomschrijving Topicus[/h1]
Topicus is een ICT dienstverlener met meer dan 300 medewerkers gevestigd in het centrum van Deventer en werken in units van maximaal 25 medewerkers. Wij bouwen volledig webgebaseerde, complexe ICT-systemen voor de sectoren zorg, onderwijs en finance in JAVA of .NET. Bij het realiseren van deze systemen krijgen we te maken met vraagstukken over security, privacy, architectuur, high performance, implementatie, miljoenen regels code en maken daarbij gebruik van de nieuwste technologieën.

Dankzij de sterke technische basis en uitgebreide domeinkennis is Topicus in staat om interessante projecten te verwerven die in Deventer worden uitgevoerd. Bijvoorbeeld de ontwikkeling van elektronische patiëntendossiers, leerlingenadministratiesystemen of op internettechnologie gebaseerde administraties van grote banken en financiële tussenpersonen.

[h2]Topicus voor studenten en young professionals[/h2]

Topicus begeleidt jaarlijks circa 50 WO- en HBO studenten met hun stage- of afstudeeropdracht. Een selectie van de openstaande opdrachten is te vinden op onze website http://www.topicus.nl/werken/studenten. Voor eigen ideeën of suggesties voor een opdracht staan wij ook open.

Voor startende ICT professionals hebben wij altijd vacatures open staan binnen het vakgebied software ontwikkeling en informatie analyse. De vacatures zijn te vinden op onze website http://www.topicus.nl/werken/young-professionals. Als teamlid maak je actief deel uit van een projectteam, waarin je meewerkt EN meedenkt naar nieuwe oplossingen voor uitdagende ict-projecten. Met de Agile projectaanpak is Topicus in staat snel en doelgericht applicaties te realiseren.

[h2]Ontwikkeling en opleiding[/h2]

Onze medewerkers laten doorgroeien is de sleutel voor ons toekomstige succes. Dat dit werkt hebben we gemerkt: nauwelijks verloop, een goede instroom van jong talent en een steeds professionelere organisatie. Talent werkt graag met talent.

Ons strategisch medewerkersbeleid bestaat vooral uit het willen laten groeien van werknemers, zowel in kennis als in effectiviteit en uitstraling. Wij doen er alles aan om ervoor te zorgen dat onze medewerkers de lat voor zichzelf hoog leggen en te bereiken.

Wij investeren daarom voortdurend in de ontwikkeling van kennis en kunde van onze medewerkers. Ze worden actief betrokken bij op maat gesneden programma`s om test- en securitytrajecten, maar ook project- en productmanagement en ondernemerschap nog verder te professionaliseren. Topicus werkt samen met diverse hogescholen en universiteiten aan het inrichten van de Topicus University.

[h2]Ruimte voor ideeën[/h2]

Binnen Topicus wordt veel ruimte geboden voor innovatie, ontwikkeling van nieuwe business concepten, ondernemerschap en creatieve uitspattingen. Op technisch vlak experimenteren we bijvoorbeeld actief met HTML5, Business Intelligence, mobiele toepassingen, MVC frameworks, het .NET Framework 4.0, LINQ en MS Generics etc. Als je een goed idee hebt, geeft Topicus jou tijd voor research en toepassing binnen projecten. Zo is ook ons eigen bier de Topicus Gifkikker ontstaan.

[h2]Wat bieden wij[/h2]

Standaard krijg je een prima salaris, winstuitkering, mobiele telefoon en notebook. Je kan ook deelnemen aan een spaarloon- en pensioenregeling of de collectieve

ziektenkostenverzekering. We borrelen elke maand met alle collega`s in onze stamkroeg, waar we onze eigen Gifkikker van het fust drinken!', NULL, NULL);
INSERT INTO pages VALUES (56, 0, 'Sponsormogelijkheden', '[h1]Sponsormogelijkheden[/h1]
Studievereniging Cover biedt bedrijven de mogelijkheid tot sponsoren van verschillende evenementen en verschillende media. Hiervoor kunt u contact opnemen met de Commissaris Extern op [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]', NULL, NULL);
INSERT INTO pages VALUES (57, 0, 'Oude besturen', '[samenvatting]Bestuur XX (2011/2012)
Bestuursnaam: Helder[/samenvatting]
[h1]Bestuur XX: Helder[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Ben Wolf
Secretaris: Maikel Grobbe
Penningmeester: Laura Baakman
Commissaris Intern: Jouke van der Weij
Commissaris Extern: Maarten van Gijssel', NULL, NULL);
INSERT INTO pages VALUES (58, 0, 'Bestuur XIX', '[samenvatting]Bestuur XIX (2010/2011)
Bestuursnaam: Stabiliteit[/samenvatting]
[h1]Bestuur XIX: Stabiliteit[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Gabe van der Weijde
Secretaris: Tineke Slotegraaf
Penningmeester: Joris de Keijser
Commissaris Intern: Diederick Kaaij
Commissaris Extern: Wolter Peterson ', '', '');
INSERT INTO pages VALUES (59, 0, 'Bestuur XVIII', '[samenvatting]Bestuur 18 (2009/2010)
Bestuursnaam: Verbonden[/samenvatting]
[h1]Bestuur XVIII: Verbonden[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Anita Drenthen
Secretaris: Eveline Broers
Penningmeester: Marco Bosman
Commissaris Intern: Eric Jansen
Commissaris Extern: Dirk Zittersteyn ', '', '');
INSERT INTO pages VALUES (60, 0, 'Bestuur XVII', '[samenvatting]Bestuur 17 (2008/2009)
Bestuursnaam: Infinity[/samenvatting]
[h1]Bestuur XVII: Infinity[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Sjors Lutjeboer
Secretaris: Ruud Henken
Penningmeester: Dyon Veldhuis
Commissaris Intern: Sybren Jansen
Commissaris Extern: Ben van Os ', '', '');
INSERT INTO pages VALUES (61, 0, 'Bestuur XVI', '[samenvatting]Bestuur 16 (2007/2008)
Bestuursthema: De Ontwaking[/samenvatting]
[h1]Bestuur XVI: De Ontwaking[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Joël Kuiper
Secretaris: Daniël Karavolos
Penningmeester: Wilco Wijbrandi
Commissaris Intern: Margreet Vogelzang
Commissaris Extern: Peter Jorritsma ', '', '');
INSERT INTO pages VALUES (62, 0, 'Bestuur XV', '[samenvatting]Bestuur 15 (2007)
Bestuursthema: Metamorfose[/samenvatting]
[h1]Bestuur XV: Metamorfose[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Neeltje de Ruijter
Secretaris: Stefan Wierda
Penningmeester: Ferdinand Stam (later: Jurjen Wierda)
Commissaris Intern: (later: Eva van Viegen)
Commissaris Extern: Jurjen Wierda ', '', '');
INSERT INTO pages VALUES (63, 0, 'Bestuur XIV', '[samenvatting]Bestuur 14 (2006)
Bestuursthema: Illusie[/samenvatting]
[h1]Bestuur XIV: Illusie[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Henrieke Quarré
Secretaris: Roeland van Batenburg
Penningmeester: Martijn Hartman
Commissaris Intern: Cas van Noort
Commissaris Extern: Nick Degens ', '', '');
INSERT INTO pages VALUES (64, 0, 'Bestuur XIII', '[samenvatting]Bestuur 13 (2006)
[/samenvatting]
[h1]Coverbestuur 13[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Bastiaan Fens
Secretaris: Lise Pijl
Penningmeester: Cas van Noort ', '', '');
INSERT INTO pages VALUES (65, 0, 'Bestuur XII', '[samenvatting]Bestuur 12 (2005)
[/samenvatting]
[h1]Coverbestuur 12[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Bastiaan Fens
Secretaris: Merel Oppelaar
Penningmeester: Ferdinand Stam
Commissaris Intern: Chris Janssen
Commissaris Extern: Rosemarijn Looije ', '', '');
INSERT INTO pages VALUES (66, 0, 'Bestuur XI', '[samenvatting]Bestuur 11 (2004)
[/samenvatting]
[h1]Coverbestuur 11[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Stefan Renkema
Secretaris: Maaike Schweers
Penningmeester: Mart van de Sanden
Commissaris Intern: Heiko Harders
Commissaris Extern: Sander van Dijk ', '', '');
INSERT INTO pages VALUES (67, 0, 'Bestuur X', '[samenvatting]2003[/samenvatting]
[h1]Bestuur 2003[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Jacob van der Blij
Secretaris: Jan Bernard Marsman
Penningmeester: Sjoerd de Jong
Commissaris Intern: Jolie Lanser
Commissaris Extern: Herman Kloosterman', '', '');
INSERT INTO pages VALUES (68, 0, 'Bestuur IX', '[samenvatting]Bestuur IX (2001/2002)[/samenvatting]
[h1]Coverbestuur IX[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Liesbeth van der Feen
Secretaris: Berto Bojink
Penningmeester: Gerben Blom
Commissaris Intern: Daan Reid
Commissaris Extern: Douwe Terluin', '', '');
INSERT INTO pages VALUES (69, 0, 'Bestuur VIII', '[samenvatting]Bestuur VIII (2000/2001)[/samenvatting]
[h1]Coverbestuur VIII[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Willem Hibbeln
Secretaris: (later: Marcia van Oploo)
Penningmeester: Jan Willem Marck
Commissaris intern: Liesbeth van der Feen
Commissaris extern: Sebastiaan Pais (later: Douwe Terluin)

2001: KI wordt een voltijdsopleiding met een complete Bachelor (niet meer alleen post-propedeutisch). De studie trekt meer studenten aan en Cover maakt vanaf dit jaar voor een aantal jaar een grote groei in ledenaantal.', '', '');
INSERT INTO pages VALUES (70, 0, 'Bestuur VII', '[samenvatting]Bestuur VII (1999/2000)[/samenvatting]
[h1]Coverbestuur VII[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Peter Zwerver
Secretaris: Marleen Schippers
Penningmeester: Gerben Blom
Commissaris intern: Fiona Douma
Commissaris extern: Ernst-Jan Tissing
', '', '');
INSERT INTO pages VALUES (71, 0, 'Bestuur VI', '[samenvatting]Bestuur VI (1997/1998)[/samenvatting]
[h1]Coverbestuur VI[/h1]
____BESTUURSFOTO____

Het 6e bestuur van Cover. Ingehamerd op 12 oktober 1998.

[h2]Leden[/h2]
Voorzitter: Sjoerd Druiven
Secretaris: Gineke ten Holt
Penningmeester: Jan Misker
ActCie Commissaris: Arjan Stuiver
StudCie Commissaris: Albert ter Haar
', '', '');
INSERT INTO pages VALUES (72, 0, 'Bestuur V', '[samenvatting]Bestuur V (1997/1998)[/samenvatting]
[h1]Coverbestuur V[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Wiebe Baron
Secretaris: Maartje van der Veen
Penningmeester: Aletta Eikelboom
StudActie: Jan Misker
Actie: Sjoerd Druiven', '', '');
INSERT INTO pages VALUES (73, 0, 'Bestuur IV', '[samenvatting]Bestuur IV (1996/1997)[/samenvatting]
[h1]Coverbestuur IV[/h1]
____BESTUURSFOTO____

Het 4e bestuur van Cover is ingehamerd op 12 september 1996.

[h2]Leden[/h2]
Voorzitter: Gregor Tee (later: Diederik Roosch)
Secretaris: Diederik Roosch (later: Arthur Perton)
Penningmeester: Johan Kruiseman
StudActie: Arthur Perton
Actie: Wiebe Baron', '', '');
INSERT INTO pages VALUES (74, 0, 'Bestuur III', '[samenvatting]Bestuur III (1995/1996)[/samenvatting]
[h1]Coverbestuur III[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Martijn de Vries
Secretaris: Erik vd Neut
Penningmeester: Libbe Oosterman
Commissaris intern: Lennart Quispel
Commissaris extern: Desiree Houkema
', '', '');
INSERT INTO pages VALUES (75, 0, 'Bestuur II', '[samenvatting]Bestuur II (1994/1995)[/samenvatting]
[h1]Coverbestuur II[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Marc Oyserman
Secretaris: Jeroen Kruse
Penningmeester: Erik vd Neut
Commissaris Intern: Jaap Bos (later: Desiree Houkema)
Commissaris Extern: Karel de Vos
', '', '');
INSERT INTO pages VALUES (76, 0, 'Bestuur I', '[samenvatting]Bestuur I (1993/1994)[/samenvatting]
[h1]Coverbestuur I[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Michiel Dulfer
Bruno Emans
Marco Oyserman
Paul Vogt

De precieze functieverdeling van het eerste Coverbestuur is onbekend.
', '', '');
INSERT INTO pages VALUES (77, 0, 'KPN Consulting', '[h1]KPN - Het beste ICT bedrijf van Nederland[/h1]

KPN speelt als marktleider in geïntegreerde IT- en telecommunicatiediensten een grote rol in de Nederlandse maatschappij. Dankzij onze dienstverlening kun jij je dagelijkse boodschappen pinnen, varen schepen veilig de haven van Rotterdam binnen, rijden de treinen van de NS en kan iedereen in geval van nood 112 bellen. Wij verbinden de samenleving met ICT-dienstverlening die innovatief en veilig is. 

Onze professionals nemen organisaties mee in de nieuwste ICT-ontwikkelingen. Door hun sectoren te begrijpen, door hun specifieke behoeften te kennen en  door hun taal te spreken. Wij adviseren onze klanten én ontwikkelen ICT-diensten die voor hen relevant zijn. Daarna implementeren en onderhouden we het ICT-netwerk, zodat opdrachtgevers hun tijd en energie zorgeloos kunnen steken in die zaken waarin zij goed zijn, om op hun beurt hun eigen klanten optimaal te bedienen. Wij zorgen dat iedereen zijn werk professioneel, efficiënt en met gemak kan doen via onze geavanceerde datacenters en op volledig geïntegreerde werkplekken. Het is niet voor niets dat KPN in 2013 door lezers van Management Team verkozen is tot beste IT-bedrijf van Nederland.

Om op hoog niveau te kunnen presteren investeert KPN volop in haar professionals. Bij het ICT bedrijf van KPN hebben we vier verschillende traineeships en twee young professionaltrajecten bij het onderdeel KPN Consulting.  Wij geven onze medewerkers de ruimte om zich te ontwikkelen en bieden uitgebreide mogelijkheden om plaats- en tijdonafhankelijk te werken, omdat wij een optimale work/life balance belangrijk vinden. Bovendien is ons adviesbedrijf, KPN Consulting,  voor het tweede jaar Great Place to Work geworden.  
KPN doet ''t gewoon. Doe je mee?

Kijk voor onze young professional vacatures (KPN Consulting) of traineeships op www.kpn.com/itsolutions of www.kpnconsulting.nl
', '', '');
INSERT INTO pages VALUES (78, 0, 'Vacature KPN Consulting', '[h1]KPN Consulting[/h1]
[h2]Young Professional Project/Proces[/h2]
[h3]Wat doet een Young Professional Project/Proces?[/h3]
Het Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het ''werkende'' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega''s goed kennen.

Alle kennis die je tijdens de introductie hebt opgedaan, kun je direct toepassen bij onze klanten in projecten waar jij de schakel tussen business en techniek zult zijn. KPN Consulting werkt samen met de 400 grootste bedrijven van Nederland zoals Shell, Nuon, Rijkswaterstaat, NS en ING. Bovendien stippel je samen met een persoonlijke coach een carrièrepad uit aan de hand van jouw kennis, ervaring en ambitie. Gedurende het jaar volg je naast je projecten nog enkele trainingen. Ter afsluiting van het traject voer je samen met je mede Young Professionalcollega''s een praktijkgerichte case op het gebied van Proces- & Projectmanagement uit, die je daarna presenteert aan onze directie.

Na het eerste jaar ontwikkel je je richting Proces- & Projectmanagement of service & performance management. Je kunt daarbij een vliegende start maken door als High Potential door te stromen naar een vervolgopleidingstraject, waar je in korte tijd de kennis, kunde en ervaring opdoet voor de rest van je loopbaan. Dit geeft je de mogelijkheid om uit te groeien tot een gewaardeerde consultant die organisaties met concrete adviezen naar een hoger niveau tilt.

[h3]Wat heb jij ons te bieden als Young Professional?[/h3]
- Afgeronde masteropleiding in ICT, Bedrijfskunde, of een Bèta studierichting;
- Aantoonbare affiniteit met en kennis van ICT;
- Goede communicatievaardigheden;
- Helikopterview, proactief, resultaatgericht en pragmatisch;
- Goede beheersing van de Nederlandse taal;
- Je staat voor kwaliteit en je doet net iets meer dan afgesproken.

[h3]Wat bieden wij jou?[/h3]
Wij bieden jou een carrière bij de nummer 1 in ICT-consultancy. Daarbij horen natuurlijk dingen als een marktconform salaris, een leaseauto, een telefoon, laptop, bonusregeling en goede secundaire arbeidsvoorwaarden. Veel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien, zodoende denken we met je mee in een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!

Daarnaast bieden wij jou een carrière bij dé kennisleider in ICT. Inventief in infrastructuren en opinieleider in leidende standaarden gaan bij ons namelijk hand in hand. Je werkt samen met ervaren en professionele collega''s op uitdagende opdrachten. Je vindt aansluiting bij collega''s rondom jouw expertise waarbij je volop de ruimte en verantwoordelijkheid krijgt voor persoonlijke ontwikkeling en kennisdeling.

[h3]Over KPN Consulting[/h3]
KPN Consulting is de nummer 1 in ICT-consultancy! Wij begeleiden de top van het Nederlandse bedrijfsleven bij het implementeren van vooruitstrevende informatietechnologie. Onze expertise is gebaseerd op ervaring en we ontdoen ICT van hypes en vaagheden. We maken technologie toepasbaar. Dit levert inventieve en waardevolle toepassingen en ervaringen op. 

Mensen en organisaties vinden steeds nieuwe mogelijkheden om met elkaar in contact te komen, samen te werken, plezier te hebben, van elkaar te leren en zaken te doen. Het is ons vak en onze passie om hiervoor de optimale infrastructuur te bieden. We analyseren de impact van veranderingen op de business, organisatie, mensen, systemen en middelen. Onze professionals zijn herkenbaar door ervaringskennis en passie voor ICT en de menselijke maat.

We zijn momenteel ruim 1100 man groot. We zien onze mensen als meer dan werknemer en investeren daar ook in. KPN Consulting stelt zich tot doel de meest aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Werken" geïmplementeerd, dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance. Daarnaast organiseren we ook dingen als de donderdagmiddagborrel, een nieuwjaarsfeest en het jaarlijkse strandfeest, gewoon omdat we dat leuk vinden.

[h3]Interesse?[/h3]
Herken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Pamela van Winterswijk, pamela.vanwinterswijk@kpn.com   06-12872367 of Mandy Klemann, Mandy.Klemann@kpn.com 06-13444246.

Screening is onderdeel van het sollicitatieproces van KPN Consulting. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv.
', '', '');
INSERT INTO pages VALUES (79, 0, 'Capgemini', '[h1]Capgemini[/h1]
[h2]Welkom bij Capgemini[/h2]
Capgemini is marktleider op het gebied van technology, outsourcing en consultancy. Onze overtuiging is dat duurzame resultaten alleen haalbaar zijn door intensieve en innovatieve vormen van  samenwerking met toonaangevende klanten, business partners en collega''s. Werken bij Capgemini staat dan ook voor samenwerken in teams van ondernemende professionals, die resultaatgerichtheid combineren met onconventioneel denken. Een inspirerende omgeving met uitdagende opdrachten/projecten waarin je gewoon jezelf kunt zijn en waarin je jouw talenten optimaal kunt inzetten en ontwikkelen.

[h2]Loopbaanontwikkeling[/h2]
Bij Capgemini kun je je vanuit verschillende startposities ontwikkelen. In eerste instantie leg je een stevige basis voor je toekomstige ontwikkeling. In een later stadium volgt een verdergaande specialisatie of een bredere ontwikkeling op het raakvlak van bedrijfsprocessen en ICT. Op basis van een persoonlijk ontwikkelplan volg je opleidingen en verricht je opdrachten die aansluiten op je ambities en de klantvragen. Je leert veel over het marktsegment waarin je werkt, over ICT- toepassingen, bedrijfsonderdelen en consultancy. In overleg met je manager of coach stippel je een opleidingstraject uit: de basis om een waardevolle bijdrage te leveren aan de opdracht bij je klant.

[h2]Profiel Student[/h2]
Capgemini biedt jong talent met een hbo of universitaire opleiding in de richting van ICT, techniek of bedrijfskunde een uitdagende omgeving en een aantrekkelijk toekomstperspectief. Kun jij samen met collega''s en klanten ambities omzetten in resultaten? Heb jij de drive om de beste te worden door jezelf te blijven ontwikkelen? Iedereen is uniek en heeft eigen ambities. Jij kent jezelf het beste en weet waartoe je in staat bent Kom jij het verschil maken bij Capgemini?

[h2]Afstuderen bij Capgemini[/h2]
Ben je nog niet helemaal klaar met je studie omdat je eerst nog moet afstuderen? Ook dan biedt Capgemini je een wereld aan mogelijkheden. Naast bestaande opdrachten die Capgemini formuleert kun je ook zelf een onderwerp voor een afstudeeropdracht aandragen. Om je afstudeeropdracht goed uit te voeren krijg je een ervaren consultant toegewezen die jou begeleidt en ondersteunt op alle vlakken. Ben jij op zoek naar een interessante plek om af te studeren? Kijk dan [url=http://www.nl.capgemini.com/werkenbij/student/afstudeerprojecten/]hier[/url] naar de  verschillende mogelijkheden die Capgemini biedt.

[h2]Beleef Capgemini: XperienceDay[/h2]
Wil je eerst de sfeer proeven binnen ons bedrijf? Meld je dan aan voor een XperienceDay. Door deze dag krijg je de mogelijkheid zelf te beleven hoe het er bij ons aan toegaat: wat voor mensen werken hier, hoe is onze sfeer en waardoor kenmerkt onze cultuur zich, maar ook wat voor soort werk we doen als consultant bij Capgemini. De XperienceDay is een uitermate geschikte dag om dit te ervaren. Geïnteresseerd? Kijk  [url=http://www.nl.capgemini.com/werkenbij/evenementen/xperiencedays/]hier[/url] wanneer de eerste mogelijkheid is om deze dag te ervaren.

[h2]Interesse?[/h2]
Wil je werken op het snijvlak van business en ICT en daarmee een brug slaan tussen ''klant en techniek''? Of heb je een bedrijfskundig achtergrond en ben je een specialist in jouw vakgebied? Of  zoek je een leuke afstudeeropdracht, klik dan [url=http://www.nl.capgemini.com/werkenbij/vacatures/?tab=1&page=1&subform=subform&q=junior/]hier[/url] voor de mogelijkheden die Capgemini heeft.', '', '');
INSERT INTO pages VALUES (80, 0, 'Ortec', '[h1]Ortec[/h1]
[h2]Over ORTEC[/h2]
ORTEC bestaat uit experts op het gebied van complexe optimalisatievraagstukken in diverse sectoren. Wij zorgen ervoor dat medicijnen op tijd in de apotheek liggen; dat grote supermarktketens miljoenen besparen op hun logistieke netwerken; dat de melk die jij koopt vers is; dat vliegtuigen op tijd  vertrekken; dat het beste staal ter wereld nog altijd uit Nederland komt; dat e-commerce bedrijven de optimale prijs kunnen vragen voor hun producten. En misschien nog wel het allerbelangrijkste: er is geen bedrijf dat zoveel CO2 bespaart als wij. Wij zorgen niet alleen nu voor onze klanten, maar wij zorgen ook voor de toekomst van onze klanten. Dat doen we wereldwijd met vestigingen op vijf continenten.

Onze software wordt bij ruim 1650 klanten wereldwijd ingezet om ingewikkelde planningen te maken, logistieke processen en routes te optimaliseren en productieprocessen te automatiseren. Sinds onze oprichting in 1981, zijn we gegroeid tot meer dan 450 medewerkers en Nederland en ruim 600 medewerkers verspreid over kantoren in Nederland, België, USA, Duitsland, Frankrijk, Engeland, Centraal Oost-Europa, Australië & Nieuw Zeeland en Zuid-Oost Azië.

[h2]Wat hebben wij je te bieden?[/h2]
ORTEC is een ambitieuze organisatie. De gemiddelde ORTEC medewerker heeft een academische achtergrond in de richting van econometrie, operationele research, informatica en  wiskunde, is nog geen 36 jaar en beschikt over een sterk ontwikkeld analytisch denkvermogen. De organisatiestructuur is plat: de communicatielijnen zijn kort en we vermijden een sterke hiërarchie. De sfeer is open en informeel, wat een uitstekende basis is voor een goede werkrelatie. Daarnaast zijn er goede ontwikkelmogelijkheden, krijgen alle medewerkers een coach toegewezen en is er voor alle junioren het Young Professionals Traject opgezet. Dit traject duurt enkele jaren. In die periode volg je enkele cursussen, maak je kennis met de ORTEC producten en de organisatie zelf en wordt je klaargestoomd voor een rol op medior niveau.

[h2]Wil jij bijdragen aan de volgende generatie optimalisatietechnologie?[/h2]
ORTEC is regelmatig op zoek naar enthousiaste studenten of afgestudeerden die de ruimte zoeken om zich te ontwikkelen en willen bijdragen aan de volgende generatie optimalisatietechnologie. Wil je weten wat jouw mogelijkheden zijn? Kijk dan voor afstudeerplekken en vacatures op http://www.ortec.nl/carriere of stuur je CV en motivatie naar recruitment@ortec.com.

ORTEC bv
Groningenweg 6k,
2803 PV Gouda
Tel: +31 (0) 182 540500

Omdat ORTEC haar pand ontgroeit, gaat het hoofdkantoor in mei 2013 verhuizen naar
kantorencomplex Lightline aan de Houtsingel 5 te Zoetermeer.', '', '');
INSERT INTO pages VALUES (81, 27, 'Commissiepagina BHVcie', '[samenvatting]De commissie die de veiligheid van de leden probeert te garanderen. [/samenvatting]
[H1]BHVcie[/H1][center][[url=mailto:bhvcie@svcover.nl]E-Mail[/url]][/center]

[commissie_foto]

DIt is de jongste commissie die Cover heeft. De leden van deze commissie kunnen gevraagd worden om als BHV''er te fungeren op verschillende activiteiten. Als er verder rondom de Coverkamer iets gebeurt waar je ons voor nodig hebt kun je natuurlijk ook altijd bij ons terecht.

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (82, 0, 'Bestuur XXI', '[samenvatting]2012/2013
"plus plus"[/samenvatting]
[h1]Bestuur XXI: plus plus[/h1]
____BESTUURSFOTO____
[h2]Leden[/h2]
Voorzitter: Marten Schutten
Secretaris: Arnoud van der Meulen
Penningmeester: Emma van Linge
Commissaris Intern: Lotte Noteboom
Commissaris Extern: Jordi van Giezen', NULL, NULL);
INSERT INTO pages VALUES (6, 6, 'Commissiepagina Excie', '[samenvatting]De ExCie neemt jullie dit jaar weer mee naar een toffe locatie binnen Europa![/samenvatting]
[H1]ExCie[/H1][center][commissie_foto] [commissie_email][/center]


Vorig collegejaar zijn we naar Stockholm en Uppsala gegaan, deze keer hebben we een andere locatie in gedachten: Boedapest! Van 19 tot 28 april zullen we naar Prezi, Scarab, Gameloft, Buda Castle, John von Neumann instituut en zelfs de opera gaan. Dus houd je agenda''s vrij: we gaan op reis!

Wil je meer weten? Kijk dan op [url=http://excie.svcover.nl]onze website[/url] voor de laatste nieuwtjes en geef je op voor 8 februari!

[commissie_leden]', '[samenvatting]De ExCie will take you to a cool location within Europe![/samenvatting]
[H1]ExCie[/H1][center][commissie_foto] [commissie_email][/center]



Last year we went to Stockholm and Uppsala, this time we have an other location in mind: Budapest!. From April 19th to April 28th we''ll visit Prezi, Buda Castle, Gameloft, the John von Neumann institute and even the Opera State House!. So, clear your schedules: we''re going on a journey!

Intrigued? Register before 8 februari on [url= http://excie.svcover.nl]our website[/url]!

[commissie_leden]', '[commissie_foto]');
INSERT INTO pages VALUES (7, 7, 'Commissiepagina Fotocie', '[samenvatting]
De FotoCie is de commissie die uiteraard foto''s maakt bij van alles en nog wat en die daarnaast ook dingen die met fotografie te maken hebben organiseert!
[/samenvatting]

[H1]FotoCie[/H1]


[commissie_foto]

[center][ [url=mailto:fotocie@svcover.nl] Email [/url]][/center]


De FotoCie maakt mooie foto''s en soms ook filmpjes bij verschillende activiteiten. Ook is er elk jaar een Fotowedstrijd en een Fotodag Soms hebben we ook nog een fotoquiz! Verder maken we graag almanak- bestuurs- of commissiefoto''s en worden we soms gevraagd om bij gala''s e.d. te fotograferen. 

Tevens beschikt de FotoCie over een eigen camera, je kan dus ook gerust in de commissie als je geen camera hebt. We leren je daarbij, als je wilt, ook direct de kneepjes van het vak!

Volg ons nu ook op Facebook en Twitter!

[b]Linkjes:[/b]
Foto''s: [url=http://www.svcover.nl/fotoboek.php]klik[/url] en [url=http://fotocie.svcover.nl]klik[/url].
Filmpjes: [url=http://www.youtube.com/user/fotocie]Youtube[/url]!
Fotowedstrijd: [url=http://fotocie.svcover.nl/] FotoCiepagina[/url].
Twitter: [url=http://twitter.com/fotocie]klik[/url].
Facebook [url=https://www.facebook.com/fotocie]klik[/url].

[commissie_leden]
', '[samenvatting]
The FotoCie is the committee that takes pictures during the various activities of Cover and organises photography related activities!
[/samenvatting]

[H1]FotoCie[/H1]


[commissie_foto]

[center][ [url=mailto:fotocie@svcover.nl] Email [/url]][/center]


The FotoCie takes astonishingly beautiful pictures and sometimes even video''s during the various activities of Cover. In addition, we organise the annual Photo Contest and Photo Day. Sometimes we even organise a Photo Quiz or a Photo Chain Letter. We love to take almanac-, board- or committee pictures and are occasionally asked to take pictures at galas or similar activities.

The FotoCie owns a camera, the Covercamera, so even if you don''t own one yourself it is still possible to join us! If you want to, we would like to teach you all the ins and outs of photography!

Follow us on Facebook and Twitter!

[b]Links:[/b]
Pictures: [url=http://www.svcover.nl/fotoboek.php]click[/url] en [url=http://fotocie.svcover.nl]click[/url].
Videos: [url=http://www.youtube.com/user/fotocie]Youtube[/url]!
Photo Contest: [url=http://fotocie.svcover.nl/] FotoCie website[/url].
Twitter: [url=http://twitter.com/fotocie]click[/url].
Facebook [url=https://www.facebook.com/fotocie]click[/url].

[commissie_leden]', NULL);
INSERT INTO pages VALUES (48, 24, 'Commissiepagina Promotie', '[samenvatting]De Promotie draagt zorg voor de posters van activiteiten. Dus heb je een activiteit, dan zorgen wij voor een passende poster![/samenvatting]

[H1]Promotie[/H1]

[commissie_foto]

[center][ [url=mailto:promotie@svcover.nl]Email[/url] ][/center]


De promotie is de commissie die posters voor je maakt of posters voor je drukt, eigenlijk de commissie die je ergens vanaf het moment dat je een poster nodig hebt, tot het moment dat je hem wilt drukken kunt inschakelen. Zo is er een beetje controle op alle posters en hoeven commissies die hun aandacht liever ergens anders hebben zich geen zorgen meer te maken over hoe het nou met de posters moet.

[commissie_leden]
[commissie_agenda]', '[samenvatting]The Promotie creates posters to promote Cover activities. So do you organise an activity? We will make you an awesome poster![/samenvatting]

[H1]Promotie[/H1]

[commissie_foto]

[center][ [url=mailto:promotie@svcover.nl]Email[/url] ][/center]


The Promotie is the committee that creates and/or prints posters for you. Actually, we are the committee that you can call somewhere in between the moment you need a poster and the moment you want to print it. In that way, the quality of the Cover posters is assured and committees are able to focus on other tasks, without having to worry about posters. 

[commissie_leden]
[commissie_agenda]', NULL);
INSERT INTO pages VALUES (19, 16, 'Commissiepagina SympoCie', '[samenvatting]Wil jij het symposium in 2014 organiseren? Laat je dan horen![/samenvatting]
[H1]SympoCie[/H1]
[center][[url=mailto:sympocie@svcover.nl]E-Mail[/url]][/center]

De SympoCie is de commissie die elk jaar een mooi symposium neerzet. De huidige commissie is druk bezig met de laatste zaken voor het symposium over supercomputing.
Kijk voor meer informatie op sympocie.svcover.nl

Wil jij graag het symposium van 2013 organiseren? Praat/mail dan een keer met wat huidige commissieleden of met de intern van het bestuur!  :)

[commissie_leden]', NULL, NULL);
INSERT INTO pages VALUES (1, 1, 'Commissiepagina Easy', '[samenvatting]Uit de naam van de WebCie kan al afgeleid worden wat deze commissie doet. De WebCie is verantwoordelijk voor de website van Cover (deze dus!).[/samenvatting]
[H1]WebCie[/H1][center][ [commissie_email] ]
[commissie_foto]
[/center]

Uit de naam van de WebCie kan al afgeleid worden wat deze commissie doet. De WebCie is verantwoordelijk voor de website van Cover (deze dus!). Mocht je ergens niet uitkomen, werkt je account niet meer, vind je een probleem of heb je andere op of aanmerkingen, neem dan contact op met de WebCie

[commissie_leden]
[commissie_agenda]', '[samenvatting]The name of the WebCie already tells you what this committee does. The WebCie is responsible for maintaining Cover''s website (the one you''re viewing now!)[/samenvatting]
[H1]WebCie[/H1][center][ [commissie_email] ]
[commissie_foto]
[/center]

The name of the WebCie already tells you what this committee does. The WebCie is responsible for maintaining Cover''s website (the one you''re viewing now!). Are you stuck with a problem, has your account stopped working, has your keen eye spotted a bug or do you have any feedback or remarks about the website? Don''t hesitate to contact the WebCie.

[commissie_leden]
[commissie_agenda]', '[page][/page][page][/page]');
INSERT INTO pages VALUES (17, 0, 'Contact', '[H1]Contactinformatie[/H1]

[H2]Cover[/H2]
[ [url=mailto:bestuur@svcover.nl]Mail Cover[/url] ][ [url=mailto:webcie@ai.rug.nl]Mail Webmaster[/url] ]

[H2]Telefonisch contact[/H2]
[H3]Bedrijven[/H3]
Wilt u informatie over wat Cover voor uw bedrijf kan betekenen (bijv. lezingen, advertenties), neem dan contact op met Sybren Römer, onze Commissaris Extern.
 
mail: [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]
tel: 06 42048635

[H3]Leden en overigen[/H3]
Voor vragen of opmerkingen, neemt u contact op met het bestuur:

mail: [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url]
tel: 050 363 6898. 

Voor dringende zaken kunt u contact opnemen met Harmke Alkemade (Voorzitter): 06 21592481

[H2]Postadres[/H2]
Studievereniging Cover
Postbus 407
9700 AK Groningen

[H2]Bezoekadres[/H2]
Nijenborgh 9
kamer 044
9747 AG Groningen

Rekeningnummer: 1037.96.940
IBAN: NL54RABO0103796940
BIC: RABONL2U

Kamer van Koophandel: 40026707

[H2]Routebeschrijving[/H2]
[b]Routebeschrijving per fiets[/b]
Op de fiets vanuit het centrum van Groningen rijd je naar het Zerniketerrein via de Prinsesseweg, later Zonnelaan. Aangekomen op het Zerniketerrein rijd je net zo lang rechtdoor totdat je op het Zernikeplein komt. Aan de Oostkant staat een groot, blauw gebouw. Dit is de Bernoulliborg.

[b]Routebeschrijving per openbaar vervoer[/b]
Aangekomen op het hoofdstation van Groningen neem je bus 11 of 15 naar Zernike. Uitstappen bij halte Nijenborgh.

Het is overigens ook mogelijk bus 11 richting Zernike te nemen vanaf station Groningen Noord. De route is dan verder hetzelfde.

Voor mensen die met de stoptrein vanuit Leeuwarden komen is het soms sneller om uit te stappen in Zuidhorn en de bus naar Groningen Centraal te nemen. Deze stopt ook bij halte Nijenborgh.

[b]Per auto[/b]
[i]Vanuit Assen (A28)[/i]
[ul]
[li]Bij Julianaplein linksaf richting Leeuwarden/Drachten.[/li]
[li]Eerste afslag rechts ringweg richting Bedum/Zuidhorn.[/li]
[li]Bij rotonde rechtsaf richting Bedum/Zuidhorn.[/li]
[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]
[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]
[/ul]


[i]Vanuit Drachten/Leeuwarden (A7)[/i]
[ul]
[li]Eerste afslag (Groningen-West) nemen, vervolgens de westelijke ringweg nemen richting Bedum.[/li]
[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]
[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]
[/ul]


[i]Vanuit richting Winschoten/Nieuweschans[/i]
[ul]
[li]Bij kruising Europaplein linksaf richting Drachten/Leeuwarden (A7/N28).[/li]
[li]Bij het Julianaplein rechtdoor.[/li]
[li]Eerste afslag rechts ringweg richting Bedum/Zuidhorn.[/li]
[li]Bij rotonde rechtsaf slaan richting Bedum/Zuidhorn.[/li]
[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]
[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]
[/ul]


[i]Vanuit Delfzijl[/i]
[ul]
[li]Bij einde Rijksweg (N41) rechtsaf richting Bedum (N28, oostelijke ringweg).[/li]
[li]Ring volgen richting Drachten tot afslag Paddepoel.[/li]
[li]Deze afslag nemen en meteen rechtsaf slaan richting Zernikecomplex.[/li]
[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]
[/ul]', '[H1]Contact information[/H1]

[H2]Cover[/H2]
[ [url=mailto:bestuur@svcover.nl]Mail Cover[/url] ][ [url=mailto:webcie@ai.rug.nl]Mail Webmaster[/url] ]

[H2]Phone numbers[/H2]
[H3]Companies[/H3]
I you are interested in what Cover can do for your company (e.g. lectures, advertisement), please contact Sybren Römer, our Commissioner External Relations.
 
mail: [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]
tel: +31 6 42048635

[H3]Members & others[/H3]
For questions or remarks, please contact the board.

mail: [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url]
tel: +31 50 363 6898. 

For more urgent matters you can contact our chairman, Harmke Alkemade, directly: +31 6 21592481

[H2]Postal address[/H2]
Studievereniging Cover
Postbus 407
9700 AK Groningen
the Netherlands

[H2]Visitors address[/H2]
Nijenborgh 9
Room 044
9747 AG Groningen
the Netherlands

[h2]Other information[/h2]
Bank account number: 1037.96.940
IBAN: NL54RABO0103796940
BIC: RABONL2U

Chamber of Commerce number: 40026707', NULL);


--
-- TOC entry 2649 (class 0 OID 0)
-- Dependencies: 224
-- Name: pages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('pages_id_seq', 88, true);


--
-- TOC entry 2607 (class 0 OID 24387)
-- Dependencies: 227
-- Data for Name: pollopties; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2650 (class 0 OID 0)
-- Dependencies: 226
-- Name: pollopties_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('pollopties_id_seq', 1242, true);


--
-- TOC entry 2608 (class 0 OID 24392)
-- Dependencies: 228
-- Data for Name: pollvoters; Type: TABLE DATA; Schema: public; Owner: webcie
--



--
-- TOC entry 2610 (class 0 OID 24397)
-- Dependencies: 230
-- Data for Name: profielen; Type: TABLE DATA; Schema: public; Owner: webcie
--

INSERT INTO profielen VALUES (945, 709, '5f4dcc3b5aa765d61d8327deb882cf99', NULL, 'http://www.gravatar.com/avatar/9eb1a30eea95d128067f60477c366be3?s=80&d=monsterid', 'http://ikhoefgeen.nl', NULL, NULL, 'Jelmer', 'en');


--
-- TOC entry 2651 (class 0 OID 0)
-- Dependencies: 229
-- Name: profielen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('profielen_id_seq', 1514, true);


--
-- TOC entry 2611 (class 0 OID 24405)
-- Dependencies: 231
-- Data for Name: profielen_privacy; Type: TABLE DATA; Schema: public; Owner: webcie
--

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
-- Mailinglijsten
--

CREATE SEQUENCE mailinglijsten_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE mailinglijsten (
    id integer NOT NULL DEFAULT nextval('mailinglijsten_id_seq'::regclass) PRIMARY KEY,
    naam varchar(100) NOT NULL,
    adres varchar(255) NOT NULL UNIQUE,
    omschrijving text NOT NULL,
    type integer NOT NULL DEFAULT 1, -- default type is opt-in
    publiek boolean NOT NULL DEFAULT TRUE,
    toegang integer,
    commissie integer NOT NULL DEFAULT 0 REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET DEFAULT,
    tag varchar(100) NOT NULL DEFAULT 'Cover'
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
    id serial NOT NULL PRIMARY KEY,
    mailinglijst integer DEFAULT NULL REFERENCES mailinglijsten (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    commissie integer DEFAULT NULL REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    bericht TEXT NOT NULL,
    return_code integer NOT NULL,
    verwerkt_op timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE mailinglijsten_opt_out (
    id serial NOT NULL PRIMARY KEY,
    mailinglijst_id integer NOT NULL REFERENCES mailinglijsten (id),
    lid_id integer NOT NULL REFERENCES leden (id) ON UPDATE CASCADE ON DELETE CASCADE,
    opgezegd_op timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);


CREATE TABLE stickers (
  id serial NOT NULL,
  label text,
  omschrijving text NOT NULL DEFAULT '',
  lat double precision,
  lng double precision,
  toegevoegd_op date,
  toegevoegd_door integer DEFAULT NULL REFERENCES leden (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET DEFAULT,
  foto bytea DEFAULT NULL,
  foto_mtime timestamp without time zone,
  CONSTRAINT stickersmap_pk PRIMARY KEY (id)
);


CREATE TABLE facebook (
    lid_id INTEGER NOT NULL REFERENCES leden (id),
    data_key VARCHAR(255) NOT NULL,
    data_value TEXT NOT NULL,
    CONSTRAINT facebook_pk PRIMARY KEY (lid_id, data_key)
);

CREATE TABLE announcements (
    id SERIAL NOT NULL,
    committee INTEGER NOT NULL REFERENCES commissies (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    created_on TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT ('now'::text)::timestamp(6) WITHOUT TIME ZONE,
    visibility integer NOT NULL DEFAULT 0,
    CONSTRAINT announcements_pk PRIMARY KEY (id)
);

CREATE TABLE registrations (
    confirmation_code VARCHAR(255) NOT NULL PRIMARY KEY,
    data TEXT NOT NULL,
    registerd_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);

CREATE TABLE applications (
    key VARCHAR(255) NOT NULL PRIMARY KEY,
    name TEXT NOT NULL,
    secret TEXT NOT NULL
);

--
-- TOC entry 2390 (class 2606 OID 25986)
-- Name: actieveleden_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY actieveleden
    ADD CONSTRAINT actieveleden_pkey PRIMARY KEY (id);


--
-- TOC entry 2392 (class 2606 OID 25988)
-- Name: agenda_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY agenda
    ADD CONSTRAINT agenda_pkey PRIMARY KEY (id);


--
-- TOC entry 2394 (class 2606 OID 25990)
-- Name: bedrijven_contactgegevens_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY bedrijven_contactgegevens
    ADD CONSTRAINT bedrijven_contactgegevens_pkey PRIMARY KEY (id);


--
-- TOC entry 2396 (class 2606 OID 25992)
-- Name: bedrijven_stageplaatsen_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY bedrijven_stageplaatsen
    ADD CONSTRAINT bedrijven_stageplaatsen_pkey PRIMARY KEY (id);


--
-- TOC entry 2400 (class 2606 OID 25994)
-- Name: boeken_categorie_categorie_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY boeken_categorie
    ADD CONSTRAINT boeken_categorie_categorie_key UNIQUE (categorie);


--
-- TOC entry 2402 (class 2606 OID 25996)
-- Name: boeken_categorie_id_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY boeken_categorie
    ADD CONSTRAINT boeken_categorie_id_key UNIQUE (id);


--
-- TOC entry 2398 (class 2606 OID 25998)
-- Name: boeken_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY boeken
    ADD CONSTRAINT boeken_pkey PRIMARY KEY (id);


--
-- TOC entry 2406 (class 2606 OID 26002)
-- Name: configuratie_key_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY configuratie
    ADD CONSTRAINT configuratie_pkey PRIMARY KEY (key);


--
-- TOC entry 2408 (class 2606 OID 26004)
-- Name: confirm_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY confirm
    ADD CONSTRAINT confirm_pkey PRIMARY KEY (key);


--
-- TOC entry 2414 (class 2606 OID 26006)
-- Name: forum_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY forum_threads
    ADD CONSTRAINT forum_messages_pkey PRIMARY KEY (id);


--
-- TOC entry 2410 (class 2606 OID 26008)
-- Name: forum_replies_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY forum_messages
    ADD CONSTRAINT forum_replies_pkey PRIMARY KEY (id);


--
-- TOC entry 2412 (class 2606 OID 26010)
-- Name: forum_sessionreads_lid_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY forum_sessionreads
    ADD CONSTRAINT forum_sessionreads_lid_key UNIQUE (lid, forum, thread);


--
-- TOC entry 2416 (class 2606 OID 26012)
-- Name: forums_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY forums
    ADD CONSTRAINT forums_pkey PRIMARY KEY (id);


--
-- TOC entry 2422 (class 2606 OID 26018)
-- Name: foto_reacties_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY foto_reacties
    ADD CONSTRAINT foto_reacties_pkey PRIMARY KEY (id);


--
-- TOC entry 2430 (class 2606 OID 26026)
-- Name: pages_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- TOC entry 2432 (class 2606 OID 26028)
-- Name: pollopties_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY pollopties
    ADD CONSTRAINT pollopties_pkey PRIMARY KEY (id);


--
-- TOC entry 2434 (class 2606 OID 26030)
-- Name: profielen_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY profielen
    ADD CONSTRAINT profielen_pkey PRIMARY KEY (lidid);


--
-- TOC entry 2436 (class 2606 OID 26032)
-- Name: profielen_privacy_field_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY profielen_privacy
    ADD CONSTRAINT profielen_privacy_field_key UNIQUE (field);


--
-- TOC entry 2438 (class 2606 OID 26034)
-- Name: profielen_privacy_id_key; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY profielen_privacy
    ADD CONSTRAINT profielen_privacy_id_key UNIQUE (id);


--
-- TOC entry 2440 (class 2606 OID 26036)
-- Name: sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie; Tablespace: 
--

ALTER TABLE ONLY sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (session_id);


--
-- TOC entry 2626 (class 0 OID 0)
-- Dependencies: 6
-- Name: public; Type: ACL; Schema: -; Owner: webcie
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM webcie;
GRANT ALL ON SCHEMA public TO webcie;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2014-02-12 16:35:49 CET

--
-- PostgreSQL database dump complete
--

