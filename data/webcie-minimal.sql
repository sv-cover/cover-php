--
-- PostgreSQL database dump
--

-- Dumped from database version 10.3
-- Dumped by pg_dump version 10.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


--
-- Name: actieveleden_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.actieveleden_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.actieveleden_id_seq OWNER TO webcie;

--
-- Name: agenda_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.agenda_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.agenda_id_seq OWNER TO webcie;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: agenda; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.agenda (
    id integer DEFAULT nextval('public.agenda_id_seq'::regclass) NOT NULL,
    kop character varying(100) NOT NULL,
    beschrijving text,
    committee_id smallint NOT NULL,
    van timestamp with time zone NOT NULL,
    tot timestamp with time zone,
    locatie character varying(100),
    image_url character varying(255) DEFAULT NULL,
    private smallint DEFAULT 0,
    extern smallint DEFAULT 0 NOT NULL,
    facebook_id character varying(20),
    replacement_for integer
);


ALTER TABLE public.agenda OWNER TO webcie;

--
-- Name: announcements; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.announcements (
    id integer NOT NULL,
    committee_id integer NOT NULL,
    subject text NOT NULL,
    message text NOT NULL,
    created_on timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    visibility integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.announcements OWNER TO webcie;

--
-- Name: announcements_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.announcements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.announcements_id_seq OWNER TO webcie;

--
-- Name: announcements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.announcements_id_seq OWNED BY public.announcements.id;


--
-- Name: applications; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.applications (
    key character varying(255) NOT NULL,
    name text NOT NULL,
    secret text NOT NULL
);


ALTER TABLE public.applications OWNER TO webcie;

--
-- Name: bedrijven_adres_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.bedrijven_adres_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bedrijven_adres_id_seq OWNER TO webcie;

--
-- Name: bedrijven_contactgegevens_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.bedrijven_contactgegevens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bedrijven_contactgegevens_id_seq OWNER TO webcie;

--
-- Name: bedrijven_stageplaatsen_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.bedrijven_stageplaatsen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bedrijven_stageplaatsen_id_seq OWNER TO webcie;

--
-- Name: besturen_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.besturen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.besturen_id_seq OWNER TO webcie;

--
-- Name: besturen; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.besturen (
    id smallint DEFAULT nextval('public.besturen_id_seq'::regclass) NOT NULL,
    naam character varying(25) NOT NULL,
    login character varying(50),
    website character varying(100),
    page_id integer
);


ALTER TABLE public.besturen OWNER TO webcie;

--
-- Name: commissies_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.commissies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.commissies_id_seq OWNER TO webcie;

--
-- Name: commissies; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.commissies (
    id smallint DEFAULT nextval('public.commissies_id_seq'::regclass) NOT NULL,
    naam character varying(25) NOT NULL,
    login character varying(50),
    website character varying(100),
    page_id integer,
    hidden integer DEFAULT 0 NOT NULL,
    vacancies date,
    type integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.commissies OWNER TO webcie;


--
-- Name: committee_email; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.committee_email (
    committee_id smallint NOT NULL,
    email text
);


ALTER TABLE public.committee_email OWNER TO webcie;

--
-- Name: committee_members; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.committee_members (
    id integer DEFAULT nextval('public.actieveleden_id_seq'::regclass) NOT NULL,
    member_id smallint NOT NULL,
    committee_id smallint NOT NULL,
    functie character varying(50)
);


ALTER TABLE public.committee_members OWNER TO webcie;

--
-- Name: configuratie; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.configuratie (
    key character varying(100) NOT NULL,
    value text NOT NULL
);


ALTER TABLE public.configuratie OWNER TO webcie;

--
-- Name: email_confirmation_tokens; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.email_confirmation_tokens (
    key character(40) NOT NULL,
    member_id integer NOT NULL,
    email text NOT NULL,
    created_on timestamp without time zone NOT NULL
);


ALTER TABLE public.email_confirmation_tokens OWNER TO webcie;

--
-- Name: forum_acl_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_acl_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_acl_id_seq OWNER TO webcie;

--
-- Name: forum_acl; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_acl (
    id integer DEFAULT nextval('public.forum_acl_id_seq'::regclass) NOT NULL,
    forum_id integer NOT NULL,
    author_type smallint,
    author_id integer,
    permissions integer
);


ALTER TABLE public.forum_acl OWNER TO webcie;

--
-- Name: forum_group_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_group_id_seq OWNER TO webcie;

--
-- Name: forum_group; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_group (
    id integer DEFAULT nextval('public.forum_group_id_seq'::regclass) NOT NULL,
    name character varying(50)
);


ALTER TABLE public.forum_group OWNER TO webcie;

--
-- Name: forum_group_member_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_group_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_group_member_id_seq OWNER TO webcie;

--
-- Name: forum_group_member; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_group_member (
    id integer DEFAULT nextval('public.forum_group_member_id_seq'::regclass) NOT NULL,
    group_id integer,
    author_type smallint,
    author_id integer
);


ALTER TABLE public.forum_group_member OWNER TO webcie;

--
-- Name: forum_header_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_header_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_header_id_seq OWNER TO webcie;

--
-- Name: forum_header; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_header (
    id integer DEFAULT nextval('public.forum_header_id_seq'::regclass) NOT NULL,
    name character varying(150),
    "position" integer
);


ALTER TABLE public.forum_header OWNER TO webcie;

--
-- Name: forum_lastvisits; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_lastvisits (
    lid integer NOT NULL,
    forum_id integer NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone
);


ALTER TABLE public.forum_lastvisits OWNER TO webcie;

--
-- Name: forum_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_messages_id_seq OWNER TO webcie;

--
-- Name: forum_messages; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_messages (
    id integer DEFAULT nextval('public.forum_messages_id_seq'::regclass) NOT NULL,
    thread_id integer NOT NULL,
    author_id integer NOT NULL,
    message text NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone NOT NULL,
    author_type smallint DEFAULT 1
);


ALTER TABLE public.forum_messages OWNER TO webcie;

--
-- Name: forum_sessionreads; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_sessionreads (
    lid_id integer NOT NULL,
    forum_id integer NOT NULL,
    thread_id integer NOT NULL
);


ALTER TABLE public.forum_sessionreads OWNER TO webcie;

--
-- Name: forum_threads_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forum_threads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forum_threads_id_seq OWNER TO webcie;

--
-- Name: forum_threads; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_threads (
    id integer DEFAULT nextval('public.forum_threads_id_seq'::regclass) NOT NULL,
    forum_id integer NOT NULL,
    author_id integer NOT NULL,
    subject character varying(250) NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone NOT NULL,
    author_type smallint DEFAULT 1,
    poll smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.forum_threads OWNER TO webcie;

--
-- Name: forum_visits; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forum_visits (
    lid_id integer NOT NULL,
    forum_id integer NOT NULL,
    lastvisit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone,
    sessiondate timestamp without time zone
);


ALTER TABLE public.forum_visits OWNER TO webcie;

--
-- Name: forums_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.forums_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.forums_id_seq OWNER TO webcie;

--
-- Name: forums; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.forums (
    id integer DEFAULT nextval('public.forums_id_seq'::regclass) NOT NULL,
    name character varying(50) NOT NULL,
    description character varying(255) NOT NULL,
    "position" integer DEFAULT 0
);


ALTER TABLE public.forums OWNER TO webcie;

--
-- Name: foto_boeken_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.foto_boeken_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.foto_boeken_id_seq OWNER TO webcie;

--
-- Name: foto_boeken; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_boeken (
    id integer DEFAULT nextval('public.foto_boeken_id_seq'::regclass) NOT NULL,
    parent_id integer DEFAULT 0 NOT NULL,
    titel character varying(50) NOT NULL,
    fotograaf text,
    date date,
    beschrijving text,
    visibility integer DEFAULT 0 NOT NULL,
    last_update timestamp without time zone,
    sort_index integer
);


ALTER TABLE public.foto_boeken OWNER TO webcie;

--
-- Name: foto_boeken_custom_visit; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_boeken_custom_visit (
    boek_id text NOT NULL,
    lid_id integer NOT NULL,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL
);


ALTER TABLE public.foto_boeken_custom_visit OWNER TO webcie;

--
-- Name: foto_boeken_visit; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_boeken_visit (
    boek_id integer NOT NULL,
    lid_id integer NOT NULL,
    last_visit timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL
);


ALTER TABLE public.foto_boeken_visit OWNER TO webcie;

--
-- Name: foto_faces; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_faces (
    id integer NOT NULL,
    foto_id integer NOT NULL,
    x real NOT NULL,
    y real NOT NULL,
    w real NOT NULL,
    h real NOT NULL,
    lid_id integer,
    deleted boolean DEFAULT false NOT NULL,
    tagged_by integer,
    custom_label character varying(255),
    cluster_id integer,
    tagged_on timestamp without time zone
);


ALTER TABLE public.foto_faces OWNER TO webcie;

--
-- Name: foto_faces_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.foto_faces_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.foto_faces_id_seq OWNER TO webcie;

--
-- Name: foto_faces_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.foto_faces_id_seq OWNED BY public.foto_faces.id;


--
-- Name: foto_hidden; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_hidden (
    foto_id integer NOT NULL,
    lid_id integer NOT NULL
);


ALTER TABLE public.foto_hidden OWNER TO webcie;

--
-- Name: foto_likes; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_likes (
    foto_id integer NOT NULL,
    lid_id integer NOT NULL,
    liked_on timestamp without time zone
);


ALTER TABLE public.foto_likes OWNER TO webcie;

--
-- Name: foto_reacties_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.foto_reacties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.foto_reacties_id_seq OWNER TO webcie;

--
-- Name: foto_reacties; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_reacties (
    id integer DEFAULT nextval('public.foto_reacties_id_seq'::regclass) NOT NULL,
    foto integer NOT NULL,
    auteur integer NOT NULL,
    reactie text NOT NULL,
    date timestamp without time zone DEFAULT ('now'::text)::timestamp(6) with time zone
);


ALTER TABLE public.foto_reacties OWNER TO webcie;

--
-- Name: foto_reacties_likes; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.foto_reacties_likes (
    id integer NOT NULL,
    reactie_id integer NOT NULL,
    lid_id integer NOT NULL
);


ALTER TABLE public.foto_reacties_likes OWNER TO webcie;

--
-- Name: foto_reacties_likes_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.foto_reacties_likes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.foto_reacties_likes_id_seq OWNER TO webcie;

--
-- Name: foto_reacties_likes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.foto_reacties_likes_id_seq OWNED BY public.foto_reacties_likes.id;


--
-- Name: fotos_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.fotos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fotos_id_seq OWNER TO webcie;

--
-- Name: fotos; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.fotos (
    id integer DEFAULT nextval('public.fotos_id_seq'::regclass) NOT NULL,
    boek integer NOT NULL,
    beschrijving character varying(255),
    added_on timestamp without time zone,
    width integer,
    height integer,
    filepath text,
    filehash character(8),
    created_on timestamp without time zone,
    sort_index integer,
    hidden boolean DEFAULT false
);


ALTER TABLE public.fotos OWNER TO webcie;

--
-- Name: gastenboek_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.gastenboek_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.gastenboek_id_seq OWNER TO webcie;

--
-- Name: leden; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.leden (
    id integer NOT NULL,
    voornaam character varying(255) NOT NULL,
    tussenvoegsel character varying(255),
    achternaam character varying(255) NOT NULL,
    adres character varying(255) NOT NULL,
    postcode character varying(7) NOT NULL,
    woonplaats character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    geboortedatum date,
    geslacht character(1) NOT NULL,
    privacy integer NOT NULL,
    type integer DEFAULT 1,
    machtiging smallint,
    beginjaar integer,
    telefoonnummer character varying(20),
    onderschrift character varying(200),
    avatar character varying(100),
    homepage character varying(255),
    nick character varying(50),
    taal character varying(10) DEFAULT 'en'::character varying,
    member_from date,
    member_till date,
    donor_from date,
    donor_till date
);


ALTER TABLE public.leden OWNER TO webcie;

--
-- Name: lid_fotos_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.lid_fotos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.lid_fotos_id_seq OWNER TO webcie;

--
-- Name: lid_fotos; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.lid_fotos (
    id integer DEFAULT nextval('public.lid_fotos_id_seq'::regclass) NOT NULL,
    lid_id integer,
    foto bytea,
    foto_mtime timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone
);


ALTER TABLE public.lid_fotos OWNER TO webcie;

--
-- Name: links_categorie_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.links_categorie_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.links_categorie_id_seq OWNER TO webcie;

--
-- Name: links_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.links_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.links_id_seq OWNER TO webcie;

--
-- Name: mailinglijsten_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.mailinglijsten_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.mailinglijsten_id_seq OWNER TO webcie;

--
-- Name: mailinglijsten; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.mailinglijsten (
    id integer DEFAULT nextval('public.mailinglijsten_id_seq'::regclass) NOT NULL,
    naam character varying(100) NOT NULL,
    adres character varying(255) NOT NULL,
    omschrijving text NOT NULL,
    publiek boolean DEFAULT true NOT NULL,
    toegang integer,
    commissie integer DEFAULT 0 NOT NULL,
    type integer DEFAULT 1 NOT NULL,
    tag character varying(100) DEFAULT 'Cover'::character varying NOT NULL,
    on_first_email_subject text,
    on_first_email_message text,
    on_subscription_subject text,
    on_subscription_message text
);


ALTER TABLE public.mailinglijsten OWNER TO webcie;

--
-- Name: mailinglijsten_abonnementen; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.mailinglijsten_abonnementen (
    abonnement_id character(40) NOT NULL,
    mailinglijst_id integer NOT NULL,
    lid_id integer,
    naam character varying(255),
    email character varying(255),
    ingeschreven_op timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    opgezegd_op timestamp without time zone
);


ALTER TABLE public.mailinglijsten_abonnementen OWNER TO webcie;

--
-- Name: mailinglijsten_berichten_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.mailinglijsten_berichten_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.mailinglijsten_berichten_id_seq OWNER TO webcie;

--
-- Name: mailinglijsten_berichten; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.mailinglijsten_berichten (
    id integer DEFAULT nextval('public.mailinglijsten_berichten_id_seq'::regclass) NOT NULL,
    mailinglijst integer,
    bericht text NOT NULL,
    return_code integer NOT NULL,
    verwerkt_op timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    commissie integer,
    sender text
);


ALTER TABLE public.mailinglijsten_berichten OWNER TO webcie;

--
-- Name: mailinglijsten_opt_out; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.mailinglijsten_opt_out (
    id integer NOT NULL,
    mailinglijst_id integer NOT NULL,
    lid_id integer NOT NULL,
    opgezegd_op timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL
);


ALTER TABLE public.mailinglijsten_opt_out OWNER TO webcie;

--
-- Name: mailinglijsten_opt_out_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.mailinglijsten_opt_out_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.mailinglijsten_opt_out_id_seq OWNER TO webcie;

--
-- Name: mailinglijsten_opt_out_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.mailinglijsten_opt_out_id_seq OWNED BY public.mailinglijsten_opt_out.id;


--
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pages_id_seq OWNER TO webcie;

--
-- Name: pages; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.pages (
    id integer DEFAULT nextval('public.pages_id_seq'::regclass) NOT NULL,
    committee_id integer NOT NULL,
    titel character varying(100) NOT NULL,
    content text,
    content_en text,
    content_de text
);


ALTER TABLE public.pages OWNER TO webcie;

--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.password_reset_tokens (
    key character(40) NOT NULL,
    member_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL
);


ALTER TABLE public.password_reset_tokens OWNER TO webcie;

--
-- Name: passwords; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.passwords (
    lid_id integer NOT NULL,
    password character varying(255) NOT NULL
);


ALTER TABLE public.passwords OWNER TO webcie;

--
-- Name: pollopties_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.pollopties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pollopties_id_seq OWNER TO webcie;

--
-- Name: pollopties; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.pollopties (
    id integer DEFAULT nextval('public.pollopties_id_seq'::regclass) NOT NULL,
    pollid integer NOT NULL,
    optie character varying(150) NOT NULL,
    stemmen smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.pollopties OWNER TO webcie;

--
-- Name: pollvoters; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.pollvoters (
    lid integer NOT NULL,
    poll integer NOT NULL
);


ALTER TABLE public.pollvoters OWNER TO webcie;

--
-- Name: profielen_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.profielen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.profielen_id_seq OWNER TO webcie;

--
-- Name: profielen_privacy; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.profielen_privacy (
    id integer NOT NULL,
    field text NOT NULL
);


ALTER TABLE public.profielen_privacy OWNER TO webcie;

--
-- Name: registrations; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.registrations (
    confirmation_code character varying(255) NOT NULL,
    data text NOT NULL,
    registerd_on timestamp without time zone DEFAULT ('now'::text)::timestamp(6) without time zone NOT NULL,
    confirmed_on timestamp without time zone
);


ALTER TABLE public.registrations OWNER TO webcie;

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.sessions (
    session_id character(40) NOT NULL,
    member_id integer,
    created_on timestamp with time zone,
    ip_address inet,
    last_active_on timestamp with time zone,
    timeout interval,
    application text,
    override_member_id integer,
    override_committees character varying(255) DEFAULT NULL::character varying
);


ALTER TABLE public.sessions OWNER TO webcie;

--
-- Name: sign_up_entries; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.sign_up_entries (
    id integer NOT NULL,
    form_id integer NOT NULL,
    member_id integer,
    created_on timestamp without time zone NOT NULL
);


ALTER TABLE public.sign_up_entries OWNER TO webcie;

--
-- Name: sign_up_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.sign_up_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sign_up_entries_id_seq OWNER TO webcie;

--
-- Name: sign_up_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.sign_up_entries_id_seq OWNED BY public.sign_up_entries.id;


--
-- Name: sign_up_entry_values; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.sign_up_entry_values (
    entry_id integer NOT NULL,
    field_id integer NOT NULL,
    value text NOT NULL
);


ALTER TABLE public.sign_up_entry_values OWNER TO webcie;

--
-- Name: sign_up_fields; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.sign_up_fields (
    id integer NOT NULL,
    form_id integer NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    properties text NOT NULL,
    sort_index integer,
    deleted boolean DEFAULT false
);


ALTER TABLE public.sign_up_fields OWNER TO webcie;

--
-- Name: sign_up_fields_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.sign_up_fields_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sign_up_fields_id_seq OWNER TO webcie;

--
-- Name: sign_up_fields_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.sign_up_fields_id_seq OWNED BY public.sign_up_fields.id;


--
-- Name: sign_up_forms; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.sign_up_forms (
    id integer NOT NULL,
    committee_id integer,
    agenda_id integer,
    created_on timestamp without time zone NOT NULL,
    open_on timestamp without time zone,
    closed_on timestamp without time zone,
    participant_limit INTEGER DEFAULT NULL
);


ALTER TABLE public.sign_up_forms OWNER TO webcie;

--
-- Name: sign_up_forms_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.sign_up_forms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sign_up_forms_id_seq OWNER TO webcie;

--
-- Name: sign_up_forms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: webcie
--

ALTER SEQUENCE public.sign_up_forms_id_seq OWNED BY public.sign_up_forms.id;


--
-- Name: so_documenten_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.so_documenten_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.so_documenten_id_seq OWNER TO webcie;

--
-- Name: so_vakken_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.so_vakken_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.so_vakken_id_seq OWNER TO webcie;

--
-- Name: stickers_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.stickers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stickers_id_seq OWNER TO webcie;

--
-- Name: stickers; Type: TABLE; Schema: public; Owner: webcie
--

CREATE TABLE public.stickers (
    id integer DEFAULT nextval('public.stickers_id_seq'::regclass) NOT NULL,
    label text,
    omschrijving text NOT NULL,
    lat double precision,
    lng double precision,
    toegevoegd_op date,
    toegevoegd_door integer,
    foto bytea,
    foto_mtime timestamp without time zone
);


ALTER TABLE public.stickers OWNER TO webcie;

--
-- Name: taken_id_seq; Type: SEQUENCE; Schema: public; Owner: webcie
--

CREATE SEQUENCE public.taken_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.taken_id_seq OWNER TO webcie;

--
-- Name: announcements id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.announcements ALTER COLUMN id SET DEFAULT nextval('public.announcements_id_seq'::regclass);


--
-- Name: foto_faces id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_faces ALTER COLUMN id SET DEFAULT nextval('public.foto_faces_id_seq'::regclass);


--
-- Name: foto_reacties_likes id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_reacties_likes ALTER COLUMN id SET DEFAULT nextval('public.foto_reacties_likes_id_seq'::regclass);


--
-- Name: mailinglijsten_opt_out id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_opt_out ALTER COLUMN id SET DEFAULT nextval('public.mailinglijsten_opt_out_id_seq'::regclass);


--
-- Name: sign_up_entries id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entries ALTER COLUMN id SET DEFAULT nextval('public.sign_up_entries_id_seq'::regclass);


--
-- Name: sign_up_fields id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_fields ALTER COLUMN id SET DEFAULT nextval('public.sign_up_fields_id_seq'::regclass);


--
-- Name: sign_up_forms id; Type: DEFAULT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_forms ALTER COLUMN id SET DEFAULT nextval('public.sign_up_forms_id_seq'::regclass);


--
-- Data for Name: agenda; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.agenda (id, kop, beschrijving, committee_id, van, tot, locatie, private, extern, facebook_id, replacement_for) FROM stdin;
2679	Open Board Meeting	The board has started their search for successors. It might be a long while before the application deadline, but we are inviting all interested members to an open board meeting. Experience what keeps us busy and what it means to be on the board. Between 11:00  and 15:00 you are free to join us and hear what we discuss during one of our weekly board meetings. Aside from listening you are also free to ask questions.	0	2017-03-15 11:00:00+01	2017-03-15 15:00:00+01	Bernoulliborg 307	0	0	\N	\N
2655	TAB+	This afternoon we will have a special TAB in the Cover room! Not only will we do all the stuff we normally do, like playing games on the Wii U and the PS4, playing yahtzee or drinking a beer or two, this afternoon we will also be eating the snacks provided by the Memory, completely free of charge!\r\n\r\nSo, feel like a break after a hard day of studying and want to just sit, relax and have a laugh, join us at 16.00!	18	2017-02-16 16:00:00+01	2017-02-16 16:00:00+01	Coverroom	0	0	\N	\N
2657	Tab-talk:Fraud Detection in Data Analytics	During this Tab-talk, Paul Spoormans and Michael Otten from SAS (Software Analytics Solutions) will come and talk about fraud detection in data analytics. It will be a fun afternoon with snacks! 	11	2017-02-23 16:00:00+01	2017-02-23 16:00:00+01	CACHE, Nijenborg 9, Groningen	0	0	\N	\N
2660	[Career] Visit TomTom headquarters - Autonomous Driving	Did you know that the worlds most famous navigation company, TomTom, is now working on self-driving cars?\r\nPlease come and join us to the headquarters of TomTom. \r\nWe will get a demo traffic presentation in the traffic tower, a Masterclass on the application of AI in Autonomous Driving by the HEAD of MAPS-Autonomous Driving (Willem Srtijbosch) and a Networking & Drinking session with Willem Strijbosch’s team.\r\nTomTom has been so nice to arrange a touring bus from the Zernike to Amsterdam and back. But, since it's a friday, you can also stay in Amsterdam and go to "thuis thuis" afterwards. \r\nOn the way back we will arrange for some beer in the bus.\r\nSign up now!\r\ntomtom.svcover.nl	26	2017-03-10 12:00:00+01	2017-03-10 21:00:00+01	TomTom headquarters Amsterdam	0	0	\N	\N
2626	Study Support Introduction to Logic	Do you have any problems with Introduction to Logic? Do you want help in preparing for you exam? During this study support session, we will aim to discuss every topic in the course, with special attention going out to topics such as Semantics and Formal Proofs.\r\n\r\nJoin this last unofficial lecture given by students who have passed this course in the past. There will be ample opportunity to ask questions.	31	2017-01-25 13:30:00+01	2017-01-25 15:30:00+01	BB 5161.0222	0	0	\N	\N
2625	Study Support Discrete Structures	Troubles with Discrete Structures? The graphs getting confusion or don't remember all your closures? Then come to this study support lecture. Where we will overview the exam material and practice the difficult problems. 	31	2017-01-23 11:00:00+01	2017-01-23 13:00:00+01	BB 5161.0041b	0	0	\N	\N
2621	Make the BORREL great again!	We all hope you have had a wonderful holiday! Even though all deadlines and exams are all coming soon, we also have time to party! \r\nLike Trump wants to make America great again, the ActiviTee wants to make the borrel great again. Back in the days, the borrel often had 100 free beers, so now we do that one more time!	2	2017-01-11 21:30:00+01	2017-01-11 21:30:00+01	De Tapperij	0	0	\N	\N
2627	PRE CONFERENCE BETA BUSINESS DAYS 2017	At 12 January 2017 the Beta Business Days pre-conference will be held. This year’s speakers are Jouri Schoemaker and Bas Haring. Jouri Schoemaker is a co-founder of Shake-On and has won the Dutch 2016 Championship of pitching. As the Beta Business Days will introduce Shake-On, he will elaborate on his product. \r\nOur main speaker is Bas Haring. He is a popular Dutch philosopher and professor at Leiden University, known from ‘Proefkonijnen’, TEDx talks, NTR and VPRO.  \r\nThe conference will take place in the Geertsemazaal in the Academy Building. The conference starts at 19:30. There will be free drinks after! Make sure to be there!	0	2017-01-12 19:30:00+01	2017-01-12 19:30:00+01	Geertsemazaal Academy Building, RUG	0	1	\N	\N
2629	Super Smash Tournament TAB	Super Smash is a game that is often played in our beloved Cover room. Do you think that you are the best Super Smash gamer of Cover? Come join our activity and show off your skills! We will also bring some free snacks for you to enjoy!\r\n\r\n	18	2017-01-19 16:00:00+01	2017-01-19 16:00:00+01	\N	0	0	\N	\N
2631	Board Game Night	It's sad but true, our usual place to play board games is no more. Not to worry we still have all our usual games for you to play at cafe the Crown. Make sure to bring your game face!	2	2017-01-18 21:00:00+01	2017-01-18 21:00:00+01	Cafe The Crown	0	0	\N	\N
2650	ExCee trip to Antwerp	As you might have heard we’re going to Antwerp! We’ll be going from the 26th till the 30th of April. We’ll visit the university and some awesome companies. Besides this we’ll doing great cultural things (Antwerp is known for their craft beers). So don’t hesitate to join and sign up via excee.svcover.nl	6	2017-04-26 00:00:00+02	2017-04-30 00:00:00+02	\N	0	0	\N	\N
2654	MxCee Trip	It's time for the trip of a lifetime! We will be visiting Los Angeles, San Francisco, Seattle and have a roadtrip in between! See our website for more information: https://mxcee.svcover.nl/	13	2017-11-21 12:00:00+01	2017-12-14 12:00:00+01	U.S.A.	0	0	\N	\N
2636	Career: High Tech Safari	On the 15th of February, Innovatiecluster Drachten will take you on a tour of their companies and offer you an exclusive peek behind the scenes. \r\nYou will meet high-tech professionals in R&D departments and at production lines and come face to face with the latest robots and innovative machines. \r\nA luxury coach will pick you up in Groningen at 10:45 o’clock. \r\nDuring the safari, you will be provided with coffee, tea, soft drinks, snacks and a lunch.\r\nThe safari ends with a buffet dinner, where representatives of the organizing companies join you to hear about your experiences during the safari. \r\nBesides the general program, you can also choose a special in-depth program on three high-tech topics: big data, vision intelligence and robotics. \r\nWhen you sign up, you can indicate which workshop you would like to attend. \r\nAround 20:00 hours, the coach will take you home again.\r\nSign up now via: https://goo.gl/forms/XettFhuWo2tKrtsv2	26	2017-02-15 10:45:00+01	2017-02-15 20:00:00+01	\N	0	0	\N	\N
2623	Study Support Calculus	Are you struggling with a topic in Calculus? Are you just generally confused by all the methods and formulas? Then come to this study support lecture, where we will practice the difficult topics once again and listen closely to all your questions.	31	2017-01-20 11:00:00+01	2017-01-20 13:00:00+01	BB5161.0041b	0	0	\N	\N
2632	Just a lovely borrel	Our monthly borrel/party at the Tapperij.	2	2017-02-01 21:30:00+01	2017-02-01 21:30:00+01	De Tapperij	0	0	154009451764746	\N
2635	'Murica MxCee TAB	The largest high-tech companies in the world desperately need your help, even if they don't know it yet. Come help US make America truly great again! And along the way, let's have some fun in LA. Discover your inner Adele or James Corden at our own carpool karaoke sessions during a roadtrip to the cooler area of Seattle. All of this is going to happen from the 24th of November until the 16th of December. We want YOU to join the MxCee trip to the west coast of the U.S.A.! \r\n\r\nRegistration will be opened at the ‘Murica TAB on the 9th of February. Join us here for more information, hamburgers and lots of American themed games. Can't you wait until then, watch our first promo trailer here: https://www.youtube.com/watch?v=v1Hod7Sikbk. Or/And find us at https://mxcee.svcover.nl for more information and to show your interest.\r\n\r\np.s. the first 8 people that register during this TAB get a present!	13	2017-02-09 16:00:00+01	2017-02-09 16:00:00+01	CACHE	0	0	1100904640055805	\N
2640	Casino Royale	Do you like fast cars, beautiful women and the thrill of gambling? Bring your fast car and fancy outfit and we will bring you the casino games you all know and love. Poker, blackjack, roulette and much more including the crowd favourite of BINGO! The activity will cost 5 euros which enables you to win some of the most fabulous prizes, like the Trump bestseller 'How to get rich', the board game the saboteur, the cutest dog you have ever seen or if you're really lucky maybe even a grand prize of 50.000 euro netto. Join us on February 13th at 20:00 in Cafe De Walrus for one of the best activities you have seen all year.	17	2017-02-13 20:00:00+01	2017-02-13 20:00:00+01	Cafe De Walrus	0	0	274504996300605	\N
2648	Martinus Brewery Tour + Tasting	Are you curious what is going on in a brewery? Then this is your chance: Join us on the tour of Martinus Brouwerij and beer tasting. We will have the tour first, then we will have 3 different special beers. Afterwards there is time to have some more drinks.\r\n\r\nIn short:\r\nWhere: Martinus Brouwerij\r\nWhen: 28 February, 7:30 PM (be on time!)\r\nWhat: Tour + 3 special beers\r\nFee: 10 euros\r\n\r\nPlease fill in this form if you want to join: https://goo.gl/forms/PAGljhHzvz3FSIXs1\r\n	2	2017-02-28 19:30:00+01	2017-02-28 19:30:00+01	Martinus Brouwerij	0	0	590069391202191	\N
2641	Beta Business Days 2017	This activity is not organised by Cover\r\nThe Beta Business Days is a unique two-day career event for all students of the Faculty of Mathematics and Natural Sciences of the University of Groningen. The event gives the more than 3500 students in the Bachelor’s, Master’s or PhD programme the opportunity to investigate their career prospects and make contacts that could lead to an internship, a thesis project or even a job. A varied programme will bring students of the natural sciences and mathematics in contact with companies with a focus on this area. Companies can deliver a presentation or host a stand to generate brand awareness among the attending students. The general lunch and networking reception will be informal networking opportunities for students and the participating businesses. The programme will also include activities such as case studies/workshops, individual meetings and business lunches for which participants will be selected on the basis of their CV or motivation.	0	2017-02-07 16:00:00+01	2017-02-07 16:00:00+01	Martini Plaza	0	0	\N	\N
2637	Alphabet Party: Fulfill your Fantasy	Are you in For the party of your dreams? Come to the Alphabet Party! This years theme is: Fulfill your Fantasy! \r\nThe party will take place on February 22 in Club Kokomo. Doors will be opened from 22:00 till 01:00 o'clock. Tickets cost only € 6,- in the pre-sale and € 7,- at the door. Buy your ticket now at Cover and put on your most Fabulous, Fantastic outfit!	32	2017-02-22 22:00:00+01	2017-02-22 22:00:00+01	Club Kokomo	0	0	\N	\N
2643	LaTeX Workshop	Do you need some help with getting acquainted with LaTeX? Want to get an overview of what a package is or know why LaTeX keeps moving your pictures to the end?Join us at the LaTeX workshop!\r\n\r\nDuring the workshop we will go over the basic setup of a LaTeX document, how to compile it, and how to work with things like images, tables, and math mode.	31	2017-02-13 13:00:00+01	2017-02-13 15:00:00+01	BB 5161.0208 and BB 5161.0283	0	0	1323978034327856	\N
2639	Swimming	After all those exhausting exams, it is time for some fun! We're going to swim at Kardinge. Join us and show your sick abs or show us how to make the biggest bomb.\r\n\r\nFor the maximum price 5 euros you can have the best afternoon you have ever had.\r\n\r\nWe will leave from Bernoulliborg at 16:45, or you can meet us at Kardinge at 17:15.	2	2017-02-06 17:15:00+01	2017-02-06 17:15:00+01	Kardinge	0	0	233445480434677	\N
2653	Deadline Registration MxCee trip	Reminder: The deadline for signing up for the MxCee trip to the west coast of the U.S.A. is at the 28th of February 2017.	13	2017-02-28 23:59:00+01	2017-02-28 23:59:00+01	\N	0	0	\N	\N
2649	General Assembly	We would like to invite you to the General Assembly on the 13th of March. The meeting will start at 19:00. 	0	2017-03-13 19:00:00+01	2017-03-13 19:00:00+01	De Walrus	0	0	\N	\N
2644	git Workshop	Have you always wanted to have a nice overview of all your coding projects? Not having six different test.c files in your directory, just because you wanted to try something out? \r\n\r\nThen attend this workshop, which will teach you about the ease and comfort of Git. Git is a distributed revision control and source code management (SCM) system. It keeps track of everything you have coded and allows you to retrieve older versions of your code. And it makes working together a breeze! \r\n\r\nDuring this workshop you will learn the basics of Git, grap the contents, and how to use git for your next (or current!) project.	31	2017-02-14 11:00:00+01	2017-02-14 13:00:00+01	NB5116.0315	0	0	1222340277857527	\N
2646	BEST Real Live Cluedo	BEST is a sister organisation which is founded by 4 study associations of the Faculty: Cover, FMF, de Chemische Binding and T.F.V. 'Professor Francken'.\r\n\r\nTheir first event will be on February 22, where you get the opportunity to play real live Cluedo and in the meanwhile get to know the people of BEST both from Groningen and even some from Leuven and Delft! Will you help solve the mystery? Join the event, get to know BEST and have lots of fun!\r\nThere is no need to sign up, just show up and you can form or join a group! There is no fee!\r\n\r\n	32	2017-02-22 13:00:00+01	2017-02-22 13:00:00+01	Nijenborgh/Bernoulliborg Hall	0	0	\N	\N
2662	Crash & Compile	On Wednesday the 15th of March, it's once again time for the annual Crash & Compile! The C&C is a programming competition held at Quintor where you take a drink every time your code fails. There will be ten programming exercises ranging in difficulty from "End of Impprog" to "HackerRank" and you'll earn more points the quicker you solve them. There are prizes to win and drinks to be drunk, so sign up (in pairs) as soon as possible, as there are only 30 spaces (15 pairs)! Dinner is included, drinks are free, and there is no entry fee!\r\nSign up at [url=https://lancee.svcover.nl/]lancee.svcover.nl[/url]!	21	2017-03-15 17:30:00+01	2017-03-15 23:00:00+01	Quintor Groningen - Ubbo Emmiussingel 112	0	0	\N	\N
2664	[Career] Lunchlecture: TNO Working life as a software engineer: looking back at RuG CS courses	Jan Pieter will explain how his studying at the university did and did not prepare him for working as a software engineer who builds prototypes using different technologies at TNO. With free lunch!	26	2017-03-08 11:00:00+01	2017-03-08 13:00:00+01	Bernoulliborg 222	0	0	\N	\N
2666	Schittermagische Borrel	This borrel will be schittermagisch! Time to get psyched and get your best drinking outfit out for a great evening!	2	2017-03-01 21:30:00+01	2017-03-01 21:30:00+01	De Tapperij	0	0	\N	\N
2668	Committee Assembly	All chairmen and treasurers are urged to join us. If you cannot be there it is important that someone else from your committee comes to represent the committee.\r\n\r\nAt this committee assembly we are going to do things a little bit differently. \r\nWe are going to go through the half year realisations and Stijn will be there to answer any questions you may still have.\r\nWe are also going discuss the future of the committees and activities. 	0	2017-03-01 15:00:00+01	2017-03-01 17:00:00+01	\N	0	0	\N	\N
2663	Symposium: Made in Groningen	High up north lies the beautiful city of Groningen. The home of the University of Groningen and Study Association Cover for Artificial Intelligence and Computing Science. But Groningen is not only a hotspot for students and tourists, it also is home to a lot of innovative companies and startups. During this symposium on the 18th of May we want to give an exclusive insight into some of these companies. What do they do, and more importantly: how do they use artificial intelligence and computing science in their daily work. Everyone is welcome to join us, but registration is mandatory.\r\n\r\nSee our website https://symposium.svcover.nl for more details and registration!	16	2017-05-18 12:00:00+02	2017-05-18 18:00:00+02	De Pijp, Boterdiep 69-1 Groningen	0	0	1877168439221276	\N
2667	Ice Skating	Previous years we had an amazing time at Kardinge and we could not miss it this year! This time of the year, cold weather is slowly disappearing again and maybe now is your last chance to be on the ice.  Also note that Kardinge is not opening its doors after March until the next winter comes!\r\n\r\nThe entrance will be €6,55. If you don't have proper ice skates, then you will also be able to rent them for 6,- at Kardinge. You can make a reservation at: AV Sport 050-5770060\r\n\r\nWe hope to see you at Kardinge! 	20	2017-03-20 20:15:00+01	2017-03-20 22:15:00+01	Kardinge	0	0	\N	\N
2698	Member weekend	Do you feel like clearing your mind and having fun with Cover friends? Join us this weekend on Member Weekend!\r\nMember Weekend will cost max €59, which includes your stay, dinner, unlimited beer, toasties and above all, BBQ included! Bring your Cover friends and a good mood — we’ll take care of the rest :-)\r\n\r\nSign up here before wednesday the 19th!\r\n\r\nhttp://memberweekend.svcover.nl/\r\n\r\nMore information will follow!	0	2017-04-21 16:00:00+02	2017-04-23 15:00:00+02	Klonie, Ellertsweg 4, 9535 TA Ellertshaar	1	0	399378473775741	\N
2699	Alumniday Computing Science: Where will I end up?	I have finished my studies, now what?\r\nThis is a question many people ask themselves. On this event alumni from Computing Science will tell you where they ended up after studying Computing Science. You get a look into the working life of these people and hopefully you will end up with a better idea of the possibilities within your studyfield.\r\nWe will begin the afternoon with lunch, coffee and tea. Also, we will end the evening with some drinks and snacks.	11	2017-04-24 13:00:00+02	2017-04-24 13:00:00+02	Bernoulliborg 165	0	0	1265162926853857	\N
2665	TAB-Talk: a political account of Privacy	The current government just [url=https://geensleep.net]signed a new law that allows our security services to monitor all internet traffic[/url], and [url=https://50pluspartij.nl/nieuwsblok1/1623-standpunten]the 50 Plus political party[/url] and [url=http://rtlxl.nl/#!/programma-132237/eebb1dfd-4a5d-4c28-b35e-1d498131e2be]Gordon[/url] want to force you to sign into internet with your real ID. On the other hand, WhatsApp, Signal, Facebook messenger and many more started encrypting their messages in a way that makes it impossible for the service providers to read their contents. All the while more people are victim to doxing, extortion and bullying by others sharing their private details and pictures. And still people claim they have nothing to hide.\r\n\r\nCommunication privacy is something that is heavily under development and under attack at the same time. But why do we need privacy? Won't everything be fine if we can't keep secrets and if we can read every terrorist's text message? Could we trust our government with our messages so they can keep us safe?\r\n\r\nTitus Stahl makes a point that communication privacy is not only of personal interest, but that it is essential for democracy to work. And he has a few ideas that we, as (potential) developers of communication solutions, can take into account while developing such systems.\r\n\r\n[url=http://www.titus-stahl.de]Titus Stahl[/url] is an Assistant Professor at the faculty of Philosophy at the RUG, focussed on political and social philosophy. He has a specific interest in communication privacy. Use PGP if you want to email him.\r\n\r\n----\r\nExample of the voice for the end of the anonymous internet:\r\n[embed]https://twitter.com/HaraldDoornbos/status/833788149373480960[/embed]	11	2017-03-09 16:00:00+01	2017-03-09 16:00:00+01	CACHE, Nijenborg 9, Groningen	0	0	\N	\N
2673	esGALAtie	Tuesday the 21st of March it's time for EsGalatie again! Did you enjoy the party last year? This year we're going to make it even better! \r\nJust like last year EsGalatie 2017 will be held in Huize Maas. The doors will be open from 22:00 - 23:00, so make sure you're on time!\r\nThe tickets will be 23 euros in the presale and 25 euros at the door. For this you will get an evening of unlimited beer, wine, soda and domestic spirits. \r\nYou can buy your tickets at the board of your association or from members of the gala committee. Do you have a friend/date that you want to bring who is not a member of the participating associations? No problem! Introducees are welcome as well. \r\nSo put on your nicest suit or dress and we hope to see you all on the 21st!	0	2017-03-21 22:00:00+01	2017-03-22 03:00:00+01	Huize Maas	1	1	382581012110567	\N
2676	Pubquiz	A pubquiz organised by the ActiviTee and the PhotoCee! So expect photo question, regular questions and of course questions about cover related stuff. If you feel like you know a lot of random things and a lot about cover, come test your knowledge while having some beers.	2	2017-03-27 20:00:00+02	2017-03-27 20:00:00+02	Cafe The Crown	0	0	\N	\N
2693	Motivational Lecture	For those of you who struggle with bringing order to your work, working ahead of deadlines and overall motiviation to do your studies, fear no more! During this brief and rapid-fire lecture Jennifer Spenader will use all of her positive energy to help you bring back your motivation with general tips and tricks that will make your studying - and even working - life easier!	31	2017-04-18 15:00:00+02	2017-04-18 17:00:00+02	BB 5161.0222	0	0	1461249043893671	\N
2677	Easter TAB	Another month, another special TAB! To celebrate the spirit of easter, the Memory will hide small plastic bags filled with chocolate eggs spread out in the Bernoulliborg. If you can find the most plastic bags before the event ends and tell us where you found them, you will win a 1kg bag of chocolate eggs!\r\nApart from that, we will serve snacks as usual in the CACHE. \r\n\r\nCome join us if you want to win more chocolate than one person can handle or just want to chill in the CACHE!	18	2017-03-23 16:00:00+01	2017-03-23 16:00:00+01	CACHE (Cover room)	0	0	\N	\N
2674	Symposium: Theme announcement borrel	On the 18th of May the SympoCee will organise the annual Cover Symposium, we kept the theme (and location) secret for a while and decided that the 21st of March is a beautiful day to present our theme. We will provide some drinks and snacks!\r\n\r\nHope to see you then,\r\n\r\n-- SympoCee	16	2017-03-21 16:00:00+01	2017-03-21 16:00:00+01	CACHE (cover room)	0	0	273416936427489	\N
2697	Pimp Your Bike	Are you bored of your old bike, and want to decorate your bike? Then this is your chance to do a makeover with your bike! The ActiviTee has organized a 'pimp your bike' event. Come to Ebbingekwartier at the 26th of April and pimp your bike, completely FREE of charge. We will provide the paint and some nice figures.	2	2017-04-26 15:00:00+02	2017-04-26 17:00:00+02	Ebbingekwartier	0	0	1925908067654526	\N
2681	Study Support Neural Networks	Are you straining your neural network over all the neurons, layers, perceptrons and convolutions? Do you need a quick reset of all the weights and connections, and get a fresh view on the course's contents? Join us as we go through an old exam and let us help you train and hopefully resolve any conflicts you might have.	31	2017-03-31 13:00:00+02	2017-03-31 15:00:00+02	BB 5161.0289	0	0	\N	\N
2692	Alumni borrel	We will have a borrel with Axon and Invariant, the alumni associations of Artificial Intelligence and Computing Science. Come and use this opportunity to hear how your study and how Cover changed through the years! \r\n\r\nP.S. There will be free snacks	0	2017-05-12 20:30:00+02	2017-05-12 20:30:00+02	Café Wolthoorn	0	0	\N	\N
2688	Spring Fever Borrel	The Spring is in the air! The flowers are visible and the ActiviTee is feeling happy. So come drink some beers  on the fifth of April and celebrate the come back of the Spring with us.	2	2017-04-05 21:30:00+02	2017-04-05 21:30:00+02	De Tapperij	0	0	\N	\N
2680	Open Board Meeting	This is the second and last chance to join board XXV in a board meeting to see if you are interested in becoming a member of board XXVI! We welcome everyone to one of our weekly board meetings. Experience what keeps us busy and what it means to be on the board. Between 11:00  and 15:00 you are free to join us and hear what we discuss during one of our meetings. Aside from listening you are also free to ask questions.	0	2017-03-29 11:00:00+02	2017-03-29 15:00:00+02	Bernoulliborg 307	0	0	\N	\N
2700	Staff BBQ	It's time for the yearly staff BBQ! The costs for this barbecue will be JUST €4,50! So it's more expensive to stay home! As the title says, there will also be staff members, so this is a nice way to come in contact with them :)\r\n\r\n[url=https://goo.gl/forms/csJAY3AFonEJQMtP2]Sign up here![/url] or in the Cover room. Please sign up before the 20st of May!\r\n	0	2017-05-24 17:30:00+02	2017-05-24 17:30:00+02	Next to the Bernoulliborg	0	0	\N	\N
2675	Study Support Program Correctness	Trouble with Program Correctness. Can't seem to find the invariant? Then come to this support lecture, where we will practice for the exam. We will try to give you a different insight.\r\n\r\nNB: due to personal circumstances with the lecturer, we had to redesign the event last minute. We no longer have an expert on the course, so we'll try to facilitate a working moment-like group activity where you can help each other. If this does not appease to you, we apologize for this short-term cancellation.	31	2017-03-30 15:00:00+02	2017-03-30 17:00:00+02	BB 5161.0293	0	0	\N	\N
2682	Study Support Lecture: Research Methods	For the students who could use some extra help with Research Methods, the StudCee is organizing a study support lecture wherein we'll give a brief overview of the course material, and answer any questions. 	31	2017-04-03 11:00:00+02	2017-04-03 13:00:00+02	Bernoulliborg 293	0	0	\N	\N
2694	Brainstorming with the Board: Fellowships 	At the March GA we heard many ideas that still need to be considered before the fellowships will become a part of Cover. At this brainstorming with the board we are talk about these ideas and get your insight on how you think the fellowships should be created!	0	2017-04-19 13:00:00+02	2017-04-19 15:00:00+02	BB 222	0	0	\N	\N
2701	Preliminaries GNSK Smash 4	Do you think you and a friend are the very best at Super Smash 4 for the WiiU? Do you want to test your skills against other students from all over the Netherlands? Now is your chance! E-Sports is now added to the sports at the Big Dutch Students Tournament(Groot Nederlands Studenten Kampioenschap). For  €43,- you can show your skills 9, 10 and 11 Juni in Eindhoven. For this money you also get:\r\n-A national students sports tournament with a lot of games\r\n-2 times breakfast & lunch (saturday & sunday)\r\n-1 times diner (saturday)\r\n-2 nights at the GNSK-camping (friday 'till sunday)\r\n-An opening act and party friday night\r\n-A big party on saturday night\r\n-An Extensive extra program for the whole weekend\r\n\r\nTo send the best team to the GNSK preliminaries will be organisied the 13th of April. These preliminaries will be held at the Coverroom and start at 16:00. If you want to join the preliminaries, please send an e-mail to board@svcover.nl with your and your friends name. If you don't have a Smash buddy you can still mail and we will pair you with someone of your level.\r\nSee you there!\r\n	0	2017-04-13 16:00:00+02	2017-04-13 16:00:00+02	CACHE (BB 0041a)	0	0	\N	\N
2712	Climbing at Bjoeks	Always wondered if climbing would be fun? You probably had plenty of experience as a kid in trees. But now you can test your skills and learn how to climb properly with this introduction class at Bjoeks. The class will start at 16.00 so be ready by then and it will take about an hour and a half. Bring sporting shoes and a (short) sport pants. Best of all this class will only cost you 10€ instead of the normal 15€! Sign up [Here] before 23.59 on 18 may!\r\n\r\n[url=https://goo.gl/forms/MaoSJh80be8f4BUF3]Sign up here before 23.59 on 18 may![/url]	2	2017-05-22 16:00:00+02	2017-05-22 17:30:00+02	Klimcentrum Bjoeks, Kardinge	0	0	\N	\N
2703	Board Interest Night	A nice evening of relaxing and chatting with a beer or other drink is always nice. You know what the board finds even better? Chilling WITH THE BOARD! During this evening you can ask ALL questions - but more specifically the ones about becoming/being/having been a board member of Cover. Whether you have been thinking about being a board member for some time now or have only briefly considered it doesn't matter, all are welcome. \r\nWe hope to see you there so we can tell you how AWESOME it is to be a board member, and how much you learn, and about all the unbelievable, cool and weird things we have experienced while being on the board.	0	2017-04-17 20:00:00+02	2017-04-17 20:00:00+02	Grote Beerstraat 206	0	0	\N	\N
2704	TAB - Pubquiz: Children Series & Cartoons	On Thursday the Memory will organize a children's series pubquiz. Join us in our nostalgia-filled pubquiz and the winners will get something memorable! At the event you will be divided into teams and compete against each other in the pubquiz. Of course you are free to choose with who you are teaming up with.	18	2017-04-20 16:00:00+02	2017-04-20 16:00:00+02	\N	0	0	\N	\N
2705	Borrel: Glow hard or go home!	Glow hard or go home! This time during the borrel we'll have neon paint and blacklights, so put on an old/cheap white t-shirt and be ready to get dirty! And of course drunk, because we have a 100 beers for 50 cent, first come first serve! *The paint should come out in the washing machine, but better be safe.	2	2017-05-03 21:30:00+02	2017-05-03 21:30:00+02	De Tapperij	0	0	\N	\N
2719	Large LAMA 	Cover is an association that is carried by the voluntary work of its active members. To show our gratitude for all the work that was outputted throughout the last year we are organizing an event just for our active members. \r\n\r\nToday we will have our yearly Large Lovely Active Members Activity. \r\nThere will be food and hopefully a lot of sun!\r\nNot only will this be a fun day, also we will announce the winner of the committee battle!\r\n\r\nNote: This activity is only open to members of committees and working groups.\r\nSign up here: https://goo.gl/forms/P8ZiglvBtqunrAbg2\r\n	0	2017-06-27 16:00:00+02	2017-06-27 16:00:00+02	de Hoornseplas	0	0	\N	\N
2711	[Carreer] Lunchlecture:  How does Centraal Beheer create value with big data and analytics?	At Centraal Beheer we have a large amount of data available. Our passion would be to track all of it, but then we would be busy with that day and night. We try to go beyond just gaining insights through the data, we also try to use and apply our models in practice, show the added value and to learn from what did and did not work. Our analysts try to impact all the different facets of our company through big data and analytics. On the 2nd of May Yory Wollerich, who is team lead Centraal Beheer Marketing Intelligence, and Diede Kemper, who is intern Artificial Intelligence, will tell you more about the cases and problems we are currently focused on.  With free lunch, so see you there! \r\n	26	2017-05-02 13:00:00+02	2017-05-02 15:00:00+02	Bernoulliborg 165	0	0	\N	\N
2714	[Career] Inhouse day @ Quintor	This evening we will visit Quintor, a large IT company in the centre of Groningen.\r\nThere will be a lecture on blockchain technology (the technology used for bitcoins), then we will have a free dinner. Followed by a scrum workshop and we will end with drinks!\r\n\r\nSo sign up here https://goo.gl/forms/VDAtQPyPyEeC0ZMf2 , all come and join us to Quintor this night!\r\n	26	2017-05-23 17:00:00+02	2017-05-23 22:00:00+02	Quintor Groningen - Ubbo Emmiussingel 112	0	0	\N	\N
2715	Mario Kart Tournament TAB	This Thursday a Mario Kart Wii tournament will take place in the Cover room. Join the tournament and destroy all your opponents in order to win! The winner gets an awesome prize so don't miss out! We provide some snacks for everyone to enjoy. \r\n\r\nThere are plenty of GameCube controllers that you can use, but if you would like to play with a Wii remote bring one yourself! \r\n\r\nYou can sign up by filling in the form in the CACHE. The form is located next to KAST (the computer you can buy credits from). \r\n\r\nSee you there!	18	2017-05-11 16:00:00+02	2017-05-11 16:00:00+02	CACHE (Cover room)	0	0	\N	\N
2707	Borrel + IntroCee theme announcement 	Just a normal borrel, so relax and just come have a beer with us!\r\n\r\nThe IntroCee will also be announcing the theme for the introductory camp next year.  So all come check out their presentation and be the first to know!	2	2017-06-07 21:30:00+02	2017-06-07 21:30:00+02	De Tapperij	0	0	\N	\N
2718	[Career] Lunch Lecture: KPN Consultancy Workshop Digital Transformation	The world changes rapidly and keeping up with technological advancements, like disruptive innovators, poses a real challenge for companies and organizations. New technologies allow companies to become more innovative, efficient and more focused on their customers. However, just trying these new technologies will not suffice so for a successful digital transformation digitalization should play a central role within the company’s strategy. KPN consulting has lots of experience guiding companies in this process and they contribute to the development of internet of things, augmented reality and cloud applications. \r\n\r\nDuring the consultancy workshop digital transformation you will help a customer with his digital transformation and the challenges this brings. Knowledge about business alone will not be enough you will also need to know your way around internet of things, digital workspace, cloud and data management. Can you combine IT and strategy optimally? Then you will be the winning digital master! \r\n	26	2017-05-15 13:00:00+02	2017-05-15 15:00:00+02	Bernoulliborg 222	0	0	834249620058588	\N
2706	Beer Cantus mix with Francken	We're doing a joint activity with our good friends from "T.F.V. Professor Francken". We'll be organising a beer cantus, which means a lot of singing and drinking! It's sure to be good fun and it will cost up to 15€. \r\n\r\nMake sure to sign up before the deadline at 17.00 on 10 May [url=https://docs.google.com/forms/d/e/1FAIpQLSeRhdMUntm16LgmQzN0Z-gemiGupboWdEa08AEVfa-O_6OF-w/viewform?usp=sf_link]here[/url]	2	2017-05-10 20:00:00+02	2017-05-10 20:00:00+02	Villa Volonté	0	0	\N	\N
2713	ZigZag	Do you know all the ins and outs of Groningen? Or would you love getting to know them? This is your chance! Join us for ZIGZAG, a quest through Groningen with a group of fellow students. At the most wonderful spots in Groningen there will be assignments for your group, but you first need to solve the riddles in order to find them. Earn jokers by completing the assingments and use them in your advantage in THE BIG FINAL.\r\n\r\n\r\nJoin us at the Grote Markt at 15:30 or come and check out THE BIG FINAL in the Noorderplantsoen at 18:00. \r\n\r\nSince the weather forecast is really nice we are having a barbecue(bring your own food) after the  BIG FINAL in the Noorderplantsoen. Make sure you join us!!!	17	2017-05-16 15:30:00+02	2017-05-16 20:30:00+02	Grote Markt 	0	0	\N	\N
2717	Borrel: Pyjama Party	Come to the last borrel of this academic year! We're tired of learning and studying, so we are ready for lots of sleep, netflix, and more sleep. We also want to party, because we have holidays! To make your life a bit easier, you can come in your pyjama so you don't have to undress when you're going to bed.\r\n\r\nps. There are free beers so you can spend your money on holidays.	2	2017-07-05 21:30:00+02	2017-07-05 21:30:00+02	De Tapperij	0	0	\N	\N
2726	Study Support: General Linguistics	Are you twisting your tongue in phonetics or lost in the woods of syntax trees? StudCee are here to help! Join us at the 20th of June as we go over the course once more and answer any lingering linguistic questions!	31	2017-06-20 11:00:00+02	2017-06-20 13:00:00+02	BB 5161.0222	0	0	252635388546543	\N
2723	Study Support: Advanced Logic	Once again the StudCee organizes a support lecture for advanced logic! Do you have any questions or do you simply not understand a certain topic? Join us on the 12th of June and we will walk you through the entire course content in one quick interactive crash course!	31	2017-06-12 13:00:00+02	2017-06-12 15:00:00+02	BB 5161.0041b	0	0	184213945436805	\N
2732	Beer Olympics	Together, the ActiviTee and SporTee organize the Beer Olympics! Whether you're a drinker or not, this team battle will be a fun afternoon. For max. €2 (depends on the amount of contestants), you can join us at the Hoornseplas to play drinking games and sports. And maybe pizza afterwards! Signing up is required:\r\n\r\nhttps://docs.google.com/forms/d/e/1FAIpQLSe4IoSh1opZ-0J9EMXpJCyZmlAhhSceTLoE8r7m_6wWRSn2ag/viewform	20	2017-06-15 16:00:00+02	2017-06-15 20:00:00+02	Hoornseplas	0	0	\N	\N
2729	Get To Know BEST	Come along to our free event, where you will be able to discover what BEST really is, participate in some traditional BEST games based on the 5 values of BEST and find out the opportunities BEST can give you at a local level and at the international level. \r\n\r\nWe will be located at the entrance of Nijenborg 4 and the timings are very flexible you can particpate for the entire time, or just for an hour (whatever works for you). No need to sign up, just turn up on the day!	0	2017-05-29 13:00:00+02	2017-05-29 17:00:00+02	Nijenborgh 4	0	0	129835807564873	\N
2727	Candidate Board Announcement Borrel	It's time for us to announce our successors! On the 30th of May, the new candidate board for 2017-2018 will be announced. So if you're curious to see who is going to lead Cover for a year, you should come! To celebrate this, we also have plenty of free beer. \r\n\r\nThe borrel starts at 21:30 and we hope to announce the candidate board around 11 pm. So be on time, and let's make it an unforgettable evening!	0	2017-05-30 21:30:00+02	2017-05-30 21:30:00+02	't Fust	0	0	415389138835441	\N
2731	Schoolyard TAB	Play the games from your childhood and get some sun! Standard snacks are available.	18	2017-06-08 16:00:00+02	2017-06-08 16:00:00+02	Behind the BB	0	0	\N	\N
2743	[CAREER] Get To Know TNO	Visit our main sponsor on this day and see what the working life at TNO is like.\r\nFor more information see:\r\nhttps://www.tno.nl/nl/over-tno/agenda/2017/get-to-know-tno/	26	2017-10-13 10:00:00+02	2017-10-13 10:00:00+02	Stieltjesweg, Delft	0	1	\N	\N
2728	General Assembly	This is going to be the last GA of this academic year where your voice can be heard!\r\nWe will be voting to approve the candidate board! \r\nWe are also going to vote on a rules and regulations change for fellowships.\r\nThe documents will be made available at least one week in advance.	0	2017-06-06 19:00:00+02	2017-06-06 19:00:00+02	Café the Crown	0	0	\N	\N
2734	[CANCELLED] Hackathon @ BelSimpel	[CANCELLED]\r\nTHIS EVENT IS CANCELLED!!! YOU CAN JOIN US IN OCTOBER FOR THE NEXT HACKATON!!!!\r\n\r\nHave you ever wanted to play with real world data? Now you can!\r\nJoin us at BelSimpel in the city center for Cover's very first\r\nHackathon.\r\n\r\nDuring this Hackathon, which lasts a whole weekend, small teams \r\nof people can work on data provided by BelSimpel. And the best \r\npart is that there are no restrictions on what do with it! If \r\nyou can come up with some creative way to handle the data, you \r\ncan! There will also be free beer and food so join us for \r\na weekend of chill programming.\r\n\r\nSign up now at https://lancee.svcover.nl/\r\n	21	2017-06-23 17:30:00+02	2017-06-25 16:00:00+02	BelSimpel Groningen - Waagstraat 1	0	0	\N	\N
2737	Final Activity: Boating through the canals of Groningen	On July 5, it is the final activity of the ActiviTee! This time, we stay in Groningen, but on the water. We are going to cruise in longboats, through the waters of Groningen, with lots of free snacks and beer (and other drinks). The best part is that this is all FREE. We will start boating at 15:30, so please be on time.\r\nThen in the evening we will have our borrel/social in the Tapperij so we can close the day. :)\r\n\r\nWe hope to see you all!\r\n\r\nDon't forget to sign up: https://goo.gl/forms/fCoKYZDaby7hvN5E2	2	2017-07-05 15:15:00+02	2017-07-05 17:30:00+02	Reitdiephaven, Groningen	0	0	117385425535065	\N
2742	Introduction day	The summer is over and it's time to start your first day of university! During the introduction day the university will have a morning program and at 2 pm we will take over. We will have 3 different parts on our side. The computer instruction, this is where your mentor will explain how all the university websites and the ways of communication. The tour, where your mentor will show you all the important locations you will need to know during your first weeks, also visiting the Cache (Cover room) and the photo location to take a head shot of all the first years. The third part is outside where there will be an obstacle course, a cotton candy machine and a popcorn machine.	8	2017-09-01 14:00:00+02	2017-09-01 14:00:00+02	Bernoulliborg	0	0	\N	\N
2724	Study Support: Languages & Machines	Are you struggling with Languages and Machines? Come and walk through the essentials of the course once more. You can ask any questions you may have left. All to help you tackle your exam.	31	2017-06-12 13:00:00+02	2017-06-12 15:00:00+02	BB 5161.0222	0	0	1898531070436185	\N
2740	Introduction Camp "Heroes vs. Villains" 	The introduction camp is organised for all the first year students of Artificial Intelligence and Computing Science. During the camp you will get to know your fellow first years, as well as a group of active older years and the study association in general. There will be games to get to know each other and drinks and music to have fun in the evening. The theme this year is "Heroes vs. Villains" and the real life role playing game will be using this theme as well. The camp will be for 2 nights and 3 days, with a full program, food and a bed provided, all for only €55.\r\n\r\nFor further info and to sign up go to the site here: (http://www.introcee.nl).\r\nPlease sign up before the 1st of September during the introday, so that if there not enough spots you will be in the drawing. If there are still free spots available you can sign up until 7 September!\r\n\r\nHope to see you all there!\r\n	8	2017-09-08 15:00:00+02	2017-09-10 16:30:00+02	Pagedal, Hoveniersweg 1, 9502 BW, Stadskanaal.	0	0	\N	\N
2725	Study Support: Linear Algebra	Do you still have some questions about Linear Algebra? Are some topics still unclear? Join this last unofficial lecture given by a students who will help you make some typical exam assignments and answer any remaining questions you may have!	31	2017-06-14 13:00:00+02	2017-06-14 15:00:00+02	BB 5161.0289	0	0	127670687786626	\N
2754	Welcome Back ThuNDr	This Thursday the first ThuNDr (Thursday Noon Drinks) activity will take place. This will be an excellent opportunity to meet your fellow (new) students. We will provide some snacks and make sure that the drinks are cold. Several board games will be available for you to play!	18	2017-09-07 16:00:00+02	2017-09-07 18:00:00+02	Bernoulliborg	0	0	\N	\N
2758	Pool Night	Everyone survived their first few weeks of lectures, so it's about time that their is a chill night. The ActiviTee has organised a pool night! You don't have to be good at pooling, you can be a beginner or a pro, everyone is welcome!The first one and a half hour of pooling is free, after that it's 1 euro per game.\r\n\r\nPlease sign up here so we can reserve the right amount of pool tables: https://goo.gl/forms/2ifeEKQjnRBdk6ma2	2	2017-09-27 20:00:00+02	2017-09-27 23:00:00+02	Streetlife	0	0	\N	\N
2755	PubQuiz	Do you think you're the smartest first year student around or do you, as an older student, feel the need to prove you are better than the first years? Come and test your knowledge during the PubQuiz. Get ready to use all of your knowledge on Groningen, sports, politics and many more! The PubQuiz will be hosted by the FirstYearCee from 20:30 onwards in the News Cafe, so please be on time. The PubQuiz will be held in teams which gives you the perfect opportunity to get to know your soon to be friends. The biggest braniacs will get a grand prize! So hesitate no more and join us!	17	2017-09-13 20:30:00+02	2017-09-13 23:00:00+02	Newscafe Groningen - Waagplein 5, 9712 JZ Groningen	0	0	\N	\N
2762	Introduction Camp Afterparty!	After the awesome introduction camp we naturally need an afterparty to look back on this memorable weekend. The proceeds from the auction has reached record heights, so you can count on a lot of free beer, snacks and probably more awesome things! It will be held at 't Golden Fust, so it's nice and central. So if you spend money on the auction make sure to come get your money's worth and if not, come enjoy some free beer anyway!	8	2017-09-25 22:00:00+02	2017-09-25 22:00:00+02	't Golden Fust - Poelestraat 15, Groningen	0	0	\N	\N
2746	Welcome (back) Social	At the sixth of September we are together going to celebrate the start of a new academic year! Of course there will be free beers! \r\n\r\nIf you are new to Cover this night is amazing to get to know all members of the association! We are just going to get some beers together and have a lovely evening. For all already members of Cover this is a great opportunity to see all your friends again. \r\n\r\nWE NOW KNOW THE LOCATION OF THE BORREL! It will be, as we're used to, in the Tapperij! 	2	2017-09-06 21:30:00+02	2017-09-06 21:30:00+02	De Tapperij, Grote Markt 36	0	0	\N	\N
2756	Linux Workshop for CS	To make your life a little easier, we are organising a workshop for Linux. We will cover navigating the maze of Linux, getting the terminal to listen to you, and getting that C-code compiled right. \r\nSo, whether you have problems taming that Linux monster or you just want to stop by to pick up some cool tips & tricks, this is the workshop for you! Be sure to come, and bring your friends!\r\n\r\nThere will be two workshops in total, so if you cannot make this one, check out the other one!	31	2017-09-11 09:00:00+02	2017-09-11 11:00:00+02	V 5161.0283	0	0	\N	\N
2761	Programming for Dummies for CS	Programming for dummies is here to help you think programmatically! In this lecture-workshop hybrid, we will  discuss how programs are like Lego houses, how daily life is full of complex algorithms, and how to cut your tomatoes recursively. All this and more to help those students who have little or no experience with programming to get into the right mindframe.\r\n\r\nPS. Note that we will only look at pseudocode - that is, no programming language specifics will be discussed.	31	2017-09-15 15:00:00+02	2017-09-15 17:00:00+02	BB 5161.0293	0	0	\N	\N
2751	Di(e)scover	It's Cover's birthday again and this year's dies theme will be "Di(e)scover"! \r\n\r\nAt 15:30 we'll start out in the Coverroom with a blind craft beer tasting competition and a scavenger hunt. Make sure to take your friends with you because this will be a team effort. \r\n\r\nAfter that, at 19:30, we'll go out for dinner at Wereldburgers. You can get a burger of choice and fries for €10. Do you want to join? Sign up here: https://goo.gl/forms/iVHLC3HdVLC6EwLP2\r\n \r\nStarting from 21:30 there is a social in 't Gat van Groningen, where every member can join up again, even if you skipped the dinner part. Beer will be only € 1,- and 2 jugs of mixtures will cost only € 10,-.\r\n\r\nWe're very excited to celebrate our birthday and we hope to see you all there!	0	2017-09-20 15:30:00+02	2017-09-20 23:59:00+02	\N	0	0	\N	\N
2763	TAD-Talk: Atos	The first TAD-Talk of the year! \r\n\r\nAtos is a large international company leading in digital transformation. They are the European leaders in Big Data, Cybersecurity and many other fields. During this TAD-talk, they will be elaborating on how they use text analysis and machine learning in their applications. Furthermore, they want your input on a case they are working on right now!\r\n\r\nThey will be bringing food and drinks with them, so if you're still recovering from the Dies the day before, we've got you covered!	26	2017-09-21 16:00:00+02	2017-09-21 18:00:00+02	CACHE (Cover room)	0	0	\N	\N
2765	Prominent in de Tent	It is time for Prominent in de Tent again! The party takes places on the 10th of October at Huize Maas. The theme of the party is "Netflix & Pils". Take the chance to become one of the characters from your favourite movie or TV-serie for one night! A pre-sale ticket for this unforgettable party costs €17,50, approach the board if you're interested. For this price you can drink unlimited beer, wine and soda. This year the doors will be open at 22:00 and they close at 00:00. So keep an eye on the time! The party itself continues till 03:00. You can also buy a ticket on the 10th of October at the door for €20,00. For more information or dress-up ideas, check the Facebook page of Prominent in de Tent. We will see you on the 10th of October.	32	2017-10-10 22:00:00+02	2017-10-11 03:00:00+02	Huize Maas	0	1	468626063523337	\N
2757	Linux Workshop for AI	To make your life a little easier, we are organising a workshop for Linux. We will cover navigating the maze of Linux, getting the terminal to listen to you, and getting that C-code compiled right. \r\nSo, whether you have problems taming that Linux monster or you just want to stop by to pick up some cool tips & tricks, this is the workshop for you! Be sure to come, and bring your friends!\r\n\r\nThere will be two workshops in total, so if you cannot make this one, check out the other one!	31	2017-09-11 13:00:00+02	2017-09-11 15:00:00+02	X 5116.0310	0	0	\N	\N
2749	General Assembly	We would like to invite you to the General Assembly on the 11th of September. The meeting will start at 19:00. All relevant documents will be emailed to you and you can also find them on https://sd.svcover.nl/.\r\n	0	2017-09-11 19:00:00+02	2017-09-11 19:00:00+02	De Walrus	0	0	\N	\N
2760	Programming for Dummies for AI	Programming for dummies is here to help you think programmatically! In this lecture-workshop hybrid, we will  discuss how programs are like Lego houses, how daily life is full of complex algorithms, and how to cut your tomatoes recursively. All this and more to help those students who have little or no experience with programming to get into the right mindframe.\r\n\r\nPS. Note that we will only look at pseudocode - that is, no programming language specifics will be discussed.	31	2017-09-15 13:00:00+02	2017-09-15 15:00:00+02	BB 5161.0222	0	0	\N	\N
2750	Constitutional General Assembly	We would like to invite you to the Constitutional General Assembly on the 18th of September. The meeting will start at 19:00. All relevant documents will be emailed to you and you can also find them on https://sd.svcover.nl/. This is also the general assembly where we will change boards, so if you have any questions for the current board you can ask them here!	0	2017-09-18 19:00:00+02	2017-09-18 19:00:00+02	Cafe de Keyzer, Turftorenstraat 4, 9712 BP Groningen	0	0	\N	\N
2769	Drinking Games	Welcome to the annual drinking games of Cover! Sign up now to discover the magical world of beer pong, the intricacies of mexxen, your own alcohol tolerance and other things!\r\nFor only 10 euro's you can be a part of this all!\r\nFun will be had, friends will be made, alcohol will be spilled,  but most importantly: Beer will be drunk!\r\n\r\nSign up now!\r\nhttps://goo.gl/forms/V78Jj4jTfd4bMZJ22\r\n\r\nUpdate: \r\nDue to a limited capacity we have closed the sign up. If you really want to participate, you can send an email to activitee@svcover.nl	2	2017-10-24 20:30:00+02	2017-10-24 23:59:00+02	Villa Volonté	1	0	\N	\N
2802	Movie ThuNDr	November's ThuNDr is a movie ThuNDr! We will watch the 1994 classic comedy The Mask, while enjoying drinks and snacks. Join us on the 23rd of November at 4 o'clock in the Cover room!	18	2017-11-23 16:00:00+01	2017-11-23 16:00:00+01	\N	0	0	\N	\N
2873	Blacklight Neon Party	The exams are getting close, and what's better during these stressful weeks than to relax and have a beer with your friends? Be sure to wear a white t-shirt that doesn't need to stay clean, we will provide neon paint!	2	2018-04-04 21:30:00+02	2018-04-04 21:30:00+02	't Gat van Groningen, Poelestraat 51	0	0	2053312914940640	\N
2778	Dodgeball	Come join us for a fun game of dodgeball! Because what's better than throwing things at your friends? It's free, and anyone can join!	20	2017-10-05 19:00:00+02	2017-10-05 19:00:00+02	Reitdiep College, Kamerlingh Onnes (Eikenlaan 286)	0	0	\N	\N
2776	Disrupt the Social	On the 4th of October the SNiC committee will disrupt the social. The entire social will be in the DisruptIT theme. We will take a lot of goodies with us, including beer openers, dice and stickers. I hear you thinking, will there be free beer? Well, come to the social and find out!\r\n\r\nDisruptIT is this year's edition of the annual SNiC conference. This year Cover will organise it and it will be held on the 15th of November. If you want more information, check our website: www.disrupt-it.nl. 	43	2017-10-04 21:30:00+02	2017-10-04 21:30:00+02	De Tapperij, Grote Markt 36	1	0	\N	\N
2792	LaTex Workshop (AI)	Are teachers pressuring you to start working with this weird new thing called LaTeX? Are all your friends using it already but are you struggling to get into it? In this workshop we'll go over the basics so you can start happily writing your reports and papers the way you never knew you wanted to!	31	2017-11-24 13:00:00+01	2017-11-24 15:00:00+01	BB216 & BB283	0	0	\N	\N
2772	Hackathon @ Belsimpel	The weekend of 13 to 15 October, starting from Friday, there will be a hackathon hosted by BelSimpel! \r\n\r\nCome join us for a weekend full of coding, fun, and a bit of healthy competition. You will be working with interesting real datasets and get help from professional data scientists and software developpers. \r\n\r\nThe winners will receive a prize of €1500! Second and third place will get €750 and €250!\r\n\r\nThe hackathon will take place at the following times: \r\nFriday the 13rd from 17:30 to 23:30; \r\nSaturday the 14th from 08:30 to 23:30; \r\nSunday the 15th from 08:30 to 15:00, with some more time for a drink afterwards. \r\n\r\nSnacks and drinks are free and provided by BelSimpel. During the event, there will also be plenty opportunities to talk to the developers from BelSimpel, as well as play games and generally chill out.\r\n\r\nWant to join? Sign up at http://lancee.svcover.nl/	21	2017-10-13 17:30:00+02	2017-10-15 15:00:00+02	BelSimpel Groningen - Waagstraat 1	0	0	\N	\N
2767	SNiC: DisruptIT	DisruptIT is the 11th conference organised by the Stichting Nationaal Informatica Congres, this year study association Cover provides the committee that will organise it and it will be held on the 15th of November.\r\n\r\nThis year’s theme is Disruptive Technology. Disruptive Technology is technology that displaces an established technology. This will shake up the current industry or is so ground breaking that it creates its own industry. This topic is focussing on current and future technologies that will change the work field of current students. It is hard for students to predict the future of a rapidly changing market. We want to give the students a mind-changing insight into the future.\r\n\r\nFor more information, check out www.disrupt-it.nl	43	2017-11-15 11:00:00+01	2017-11-15 20:00:00+01	\N	0	0	608510912872286	\N
2779	Staff Lunch	We are going to have a nice lunch in the Cover room with the staff. So come and join us to enjoy delicious sandwiches. Feel free to walk in between 12.00 and 14.00.	0	2017-10-17 12:00:00+02	2017-10-17 14:00:00+02	Cover room	0	0	\N	\N
2775	Brainstorm with the Board	Do you want to share and brainstorm about your great ideas for Cover? Come to Brainstorm with the Board to discuss them! We would also like to brainstorm about some topics that we mentioned during the Constitutional General Assembly, which were a magazine for the association and what we would like to have as guest speaker rewards.   	0	2017-10-04 13:00:00+02	2017-10-04 15:00:00+02	Linneasborg 0165	0	0	\N	\N
2783	Study Support: Neurophysics	Are you still having problems with Neurophysics? Do you want help in preparing for you exam? During this study support session, we will be going through example questions from the Mechanics and Action Potentials sections of the reader.	31	2017-10-24 13:00:00+02	2017-10-24 15:00:00+02	BB 5161.0267	0	0	1310048712451795	\N
2780	Visit TU Delft	Today we will go to the university of Delft to take a look at what is happening at the research departments of the university that won Elon Musk's hyperloop competition.\r\n\r\nWe will go by train, so we gather at the "Peerd van Ome Loeks" at the train station of Groningen at 09:00. They program in Delft will end around 19:00 and from there you can go back to Groningen or "home home". \r\n\r\nMore information about the program will follow. You can sign up here: http://delft.svcover.nl	11	2017-10-27 10:00:00+02	2017-10-27 19:00:00+02	Delft	0	0	\N	\N
2784	Study Support: Statistics	Still stumbling over those statistics? Can't see the anomaly for the data? Join us for our study support session for Statistics where we will made sure your grades fit in the upper quartile and you can reject that null (grade) hypothesis!\r\nWe will be covering all the statistical tests, how to write up your answer in the exam, as well as having a Q&A session where you can bring in your own statistical questions.	31	2017-10-26 13:00:00+02	2017-10-26 15:00:00+02	Energy Academy 5159.0062	0	0	1929039387414254	\N
2788	Study Support: Introduction to Logic	Having too many antics with your Semantics? Need to formalise those Informal Proofs? Join us for the study support session for Introduction to Logic!\r\nDuring this study support session, we will aim to discuss every topic in the course, with special attention going out to topics such as Semantics and Formal Proofs.	31	2017-11-01 15:00:00+01	2017-11-01 17:00:00+01	BB 5161.0041b	0	0	1909590552697838	\N
2786	Bocktober ThuNDr	The leaves are falling from the trees and that mean's the fall has started. Which also means it's time for the autumn bock beers, so enjoy some (bock) craft beers and snacks during Thursday 'Noon Drinks! There will be videogames, games and a general good vibe!	18	2017-10-26 16:00:00+02	2017-10-26 16:00:00+02	\N	0	0	\N	\N
2789	Study Support: Imperative Programming	Do you have any problems with the theory of Imperative Programming? Do you want help in preparing for you exam? During this study support session, we will discuss parts of an old exam and help you understand subjects that are relevant for your exam (such as recognizing the time complexity of a program). \r\n\r\nDuring this lecture, we will also shortly deal with some topics that might need a short reminder, such as the workings of recursion. There will also we the opportunity to ask questions.	31	2017-11-08 11:00:00+01	2017-11-08 13:00:00+01	BB 5161.0267	0	0	1403878313062260	\N
2782	New Location Social!	It was time for us to find a new location for our socials. We looked at a few different bars and decided upon 't Gat van Groningen in the end. We hope to have a lot of amazing nights here, so be sure to check out the new location! To make the first social at this new location even better, there will be 100 free beers!	2	2017-11-01 21:30:00+01	2017-11-01 21:30:00+01	't Gat van Groningen, Poelestraat 51	1	0	\N	\N
2791	WAMPEX	Do you want to have a night of outdoorsy fun, join the WAMPEX! The WAMPEX is a puzzle route of about 25 km, through the beautiful nature of Frysia. This route is a great opportunity to challenge your puzzle solving skills and endurance together with your team. The activity starts in the evening and takes the whole night.\r\nMore information and the sign-up page can be found at https://sportee.svcover.nl. The sign ups are first come first serve, so sign up while you still can.	20	2017-11-17 19:00:00+01	2017-11-17 19:00:00+01	\N	0	0	\N	\N
2793	LaTeX Workshop (CS)	Are teachers pressuring you to start working with this weird new thing called LaTeX? Are all your friends using it already but are you struggling to get into it? In this workshop we'll go over the basics so you can start happily writing your reports and papers the way you never knew you wanted to!	31	2017-11-21 15:00:00+01	2017-11-21 17:00:00+01	BB208 & BB283	0	0	\N	\N
2795	[CAREER] Get There: Energy Data Hub 	It's time for another career event!\r\n\r\nMonday the 20th of November, Get There is coming to give a talk on the Energy Data Hub. They will be discussing the (AI)techniques they are using and the challenges they are facing, focusing on security, scalability and performance. Afterwards, there will be an interactive session, where they will be looking for input on these challenges from you!\r\n\r\nThe session will be ended with a borrel where we can discuss all your ideas.	26	2017-11-20 15:00:00+01	2017-11-20 18:00:00+01	BB 5161.0293	0	0	\N	\N
2790	Ugly Christmas Sweater Social	It's holiday season! It's getting colder and colder, and t-shirts and tops are changed for sweaters. This social you can wear your prettiest ugly Christmas sweater. The whole social is Christmas themed, be on time and get your free beers and a free Christmas hat! 	2	2017-12-06 21:30:00+01	2017-12-06 23:59:00+01	't Gat van Groningen, Poelestraat 51	0	0	1947126732275045	\N
2803	Career Day 2017	Just like previous years, this year a career day will be organized for the students of the Faculty of Science and Engineering. This year's Career Day will be organized by the Faculty of Science and Engineering in collaboration with the Beta Business Days. The event is aimed at the faculty's Master's students and is a preparation for your future career. \r\n\r\nDuring this day you have the opportunity to discover what job suits your interests and skills best. Do you want to work at a company, the government, the media or the university as a research scientist, an entrepreneur, inventor, advisor, policy maker or science communicator? Or are you not sure what opportunities lie in store for you, outside academia? Various interesting presentations will be given and alumni will tell you about their careers to help you figure out your options and the paths open to you!\r\n\r\nOn top of that we will help you to improve your application, self presentation and communication skills so you will be fully prepared when the right opportunity comes along. There will be workshops to help you to find out who you are, to improve your soft skills and to present yourself. Please look around on the webpage for the workshops and presentations that are interesting to you.\r\n\r\nFurthermore, you will have the opportunity to have your CV checked and to have a professional LinkedIn photo taken. We will end the day with networking drinks and the opportunity to talk to the alumni and representative of the companies and organzaitions taking part in our event.\r\n\r\n	0	2017-11-30 09:30:00+01	2017-11-30 17:00:00+01	Zernike Campus	0	1	\N	\N
2798	[CAREER] Belsimpel: First Year Coding Challenge	Come to our first time ever career event for first year students!\r\n\r\nOn Tuesday the 28th of November Belsimpel will be hosting a coding challenge for first year students. The coding exercises will be tailored to the programming level of first year students. There will be free food beforehand and free drinks afterwards. The winners of the coding challenge will earn a cool prize!\r\n\r\nThe challenge will be done in teams of two and both winners will get a Samsung M5 Medium Wireless Audio Smart Speaker!\r\n\r\nYou should bring your own laptop!!\r\n\r\nSign up here: \r\nhttps://goo.gl/forms/4Ia5gHJkEKXpC9bi1\r\n\r\nSign ups will close on November 26th.	26	2017-11-28 17:00:00+01	2017-11-28 22:00:00+01	Belsimpel Groningen - Waagstraat 1	0	0	900876566729993	\N
2808	Committee Assembly	The committee assembly will be organised once more. All chairmen and treasurers are urged to come. In case neither can make it to the assembly, make sure to have at least another member of the committee is present to represent the committee.\r\n\r\nWe will be discussing the budget, so if you wish to modify your yearly budget, this is the time and place to do so. Furthermore we will be discussing the  rescheduling and/or cancellation of activities, and the new additions committee manual. \r\n\r\nThere will be a couple of drinks to conclude the assembly.	0	2017-12-18 15:00:00+01	2017-12-18 18:00:00+01	Nijenborgh 4, room 5114.0004	1	0	\N	\N
2794	GALA	Honourable members,\r\n\r\nDecember may feel far away, but rest assure, the Activitee is already busy to organize this year’s final activity:\r\n\r\n21 st of December  SAVE THE DATE!\r\n\r\nIts going to be an unforgettable, sensational and phenomenal GALA.\r\n\r\nThere will be unlimited amount of beer and wine for only 18,- (intros for 20,-)\r\n\r\nSign up NOW at https://gala.svcover.nl/\r\n\r\nCheers,\r\nActivitee	2	2017-12-21 23:00:00+01	2017-12-22 03:00:00+01	Newscafe Groningen - Waagplein 5, 9712 JZ Groningen	0	0	1524164797652815	\N
2804	Map Your AI Study Programme	Are you a 2nd or 3rd year AI Bachelor student? \r\n\r\nLet the academic advisor and fellow students inform you about the options you have to plan your Bachelor program. \r\n\r\nWe will discuss your compulsory program, practical and elective courses, going abroad, minor options, Masters, etc. Senior students will share their experiences with the practical and elective courses, to help you choose and plan your own programme.	0	2017-12-19 13:00:00+01	2017-12-19 15:00:00+01	NB 5111:0022	0	0	173461793394041	\N
2846	EscalatieMix TAD	Now for something different!\r\n\r\nWe have had several requests over the year(s) about an EscalatieMix TAD, so here it finally is!\r\nWith free beers (limited amount), we are sure that there is enough available to give everyone a nice start!\r\nCome join us in the Cover room and drink with us every time the horn sounds!\r\n\r\nSee you there!\r\n	18	2018-03-15 16:00:00+01	2018-03-15 18:00:00+01	CACHE (Cover room)	0	0	\N	\N
2852	Open Board Meeting 2	Are you just curious about what the board does during their weekly meetings? Or are you interested in doing a board year at Cover? Come join us during one of the open board meetings to see how we run the association day to day! How we handle problems, which parties are involved, how we interact with the university and much more! \r\n\r\nJoin us to see all this for yourself! \r\n\r\nYou are free to join late or leave early.	0	2018-03-22 11:00:00+01	2018-03-22 15:00:00+01	LB 5172.0804	0	0	\N	\N
2806	TAD-talk: How NLP can help Festimap	Festimap is a brand new startup from Groningen founded by two Software Engineering-students from the Hanzehogeschool. They created a webapplication that allows you to find parties, perfomances and festivals easily. Nearby events can be seen on the map and filters give the posibility to search for specific genres and price ranges.\r\n\r\nThe subject of this TAD-Talk is how NLP could be used to improve Festimap's application.\r\n\r\nTry Festimap here:\r\nhttps://festimap.nl/\r\n\r\nSo come join this TAD-Talk and get to know Festimap, while enjoying some drinks!	26	2017-12-14 16:00:00+01	2017-12-14 18:00:00+01	CACHE (Coverroom)	0	0	911504242359591	\N
2812	New Years' TAD with Staff	Had a nice Christmas holiday? Did you go on vacation? Is that the reason you're excited to start a whole new year? Maybe it's not... Come celebrate the start of 2018 with Cover anyway! Staff is also invited so it should be loads of fun!\r\nThere will be free drinks and snacks available.	0	2018-01-11 16:00:00+01	2018-01-11 18:00:00+01	CACHE (Cover room)	0	0	1777636459199928	\N
2831	[Career Month] Neural Networks for a Self-Driving Boat - Lunch Lecture by Xomnia	David Woudenberg is Xomnia's Data Scientist and project lead of their self-driving boat. During this talk he will explain the boat's transformation from a former lifeboat to a self-driving boat using neural network/deep learning technology. To use Artificial Intelligence technologies in our daily life makes this project challenging and exciting.\r\n\r\nCover will provide a free lunch!\r\n\r\nAbout Xomnia:\r\nXomnia is the leading Dutch big data company that empowers organizations to create maximum value out of data. We excel in generating value from data by developing, integrating and maintaining your big data platforms and predictive solutions. Xomnia’s big data training programs enable and further develop the data-driven capabilities of your own employees. We deliver long term data science and big data engineering capacity through our traineeships. When in need of a temporary capacity boost, we share our experienced big data experts with you. In Xomnia you will find the full-service big data partner to realize your data-driven ambitions. Last but not least our company is located in the city center of Amsterdam. Every Friday afternoon we have drinks at our HQ. Oh, and we have the first self-driving boat that works entirely on artificial intelligence!	26	2018-02-20 11:00:00+01	2018-02-20 13:00:00+01	BB 5161.0273	0	0	1161327550665201	\N
2815	General Assembly	We would like to invite you to the General Assembly on the 15th of January. The meeting will start at 17:00. All relevant documents will be emailed to you and you can also find them on [url=https://sd.svcover.nl/Archive/General%20Assemblies/2018-01-15/]documents & templates[/url].\r\n\r\nMake sure to sign up for pizza which will only cost 2,50€ a piece, so you have something to eat during the GA! Signup sheet is in the Cover room.	0	2018-01-15 17:00:00+01	2018-01-15 17:00:00+01	BB 5161.0289	0	0	342813446200210	\N
2810	[CAREER] Cyber Security workshop at Quintor	Do you want to know how to protect your files? Or want to know how hackers get into secure databases? Join us then, for the Cyber Security workshop by Quintor. We will meet at their office, and have dinner together, after which we will start with the workshop. \r\n\r\nTo sign up, please fill in this link: https://goo.gl/forms/uT992PpVG8zi9iY22 	26	2017-12-19 17:00:00+01	2017-12-19 21:00:00+01	Quintor, Ubbo Emmiussingel 112	0	0	305206999985537	\N
2814	Board game night	We know that the exams are a stressful time. This is why the Activitee wants to help you relax with this chill board game night! Games will be provided, so you can sit back, have a beer and play games with your friends!	2	2018-01-22 20:30:00+01	2018-01-22 20:30:00+01	Het Concerthuis	1	0	584559418602809	\N
2838	Alphabet Party	We survived the first month of the year already, the exams are over (luckily) and we are starting with a brand new semester, which means, brand new activities! One of these activities is the annual Alphabet Party.\r\n \r\nWhat about it? Yearly we organise this party in collaboration with other study associations, this year we are with 18 in total. Every year a letter from the alphabet is picked and we think of a theme starting with this letter. You can base your outfit on the chosen letter.\r\n\r\nThis year the ‘M’ was selected, and the theme we made up is ‘Midnight Madness’. We hope you will all create the maddest outfits and come party with us on Thursday the 22nd of February. It promises to be an awesome night!\r\n\r\nTickets cost €5,- in presale and €6,50 at the doors and includes two drinks and free use of the cloakroom. Furthermore, you get  10% discount on your costume by showing your ticket at Firma Mulder. Any questions? Mail to alfabetfeest@gmail.com or send a message via the Facebook page. We hope to see you all there!	32	2018-02-22 22:00:00+01	2018-02-22 22:00:00+01	Club Kokomo, Gelkingestraat 1, 9711 NA Groningen	0	0	1780265705328519	\N
2813	New Years' Social	Now that the holidays are over and the exams are getting closer, how nice is it to have a relaxing night out with your friends while enjoying some beers? On this first social of the new year, there will be 100 beers for only 50 cents each, so be there on time!	2	2018-01-10 21:30:00+01	2018-01-10 21:30:00+01	't Gat van Groningen, Poelestraat 51	0	0	313256069161433	\N
2842	Meet the Researchers	Are you curious about what the researchers of the University of Groningen are up to? Want to gain more insight in the projects that they are involved in? Then come to Meet the Researchers! We will have various researchers come and give short presentations about their own research, and what the implications might be for CS, AI, or students in general. There also will be food provided during the event, and a drink after! 	11	2018-02-22 13:00:00+01	2018-02-22 16:30:00+01	5118.-152	0	0	2025633001051494	\N
2827	[Career Month] Hacking in Practice - Lunch Lecture by TNO	The world of Cyber Security today is full of hacking and, more importantly, it’s implications. In order to be a significant player in this field, TNO performs research in the fields of digital attacks, risk management and prevention. Fully understanding how a hacker works is a part of this research.\r\n\r\nIn this talk, Jan Kazemier, scientist at TNO Cyber Security & Robustness (and alumnus of Cover), will give a brief introduction into the world of hacking in practice.\r\n\r\nCover will provide a free lunch for everyone!	26	2018-02-06 11:00:00+01	2018-02-06 13:00:00+01	BB 5161.0289	0	0	1366492286788720	\N
2867	Study Support: Algorithms & Data Structures in C	Struggling to understand how to structure your data? Come to the support session for ADinC where we will take you through as many exam questions as possible.	31	2018-04-10 14:30:00+02	2018-04-10 16:30:00+02	BB 5161.0267	0	0	209244722959399	\N
2832	[Career Month] Lunch Lecture by Spindle	Organizing without management\r\n\r\nAt Spindle the traditional hierarchical model of organization is replaced by a system called Holacracy. In the system, it is assumed that people work better if they're treated as adults and given the opportunity to organize themselves. The classical function building is replaced by an evolving system of different roles. People who work in roles generally have more responsibility for their tasks, which makes them more ambitious to reach the best result. Joris Engbers will tell about his experience with this brand-new way of working.  Read more about them [url=https://wearespindle.com/]here[/url].\r\n\r\nCover will provide free lunch!	26	2018-02-13 11:00:00+01	2018-02-13 13:00:00+01	BB 5161.0253	0	0	2450203858537318	\N
2840	Git Workshop	Have you always wanted to have a nice overview of all your coding projects? Not having six different test.c files in your directory, just because you wanted to try something out? \r\n\r\nThen attend this workshop, which will teach you about the ease and comfort of Git. Git is a distributed revision control and source code management (SCM) system. It keeps track of everything you have coded and allows you to retrieve older versions of your code. And it makes working together a breeze! \r\n\r\nDuring this workshop you will learn the basics of Git, grap the contents, and how to use git for your next (or current!) project.	31	2018-02-16 15:00:00+01	2018-02-16 17:00:00+01	BB 5161.0273	0	0	590575364614582	\N
2845	[Career Month] Soft Skills Workshop by Unipartners	Apart from the interesting IT-projects you may encounter during the lunch lectures in this Career Month, soft skills are also a very important part of most jobs, whether you are working in business or in research. During this TAD-talk, you will get the chance to work on and learn about some of these skills in a relaxed setting.\r\n\r\nUnipartners is a consultancy firm lead and run by students, working on hundreds of projects in all of the Netherlands. So this is your chance to ask anything about excelling in your future career or research!	26	2018-02-15 16:00:00+01	2018-02-15 18:00:00+01	CACHE (Coverroom)	0	0	164078320909773	\N
2865	Study Support: Advanced Logic	Once again the StudCee organizes a support lecture for advanced logic! Do you have any questions or do you simply not understand a certain topic? Join us on the 28th of March and we will walk you through the entire course content in one quick interactive crash course!	31	2018-03-28 09:00:00+02	2018-03-28 11:00:00+02	BB 5161.0289	0	0	1538850746184039	\N
2935	Waterskiing	Join us waterskiing on the 26th of june at 15:30\r\nLocation:\r\nBreak Out Grunopark\r\nHoofdweg 163\r\n9614AD Harkstede\r\n\r\nThe costs for waterskiing will be at most €4. We don't think it's necessary, but if you want you can rent a wetsuit for an additional €5. You can also choose to rent a wakeboard instead of waterskis, however this costs an additional €7.\r\n\r\nThere is a maximum of 30 participants, first come first serve, so be there or be square.\r\nSign up at [url=https://waterski.svcover.nl]waterski.svcover.nl[/url]	20	2018-06-26 15:30:00+02	2018-06-26 17:00:00+02	Break Out Grunopark, Hoofdweg 163, 9614AD Harkstede	1	0	\N	\N
2863	General Assembly	It's time for the next General Assembly! After the succes of last time we decided to have it at the university again. This time we'll discuss, among others, the half year reports, long term plan and extra sponsor income. Make sure to have your voice heard and come by. A list to sign up for pizza's will be placed in the Cover room, so make sure to sign up for it to not miss out on the food.\r\n\r\nAll relevant documents will be emailed to you and you can also find them on https://sd.svcover.nl/.\r\n\r\nFor dinner we will order pizza you can sign-up for pizza in the following form:\r\nhttps://pizza.svcover.nl	0	2018-03-19 17:00:00+01	2018-03-19 23:00:00+01	Nijenborg 5114.0004	0	0	190540591552582	\N
2911	Study Support: General Linguistics	Are you twisting your tongue in phonetics or lost in the woods of syntax trees? StudCee are here to help! Join us at the 19th of June as we go over the course once more and answer any lingering linguistic questions!\r\n	31	2018-06-19 13:00:00+02	2018-06-19 15:00:00+02	BB 5161.0293	0	0	624335147903523	\N
2817	An introduction to price prediction and trading tools by ING	Can you predict the stock and bond prices? This is one of the most difficult topics in Artificial Intelligence and Data Analysis. Niels Denissen of ING will tell about there cutting edge technologies they use to help traders make their decisions, and the problems they are still facing. \r\n\r\nSign up here: https://goo.gl/forms/GR8XFMlIw8KyMmhG2\r\n\r\nKatana is a streaming platform designed for traders working at ING. It aggregates and enriches multiple financial market sources in real time to provide a single overview for them to base their decisions on.\r\nAdditionally we use machine learning to augment this process by providing predictions with the knowledge of hundreds of thousands previously traded deals. With Katana, traders are shown to be faster and more accurate in their decision making process. Katana has been built with open-source software, including technologies such as Kafka, Flink, CouchDB, and Docker. The analytics stack has been built using Python, using Pandas, SK-Learn, and Keras.\r\n\r\nMy name is Niels Denissen and I currently work as a Data Engineer in ING's Wholesale Banking Advanced Analytics team for project Katana. Before starting at ING roughly 2,5 years ago I finished a masters in Artificial Intelligence at the University of Utrecht and a bachelors of Computer Science at the Technical University of Eindhoven. I'm passionate about working with data: Providing fast and reliable solutions for complex problems, as well as practicing data science whenever time allows. Outside of work I like cycling, soccer, snowboarding, and value time spent with friends and family.	26	2018-01-16 19:30:00+01	2018-01-16 19:30:00+01	Newscafe Groningen - Waagplein 5, 9712 JZ Groningen	0	0	\N	\N
2819	Study Support: Signals & Systems	In this support session we will be giving examples from the five main topics; Sinusoidal Waves, Spectrums, LTI Systems, Fourier Analysis, and Z-Transformations. These examples will be taken from previous exams and tutorial exercises.	31	2018-01-19 13:00:00+01	2018-01-19 15:00:00+01	BB5161.0105	0	0	401971290217241	\N
2824	Study Support: Calculus for AI & CS	Are you struggling with a topic in Calculus? Are you just generally confused by all the methods and formulas? Then come to this study support lecture, where we will practice the difficult topics once again and listen closely to all your questions.	31	2018-01-22 13:00:00+01	2018-01-22 15:00:00+01	BB 5161.0289	0	0	192938708116985	\N
2821	Study Support: Discrete Structures	Troubles with Discrete Structures? The graphs getting confusion or don't remember all your closures? Then come to this study support lecture. Where we will overview the exam material and practice the difficult problems.	31	2018-01-23 11:00:00+01	2018-01-23 13:00:00+01	BB 5161.0289	0	0	154539578604647	\N
2822	Study Support: Introduction to Logic	Do you have any problems with Introduction to Logic? Do you want help in preparing for you exam? During this study support session, we will aim to discuss every topic in the course, with special attention going out to topics such as Semantics and Formal Proofs. \r\n\r\nJoin this last unofficial lecture given by students who have passed this course in the past. There will be ample opportunity to ask questions.	31	2018-01-23 13:00:00+01	2018-01-23 15:00:00+01	BB 5161.0222	0	0	2062843393995177	\N
2820	Study Support: Data Analytics and Communication	For the students who could use some extra help with Data Analytics and Communication, the StudCee is organizing a study support lecture wherein we'll give a brief overview of the course material, and answer any questions.	31	2018-01-18 11:00:00+01	2018-01-18 13:00:00+01	NB 5113.0104	0	0	353555758445408	\N
2825	Little LAMA (Lovely Active Member Activity)	The Little LAMA (Lovely Active Member Activity) is coming up again. To thank all our active committee members for their hard work we would like to invite them to our game of living Cluedo! It will take place in the city center where we meet up in front of the police station and the game will be explained. At the end of the game everybody will meet up again at the Drie gezusters and we'll have a free drink. After that we will head to 't Gat to enjoy the monthly social!\r\n\r\nNote that this is only for people who are in a committee of Cover!	0	2018-02-07 20:00:00+01	2018-02-07 20:00:00+01	Police station (city center), Rademarkt 12	1	0	\N	\N
2837	[Career Month] Lunch Lecture by Topicus	Topicus in one of most well known IT-companies in the Netherlands, and specializes in a lot of different sectors, like security, health care, finance and education. During this talk, you will get the chance to see what it is like to work at such a company. \r\n\r\nCover will provide a free lunch!	26	2018-02-27 11:00:00+01	2018-02-27 13:00:00+01	BB 5161.0253	0	0	1599248253500352	\N
2823	Valentine Social	Valentines day is coming up soon and that means love is in the air! To prepare you for this day we will be doing a lollypop action. This means you can buy a lolly beforehand and write a card to go with it. During the social we will hand these out to the correct persons. So if you want to declare your love to your secret crush, want to send your girl/boyfriend a nice treat or just want to make a friend happy, make sure you don't forget to write your card and order your lollypop in time.\r\n\r\nA lollypop will cost 1 euro and you have to order it before 2 February!\r\nOrdering can be done by filling out the form in the Cover room and writing your message.	2	2018-02-07 21:30:00+01	2018-02-07 21:30:00+01	't Gat van Groningen, Poelestraat 51	0	0	325533211289894	\N
2830	Member weekend	It's time for another member weekend. Come and join us for a weekend full of eating, drinking and games! Make sure you bring you Cover friends and a good mood!\r\n\r\nIf you want to organise a nice activity during the weekend, contact the board!\r\n\r\nSign up here: https://memberweekend.svcover.nl	0	2018-03-23 17:00:00+01	2018-03-25 17:00:00+02	Wouda, Wester Es 3, 8426BJ, Appelscha	0	0	154078328596705	\N
2828	Paintball	Do you wanna play paintball? Now is your chance! If you have always wanted to shoot your friends with little balls of paint, now you can!\r\nWe will be playing paintball in an indoor facility, so don't worry about the weather.\r\nYou can sign up at: https://goo.gl/forms/8O9kYUifi3quZVxk2.\r\nThere are only a limited amount of spots available, so sign up while you still can.	20	2018-02-12 20:00:00+01	2018-02-12 20:00:00+01	Paintball City, Bornholmstraat 46	0	0	391106528028714	\N
2848	March Social	Just a standard, lovely social! Come and drink some beers with us!	2	2018-03-07 21:30:00+01	2018-03-07 21:30:00+01	Donovan's, Peperstraat 15, 9711 PC, Groningen	1	0	209568432955807	\N
2908	Study Support: Linear Algebra & Multivariable Calculus	Do you still have some questions about Linear Algebra? Are some topics still unclear? Join this last unofficial lecture given by other students, where we'll recap the course material, cover some example exam questions, and answer your questions too!	31	2018-06-15 13:00:00+02	2018-06-15 15:00:00+02	BB 5161.0041b	0	0	1285905131541619	\N
2849	Beta Business Days 2018	The Beta Business Days 2018 takes place on the 13 th and 14 th of March in MartiniPlaza. This\r\nunique career event is the perfect opportunity to orientate yourself, find internships or even\r\nstarting your career!\r\n\r\nAlongside activities as Business Presentations, Case Studies, Business Lunches, Individual\r\nInterviews and a Business Expo. There will be two special activities for Cover members.\r\n\r\n[b]Live Blockchain Demo: Machine communication with cryptocurrency technology[/b]\r\nCryptocurrencies are worth billions. However, people are still not able to crack the\r\ntechnology to their advantage. Everyone can see all the addresses that contain a certain\r\namount of coins, but only the owner of the address determines what happens with the coins.\r\nSuppose these coins are replaced by a quantity of information. The owner of the data\r\ndetermines to whom this data is sent. If encryption is applied to the data when sent, an\r\nopportunity arises to send data through a ledger publicly and in a non-transparent way. The\r\nowner determines who in the world receives the information. Like a transaction with\r\ncryptocurrency, the data cannot be changed afterwards and the source is verified. This way\r\nof sharing data makes it possible to have machines and sensors worldwide conduct both\r\npublic and private &quot;conversations&quot; in a safe way. During this interactive presentation we will\r\ntake a closer look at the technology behind a cryptocoin and how this technology can be\r\nused to make devices communicate and negotiate.\r\n\r\n[b]Speed dating[/b]\r\nDon’t know what company fits you best? Join the speed date session! During this activity,\r\nyou have a chance to talk to representatives of 5 different companies.\r\nFor more information and free enrollment: [url=www.betabusinessdays.nl!]www.betabusinessdays.nl![/url]	0	2018-03-13 10:00:00+01	2018-03-14 19:00:00+01	Martiniplaza	0	1	1789420044443123	\N
2855	Lustrum	Come and join us on the lustrum of Cover. \r\nWhat is a lustrum you said? Well, it's the celebration of an association's birthday every 5 years. \r\nSince we are going to celebrate this in the most outrageous, extravagant and escalating way, we will celebrate this not for just 1 day, not just 3 days, but for a whole week!!!\r\nWe will soon start with a theme announcement somewhere soon, and from then on we will slowly leak drops of awesome information.\r\nSo hang on, it will be LEGEN...wait for it ...DARY!!!!	9	2018-09-17 09:00:00+02	2018-09-21 23:59:00+02	\N	0	0	\N	\N
2851	Open Board Meeting 1	Are you just curious about what the board does during their weekly meetings? Or are you interested in doing a board year at Cover? Come join us during one of the open board meetings to see how we run the association day to day! How we handle problems, which parties are involved, how we interact with the university and much more!\r\n\r\nJoin us to see all this for yourself!\r\n\r\nYou are free to join late or leave early.	0	2018-03-07 11:00:00+01	2018-03-07 11:00:00+01	LB 5171.0603	0	0	\N	\N
2839	Improv Workshop with Stranger Things Have Happened	The people from the improv group Stranger Things Have Happened are coming over to give us a workshop in improvisational acting. They have very fun people and have a great sense of humor. We'll have the workshop from 5 till 6 (make sure to be on time!) and afterwards we'll go out for dinner. The dinner will be at Blokes in the city center.\r\n\r\nBut the best thing of all, all of this will be free!\r\nMake sure to sign up fast as there are only 32 spots available!\r\n\r\nSign up using this link: https://goo.gl/forms/oOxL4F1IrL8c7EUC3	0	2018-02-28 17:00:00+01	2018-02-28 17:00:00+01	LB 5173.0141 and LB 5173.0151	0	0	196546324427255	\N
2857	Board Information Session	Are you curious about what the board actually does? How we keep the association running, how we try to improve things or what it actually is that keeps us busy? During the Board Information Session we'll explain what the board actually does. But also what each board function entails (Chairman, Secretary, Treasurer, Commissioner of Internal Affairs, Commissioner of External Affairs). And of course there is plenty of room for questions from you. Come join us and learn how the board works!	0	2018-03-12 17:00:00+01	2018-03-12 17:00:00+01	BB 0041b	0	0	\N	\N
2850	Crash & Compile	On Thursday the 22nd of March, the annual Crash & Compile returns! The C&C is a programming competition held at Quintor where you take a drink every time your code fails. There will be ten programming exercises, each more difficult than the last, and you'll earn more points the quicker you solve them. There are prizes to win and drinks to be drunk, so sign up (in pairs) as soon as possible, as there are only 30 spaces (15 pairs)! Drinks and dinner are generously provided by Quintor, so there is no entry fee. Sign up at lcdee.svcover.nl!	21	2018-03-22 17:30:00+01	2018-03-22 23:00:00+01	Quintor (Ubbo Emmiussingel 112, 9711 BK Groningen)	0	0	176522312984595	\N
2866	Study Support: Program Correctness	Trouble with Program Correctness? Can't seem to find the invariant? Then come to this support lecture, where we will practice for the exam. We will try to give you a different insight.	31	2018-04-03 15:00:00+02	2018-04-03 17:00:00+02	BB 5161.0222	0	0	165939157461991	\N
2853	Board Interest Night	Are you interested in doing a board year at Cover? Are you still unsure or have more questions about it? At the board interest night the current board, but also old board members from previous years will be present and available for questions. Come have a chat with them and have a beer. The setting will be casual, so if you're even slightly interested just come by and have a fun and informative evening!\r\n\r\nNote: When you arrive, please call Yannick to let you in.	0	2018-03-26 20:00:00+02	2018-03-26 20:00:00+02	Steentilstraat 35a	0	0	\N	\N
2869	[CANCELLED] [Dutch only] Career in education information day	Unfortunately, this event has been cancelled.	32	2018-04-23 17:00:00+02	2018-04-23 19:30:00+02	\N	0	1	\N	\N
2909	Study Support: Neural Networks	Are you straining your neural network over all the neurons, layers, perceptrons and convolutions? Do you need a quick reset of all the weights and connections, and get a fresh view on the course's contents? Join us as we go through a lecture covering all the topics that need a bit more training.	31	2018-06-18 13:00:00+02	2018-06-18 15:00:00+02	BB 5161.0222	0	0	221763965075650	\N
2929	Conference	Headache? Tired? Frankly hungover from the start of the lustrum week? Then you will be glad to hear that we reached the calm middle of the week now! Join us in a series of enrapturing, scholastic and tantalizing talks and workshops on Wednesday afternoon. If you think the adjectives in the previous sentence are big, just wait until you hear from our speakers.\r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-19 13:00:00+02	2018-09-19 18:00:00+02	\N	0	0	\N	\N
2864	National MasterDay AI	Are you currently enrolled in a bachelor Artificial Intelligence or something in related fields?\r\nNSVKI organizes a master information day. The AI masters of Amsterdam, Groningen, Nijmegen and Utrecht will be represented.\r\nIf you're not sure yet what kind of master you want to do, to what city you want, if you want to do a master at all and what a specific master will be like, you are more than welcome on this day!\r\n\r\nWhat: Master information day\r\nWhere: Utrecht, the Ruppert building in science park Uithof\r\nWhen: 21 Maart, 13:00\r\nProgramme:\r\n13:00 - 13:30 Reception with coffee and tea in hall Ruppertbuilding \r\n13:30 - 14:15 Opening speech in Ruppert Room 114\r\n14:15 - 17:00 Information masters:\r\n   ‍‍14:15 - 17:00 Ruppert Zaal 111:\r\n‍‍ ‍‍ ‍‍‍‍‍‍ ‍   RUG, RU, UVA & VU joined master\r\n   ‍‍‍‍‍‍14:15 - 15:00 Ruppert Zaal 123:\r\n‍‍ ‍‍‍‍‍‍ ‍‍    ‍‍‍‍‍UU, UvA, VU\r\n‍‍   ‍‍‍‍‍‍15:15 - 17:00 Ruppert Zaal 134:\r\n ‍‍‍‍‍‍ ‍‍ ‍‍‍‍‍‍   UU, UvA, VU\r\n\r\nVisit http://nsvki.nl/ for more information about the masters.\r\n\r\nFrom Groningen we will travel with a group to Utrecht. If you want to join, please send a Whatsapp message to +316 42 73 84 29 in order to be added to the Whatsapp group.	0	2018-03-21 12:00:00+01	2018-03-21 17:00:00+01	Utrecht University	0	1	151112192231060	\N
2905	Wind surfing	Come join us on the (hopefully) wavey, sunny, yet breezy 28th of May. We will participate in a surf clinic organised by G.S.W.V. SurfAce at the Hoornsemeer.\r\nFor only 5 euro's, you can join in on the fun and learn with everyone else how cold the water is right now and maybe learn some wind surfing as well!\r\n\r\nSign-up here: [url=https://windsurfing.svcover.nl]windsurfing.svcover.nl[/url]\r\n\r\nP.S. Don't forget to bring your sun lotion/towel/swimming clothes!	2	2018-05-28 16:00:00+02	2018-05-28 18:00:00+02	Hoornsemeer	0	0	494480797633918	\N
2872	La La LAN	Do you like games? Do you like binging on them for an entire weekend?\r\n\r\nOf course you do and we have just the thing for you: A LAN party going on for a full weekend! Come and join us at the 2018 version of everyone’s favorite event. You can bring your own computer yourself or pay a small fee to make use of the transport service to carry your desktop PCs. Alternatively, just use the consoles in the Cover room to crush fellow Cover members in whatever game you want to play. Sign up and snacks are free, and we will order dinner (also free once you pay for it). If this wasn’t enough to lure you in, we will also be organizing an adrenaline-filled tournament with amazing prizes.\r\n\r\nSign up at [url=https://lan.svcover.nl]lan.svcover.nl[/url]!	21	2018-04-20 17:00:00+02	2018-04-22 13:00:00+02	Study Landscape (Bernoulliborg)	0	0	2065458510355168	\N
2877	Brainstorm with the board - Long Term Plan	As discussed during the GA, we are in the process of forming a Long Term Plan and we need your help! The current plan needs to be tuned and elaborated, so come voice your idea's for Cover. Tell us your vision for Cover in 5 years. Tell us what you think is important to focus on the next 3 to 5 years.\r\n\r\nThe current proposal can be find [url=https://sd.svcover.nl/Archive/General%20Assemblies/2018-03-19/07_Long_Term_Plan.pdf]here[/url] and printed in the Cover room\r\n	0	2018-04-16 17:00:00+02	2018-04-16 17:00:00+02	Cover room	0	0	295987950934665	\N
2922	[CAREER] TAD-talk: Peaks	Time for the last TAD-talk of the year!\r\n\r\nThe Dutch start-up behind Peaks, one of the most refreshing investment apps of the past years will come and give a talk on how to get started with an innovative company, and lead a case on the implementation of Machine Learning in their system.\r\n\r\nPeaks ([url=http://peaks.nl]peaks.nl[/url]) helps you to invest your change: every cent counts! To do so they have build a platform that helps you to set aside small amounts of money and invest it wisely. However, they do not yet use Machine Learning for this process, and would like your input to make this possible!\r\n\r\nSo come join us for this interesting and interactive TAD-talk, there will be drinks and pizza for everyone! 	26	2018-06-14 16:00:00+02	2018-06-14 18:00:00+02	Coverroom	0	0	649705622048165	\N
2887	[CAREER] Kickstart your Career	A day focussed on giving students insight into the inner and outer workings of several relevant companies. The companies will give interactive workshops for the students so that they get an idea of their work-ethics and topics of interest.\r\n\r\nThe companies that will be present are:\r\nKPMG - One of the 'Big Four' companies, focussed on audit, advisory and consultancy. Homepage: https://home.kpmg.com/nl/nl/home.html\r\nYoungCapital - An employment agency that aims to connect students and young professionals with suited companies. Also one of the most growing companies of 2017. Homepage: https://www.youngcapital.nl/\r\nCosmonio -  A.I. platform for rapid deep-learning application development. Homepage: https://cosmonio.com/\r\nCGI - Global IT-solutions company will give insights on the use of blockchain in practice. Homepage: https://www.cgi.com/en\r\n\r\nThe day will be mostly free from lectures and tutorials (for Computer Science and Artificial Intelligence students) in order to avoid interference with your busy study schedules! There will also be lunch and dinner with the present companies. The day will be ended with a networking borrel with Cover's partners.\r\n\r\nYou can sign up at www.career.svcover.nl!\r\n	26	2018-05-01 11:00:00+02	2018-05-01 20:00:00+02	Zernike - Various locations	0	0	\N	\N
2931	Nature Olympics	This day will be the birthday of Cover. Cover will become 25 years old! To celebrate this festive occasion, we will have an outdoor day at the Hoornsemeer. In the afternoon, we will have a tournament with multiple fun games at which you can win a fantastic price if you win with your team. After the tournament there will be a nice barbecue and a party. Of course, we will also take a special moment to dwell upon the fact that Cover just became 25 years old. We will see you there!\r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-20 14:00:00+02	2018-09-20 14:00:00+02	\N	0	0	\N	\N
2880	Hooghoudt Liquor Tour and Tasting	On the 23th of April, we will go to Hooghoudt distillery. Here we get to see how the typical Groninger liquors are made. On top of that we will get to taste a lot of nice liquors during this tour. The costs for this activity will be 10 euro's per person.\r\n\r\nIf you want to join us, please subscribe: https://goo.gl/forms/qq7XxzsEpbV3IiQw2	2	2018-04-23 20:00:00+02	2018-04-23 20:00:00+02	\N	0	0	572348129787614	\N
2913	LLAMA (Large Lovely Active Member Activitee)	It's time to thank all the members that were active in a committee this year!\r\n\r\n* Please note that this event is based on invitations.\r\n\r\nAs a board we want to thank everybody that made the effort for keeping Cover this awesome by doing a committee. We'll be doing this in the traditional way, by organizing a free barbecue at the Hoornsemeer. Aside from the barbecue we've also arranged a bunch of fun activities (on which more info will follow later). So block the spot in your agenda and come join us on this (hopefully sunny) lazy, chilling in the sun event! \r\n\r\nPlease sign up for the LLAMA so we can do proper purchases: [url=https://llama.svcover.nl]llama.svcover.nl[/url].	0	2018-07-02 17:00:00+02	2018-07-02 17:00:00+02	Hoornsemeer	1	0	\N	\N
2878	[CAREER] Interactive excursion HTG	Departure from Zernike 13:00.\r\nReturn Zernike 18:00\r\nCover will take care of transportation.\r\n\r\nOn the 18th of April we will travel to Delfzijl for a excursion to HTG. The company organizes an inhouseday for Cover so you can get into contact with the young professionals that are working in IT-solutions. Next to that you've got the chance to show your talent in a challenging case about the Microsoft HoloLens. We'll end the day with drinks.\r\n\r\nAt this moment there are 60 people working at different locations in the EU and Middle East, focusing on implementing new technologies to realize the planned growth and development of HTG. One of the current technologies worked on is the Microsoft Hololens, which can greatly improve the logistic processes. Do you want to do a project on augmented reality? HTG is looking for people willing to do a thesis on the HoloLens. \r\n\r\nNext to that, HTG is one of the first companies that have implemented the AutoStore in logistics. In this process there are 42 robots working together to collect orders. Of course we will have a look at this in Delfzijl! \r\n\r\nDuring the day we will discuss:\r\n- Tools used for predictive analysis. \r\n- The AutoStore used to realize B2B2C-orders\r\n- Development of the HoloLens-application to optimize orderpicking.\r\n- The infrastructure- and applicationenvironment\r\n- The organization and activities of the IT-division\r\n- Possibilities of an internship or traineeship at HTG\r\n\r\n\r\nDeadline subscribing: 11 April.\r\nSubscribe for this activity here: \r\nhttps://goo.gl/forms/vGZ6kJ5CfcoDRJy73 \r\n\r\n	26	2018-04-18 13:00:00+02	2018-04-18 18:00:00+02	\N	0	0	1375639629204789	\N
2883	Deadline Board Applications	Do you want to do a board year? Apply now for board XXVII by sending your resume and motivation letter to apply@svcover.nl before 23:59 on the 15th of April! \r\n\r\nAre you still in doubt? Approach a board member to schedule a coffee date to talk about what being board entails.	0	2018-04-15 09:00:00+02	2018-04-15 23:59:00+02	\N	0	0	\N	\N
2879	Lustrum Theme Announcement TAD	Are you also super excited about the upcoming lustrum in September? Can you also hardly wait for it to happen?  On the 19th of April we will announce the theme of our lustrum so that you can even be more exited! We will start at 4 o'clock in the afternoon, and we will have free snacks and drinks in the Cover room!	9	2018-04-19 16:00:00+02	2018-04-19 16:00:00+02	Cover Room	0	0	\N	\N
2893	Academic Writing Workshop	Do you want to make sure that your thesis is easy to read while properly getting your point across? Do you want to produce a quality paper instead of just another bachelors project doomed to gather dust in the repository?Then join us at the Academic Writing workshop! \r\nHere we will go over a lot of tips that can help you get your point across, while they will also make writing more fun. The workshop will cover more unambiguous structural writing as well as other language devices. We will also spend some time on how to make and give presentations. 	31	2018-05-25 13:00:00+02	2018-05-25 15:00:00+02	BB 5161.0041b (next to the Cover room)	0	0	219233952182667	\N
2902	EsGalatie	Study associations Meander, EPU, ASCI, STUFF, Gerardus van der Leeuw, TW!ST, Commotie, Cover, Multi and Siduri are organising THE GALA of the year 'EsGalatie'!!! \r\n\r\nThis is THE annual legendary gala to conclude this academic year with a huge party! Be there or be square! \r\n\r\nThe theme is Roaring Twenties! So grab your glitter dress, boas or smoking and go party with us on swinging beats and unlimited beer, wine and soda! Ticket sale will start May 7th!	32	2018-05-30 22:30:00+02	2018-05-31 02:30:00+02	Newscafe Groningen - Waagplein 5, 9712 JZ Groningen	0	0	207251113210910	\N
2895	Trip to Bremen	Do you want to have an awesome three day adventure with fellow Cover members? Then join the ABCee for a trip to Bremen!\r\n\r\nWe will have a host of cool activities in this great city, including a visit to the University of Bremen. There we will visit their robotics laboratory and the Ambient Assisted Living Laboratory - BAALL \r\n among other things. Of course, there will also be free time to explore the city.\r\n\r\nThe price will be less than 40 euros!\r\n\r\nInterested? Sign up at [url=https://bremen.svcover.nl]bremen.svcover.nl[/url]!	11	2018-05-24 08:00:00+02	2018-05-26 21:00:00+02	\N	0	0	\N	\N
2892	Mario Kart TAD	For this TAD, we're bringing back a classic: a Mario Kart 8 tournament! As usual, free snacks are available to munch on while you watch some intense racing. If you need more salt on your snacks, just ask the participant that got hit by a blue shell.	18	2018-05-17 16:00:00+02	2018-05-17 16:00:00+02	\N	0	0	175424136482788	\N
2885	May social	Now, this is a story all about how\r\nOur livers got flipped-turned upside down\r\nAnd I'd like to take a minute\r\nJust sit right there\r\nI'll tell you how the social has nothing to do with Bel Air.\r\n	2	2018-05-09 21:30:00+02	2018-05-09 21:30:00+02	't Gat van Groningen, Poelestraat 51	0	0	243614866203871	\N
2910	[RESCHEDULED] Study Support: Languages & Machines	Are you struggling with Languages and Machines? Come and walk through the essentials of the course once more. You can ask any questions you may have left. All to help you tackle your exam.\r\n	31	2018-06-20 15:00:00+02	2018-06-20 17:00:00+02	BB 5161.0289	0	0	369161433581713	\N
2912	June Social: A touch of tiger	The end of the year is approaching quickly, so there are only a few possibilities to have some drinks with your friends at Cover and let your inner Tiger out! Dresscode for this Social is something with Tiger/Leopard print	2	2018-06-06 21:30:00+02	2018-06-06 21:30:00+02	't Gat van Groningen, Poelestraat 51	0	0	\N	\N
2901	Staff BBQ	Looking at the lovely weather outside, it is time for the annual Staff BBQ! The barbecue will take place at the first of June. So come join us in front of the Bernouilliborg at 17.00 to mix and mingle with staff members and enjoy some nice food for only 3 euros. Sign-up at [url=https://bbq.svcover.nl]bbq.svcover.nl.[/url]\r\n\r\nHope to see you there!	0	2018-06-01 17:00:00+02	2018-06-01 21:00:00+02	Field in front of the Bernouilliborg	0	0	259939871242182	\N
2903	Alumni Social	Ever wonder what people are up to after they finish their studies and start working? This is your chance to casually ask them some questions and drink a beer with them. Of course it might also be a chance to see some old friends again.\r\n\r\nThe make the event even better Cover will be providing some free snacks!	0	2018-05-25 20:30:00+02	2018-05-25 20:30:00+02	Café Wolthoorn, Turftorenstraat 6	0	0	1882361285394252	\N
2927	Bommen Berend	Every year, the city of Groningen celebrates The Siege of Groningen. This was a battle that took place in 1672, between Groningen and Bernhard von Galen, bishop form Münster. The bishop has as nickname “Bommen Berend”, due to his excessive use of bombs (Dutch: bommen). They celebrate this victory on 28 August, where they eat the “Groote Maaltijd”. This meal consists of sauerkraut, the bishop’s favourite meal, and a big meatball: the “Bommen Berend Bom”. \r\n\r\nWe also would like to celebrate this day, but perhaps leaving out the sauerkraut. Therefore we hereby invite you to a battle, where we have swapped the bombs with laser guns. This battle will take place at our beloved Zernike and you can come and leave whenever you want. Don’t forget that people can play dirty, so don’t wear your nicest clothes. \r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-17 14:00:00+02	2018-09-17 18:00:00+02	\N	0	0	\N	\N
2933	Special Dinner	Before ending the week with the Intergalactic Gala there is time to sit down for dinner with your Cover friends and talk about the great week we all had. During dinner we will present the best photos that have been submitted for the photo challenge and we will announce the overall winner! Next to that we will have a fun game during the dinner. Since alumni are invited to this activity, there is also time to catch up with all your friends from back in the days. After this lovely dinner we will go to the Intergalactic Gala and have a spectacular ending of this week. \r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-21 19:00:00+02	2018-09-21 19:00:00+02	\N	0	0	\N	\N
2934	Intergalactic Gala	After a delicious dinner it’s time for the last lustrumacitvity of the week, the intergalactic gala! We will finish the week in suits and ties for the gentlemen and dresses and high heels for the ladies. There will be unlimited beer, wine, soda and hard liquor (binnenlands gedestilleerd). With a lot of people at this activity, we want to celebrate the end of the amazing lustrum, and will make sure that this week will never be forgotten! So, suit up and join our trip to the galaxy this evening.\r\n\r\nPs. You’re allowed to experience this awesome last trip with a date\r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-21 21:30:00+02	2018-09-21 21:30:00+02	\N	0	0	\N	\N
2942	Viva las Vegas	Continuing our journey, we have arrived in the wonderful world/experience of Las Vegas. During this lovely evening activity, there will be the opportunity to visit our very own lustrum casino. You can play the best games with amazing lustrum dollars, which you will receive in exchange for your entrance ticket.\r\nIf you’ve lost all your dollars, don’t worry! You can always join a bachelorette party, get married in a lovely wedding chapel or take a nice picture with the Eiffel Tower.\r\nMake sure you look Las Vegas worthy!\r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-19 20:00:00+02	2018-09-19 20:00:00+02	\N	0	0	\N	\N
2920	Hackathon @ Dataprovider.com	It's time for another hackathon! This time we will be hosted by Dataprovider.com, a vibrant young data company that specializes on indexing data from the internet.\r\n\r\nThey will be giving us access to their vast database (data from 300 million+ websites), and it will be your job to find the best solution to a problem by using your best data science and programming skills!\r\n\r\nSound interesting? Sign up at [url=https://hackathon.svcover.nl]hackathon.svcover.nl[/url]!\r\n\r\nFood and drinks will be taken care of, and there will be a prize for 1st, 2nd and 3rd place!	0	2018-06-09 10:00:00+02	2018-06-09 22:00:00+02	Mediacentrale, Helperpark 270 - 298, 9723 ZA Groningen	0	0	\N	\N
2916	General Assembly	The last general assembly before the summer is almost here! We'll discuss the budget and year schedule for next year, but also a revised version of the long term plan. Don't forget the candidate board is also up for approval during this GA!\r\n\r\nA short summary can be found below, but also please read the documents (which can be found on [url=https://sd.svcover.nl]the sd[/url]).\r\n\r\n[b]Year Schedule '18-'19[/b]\r\n\r\nIn the year schedule you can find when activities are planned. Some of them are set on a certain date and other are just scheduled for a certain week. You can also find how many activities each committee has. This has been put together by Marie-Claire (our commissioner of internal affairs). She discussed with the various committees what works best and came to this result.\r\n\r\n[b]Budget '18-'19[/b]\r\n\r\nThis will be the budget for next year, so you can find in here how much money we have to spend and what we are going to spend it on. Because this is made by Nico (our treasurer) in collaboration with the committees, there is still some room for the treasurer of next year to make changes during the September GA. However this does not mean that this isn't important because next year we have a lot of extra money to spend! This money has been distributed over different committees and activities.\r\n\r\n[b]Long Term Plan[/b]\r\n\r\nAfter discussing the first version of the long term plan during the last GA we decided to have a brainstorm with the board. During that brainstorm we got a ton of useful input and we used that input to rewrite the document. We think the current plan is solid and we can start using it. It is very important that future boards keep adding to it and updating it.\r\n\r\nIn the long term plan we outline the goals for the next 3 to 5 years for the association. The steps on how to reach those goals are not in here because they are up to the future boards that work on it. Which also means some of these goals don't have a clear solution of how to get there, but the goals are still very important. We'd also like to stress it's important to have such a plan in place and future boards can still improve it!\r\n\r\n[b]Board XXVII[/b]\r\nDuring this general assembly we will be discussing the board for next year, as well as approve them. If you want to hear who  is going to run the association for academic year 2018-2019 and why, please attend the GA. \r\n\r\nSince all these documents are up for approval please read the them as well before casting your vote during the GA!	0	2018-06-04 19:00:00+02	2018-06-04 19:00:00+02	Cafe De Walrus, Pelsterstraat 25	0	0	\N	\N
2900	Candidate Board Announcement Social	Are you as excited as we are for the new board? Do you want to find out who will govern the association next year? Come to the candidate announcement social on the 28th of May. Not only will you found out who will call themselves board XXVII, but free drinks are guaranteed. As a bonus you get to celebrate these nice and lovely people!	0	2018-05-28 22:00:00+02	2018-05-28 22:00:00+02	De Brouwerij, Poelestraat 27	0	0	\N	\N
2928	Groninger Dow Jones Party	To celebrate the opening of the Lustrum, we will have a Dow Jones party at Lola on the Monday night. During this epic event at a super fancy location, prices of the drinks will fluctuate depending on the supply and demand of that specific drink. For example: if everybody drinks beer, the price of beer will go up, but the price of another drink will go down. If nobody drinks wine, the price of wine will be very low while other prices might go up. So, buy at the right time to get the best drinks for the best prices. At this activity, we will be able to do what we have always wanted to: being able to influence the prices of our own drinks. \r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-17 22:00:00+02	2018-09-17 22:00:00+02	\N	0	0	\N	\N
2925	PhotoCee Crazy 88 Closing TAD	At the May social the PhotoCee started their activity: a crazy 88. A bit over a month later, during this TAD, we will announce the winners of this competition and show the best/funniest submissions! \r\n\r\nThe final score of participating teams get finalised at the start of the TAD, so until then you still have the opportunity to sign up with your team and battle the other teams for the prizes! \r\n\r\nSign-up sheet: [url=https://crazy88.svcover.nl]crazy88.svcover.nl[/url]\r\nChallenges: [url=https://crazy88rules.svcover.nl]crazy88rules.svcover.nl[/url]	7	2018-06-21 16:00:00+02	2018-06-21 16:00:00+02	Cover room	0	0	\N	\N
2945	Boating through the Canals	As our final activity this year, we will be boating through the canals of Groningen! After the great success this activity was last year, we decided to repeat it. Expect drinks, snacks, fun, water and hopefully sun! Come with your friends and spend an amazing afternoon on the water with us! \r\nBe present 15 minutes early, at 15:45, before the lovely departure or we cannot guarantee that we will wait on you!\r\n\r\nPlease sign up for this activity using the form below!	2	2018-07-04 16:00:00+02	2018-07-04 16:00:00+02	Reitdiephaven, Groningen	0	0	\N	\N
2930	5^2 feast	On this afternoon and evening we cordially invite you to our 5^2 feast. A magnificent dinner which will make your eyes sparkle with joy and your belly full. \r\n5^2 feast, what's that you ask? \r\nWe will be:\r\n- visiting 5 different restaurants\r\n- from 5 different countries\r\n- you will get a total of 5 courses\r\n- accompanied with 5 drinks\r\n- in just 5 hours\r\n\r\nAnd all of this for 5^2 euro's! \r\nDresscode: slightly overdressed\r\nFree for passe-partout platinum members\r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-18 15:30:00+02	2018-09-18 15:30:00+02	\N	0	0	\N	\N
2941	The Hangover	Yesterday we had an amazing night in Vegas. With a lot of partying and fun casino games it has perhaps become pretty late. All this partying would probably have given you quite a hangover today. To make things a little bit better for you, we invite you to a so called "brakke brunch". We will provide tasty food and drinks so you can start the day with a smile, despite your possible hangover. \r\n\r\nFor more information and registration, check out [url=https://lustrumcee.svcover.nl/activity/]our website[/url]!	9	2018-09-20 11:00:00+02	2018-09-20 13:00:00+02	Cover Room	0	0	\N	\N
2947	Introductory Camp: ReWind	At the start of the year it is time for something wonderful again. After the first week of lectures it is again time for the Introductory Camp! This year we will have a blast to the past with the theme "Rewind!". So join us for a weekend filled with fun and nostalgia! You can sign up and find more information at https://introcee.svcover.nl/index.php	8	2018-09-07 15:30:00+02	2018-09-09 17:00:00+02	De Pageborg	0	0	\N	\N
2946	Introductory Day	We kick of the year with this year's Introductory Day! During the first part of the day, you will get to know the University, its professors and the campus, and you will get some very useful information to start the academic year. After this, some great activities and a barbecue will be organized, such that you can meet your mentors and fellow students in a nice and informal way. Make sure not to miss out on this useful and pleasant day! You can sign up for the barbecue (which is free for first-year students) at https://introcee.svcover.nl/barbecue.php	8	2018-08-31 09:00:00+02	2018-08-31 09:00:00+02	Bernoulliborg	0	0	\N	\N
\.


--
-- Data for Name: announcements; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.announcements (id, committee_id, subject, message, created_on, visibility) FROM stdin;
261	0	Location March Social	[img=/documenten/Announcement_banners/20172018/MarchSocial_banner.jpeg]\r\n\r\n[b]Attention: the location for this social is the Donovan's![/b]\r\n\r\nJust a standard, lovely social! Come and drink some beers with us!	2018-03-02 11:59:42.68214	0
259	0	Pub Lecture: Price prediction and trading tools by ING	[img=/documenten/Announcement_banners/20172018/BannersiteING.jpg]\r\nDate & time: Tuesday 16th of January, 19:30\r\nLocation: Newscafe Groningen - Waagplein 5, 9712 JZ Groningen\r\n\r\nCan you predict the stock and bond prices? This is one of the most difficult topics in Artificial Intelligence and Data Analysis. Niels Denissen of ING will tell about the cutting edge technologies they use to help traders make their decisions, and the problems they are still facing. \r\n\r\nSign up here: https://goo.gl/forms/GR8XFMlIw8KyMmhG2 	2018-01-13 17:01:48.110351	0
260	0	Career Month	[img=/documenten/Announcement_banners/20172018/Career_month.png]\r\n\r\n[b]February is Career Month at Cover![/b]\r\n\r\nDuring the month of February, we will be organizing activities to let you get a taste of the different types of IT-companies you can end up working for once you finish your studies.\r\n\r\nOn every Tuesday from 11:00 to 13:00, a different company from a different sector will present itself during a Lunch Lecture, and Cover will take provide a free lunch!\r\n\r\nThe schedule is as follows:\r\n\r\n06/02: TNO - Hacking in Practice\r\n13/02: Spindle - Groningen start-up\r\n20/02: Xomnia - AI for a self-driving boat\r\n27/02: Topicus - IT solutions 	2018-01-30 16:16:37.017453	0
262	0	Member Weekend	[img=/documenten/Announcement_banners/20172018/Member_Weekend.png]\r\n\r\nIt is time for another member weekend! Grab your onesie, cosy house shoes, and your nearest friend and come chill out for a weekend in Appelscha. Not convinced yet? Then have a look at the pictures of previous years [url=https://www.svcover.nl/fotoboek.php?book=1300]here[/url], [url=https://www.svcover.nl/fotoboek.php?book=1210]here[/url], and [url=https://www.svcover.nl/fotoboek.php?book=1147]here[/url]!\r\n\r\nNow that you're convinced, sign up through [url=https://memberweekend.svcover.nl]memberweekend.svcover.nl[/url]. It will take place in the weekend of 23 through 25 March, and will cost a maximum of €60. For that you'll get a bed for two nights, unlimited food and drinks, and some awesome memories!	2018-03-12 18:33:28.586175	0
263	0	Apply for board XXVII	[img=/documenten/Announcement_banners/20172018/BoardXXVII.png]\r\n\r\nDo you want to do a board year? Apply now for board XXVII by sending your resume and motivation letter to apply@svcover.nl before the 15th of April!\r\n\r\nAre you still in doubt? Approach a board member to schedule a coffee date to talk about what being board entails.	2018-04-04 16:08:19.023752	0
264	26	Kickstart Your Career	[img=/documenten/Announcement_banners/20172018/KyCBanner.png]\r\n\r\nCurious what you can do with your degree after you've finished studying? This is your chance to discover the possibilities! During this day, multiple companies from different sectors in the IT-world will be giving interactive, in-depth workshops.\r\n\r\nThe companies that will be present are:\r\nKPMG - One of the 'Big Four' companies, focussed on audit, advisory and consultancy: https://home.kpmg.com/nl/nl/home.html\r\nYoungCapital - An agency that connects students and young professionals with suited companies: https://www.youngcapital.nl/\r\nCosmonio - A.I. platform for rapid deep-learning application development: https://cosmonio.com/\r\nCGI - Global IT-solutions company will give insights on the use of blockchain in practice: https://www.cgi.com/en\r\n\r\nInterested? Sign up at www.career.svcover.nl!	2018-04-23 21:19:28.587263	0
265	0	ABCee trip to Bremen	[img=/documenten/Announcement_banners/20172018/Bremen_banner.jpg]\r\n\r\nBremen trip! 24-26 Mei.\r\nLess than 40 euros!\r\nInterested? Sign up at [url=https://bremen.svcover.nl]bremen.svcover.nl[/url]	2018-05-07 10:36:49.508731	0
266	0	Candidate board	[img=/documenten/Announcement_banners/20172018/KB_foto_XXVII-reasonably-sized.jpg]\r\n\r\nLadies and gentlemen,\r\n\r\nIt is an honour and a great pleasure to announce the 27th candidate board!\r\n\r\nChairman: Max Velich\r\nSecretary: David Homan\r\nTreasurer: Daan Lambert\r\nCommissioner of Internal Affairs: Chris Ausema\r\nCommissioner of External Affairs: Jan Christiaan Zwier\r\nCommissioner of Education: Emily Beuken\r\n\r\nWe, as board XXVI, are very proud of these six people, not only because it is the very first time in the history of our beloved association that there will be a Commissioner of Education. It is also the very first time ever we have announced candidates with an international background, and we announced two!\r\n\r\nSigned,\r\nOn behalf of the board,\r\n\r\nYannick Stoffers\r\n[i]Secretary[/i]	2018-05-29 18:37:01.534016	0
\.


--
-- Data for Name: applications; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.applications (key, name, secret) FROM stdin;
\.


--
-- Data for Name: besturen; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.besturen (id, naam, login, website, page_id) FROM stdin;
1	Bestuur I	bestuur1		76
2	Bestuur II	bestuur2		75
3	Bestuur III	bestuur3		74
4	Bestuur IV	bestuur4		73
5	Bestuur V	bestuur5		72
6	Bestuur VI	bestuur6		71
7	Bestuur VII	bestuur7		70
8	Bestuur VIII	bestuur8		69
9	Bestuur IX	bestuur9		68
10	Bestuur X	bestuur10		67
11	Bestuur XI	bestuur11		66
12	Bestuur XII	bestuur12		65
14	Bestuur XIV	bestuur14		63
15	Bestuur XV	bestuur15		62
16	Bestuur XVI	bestuur16		61
17	Bestuur XVII	bestuur17		60
18	Bestuur XVIII	bestuur18		59
19	Bestuur XIX	bestuur19		58
20	Bestuur XX	bestuur20		57
21	Bestuur XXI	bestuur21	\N	82
22	Bestuur XXII	bestuur22	\N	83
13	Bestuur XIII	bestuur13		64
24	Board XXIII	bestuur23	\N	116
25	Board XXIV: Evolution	bestuur24	\N	123
26	Board XXV: BOLD	bestuur25	\N	131
\.


--
-- Data for Name: commissies; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.commissies (id, naam, login, website, page_id, hidden, vacancies, type) FROM stdin;
35	Corporate identity	corporateidentity	\N	111	0	\N	2
40	Knights	knights	\N	117	1	\N	1
13	MxCee	mxcee		13	0	\N	1
43	SNiC	snic	\N	124	0	\N	1
25	AphrodiTee	aphroditee		49	1	\N	1
14	Board of Advisors	boa	\N	14	0	\N	1
18	Memory	memory	\N	42	0	2017-09-22	1
12	AudiCee	audicee	\N	12	0	\N	1
6	ExCee	excee	\N	6	0	2017-10-09	1
9	LustrumCee	lustrumcee	\N	9	0	\N	1
41	ParenTee	parentee	\N	119	0	\N	1
15	FoodCee	foodcee	\N	15	1	\N	1
16	SympoCee	sympocee	\N	19	0	\N	1
4	YearbookCee	yearbookcee	\N	4	0	2018-02-28	1
3	BookCee	bookcee	\N	3	1	\N	1
5	Brainstorm	brainstorm	\N	5	1	\N	1
44	DisCover	discover	\N	136	0	2018-02-28	1
8	IntroCee	introcee	\N	8	0	2018-01-31	1
32	External Committees	externalcommittees		97	0	2018-04-04	1
21	LCDee	lcdee	\N	45	0	2018-05-31	1
7	PhotoCee	photocee	\N	7	0	2018-03-30	1
11	ABCee	abcee	\N	11	0	2018-05-31	1
31	StudCee	studcee	\N	94	0	2018-05-31	1
29	PiraCee	piracie	\N	91	1	\N	1
26	ComExA	comexa	\N	50	0	\N	1
17	FirstYearCee	firstyearcee	\N	39	1	\N	1
45	PhDee	phdee	\N	139	1	\N	1
20	SporTee	sportee	\N	44	0	2018-05-31	1
0	Board	board	\N	0	0	\N	3
30	Candidate Board	candy	\N	93	0	\N	3
33	CSS	css	\N	98	1	\N	3
28	KISO	kiso		89	1	\N	3
2	ActiviTee	activitee	\N	2	0	2018-05-31	1
24	PubliciTee	publicitee		48	0	2018-05-31	1
27	HEROcee	herocee		81	0	2018-01-01	1
1	AC/DCee	webcie	\N	1	0	2018-05-31	1
\.



--
-- Data for Name: committee_email; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.committee_email (committee_id, email) FROM stdin;
\.


--
-- Data for Name: committee_members; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.committee_members (id, member_id, committee_id, functie) FROM stdin;
4986	1	1	Voorzitter
\.


--
-- Data for Name: configuratie; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.configuratie (key, value) FROM stdin;
weblog_forum	26
news_forum	27
poll_forum	28
boekcie_webshop_link	https://cover.itdepartment.nl/
boeken_bestellen	1
\.


--
-- Data for Name: email_confirmation_tokens; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.email_confirmation_tokens (key, member_id, email, created_on) FROM stdin;
\.


--
-- Data for Name: forum_acl; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_acl (id, forum_id, author_type, author_id, permissions) FROM stdin;
\.


--
-- Data for Name: forum_group; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_group (id, name) FROM stdin;
\.


--
-- Data for Name: forum_group_member; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_group_member (id, group_id, author_type, author_id) FROM stdin;
\.


--
-- Data for Name: forum_header; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_header (id, name, "position") FROM stdin;
\.


--
-- Data for Name: forum_lastvisits; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_lastvisits (lid, forum_id, date) FROM stdin;
\.


--
-- Data for Name: forum_messages; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_messages (id, thread_id, author_id, message, date, author_type) FROM stdin;
\.


--
-- Data for Name: forum_sessionreads; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_sessionreads (lid_id, forum_id, thread_id) FROM stdin;
\.


--
-- Data for Name: forum_threads; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_threads (id, forum_id, author_id, subject, date, author_type, poll) FROM stdin;
\.


--
-- Data for Name: forum_visits; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forum_visits (lid_id, forum_id, lastvisit, sessiondate) FROM stdin;
\.


--
-- Data for Name: forums; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.forums (id, name, description, "position") FROM stdin;
28	Polls	Polls	0
\.


--
-- Data for Name: foto_boeken; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_boeken (id, parent_id, titel, fotograaf, date, beschrijving, visibility, last_update, sort_index) FROM stdin;
1311	1232	General Assembly June	PhotoCee (Martijn)	2017-06-06		0	2017-09-18 00:58:57	\N
1310	1306	General Assembly	PhotoCee (Martijn)	2017-09-11		0	2017-09-18 00:40:40	\N
1306	0	Photos from 2017/2018	PhotoCee	2017-09-01		0	2018-04-12 16:58:28	\N
1307	1306	FirstYearCee PubQuiz	Annet	2017-09-13		0	2017-09-14 13:21:54	\N
1309	1306	Welcome (back) Social	PhotoCee (Martijn)	2017-09-06		0	2017-09-15 00:50:25	\N
1335	1232	Symposium: Made in Groningen	PhotoCee	2017-05-18	These pictures were made at the Symposium: Made in Groningen.	3	\N	\N
1330	1232	ExCee to Antwerp	ExCee	2017-04-25		0	2017-10-15 13:38:42	\N
1333	1306	Lovely moments not related to activities	Whoever submits lovely photos <3	2018-09-01	This album contains lovely photos of moments outside of activities.	0	2017-11-16 17:09:19	\N
1336	1306	2017 Staff BBQ	PhotoCee	2017-05-24	On the 24th of May members of Cover and staff of RUG got together for a nice BBQ.	0	2017-11-27 16:39:20	\N
1337	1232	Staff BBQ	PhotoCee	2017-05-24		3	2017-12-05 12:14:40	\N
1317	1315	Photo Assignment	Participants	2017-09-08	During the board game on the introduction camp, all teams had to play several committee games. Among which the PhotoCee game: depict one of the 26 pre-selected proverbs and send a picture of it to the game overseer. The results are displayed in this album.	0	2017-10-03 22:30:42	\N
1313	1306	Introductory Camp Heroes vs Villains	PhotoCee (Martijn & Yannick) + Participants (René, Lisa & others)	2017-09-08		0	2018-01-15 19:09:33	\N
1327	1325	Friday	PhotoCee	2017-10-13		0	2017-10-15 13:31:58	0
1329	1325	Sunday	PhotoCee	2017-10-15		0	2017-10-16 12:17:52	2
1328	1325	Saturday	PhotoCee	2017-10-14		0	2017-10-15 13:33:30	1
1332	1306	Visit TU Delft	Robin Twickler	2017-10-27		0	2017-10-27 23:57:31	\N
1334	1306	[CAREER] Get There: Energy Data Hub	PhotoCee	2017-11-20	Get There gave a talk and workshop for our members about energy distribution.	3	\N	\N
1314	1313	Friday	PhotoCee (Martijn & Yannick) + Participants (René, Lisa & others)	2017-09-08		0	2017-10-03 22:21:06	0
1316	1313	Sunday	PhotoCee (Martijn & Yannick) + Participants (René, Lisa & others)	2017-09-10		0	2017-10-03 22:23:04	2
1315	1313	Saturday	PhotoCee (Martijn & Yannick) + Participants (René, Lisa & others)	2017-09-09		0	2017-10-03 22:22:40	1
1308	1306	Regret Repository	PhotoCee	2018-09-09	This album is only available to members of Cover. If you still regret your pictures and want them to disappear forever, please contact the PhotoCee (photocee@svcover.nl).	1	2018-01-11 13:42:02	\N
1318	1306	Di(e)scover	PhotoCee	2017-09-20		0	2017-10-14 16:05:39	\N
1319	1306	TAD-Talk: Atos	PhotoCee	2017-09-21		3	2017-10-14 16:11:23	\N
1320	1306	Introduction camp Afterparty!	PhotoCee	2017-09-25		0	2017-11-04 01:16:25	\N
1322	1306	Disrupt the Social	PhotoCee	2017-10-04		0	2017-10-14 16:18:10	\N
1339	1306	Gala	PhotoCee	2017-12-21		0	2018-01-11 13:42:49	\N
1325	1306	Belsimpel.nl Hackathon	PhotoCee	2017-10-13		0	\N	\N
1312	1306	Constitutional General Assembly	PhotoCee	2017-09-18		0	2017-09-20 01:13:42	\N
1331	1306	Bocktober ThuNDr	Martijn Luinstra	2017-10-26		3	\N	\N
1340	1306	General Assembly	PhotoCee	2018-01-15		0	2018-01-25 12:14:03	\N
1341	0	PhotoCee Meetings	PhotoCee	1970-01-01		3	\N	\N
1342	1341	05-03-2018	PhotoCee	2018-03-05		3	2018-03-08 11:30:16	\N
1344	1306	General Assembly	PhotoCee	2018-03-19		0	2018-03-24 16:17:48	\N
1345	1306	MxCee trip to the USA	MxCee committee	2017-11-21		0	2018-03-29 15:37:40	\N
1346	1339	Photo Booth	PhotoCee	2017-12-21		0	2018-04-03 16:55:11	\N
1347	1306	Just a regular TAD, but with a new Cover camera	PhotoCee	2018-03-29		0	2018-04-04 15:34:12	\N
1343	1306	March Social	Floris, Fleur, Yannick	2018-03-07		0	2018-03-09 16:31:31	\N
1349	1306	Blacklight Neon Party	Yannick	2018-04-04		0	2018-04-12 17:24:56	\N
1348	1306	Member Weekend	Martijn Luinstra + René Mellema	2018-03-23		0	2018-04-04 15:44:19	\N
1338	1306	Ugly Christmas Sweater Social	PhotoCee	2017-12-06		0	2017-12-20 17:37:07	\N
1321	1306	Brainstorm with the Board	PhotoCee	2017-10-04		0	2017-10-14 16:14:55	\N
1350	1341	Meeting in noorderplantsoen	all	2018-05-07		3	2018-05-09 12:30:52	\N
1354	1306	June social	Floris	2018-06-06		0	2018-06-12 17:55:53	\N
1355	1306	General Assembly of June	Henry Maathuis	2018-06-04		0	2018-06-13 00:02:14	\N
1353	1306	2018 Staff BBQ	Henry Maathuis	2018-06-01		0	2018-06-02 22:24:03	\N
\.


--
-- Data for Name: foto_boeken_custom_visit; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_boeken_custom_visit (boek_id, lid_id, last_visit) FROM stdin;
\.


--
-- Data for Name: foto_boeken_visit; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_boeken_visit (boek_id, lid_id, last_visit) FROM stdin;
\.


--
-- Data for Name: foto_faces; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_faces (id, foto_id, x, y, w, h, lid_id, deleted, tagged_by, custom_label, cluster_id, tagged_on) FROM stdin;
\.


--
-- Data for Name: foto_hidden; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_hidden (foto_id, lid_id) FROM stdin;
\.


--
-- Data for Name: foto_likes; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_likes (foto_id, lid_id, liked_on) FROM stdin;
\.


--
-- Data for Name: foto_reacties; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_reacties (id, foto, auteur, reactie, date) FROM stdin;
\.


--
-- Data for Name: foto_reacties_likes; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.foto_reacties_likes (id, reactie_id, lid_id) FROM stdin;
\.


--
-- Data for Name: fotos; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.fotos (id, boek, beschrijving, added_on, width, height, filepath, filehash, created_on, sort_index, hidden) FROM stdin;
48637	1312		2017-09-20 01:13:42	3872	2592	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2680.jpg	5d7d046d	2017-09-18 19:33:17	\N	f
48638	1312		2017-09-20 01:13:42	3872	2592	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2683.jpg	be65a3e1	2017-09-18 19:33:58	\N	f
48639	1312		2017-09-20 01:13:42	3745	2507	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2690.jpg	96139339	2017-09-18 19:56:29	\N	f
48640	1312		2017-09-20 01:13:42	3631	2431	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2696.jpg	cb03db0f	2017-09-18 19:58:49	\N	f
48641	1312		2017-09-20 01:13:42	3512	2351	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2699.jpg	c7831d7c	2017-09-18 20:05:38	\N	f
48643	1312		2017-09-20 01:13:42	2125	3174	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2715.jpg	99bd7fc5	2017-09-18 20:21:11	\N	f
48644	1312		2017-09-20 01:13:42	3602	2411	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2716.jpg	53f6f890	2017-09-18 20:21:22	\N	f
48645	1312		2017-09-20 01:13:42	2194	3277	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2719.jpg	1617274d	2017-09-18 20:32:47	\N	f
48646	1312		2017-09-20 01:13:42	3583	2399	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2724.jpg	f203f20c	2017-09-18 20:33:51	\N	f
48647	1312		2017-09-20 01:13:42	2227	2227	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2725.jpg	d99d60e3	2017-09-18 20:47:55	\N	f
48648	1312		2017-09-20 01:13:42	3431	2297	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2750.jpg	001c44ec	2017-09-18 21:43:06	\N	f
48649	1312		2017-09-20 01:13:42	3667	2455	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2752.jpg	fb51027a	2017-09-18 21:43:41	\N	f
48650	1312		2017-09-20 01:13:42	2103	3141	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2755.jpg	e00d9d13	2017-09-18 21:44:48	\N	f
48651	1312		2017-09-20 01:13:42	3550	1997	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2756.jpg	c22ea47e	2017-09-18 21:45:03	\N	f
48652	1312		2017-09-20 01:13:42	3316	2220	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2766.jpg	71aea7d9	2017-09-18 21:48:38	\N	f
48653	1312		2017-09-20 01:13:42	3822	2559	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2767.jpg	7dbb408f	2017-09-18 21:49:26	\N	f
48654	1312		2017-09-20 01:13:42	3622	2425	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2772.jpg	48d97ca6	2017-09-18 21:50:50	\N	f
48655	1312		2017-09-20 01:13:42	1965	2935	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2783.jpg	589f5d45	2017-09-18 22:11:05	\N	f
48656	1312		2017-09-20 01:13:42	2329	3479	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2786.jpg	035391c4	2017-09-18 22:13:29	\N	f
48657	1312		2017-09-20 01:13:42	2044	3053	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2791.jpg	e7464616	2017-09-18 22:13:50	\N	f
48658	1312		2017-09-20 01:13:42	3872	2592	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2800.jpg	96c66dd0	2017-09-18 22:14:59	\N	f
48659	1312		2017-09-20 01:13:42	3740	2504	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2812.jpg	6299a1c0	2017-09-18 22:16:53	\N	f
48660	1312		2017-09-20 01:13:42	1913	2858	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2814.jpg	83b5074a	2017-09-18 22:17:36	\N	f
48661	1312		2017-09-20 01:13:42	3622	2425	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2826.jpg	470116fd	2017-09-18 23:28:35	\N	f
48544	1307		2017-09-14 13:21:50	5755	3837	fotos20172018/20170913FYCpubquiz/pubquizFYC-1.jpg	3167c9a8	2017-09-13 20:39:21	\N	f
48545	1307		2017-09-14 13:21:50	4393	2929	fotos20172018/20170913FYCpubquiz/pubquizFYC-2.jpg	433df6a1	2017-09-13 20:40:20	\N	f
48546	1307		2017-09-14 13:21:50	5824	3883	fotos20172018/20170913FYCpubquiz/pubquizFYC-3.jpg	01cca54c	2017-09-13 20:42:51	\N	f
48547	1307		2017-09-14 13:21:50	5683	3789	fotos20172018/20170913FYCpubquiz/pubquizFYC-4.jpg	9cde7be0	2017-09-13 20:47:35	\N	f
48548	1307		2017-09-14 13:21:50	5164	3443	fotos20172018/20170913FYCpubquiz/pubquizFYC-5.jpg	e9ce211e	2017-09-13 20:52:37	\N	f
48549	1307		2017-09-14 13:21:51	5024	3349	fotos20172018/20170913FYCpubquiz/pubquizFYC-6.jpg	eb91a6da	2017-09-13 20:53:59	\N	f
48550	1307		2017-09-14 13:21:51	5798	3865	fotos20172018/20170913FYCpubquiz/pubquizFYC-7.jpg	baa60173	2017-09-13 20:57:33	\N	f
48551	1307		2017-09-14 13:21:51	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-8.jpg	792a5322	2017-09-13 21:12:47	\N	f
48552	1307		2017-09-14 13:21:51	5188	3459	fotos20172018/20170913FYCpubquiz/pubquizFYC-9.jpg	cb6f61e6	2017-09-13 21:13:11	\N	f
48553	1307		2017-09-14 13:21:51	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-10.jpg	a6c189e6	2017-09-13 22:02:21	\N	f
48554	1307		2017-09-14 13:21:52	5745	3830	fotos20172018/20170913FYCpubquiz/pubquizFYC-11.jpg	d1bfd922	2017-09-13 22:02:37	\N	f
48555	1307		2017-09-14 13:21:52	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-12.jpg	7369240c	2017-09-13 22:03:00	\N	f
48556	1307		2017-09-14 13:21:52	5512	3675	fotos20172018/20170913FYCpubquiz/pubquizFYC-13.jpg	df1600c2	2017-09-13 22:03:18	\N	f
48557	1307		2017-09-14 13:21:52	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-14.jpg	be8d1b06	2017-09-13 22:03:37	\N	f
48558	1307		2017-09-14 13:21:52	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-15.jpg	3403f4db	2017-09-13 22:03:46	\N	f
48559	1307		2017-09-14 13:21:52	6000	3233	fotos20172018/20170913FYCpubquiz/pubquizFYC-16.jpg	ac33d03b	2017-09-13 22:06:14	\N	f
48560	1307		2017-09-14 13:21:53	2667	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-17.jpg	f821ca75	2017-09-13 22:06:32	\N	f
48561	1307		2017-09-14 13:21:53	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-18.jpg	ca08606c	2017-09-13 22:11:29	\N	f
48562	1307		2017-09-14 13:21:53	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-19.jpg	612e71af	2017-09-13 22:19:59	\N	f
48563	1307		2017-09-14 13:21:53	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-20.jpg	007805d3	2017-09-13 22:20:54	\N	f
48564	1307		2017-09-14 13:21:53	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-21.jpg	100f2a01	2017-09-13 22:23:19	\N	f
48565	1307		2017-09-14 13:21:53	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-22.jpg	20f92ca6	2017-09-13 22:23:49	\N	f
48566	1307		2017-09-14 13:21:54	6000	4000	fotos20172018/20170913FYCpubquiz/pubquizFYC-23.jpg	8ab4435c	2017-09-13 22:58:17	\N	f
48567	1307		2017-09-14 13:21:54	4639	3093	fotos20172018/20170913FYCpubquiz/pubquizFYC-24.jpg	4d5058ad	2017-09-13 23:03:06	\N	f
48568	1308		2017-09-15 00:49:20	5184	3456	fotos20172018/Regret_Repository/20170906-Borrel-Regret-Martijn-0988.jpg	a3643c32	2017-09-06 23:24:52	\N	f
48569	1308		2017-09-15 00:49:20	5184	3456	fotos20172018/Regret_Repository/20170907-Borrel-Regret-Martijn-1078.jpg	095a3d30	2017-09-07 00:38:07	\N	f
48570	1308		2017-09-15 00:49:20	3179	4769	fotos20172018/Regret_Repository/20170907-Borrel-Regret-Martijn-1143.jpg	acec82f3	2017-09-07 00:57:24	\N	f
48571	1308		2017-09-15 00:49:20	5184	3456	fotos20172018/Regret_Repository/20170907-Borrel-Regret-Martijn-1184.jpg	7057dc6c	2017-09-07 01:09:10	\N	f
48572	1309		2017-09-15 00:50:21	4552	3035	fotos20172018/20170906_Social/20170906-Borrel-Martijn-0984.jpg	90e5acda	2017-09-06 23:21:21	\N	f
48573	1309		2017-09-15 00:50:21	4102	2735	fotos20172018/20170906_Social/20170906-Borrel-Martijn-0985.jpg	0b751865	2017-09-06 23:22:17	\N	f
48574	1309		2017-09-15 00:50:21	3384	3384	fotos20172018/20170906_Social/20170906-Borrel-Martijn-0993.jpg	3b5b026e	2017-09-06 23:25:13	\N	f
48575	1309		2017-09-15 00:50:22	4940	3293	fotos20172018/20170906_Social/20170906-Borrel-Martijn-0994.jpg	7d54bb89	2017-09-06 23:28:00	\N	f
48576	1309		2017-09-15 00:50:22	3270	3270	fotos20172018/20170906_Social/20170906-Borrel-Martijn-1000.jpg	fa0aee50	2017-09-06 23:30:46	\N	f
48577	1309		2017-09-15 00:50:22	5005	3337	fotos20172018/20170906_Social/20170906-Borrel-Martijn-1007.jpg	8c900e04	2017-09-06 23:31:58	\N	f
48578	1309		2017-09-15 00:50:22	5184	3456	fotos20172018/20170906_Social/20170906-Borrel-Martijn-1008.jpg	0f207fd6	2017-09-06 23:32:15	\N	f
48579	1309		2017-09-15 00:50:22	4257	2838	fotos20172018/20170906_Social/20170906-Borrel-Martijn-1010.jpg	784c4fa2	2017-09-06 23:56:44	\N	f
48580	1309		2017-09-15 00:50:22	1818	2727	fotos20172018/20170906_Social/20170906-Borrel-Martijn-1012.jpg	e5cb612a	2017-09-06 23:57:44	\N	f
48581	1309		2017-09-15 00:50:22	4882	3255	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1019.jpg	387ba5ff	2017-09-07 00:06:05	\N	f
48582	1309		2017-09-15 00:50:22	4744	3163	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1025.jpg	51a7ec7f	2017-09-07 00:12:19	\N	f
48583	1309		2017-09-15 00:50:22	3456	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1030.jpg	8ba74518	2017-09-07 00:13:36	\N	f
48584	1309		2017-09-15 00:50:23	2304	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1035.jpg	d053c6d7	2017-09-07 00:25:49	\N	f
48585	1309		2017-09-15 00:50:23	4867	3245	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1048.jpg	825b190f	2017-09-07 00:27:25	\N	f
48586	1309		2017-09-15 00:50:23	3456	5184	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1055.jpg	bdb2270d	2017-09-07 00:27:46	\N	f
48587	1309		2017-09-15 00:50:23	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1060.jpg	e879e143	2017-09-07 00:28:22	\N	f
48588	1309		2017-09-15 00:50:23	3456	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1066.jpg	1419dbf1	2017-09-07 00:35:12	\N	f
48589	1309		2017-09-15 00:50:23	4330	2887	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1068.jpg	b7261d0c	2017-09-07 00:37:18	\N	f
48590	1309		2017-09-15 00:50:23	5038	3359	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1084.jpg	b4f3520f	2017-09-07 00:42:29	\N	f
48591	1309		2017-09-15 00:50:23	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1090.jpg	ffbbe2de	2017-09-07 00:42:54	\N	f
48592	1309		2017-09-15 00:50:24	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1092.jpg	ba0fcb88	2017-09-07 00:43:15	\N	f
48593	1309		2017-09-15 00:50:24	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1095.jpg	d9826043	2017-09-07 00:43:42	\N	f
48594	1309		2017-09-15 00:50:24	2293	3439	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1102.jpg	f20f1ef0	2017-09-07 00:45:59	\N	f
48595	1309		2017-09-15 00:50:24	4647	3098	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1105.jpg	8f8b749b	2017-09-07 00:46:29	\N	f
48596	1309		2017-09-15 00:50:24	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1125.jpg	63d586d7	2017-09-07 00:49:31	\N	f
48597	1309		2017-09-15 00:50:24	4672	3115	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1151.jpg	359ce3ea	2017-09-07 01:01:48	\N	f
48598	1309		2017-09-15 00:50:24	4941	3294	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1175.jpg	f51e8bd1	2017-09-07 01:04:35	\N	f
48599	1309		2017-09-15 00:50:24	5184	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1201.jpg	20c0006f	2017-09-07 01:12:40	\N	f
48600	1309		2017-09-15 00:50:24	3964	2643	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1208.jpg	ea466915	2017-09-07 01:13:27	\N	f
48601	1309		2017-09-15 00:50:25	4623	3082	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1217.jpg	3c07fceb	2017-09-07 01:16:08	\N	f
48602	1309		2017-09-15 00:50:25	3574	2383	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1221.jpg	a7eb5496	2017-09-07 01:18:02	\N	f
48603	1309		2017-09-15 00:50:25	2304	3456	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1237.jpg	19408e4c	2017-09-07 01:23:27	\N	f
48604	1309		2017-09-15 00:50:25	4477	2985	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1238.jpg	f67939e0	2017-09-07 01:23:56	\N	f
48605	1309		2017-09-15 00:50:25	3342	3342	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1245.jpg	c704e690	2017-09-07 01:28:20	\N	f
48606	1309		2017-09-15 00:50:25	4777	3185	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1253.jpg	0a87f369	2017-09-07 01:28:50	\N	f
48607	1309		2017-09-15 00:50:25	4647	3098	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1263.jpg	ba8758e7	2017-09-07 01:29:00	\N	f
48608	1309		2017-09-15 00:50:25	3245	3245	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1266.jpg	e93ea1f1	2017-09-07 01:29:35	\N	f
48609	1309		2017-09-15 00:50:25	4793	3195	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1270.jpg	4f08940b	2017-09-07 01:29:43	\N	f
48610	1309		2017-09-15 00:50:25	4566	3044	fotos20172018/20170906_Social/20170907-Borrel-Martijn-1285.jpg	1c921bc0	2017-09-07 01:40:43	\N	f
48611	1310		2017-09-18 00:40:39	4734	3156	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9596.jpg	c8065959	2017-09-11 19:28:01	\N	f
48612	1310		2017-09-18 00:40:39	3363	5044	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9598.jpg	f9e8c4f1	2017-09-11 19:28:24	\N	f
48613	1310		2017-09-18 00:40:39	3098	4647	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9602.jpg	6201a464	2017-09-11 19:28:37	\N	f
48614	1310		2017-09-18 00:40:40	5163	3442	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9605.jpg	ccdadb74	2017-09-11 19:29:31	\N	f
48615	1310		2017-09-18 00:40:40	5184	3456	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9606.jpg	69f0d0b0	2017-09-11 20:22:39	\N	f
48616	1310		2017-09-18 00:40:40	4680	3120	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9607.jpg	7326b66a	2017-09-11 20:22:48	\N	f
48617	1310		2017-09-18 00:40:40	4322	2881	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9608.jpg	beeeb69b	2017-09-11 20:23:07	\N	f
48618	1310		2017-09-18 00:40:40	5080	3387	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9611.jpg	ececf8cd	2017-09-11 20:29:34	\N	f
48619	1310		2017-09-18 00:40:40	5184	3456	fotos20172018/20170911_GeneralAssembly/20170911-GA-Martijn-9613.jpg	f4e19774	2017-09-11 21:05:12	\N	f
48620	1311		2017-09-18 00:58:57	2140	3210	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9461.jpg	99b8dae1	2017-06-06 19:24:59	\N	f
48621	1311		2017-09-18 00:58:57	4509	3006	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9465.jpg	e00cbda8	2017-06-06 19:26:59	\N	f
48622	1311		2017-09-18 00:58:57	4438	2959	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9475.jpg	5a2c7276	2017-06-06 20:33:52	\N	f
48623	1311		2017-09-18 00:58:57	4768	3179	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9478.jpg	a86ce530	2017-06-06 20:43:32	\N	f
48624	1311		2017-09-18 00:58:57	5155	3437	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9483.jpg	8c5bfacd	2017-06-06 20:44:29	\N	f
48625	1311		2017-09-18 00:58:57	5184	3456	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9486.jpg	1b101e58	2017-06-06 21:56:07	\N	f
48626	1311		2017-09-18 00:58:57	4991	3327	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9488.jpg	160f828b	2017-06-06 22:05:23	\N	f
48627	1311	Busted!	2017-09-18 00:58:57	3456	5184	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9496.jpg	7b152e5d	2017-06-06 22:07:41	\N	f
48628	1311		2017-09-18 00:58:57	4950	3300	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9502.jpg	4dcd58cc	2017-06-06 22:14:29	\N	f
48629	1311		2017-09-18 00:58:57	3207	2138	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9506.jpg	c5fb7fc2	2017-06-06 22:14:49	\N	f
48630	1311		2017-09-18 00:58:57	5184	3456	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9509.jpg	b2c8cb65	2017-06-06 22:51:50	\N	f
48631	1311		2017-09-18 00:58:57	4510	3007	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9515.jpg	6f69676e	2017-06-06 23:01:10	\N	f
48632	1311		2017-09-18 00:58:57	5184	3456	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9517.jpg	d051cf51	2017-06-06 23:01:34	\N	f
48633	1311		2017-09-18 00:58:57	4266	2844	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9533.jpg	2d069b4c	2017-06-06 23:12:26	\N	f
48634	1311		2017-09-18 00:58:57	5045	3363	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9550.jpg	54d340ed	2017-06-06 23:25:53	\N	f
48635	1311		2017-09-18 00:58:57	4202	2801	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9551.jpg	f75cd345	2017-06-06 23:27:28	\N	f
48636	1311		2017-09-18 00:58:57	5184	3456	fotos20162017/20170606_GeneralAssembly/20170606-GA-Martijn-9554.jpg	325c0996	2017-06-06 23:30:36	\N	f
48663	1312	🙍‍♂️🙋‍♂️🙋‍♀️🙋‍♂️🙋‍♂️🙋‍♀️	2017-09-20 01:13:42	3794	2540	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2830.jpg	944d31a7	2017-09-18 23:29:23	\N	f
48669	1308		2017-09-20 01:17:06	3525	2360	fotos20172018/Regret_Repository/20170918-ConstitutionalGA-REGRET-Martijn-2736.jpg	28255b1c	2017-09-18 21:17:38	\N	f
48662	1312	🙋‍♀️🐻	2017-09-20 01:13:42	2592	3872	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2828.jpg	b99d20df	2017-09-18 23:29:09	\N	f
48664	1312	🙋‍♂️🙆‍♂️🙋‍♂️🙋‍♂️🙋‍♂️	2017-09-20 01:13:42	3870	2591	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2832.jpg	509e2d31	2017-09-18 23:29:44	\N	f
48666	1312	The aftermath 🎈	2017-09-20 01:13:42	3609	2416	fotos20172018/20170918_ConstitutionalGeneralAssembly/NewBoard-1.jpg	3e0e2ac4	2017-09-19 10:24:16	\N	f
48667	1312	🎈🎈🎈🎈🎈	2017-09-20 01:13:42	3664	2453	fotos20172018/20170918_ConstitutionalGeneralAssembly/NewBoard-2.jpg	dbb4f0ec	2017-09-19 10:25:17	\N	f
48665	1312	👨‍💻👩‍💼👨‍💼👨‍💼👩‍💼	2017-09-20 01:13:42	3797	2542	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170919-ConstitutionalGA-Martijn-2835.jpg	f6bb9c41	2017-09-19 00:26:43	\N	f
48668	1312	🎈🎈🎈🎈🎈🎈🎈🎈🎈🎈🎈🎈🎈🎈	2017-09-20 01:13:42	3954	1728	fotos20172018/20170918_ConstitutionalGeneralAssembly/NewBoard-3.jpg	c3bdce88	2017-09-19 10:36:45	\N	f
48642	1312	🙆‍♂️	2017-09-20 01:13:42	3858	2583	fotos20172018/20170918_ConstitutionalGeneralAssembly/20170918-ConstitutionalGA-Martijn-2714.jpg	3bd150b1	2017-09-18 20:19:50	\N	f
48670	1314		2017-10-03 22:21:02	5097	2867	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1302.jpg	e01a2517	2017-09-08 15:19:14	\N	f
48671	1314		2017-10-03 22:21:02	3415	5122	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1308.jpg	ac6f997e	2017-09-08 15:20:14	\N	f
48672	1314		2017-10-03 22:21:02	4744	3163	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1313.jpg	2dc49a71	2017-09-08 15:22:25	\N	f
48673	1314		2017-10-03 22:21:02	4935	3290	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1317.jpg	08f82ef9	2017-09-08 15:25:02	\N	f
48674	1314		2017-10-03 22:21:02	5011	3341	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1321.jpg	673c8235	2017-09-08 15:37:39	\N	f
48675	1314		2017-10-03 22:21:02	3161	4741	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1329.jpg	ae945f7e	2017-09-08 15:43:15	\N	f
48676	1314		2017-10-03 22:21:02	3896	2597	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1331.jpg	0f1cd9c9	2017-09-08 15:46:50	\N	f
48677	1314		2017-10-03 22:21:02	3456	5184	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1338.jpg	6f3c4368	2017-09-08 16:02:57	\N	f
48678	1314		2017-10-03 22:21:02	5068	3379	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1340.jpg	36ddee81	2017-09-08 16:07:25	\N	f
48679	1314		2017-10-03 22:21:02	5184	2916	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1352.jpg	7cb3b380	2017-09-08 17:52:57	\N	f
48680	1314		2017-10-03 22:21:02	5089	3393	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1359.jpg	385d0069	2017-09-08 17:54:48	\N	f
48681	1314		2017-10-03 22:21:02	3850	2567	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7333.jpg	31a21349	2017-09-08 17:57:10	\N	f
48682	1314		2017-10-03 22:21:02	3373	2249	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7335.jpg	60119a63	2017-09-08 17:59:35	\N	f
48683	1314		2017-10-03 22:21:02	5035	3357	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1374.jpg	52a5116c	2017-09-08 18:00:13	\N	f
48684	1314		2017-10-03 22:21:02	4744	3163	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1375.jpg	b55f2a48	2017-09-08 18:00:31	\N	f
48685	1314		2017-10-03 22:21:02	5134	3423	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1386.jpg	86220114	2017-09-08 18:04:04	\N	f
48686	1314		2017-10-03 22:21:02	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1391.jpg	a7505749	2017-09-08 18:06:46	\N	f
48687	1314		2017-10-03 22:21:02	5083	3389	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1398.jpg	c420eda1	2017-09-08 18:09:03	\N	f
48688	1314		2017-10-03 22:21:02	5114	3409	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1402.jpg	3056eac7	2017-09-08 18:09:41	\N	f
48689	1314		2017-10-03 22:21:02	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1407.jpg	43cf9ecd	2017-09-08 18:10:02	\N	f
48690	1314		2017-10-03 22:21:02	4769	3179	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1408.jpg	1e34451c	2017-09-08 18:10:11	\N	f
48691	1314		2017-10-03 22:21:02	1747	2620	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7355.jpg	657bbb04	2017-09-08 18:10:23	\N	f
48692	1314		2017-10-03 22:21:02	4740	3160	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1413.jpg	fa97106a	2017-09-08 18:10:26	\N	f
48693	1314		2017-10-03 22:21:02	3429	3429	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1416.jpg	47760ebb	2017-09-08 18:10:32	\N	f
48694	1314		2017-10-03 22:21:03	4675	3117	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1420.jpg	88b594cf	2017-09-08 18:11:17	\N	f
48695	1314		2017-10-03 22:21:03	4720	3147	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1421.jpg	08bd80d4	2017-09-08 18:13:00	\N	f
48696	1314		2017-10-03 22:21:03	3877	2181	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7357.jpg	94279c68	2017-09-08 18:16:05	\N	f
48697	1314		2017-10-03 22:21:03	4929	3286	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1427.jpg	3bf6aef1	2017-09-08 18:16:31	\N	f
48698	1314		2017-10-03 22:21:03	4837	3225	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1433.jpg	715a0a46	2017-09-08 18:17:19	\N	f
48699	1314		2017-10-03 22:21:03	2294	3441	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1436.jpg	5d2101e7	2017-09-08 18:17:37	\N	f
48700	1314		2017-10-03 22:21:03	3689	2459	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7361.jpg	13730a0b	2017-09-08 18:17:40	\N	f
48701	1314		2017-10-03 22:21:03	4230	2820	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7366.jpg	2f8cc26a	2017-09-08 18:19:54	\N	f
48702	1314		2017-10-03 22:21:03	5074	3383	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1452.jpg	e621c95d	2017-09-08 18:20:40	\N	f
48703	1314		2017-10-03 22:21:03	5065	3377	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1454.jpg	8754400e	2017-09-08 18:20:48	\N	f
48704	1314		2017-10-03 22:21:03	5126	3417	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1456.jpg	0e7d34e3	2017-09-08 18:21:00	\N	f
48705	1314		2017-10-03 22:21:03	5005	3337	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1457.jpg	a6fa5347	2017-09-08 18:21:09	\N	f
48706	1314		2017-10-03 22:21:03	5073	3382	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1460.jpg	b8ff993c	2017-09-08 18:21:16	\N	f
48707	1314		2017-10-03 22:21:03	5134	3423	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1463.jpg	d3cf800d	2017-09-08 18:21:35	\N	f
48708	1314		2017-10-03 22:21:03	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1468.jpg	7bc51a7b	2017-09-08 18:23:01	\N	f
48709	1314		2017-10-03 22:21:03	3107	4660	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1473.jpg	d702ae11	2017-09-08 18:24:40	\N	f
48710	1314		2017-10-03 22:21:03	3448	2299	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7389.jpg	9520af1e	2017-09-08 18:25:18	\N	f
48711	1314		2017-10-03 22:21:03	3456	5184	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1476.jpg	2d84e6f3	2017-09-08 18:25:56	\N	f
48712	1314		2017-10-03 22:21:03	3342	5013	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1477.jpg	284a4772	2017-09-08 18:26:11	\N	f
48713	1314		2017-10-03 22:21:03	4232	2381	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7398.jpg	b7cdddb7	2017-09-08 18:26:24	\N	f
48714	1314		2017-10-03 22:21:03	5059	3373	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1479.jpg	2a4ddb05	2017-09-08 18:26:31	\N	f
48715	1314		2017-10-03 22:21:03	2211	3316	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7403.jpg	8ce50ef6	2017-09-08 18:28:43	\N	f
48716	1314		2017-10-03 22:21:03	4875	3250	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1483.jpg	8853ff38	2017-09-08 18:31:08	\N	f
48717	1314		2017-10-03 22:21:03	3910	2607	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7406.jpg	bf13192e	2017-09-08 18:33:17	\N	f
48718	1314		2017-10-03 22:21:03	4944	3296	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1489.jpg	e5ca8b80	2017-09-08 18:33:21	\N	f
48719	1314		2017-10-03 22:21:03	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1510.jpg	a529657e	2017-09-08 20:12:52	\N	f
48720	1314		2017-10-03 22:21:03	5184	2916	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1512.jpg	8e1293b1	2017-09-08 20:13:04	\N	f
48721	1314		2017-10-03 22:21:03	4879	3253	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1523.jpg	3e5ea524	2017-09-08 20:26:01	\N	f
48722	1314		2017-10-03 22:21:03	4696	3131	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1525.jpg	34f401d7	2017-09-08 20:26:39	\N	f
48723	1314		2017-10-03 22:21:03	5104	3403	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1528.jpg	d0626180	2017-09-08 20:27:26	\N	f
48724	1314		2017-10-03 22:21:03	5134	3423	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1530.jpg	b1c1c813	2017-09-08 20:29:22	\N	f
48725	1314		2017-10-03 22:21:03	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1531.jpg	d94f586a	2017-09-08 20:29:33	\N	f
48726	1314		2017-10-03 22:21:03	4691	3127	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1536.jpg	2eb2b575	2017-09-08 20:44:16	\N	f
48727	1314		2017-10-03 22:21:03	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1538.jpg	e293d80c	2017-09-08 20:44:28	\N	f
48728	1314		2017-10-03 22:21:04	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1539.jpg	bee1f797	2017-09-08 20:44:35	\N	f
48729	1314		2017-10-03 22:21:04	5121	3414	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1542.jpg	5d13772c	2017-09-08 20:46:29	\N	f
48730	1314		2017-10-03 22:21:04	4891	3261	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1551.jpg	30484007	2017-09-08 20:49:58	\N	f
48731	1314		2017-10-03 22:21:04	4754	3169	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1555.jpg	ed7993c4	2017-09-08 20:50:59	\N	f
48732	1314		2017-10-03 22:21:04	4552	3035	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1562.jpg	e34db017	2017-09-08 20:51:46	\N	f
48733	1314		2017-10-03 22:21:04	3810	2540	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7447.jpg	cd4b6fd4	2017-09-08 21:06:21	\N	f
48734	1314		2017-10-03 22:21:04	4272	2848	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7453.jpg	98351372	2017-09-08 21:07:47	\N	f
48735	1314		2017-10-03 22:21:04	4215	2810	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7460.jpg	d0ef6922	2017-09-08 21:20:58	\N	f
48736	1314		2017-10-03 22:21:04	2848	2848	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7463.jpg	3ebaf481	2017-09-08 21:36:47	\N	f
48737	1314		2017-10-03 22:21:04	4920	3280	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1578.jpg	350e8de1	2017-09-08 21:45:56	\N	f
48738	1314		2017-10-03 22:21:04	4272	2848	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7505.jpg	5307a89a	2017-09-08 22:00:28	\N	f
48739	1314		2017-10-03 22:21:04	2848	4272	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7509.jpg	684419eb	2017-09-08 22:10:50	\N	f
48740	1314		2017-10-03 22:21:04	2848	4272	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7514.jpg	f7435d21	2017-09-08 22:10:55	\N	f
48741	1314		2017-10-03 22:21:04	4173	2397	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7524.jpg	30dc8aca	2017-09-08 22:13:28	\N	f
48742	1314		2017-10-03 22:21:04	3304	2203	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7534.jpg	54c8d22c	2017-09-08 22:14:44	\N	f
48743	1314		2017-10-03 22:21:04	3689	2459	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7536.jpg	c1cc5473	2017-09-08 22:15:10	\N	f
48744	1314		2017-10-03 22:21:04	3387	2258	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7547.jpg	15a37fbd	2017-09-08 22:18:24	\N	f
48745	1314		2017-10-03 22:21:04	3961	2228	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7552.jpg	fd8b30b1	2017-09-08 22:19:37	\N	f
48746	1314		2017-10-03 22:21:04	3857	2571	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7570.jpg	8f3592d4	2017-09-08 22:29:22	\N	f
48747	1314		2017-10-03 22:21:04	5132	2887	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1602.jpg	40312ff1	2017-09-08 22:30:45	\N	f
48748	1314		2017-10-03 22:21:04	4228	2819	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1615.jpg	f9d672f2	2017-09-08 22:31:52	\N	f
48749	1314		2017-10-03 22:21:04	4040	2326	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1624.jpg	4735c96d	2017-09-08 22:41:13	\N	f
48750	1314		2017-10-03 22:21:04	3082	4623	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1644.jpg	4b5318b9	2017-09-08 22:43:30	\N	f
48751	1314		2017-10-03 22:21:04	3115	4672	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1671.jpg	18d72e2f	2017-09-08 22:46:39	\N	f
48752	1314		2017-10-03 22:21:04	4006	2671	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1672.jpg	cb63b8da	2017-09-08 22:46:45	\N	f
48753	1314		2017-10-03 22:21:04	4272	2848	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7640.jpg	24292a8f	2017-09-08 22:50:11	\N	f
48754	1314		2017-10-03 22:21:04	3282	4923	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1688.jpg	0b8f3ee4	2017-09-08 22:55:52	\N	f
48755	1314		2017-10-03 22:21:04	4934	3289	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1709.jpg	60166892	2017-09-08 22:57:52	\N	f
48756	1314		2017-10-03 22:21:04	3890	2593	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7698.jpg	a069c2a2	2017-09-08 23:01:21	\N	f
48757	1314		2017-10-03 22:21:04	3858	2572	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7699.jpg	b7d33ea6	2017-09-08 23:01:26	\N	f
48758	1314		2017-10-03 22:21:05	3790	2527	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7706.jpg	477300ba	2017-09-08 23:02:50	\N	f
48759	1314		2017-10-03 22:21:05	2633	3950	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7713.jpg	0c9ef054	2017-09-08 23:04:16	\N	f
48760	1314		2017-10-03 22:21:05	4272	2848	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7722.jpg	83caf3a4	2017-09-08 23:05:54	\N	f
48761	1314		2017-10-03 22:21:05	4816	3211	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1712.jpg	8bbd3622	2017-09-08 23:06:38	\N	f
48762	1314		2017-10-03 22:21:05	4211	2369	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7729.jpg	9d53ebb3	2017-09-08 23:07:38	\N	f
48763	1314		2017-10-03 22:21:05	4449	2966	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-1718.jpg	56858a5c	2017-09-08 23:07:44	\N	f
48764	1314		2017-10-03 22:21:05	3247	2165	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7732.jpg	9556e7e8	2017-09-08 23:08:44	\N	f
48765	1314		2017-10-03 22:21:05	2829	2829	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7733.jpg	64893b09	2017-09-08 23:09:43	\N	f
48766	1314		2017-10-03 22:21:05	3642	2428	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7736.jpg	1f754df6	2017-09-08 23:10:01	\N	f
48767	1314		2017-10-03 22:21:05	1801	2701	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7747.jpg	6cd80d16	2017-09-08 23:13:43	\N	f
48768	1314		2017-10-03 22:21:05	2527	3790	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7752.jpg	75f41652	2017-09-08 23:15:34	\N	f
48769	1314		2017-10-03 22:21:05	3408	2272	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7801.jpg	ab7df71c	2017-09-08 23:43:57	\N	f
48770	1314		2017-10-03 22:21:05	4121	2318	fotos20172018/20170908-10_IntroCamp/Friday/20170908-IntroCamp-Martijn-7805.jpg	8717b623	2017-09-08 23:44:50	\N	f
48771	1314		2017-10-03 22:21:05	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1758.jpg	b56b5559	2017-09-09 00:03:34	\N	f
48772	1314		2017-10-03 22:21:05	3891	2594	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1763.jpg	f89001cf	2017-09-09 00:04:51	\N	f
48773	1314		2017-10-03 22:21:05	4016	2677	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1765.jpg	17a08bf6	2017-09-09 00:05:18	\N	f
48774	1314		2017-10-03 22:21:05	4574	3049	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1767.jpg	afb933a7	2017-09-09 00:05:49	\N	f
48775	1314		2017-10-03 22:21:05	4062	2708	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1771.jpg	dd7b1074	2017-09-09 00:07:48	\N	f
48776	1314		2017-10-03 22:21:05	4291	2861	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1772.jpg	d00de630	2017-09-09 00:08:57	\N	f
48777	1314		2017-10-03 22:21:05	4159	2773	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1774.jpg	5189236a	2017-09-09 00:13:58	\N	f
48778	1314		2017-10-03 22:21:05	4428	2952	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1780.jpg	95b56847	2017-09-09 00:16:20	\N	f
48779	1314		2017-10-03 22:21:05	4498	2999	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1807.jpg	ca03e4cf	2017-09-09 00:26:17	\N	f
48780	1314		2017-10-03 22:21:05	4354	2903	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1815.jpg	ce70ddd7	2017-09-09 00:39:23	\N	f
48781	1314		2017-10-03 22:21:05	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1822.jpg	6d7f4693	2017-09-09 00:41:06	\N	f
48782	1314		2017-10-03 22:21:06	5184	3456	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1828.jpg	ad4aeb33	2017-09-09 00:41:20	\N	f
48783	1314		2017-10-03 22:21:06	4183	2789	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1834.jpg	70fcbd59	2017-09-09 00:42:06	\N	f
48784	1314		2017-10-03 22:21:06	4111	2741	fotos20172018/20170908-10_IntroCamp/Friday/20170909-IntroCamp-Martijn-1839.jpg	0dcb7d89	2017-09-09 00:50:03	\N	f
48786	1315		2017-10-03 22:22:34	2177	3265	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1858.jpg	f556dce6	2017-09-09 10:09:07	1	f
48787	1315		2017-10-03 22:22:34	4483	2989	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1861.jpg	7c7ffbe1	2017-09-09 10:09:22	2	f
48788	1315		2017-10-03 22:22:34	2283	3424	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1862.jpg	99c85fd9	2017-09-09 10:09:27	3	f
48789	1315		2017-10-03 22:22:34	2230	3345	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1864.jpg	ba3f354b	2017-09-09 10:09:31	4	f
48790	1315		2017-10-03 22:22:35	4808	3205	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1865.jpg	bb4d2219	2017-09-09 10:10:16	5	f
48791	1315		2017-10-03 22:22:35	4768	3179	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1867.jpg	6a941fed	2017-09-09 10:10:19	6	f
48792	1315		2017-10-03 22:22:35	4743	3162	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1870.jpg	1f4231b2	2017-09-09 10:16:49	7	f
48793	1315		2017-10-03 22:22:35	4989	3326	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1874.jpg	a6dc914b	2017-09-09 10:17:52	8	f
48794	1315		2017-10-03 22:22:35	4599	3066	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1876.jpg	9fd7f909	2017-09-09 10:19:54	9	f
48795	1315		2017-10-03 22:22:35	4924	2770	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1880.jpg	425a11fc	2017-09-09 11:06:44	10	f
48796	1315		2017-10-03 22:22:35	4374	2916	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1883.jpg	a3d22983	2017-09-09 11:07:27	11	f
48797	1315		2017-10-03 22:22:35	4515	3010	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1887.jpg	9ba02c9b	2017-09-09 11:09:07	12	f
48798	1315		2017-10-03 22:22:35	4574	3049	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1888.jpg	8f440893	2017-09-09 11:12:34	13	f
48799	1315		2017-10-03 22:22:35	4272	2403	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7819.jpg	ecf88efa	2017-09-09 11:53:10	14	f
48802	1315		2017-10-03 22:22:35	4477	2985	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1901.jpg	a9b6f513	2017-09-09 12:10:01	18	f
48803	1315		2017-10-03 22:22:35	4322	2881	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1903.jpg	a9a792e9	2017-09-09 12:11:08	19	f
48804	1315		2017-10-03 22:22:35	5092	3395	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1908.jpg	50b8386e	2017-09-09 12:11:57	20	f
48805	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1912.jpg	7b093f35	2017-09-09 12:12:46	21	f
48806	1315		2017-10-03 22:22:35	2865	1910	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7831.jpg	6fb6becd	2017-09-09 12:12:51	22	f
48807	1315		2017-10-03 22:22:35	4860	3240	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1915.jpg	f146be53	2017-09-09 12:13:53	23	f
48808	1315		2017-10-03 22:22:35	4116	2744	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7838.jpg	ba20b9b6	2017-09-09 12:14:24	24	f
48809	1315		2017-10-03 22:22:35	3769	2513	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7843.jpg	59aa2aaf	2017-09-09 12:15:55	25	f
48810	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1916.jpg	41fdab2f	2017-09-09 12:16:10	26	f
48811	1315		2017-10-03 22:22:35	2284	2284	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7846.jpg	5318b487	2017-09-09 12:17:15	27	f
48812	1315		2017-10-03 22:22:35	3453	2312	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2496.jpg	ef20e0f6	2017-09-09 12:17:40	28	f
48813	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1920.jpg	a1eb2d63	2017-09-09 12:18:15	29	f
48814	1315		2017-10-03 22:22:35	3198	2141	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2497.jpg	94c2508e	2017-09-09 12:18:26	30	f
48815	1315		2017-10-03 22:22:35	3397	3397	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1923.jpg	9c63cd1c	2017-09-09 12:19:15	31	f
48816	1315		2017-10-03 22:22:35	3146	2106	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2500.jpg	f044a59c	2017-09-09 12:19:35	32	f
48817	1315		2017-10-03 22:22:35	2608	3912	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7854.jpg	64d89f17	2017-09-09 12:19:50	33	f
48818	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1936.jpg	25eac3d7	2017-09-09 12:26:49	34	f
48819	1315		2017-10-03 22:22:35	4550	3033	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1944.jpg	9d32d6ff	2017-09-09 12:27:37	35	f
48820	1315		2017-10-03 22:22:35	4762	3175	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1946.jpg	6bce442f	2017-09-09 12:28:53	36	f
48821	1315		2017-10-03 22:22:35	4801	3201	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1948.jpg	5fb5410a	2017-09-09 12:29:36	37	f
48822	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1950.jpg	837c0db8	2017-09-09 12:30:00	38	f
48823	1315		2017-10-03 22:22:35	4642	3095	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1958.jpg	b6538a16	2017-09-09 12:33:25	39	f
48824	1315		2017-10-03 22:22:35	3663	2452	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2519.jpg	26a9c5f9	2017-09-09 12:39:10	40	f
48825	1315		2017-10-03 22:22:35	4918	3279	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1960.jpg	41b1c6d4	2017-09-09 12:39:12	41	f
48826	1315		2017-10-03 22:22:35	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1964.jpg	2c2c6456	2017-09-09 12:39:48	42	f
48827	1315		2017-10-03 22:22:35	5073	3382	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1966.jpg	f6f963b2	2017-09-09 12:40:39	43	f
48828	1315		2017-10-03 22:22:36	4932	3288	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1973.jpg	831e0dda	2017-09-09 12:42:42	44	f
48829	1315		2017-10-03 22:22:36	3839	2570	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2525.jpg	e0677d02	2017-09-09 12:42:46	45	f
48830	1315		2017-10-03 22:22:36	4696	3131	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1974.jpg	61fdc733	2017-09-09 12:42:59	46	f
48831	1315		2017-10-03 22:22:36	2304	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1978.jpg	e5d256c6	2017-09-09 12:43:49	47	f
48832	1315		2017-10-03 22:22:36	4131	2324	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7876.jpg	fd8fe2ff	2017-09-09 12:45:16	48	f
48833	1315		2017-10-03 22:22:36	1457	2177	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2532.jpg	e979e4c4	2017-09-09 12:49:42	49	f
48834	1315		2017-10-03 22:22:36	2832	1896	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2535.jpg	699e4011	2017-09-09 12:50:11	50	f
48835	1315		2017-10-03 22:22:36	3471	2324	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2536.jpg	412a985c	2017-09-09 12:52:19	51	f
48836	1315		2017-10-03 22:22:36	4272	2848	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7882.jpg	fec7dde6	2017-09-09 12:55:55	52	f
48837	1315		2017-10-03 22:22:36	4040	2693	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7884.jpg	26400f36	2017-09-09 13:00:53	53	f
48838	1315		2017-10-03 22:22:36	3872	2592	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2539.jpg	a3beb72d	2017-09-09 13:03:13	54	f
48840	1315		2017-10-03 22:22:36	3485	2333	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2543.jpg	60a91414	2017-09-09 13:05:14	56	f
48841	1315		2017-10-03 22:22:36	3289	2202	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2545.jpg	38924d60	2017-09-09 13:11:51	58	f
48843	1315		2017-10-03 22:22:36	3588	2392	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7903.jpg	0a48d004	2017-09-09 14:59:56	61	f
48844	1315		2017-10-03 22:22:36	1735	2592	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2566.jpg	32a2cf12	2017-09-09 15:08:17	62	f
48801	1315		2017-10-03 22:22:35	4899	3266	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1896.jpg	4f6232b3	2017-09-09 12:08:10	17	f
48842	1315		2017-10-03 22:22:36	4272	2848	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7888.jpg	a643bb37	2017-09-09 13:24:58	60	f
48848	1315		2017-10-03 22:22:36	5142	3428	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2021.jpg	3642535c	2017-09-09 15:12:37	66	f
48849	1315		2017-10-03 22:22:36	4971	3314	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2023.jpg	fb8e91e2	2017-09-09 15:13:26	67	f
48850	1315		2017-10-03 22:22:36	3456	5184	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2027.jpg	e61dc7ed	2017-09-09 15:13:47	68	f
48851	1315		2017-10-03 22:22:36	3669	2446	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7921.jpg	dcea8e79	2017-09-09 15:14:43	69	f
48852	1315		2017-10-03 22:22:36	4224	2816	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2033.jpg	b3aa828e	2017-09-09 15:15:02	70	f
48853	1315		2017-10-03 22:22:36	2592	3872	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2571.jpg	bb1b9dd4	2017-09-09 15:15:03	71	f
48854	1315		2017-10-03 22:22:36	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2039.jpg	f0bb9fd3	2017-09-09 15:15:24	72	f
48855	1315		2017-10-03 22:22:36	3720	2490	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2574.jpg	87e12955	2017-09-09 15:16:05	73	f
48856	1315		2017-10-03 22:22:36	2275	3398	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2575.jpg	36c20323	2017-09-09 15:17:08	74	f
48857	1315		2017-10-03 22:22:36	5131	3421	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2044.jpg	11532259	2017-09-09 15:17:23	75	f
48858	1315		2017-10-03 22:22:36	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2047.jpg	a84c3f9d	2017-09-09 15:18:32	76	f
48859	1315		2017-10-03 22:22:36	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2053.jpg	efa2c8c1	2017-09-09 15:19:52	77	f
48860	1315		2017-10-03 22:22:36	4030	2687	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7927.jpg	e3bf5fbf	2017-09-09 15:20:39	78	f
48861	1315		2017-10-03 22:22:36	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2063.jpg	558c7c4d	2017-09-09 15:21:23	79	f
48862	1315		2017-10-03 22:22:36	2871	4306	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2069.jpg	4a448a22	2017-09-09 15:21:46	80	f
48863	1315		2017-10-03 22:22:36	2338	3493	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2579.jpg	9e87637c	2017-09-09 15:22:07	81	f
48864	1315		2017-10-03 22:22:36	4891	3261	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2074.jpg	4d2e9da5	2017-09-09 15:22:47	82	f
48865	1315		2017-10-03 22:22:36	3816	2147	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7928.jpg	88277afa	2017-09-09 15:22:49	83	f
48866	1315		2017-10-03 22:22:36	2448	1632	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7932.jpg	dc3b0e2a	2017-09-09 15:23:31	84	f
48867	1315		2017-10-03 22:22:36	4237	2825	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2086.jpg	ec282dc8	2017-09-09 15:23:31	85	f
48868	1315		2017-10-03 22:22:36	3223	4834	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2097.jpg	45e1aede	2017-09-09 15:23:59	86	f
48869	1315		2017-10-03 22:22:36	3146	2097	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7935.jpg	ce322732	2017-09-09 15:24:21	87	f
48870	1315		2017-10-03 22:22:36	2533	1689	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7936.jpg	ac6bbd47	2017-09-09 15:24:28	88	f
48871	1315		2017-10-03 22:22:37	2749	4124	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2100.jpg	8b33bbc4	2017-09-09 15:24:39	89	f
48872	1315		2017-10-03 22:22:37	2232	3334	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2584.jpg	df46384e	2017-09-09 15:25:45	90	f
48873	1315		2017-10-03 22:22:37	5071	3381	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2102.jpg	09e3ff5b	2017-09-09 15:26:13	91	f
48874	1315		2017-10-03 22:22:37	3744	2506	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2589.jpg	58ebc2b5	2017-09-09 15:27:36	92	f
48875	1315		2017-10-03 22:22:37	3193	2129	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7943.jpg	7280f58b	2017-09-09 15:28:23	93	f
48876	1315		2017-10-03 22:22:37	3547	2365	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7949.jpg	875ee22a	2017-09-09 15:33:20	94	f
48877	1315		2017-10-03 22:22:37	3834	2556	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7953.jpg	fb9ee01c	2017-09-09 15:34:47	95	f
48878	1315		2017-10-03 22:22:37	3729	2486	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7956.jpg	7a4fd74b	2017-09-09 15:35:30	96	f
48879	1315		2017-10-03 22:22:37	4150	2767	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7960.jpg	0ef5e18c	2017-09-09 15:36:11	97	f
48880	1315		2017-10-03 22:22:37	3543	2372	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2601.jpg	6c42051a	2017-09-09 15:36:23	98	f
48881	1315		2017-10-03 22:22:37	3200	2142	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2611.jpg	a2cc6a09	2017-09-09 15:37:46	99	f
48882	1315		2017-10-03 22:22:37	2282	3409	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2614.jpg	3df70aa9	2017-09-09 15:38:54	100	f
48883	1315		2017-10-03 22:22:37	2513	2513	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7972.jpg	18d40479	2017-09-09 15:40:27	101	f
48884	1315		2017-10-03 22:22:37	3588	2392	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7994.jpg	a51d224f	2017-09-09 15:44:45	102	f
48885	1315		2017-10-03 22:22:37	3052	2035	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8004.jpg	3257233f	2017-09-09 15:46:15	103	f
48886	1315		2017-10-03 22:22:37	2764	4147	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2123.jpg	51031f4c	2017-09-09 15:48:02	104	f
48887	1315		2017-10-03 22:22:37	3247	4871	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2126.jpg	280499b2	2017-09-09 15:48:13	105	f
48888	1315		2017-10-03 22:22:37	4447	2965	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2127.jpg	804de995	2017-09-09 15:48:32	106	f
48889	1315		2017-10-03 22:22:37	5076	3384	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2136.jpg	9d5eb2f3	2017-09-09 15:48:45	107	f
48846	1315		2017-10-03 22:22:36	3640	2437	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2569.jpg	9cb462b4	2017-09-09 15:11:22	64	f
48847	1315		2017-10-03 22:22:36	3347	2231	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-7909.jpg	7756de86	2017-09-09 15:12:36	65	f
48893	1315		2017-10-03 22:22:37	3370	5055	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2141.jpg	52c2ea1e	2017-09-09 15:49:30	111	f
48894	1315		2017-10-03 22:22:37	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2145.jpg	7b6bc069	2017-09-09 15:50:23	112	f
48895	1315		2017-10-03 22:22:37	4056	2704	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2151.jpg	025a005c	2017-09-09 15:53:02	113	f
48896	1315		2017-10-03 22:22:37	4272	2848	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8019.jpg	8d540769	2017-09-09 15:53:27	114	f
48897	1315		2017-10-03 22:22:37	4720	3147	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2153.jpg	4d78be7a	2017-09-09 15:53:42	115	f
48898	1315		2017-10-03 22:22:37	4281	2854	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2154.jpg	34d5db2f	2017-09-09 15:53:44	116	f
48899	1315		2017-10-03 22:22:37	2516	1677	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8027.jpg	f401c5c1	2017-09-09 15:54:18	117	f
48900	1315		2017-10-03 22:22:37	3181	2121	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8030.jpg	b2a8d4d5	2017-09-09 15:54:40	118	f
48901	1315		2017-10-03 22:22:37	2725	4087	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2166.jpg	14f4dce0	2017-09-09 15:54:58	119	f
48902	1315		2017-10-03 22:22:37	4309	2873	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2168.jpg	3ed614fa	2017-09-09 15:55:08	120	f
48903	1315		2017-10-03 22:22:37	2302	1535	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8041.jpg	eb76bbd9	2017-09-09 15:55:37	121	f
48904	1315		2017-10-03 22:22:37	3186	2124	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8048.jpg	a37a7ed6	2017-09-09 15:57:36	122	f
48905	1315		2017-10-03 22:22:37	4195	2797	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8049.jpg	37af38f4	2017-09-09 15:58:05	123	f
48906	1315		2017-10-03 22:22:37	2224	1483	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8057.jpg	d2e0ac86	2017-09-09 15:59:01	124	f
48907	1315		2017-10-03 22:22:37	3696	2464	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8059.jpg	c274d5c1	2017-09-09 15:59:23	125	f
48908	1315		2017-10-03 22:22:37	4062	2708	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2181.jpg	6e76e854	2017-09-09 16:00:07	126	f
48909	1315		2017-10-03 22:22:37	4596	3064	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2184.jpg	e45474eb	2017-09-09 16:01:37	127	f
48910	1315		2017-10-03 22:22:37	3046	2031	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8066.jpg	f9aa64ba	2017-09-09 16:02:40	128	f
48911	1315		2017-10-03 22:22:37	3162	4743	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2191.jpg	89647009	2017-09-09 16:02:45	129	f
48912	1315		2017-10-03 22:22:37	3331	2221	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8069.jpg	f0263e73	2017-09-09 16:04:51	130	f
48913	1315		2017-10-03 22:22:37	3444	5166	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2213.jpg	eb5f2dee	2017-09-09 16:06:45	131	f
48914	1315		2017-10-03 22:22:37	4615	3077	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2219.jpg	2898a664	2017-09-09 19:28:27	132	f
48915	1315		2017-10-03 22:22:38	5046	3364	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2225.jpg	bcbbade7	2017-09-09 19:31:03	133	f
48916	1315		2017-10-03 22:22:38	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2226.jpg	bfcde289	2017-09-09 19:32:16	134	f
48917	1315		2017-10-03 22:22:38	3867	2578	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2227.jpg	8d7e0af8	2017-09-09 19:32:35	135	f
48918	1315		2017-10-03 22:22:38	4936	3291	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2230.jpg	7540d19c	2017-09-09 19:34:38	136	f
48919	1315	4G, it attracts crow(d)s!	2017-10-03 22:22:38	5152	3435	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2238.jpg	3ad44fd9	2017-09-09 19:37:22	137	f
48920	1315		2017-10-03 22:22:38	5002	3335	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2247.jpg	a0cd8172	2017-09-09 20:02:17	138	f
48921	1315		2017-10-03 22:22:38	4810	3207	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2250.jpg	e2e8b2aa	2017-09-09 20:02:28	139	f
48922	1315		2017-10-03 22:22:38	4842	3228	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2252.jpg	b7889af0	2017-09-09 20:06:31	140	f
48923	1315		2017-10-03 22:22:38	4592	3061	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2261.jpg	e6420201	2017-09-09 20:24:01	141	f
48924	1315		2017-10-03 22:22:38	4567	3045	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2273.jpg	691878e2	2017-09-09 20:36:20	142	f
48925	1315		2017-10-03 22:22:38	2946	4419	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2285.jpg	a6131628	2017-09-09 20:37:01	143	f
48926	1315		2017-10-03 22:22:38	3002	4503	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2287.jpg	2eb5c872	2017-09-09 20:37:10	144	f
48927	1315		2017-10-03 22:22:38	2757	2757	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2297.jpg	97d7c314	2017-09-09 20:37:31	145	f
48928	1315		2017-10-03 22:22:38	4623	3082	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2310.jpg	0deb148e	2017-09-09 20:53:27	146	f
48929	1315		2017-10-03 22:22:38	3655	2437	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2314.jpg	e88e3152	2017-09-09 20:53:45	147	f
48930	1315		2017-10-03 22:22:38	4599	3066	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2323.jpg	fc6fbf1b	2017-09-09 21:00:14	148	f
48931	1315		2017-10-03 22:22:38	4911	3274	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2325.jpg	183154cf	2017-09-09 21:00:24	149	f
48932	1315		2017-10-03 22:22:38	4111	2741	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2330.jpg	08a4e30d	2017-09-09 21:00:41	150	f
48933	1315		2017-10-03 22:22:38	4769	3179	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2333.jpg	5762cdd7	2017-09-09 21:01:19	151	f
48934	1315		2017-10-03 22:22:38	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2339.jpg	da48dab4	2017-09-09 21:02:11	152	f
48891	1315		2017-10-03 22:22:37	2232	1494	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2634.jpg	5b6a7298	2017-09-09 15:48:56	109	f
48892	1315		2017-10-03 22:22:37	2929	4393	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2139.jpg	f25aa592	2017-09-09 15:49:29	110	f
48938	1315		2017-10-03 22:22:38	5149	3433	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2353.jpg	7333274e	2017-09-09 21:08:07	156	f
48939	1315		2017-10-03 22:22:38	4768	3179	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2354.jpg	4cdc5fe7	2017-09-09 21:08:28	157	f
48940	1315		2017-10-03 22:22:38	4696	3131	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2357.jpg	08f0f8b9	2017-09-09 21:10:18	158	f
48941	1315		2017-10-03 22:22:38	4696	3131	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2362.jpg	0095532a	2017-09-09 21:11:06	159	f
48942	1315		2017-10-03 22:22:38	4624	3083	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2374.jpg	573a8109	2017-09-09 21:17:27	160	f
48943	1315		2017-10-03 22:22:38	4647	3098	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2382.jpg	0959910c	2017-09-09 21:17:42	161	f
48944	1315		2017-10-03 22:22:39	3387	3387	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2393.jpg	9c653df8	2017-09-09 21:22:37	162	f
48945	1315		2017-10-03 22:22:39	2269	3403	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2394.jpg	ef97037b	2017-09-09 21:22:45	163	f
48946	1315		2017-10-03 22:22:39	4834	3223	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2399.jpg	dc15c082	2017-09-09 21:23:02	164	f
48947	1315		2017-10-03 22:22:39	4062	2708	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2410.jpg	a1908460	2017-09-09 21:25:10	165	f
48948	1315		2017-10-03 22:22:39	4988	3325	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2411.jpg	3bebed19	2017-09-09 21:25:20	166	f
48949	1315		2017-10-03 22:22:39	3696	2464	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2416.jpg	c294b3dd	2017-09-09 21:25:37	167	f
48950	1315		2017-10-03 22:22:39	2087	3131	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2421.jpg	e03d069e	2017-09-09 21:25:44	168	f
48951	1315		2017-10-03 22:22:39	4783	3189	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2422.jpg	5f012630	2017-09-09 21:26:19	169	f
48952	1315		2017-10-03 22:22:39	4906	3271	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2424.jpg	426c8c61	2017-09-09 21:26:42	170	f
48953	1315		2017-10-03 22:22:39	4379	2919	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2425.jpg	af055388	2017-09-09 21:28:17	171	f
48954	1315		2017-10-03 22:22:39	3762	2508	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2431.jpg	4765e54d	2017-09-09 21:29:25	172	f
48955	1315		2017-10-03 22:22:39	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2433.jpg	c6e3b7e4	2017-09-09 21:30:59	173	f
48956	1315		2017-10-03 22:22:39	3745	2497	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2436.jpg	6af855e8	2017-09-09 21:31:14	174	f
48957	1315		2017-10-03 22:22:39	4966	3311	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2440.jpg	2e4fb388	2017-09-09 21:32:27	175	f
48958	1315		2017-10-03 22:22:39	4696	3131	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2443.jpg	fd908075	2017-09-09 21:32:49	176	f
48959	1315		2017-10-03 22:22:39	4999	3333	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2444.jpg	dd356060	2017-09-09 21:32:54	177	f
48960	1315		2017-10-03 22:22:39	4875	3250	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2451.jpg	e1b1140f	2017-09-09 21:33:36	178	f
48961	1315		2017-10-03 22:22:39	3175	3175	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2459.jpg	2c5b39a6	2017-09-09 21:38:23	179	f
48962	1315		2017-10-03 22:22:39	3084	4626	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2471.jpg	8d0a9db7	2017-09-09 21:40:49	180	f
48963	1315		2017-10-03 22:22:39	4340	2893	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2476.jpg	dd6faa06	2017-09-09 21:42:30	181	f
48964	1315		2017-10-03 22:22:39	2724	4086	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2479.jpg	6a674010	2017-09-09 21:42:50	182	f
48965	1315		2017-10-03 22:22:39	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2495.jpg	00456bd6	2017-09-09 21:48:23	183	f
48966	1315		2017-10-03 22:22:39	3001	4501	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2509.jpg	5b944670	2017-09-09 21:54:43	184	f
48967	1315		2017-10-03 22:22:39	2304	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2516.jpg	c8e9be65	2017-09-09 21:54:57	185	f
48968	1315		2017-10-03 22:22:39	5053	3369	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2528.jpg	f291a559	2017-09-09 21:59:12	186	f
48969	1315		2017-10-03 22:22:39	3872	2592	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2659.jpg	1865b66c	2017-09-09 21:59:21	187	f
48970	1315		2017-10-03 22:22:39	3872	2592	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2660.jpg	a5cab9f4	2017-09-09 22:00:29	188	f
48971	1315		2017-10-03 22:22:39	4588	3059	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2532-2.jpg	b336c2ef	2017-09-09 22:03:07	189	f
48972	1315		2017-10-03 22:22:39	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2534.jpg	34a4007d	2017-09-09 22:03:34	190	f
48973	1315		2017-10-03 22:22:39	4750	3167	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2537.jpg	e5699124	2017-09-09 22:04:16	191	f
48974	1315		2017-10-03 22:22:39	3092	4638	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2548.jpg	c41e4882	2017-09-09 22:08:26	192	f
48975	1315		2017-10-03 22:22:39	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2553.jpg	8a4c4aef	2017-09-09 22:09:06	193	f
48976	1315		2017-10-03 22:22:39	4379	2919	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2561.jpg	20a4cbad	2017-09-09 22:13:52	194	f
48977	1315		2017-10-03 22:22:39	4630	3087	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2570.jpg	15dd678c	2017-09-09 22:16:22	195	f
48978	1315		2017-10-03 22:22:39	4810	3207	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2578.jpg	7fe0124f	2017-09-09 22:16:55	196	f
48979	1315		2017-10-03 22:22:39	1989	2983	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2582.jpg	63cfa016	2017-09-09 22:18:10	197	f
48936	1315		2017-10-03 22:22:38	4378	2919	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2346.jpg	6b716e67	2017-09-09 21:05:22	154	f
48937	1315		2017-10-03 22:22:38	4826	3217	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2348.jpg	55763bbf	2017-09-09 21:06:31	155	f
49011	1316		2017-10-03 22:23:03	4659	3106	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2725.jpg	b9ca1371	2017-09-10 13:56:51	\N	f
49012	1316		2017-10-03 22:23:03	4942	3295	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2729.jpg	f0f648c8	2017-09-10 13:59:43	\N	f
49013	1316		2017-10-03 22:23:03	3451	2301	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2733.jpg	f6b63191	2017-09-10 14:00:07	\N	f
49014	1316		2017-10-03 22:23:03	4854	3236	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2736.jpg	7d0246fc	2017-09-10 14:00:57	\N	f
49015	1316		2017-10-03 22:23:03	5184	3456	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2741.jpg	19f81a0c	2017-09-10 14:05:36	\N	f
49016	1316		2017-10-03 22:23:03	5158	3224	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2744.jpg	26edf2b3	2017-09-10 14:06:08	\N	f
49017	1316		2017-10-03 22:23:04	4213	2809	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2748.jpg	fdb479e6	2017-09-10 14:06:54	\N	f
49018	1316		2017-10-03 22:23:04	4606	3071	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2749.jpg	4224ceec	2017-09-10 14:07:40	\N	f
49019	1316		2017-10-03 22:23:04	2756	2756	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2758.jpg	21251df9	2017-09-10 14:10:46	\N	f
49020	1316		2017-10-03 22:23:04	4945	3297	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2763.jpg	ac680dea	2017-09-10 14:13:39	\N	f
49021	1316		2017-10-03 22:23:04	4895	3263	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2769.jpg	b9601079	2017-09-10 14:15:43	\N	f
49022	1316		2017-10-03 22:23:04	3104	3104	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2774.jpg	5169a0f2	2017-09-10 14:17:00	\N	f
49023	1316		2017-10-03 22:23:04	2280	3420	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2780.jpg	9fcfd4f2	2017-09-10 14:17:32	\N	f
49024	1316		2017-10-03 22:23:04	2798	4197	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2792.jpg	163ce75d	2017-09-10 14:23:40	\N	f
49025	1316		2017-10-03 22:23:04	5170	3447	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2799.jpg	cd231b24	2017-09-10 14:24:16	\N	f
49026	1316		2017-10-03 22:23:04	5036	2833	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2827.jpg	ad7e84aa	2017-09-10 14:28:36	\N	f
48983	1315		2017-10-03 22:22:39	4306	2871	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2604.jpg	759a2359	2017-09-09 22:33:24	201	f
48984	1315		2017-10-03 22:22:40	4774	3183	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2612.jpg	c415c1a5	2017-09-09 22:34:23	202	f
48985	1315		2017-10-03 22:22:40	4777	3185	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2617.jpg	da841ed9	2017-09-09 22:34:36	203	f
48986	1315		2017-10-03 22:22:40	4818	3212	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2634-2.jpg	a76d4c20	2017-09-09 23:25:00	204	f
48987	1315		2017-10-03 22:22:40	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2635.jpg	4573eda3	2017-09-09 23:25:19	205	f
48988	1315		2017-10-03 22:22:40	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2637.jpg	e7a62e34	2017-09-09 23:25:48	206	f
48989	1315		2017-10-03 22:22:40	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2638.jpg	d71ca2f3	2017-09-09 23:26:20	207	f
48990	1315		2017-10-03 22:22:40	5131	3421	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2640.jpg	f764754b	2017-09-09 23:26:41	208	f
48991	1315		2017-10-03 22:22:40	5098	3399	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2647.jpg	6da03168	2017-09-09 23:27:38	209	f
48992	1315		2017-10-03 22:22:40	5109	3406	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2649.jpg	da3400fe	2017-09-09 23:28:11	210	f
48993	1315		2017-10-03 22:22:40	4945	3297	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2657.jpg	7330253c	2017-09-09 23:30:17	211	f
48994	1315		2017-10-03 22:22:40	5170	3447	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2658.jpg	9b050dfb	2017-09-09 23:30:35	212	f
48995	1315		2017-10-03 22:22:40	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2662.jpg	ca710b5c	2017-09-09 23:31:15	213	f
48996	1315		2017-10-03 22:22:40	5175	3450	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2666.jpg	4d207456	2017-09-09 23:33:11	214	f
48997	1315		2017-10-03 22:22:40	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2669.jpg	43b0dfd9	2017-09-09 23:34:24	215	f
48998	1315		2017-10-03 22:22:40	4839	3226	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2672.jpg	c4dcd37a	2017-09-09 23:37:23	216	f
48999	1315		2017-10-03 22:22:40	4462	2975	fotos20172018/20170908-10_IntroCamp/Saturday/20170910-IntroCamp-Martijn-2682.jpg	709beb3d	2017-09-10 00:55:03	217	f
48982	1315		2017-10-03 22:22:39	5133	3422	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2603.jpg	1637c962	2017-09-09 22:33:16	200	f
48981	1315		2017-10-03 22:22:39	4867	3245	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2594.jpg	fe10b6b9	2017-09-09 22:33:08	199	f
49027	1316		2017-10-03 22:23:04	2774	4161	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2836.jpg	45ab1a3a	2017-09-10 14:31:47	\N	f
49028	1316		2017-10-03 22:23:04	4789	3193	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2841.jpg	2f76e5ce	2017-09-10 14:34:01	\N	f
49029	1316		2017-10-03 22:23:04	4946	3297	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2842.jpg	384ee7f6	2017-09-10 14:35:47	\N	f
49030	1316		2017-10-03 22:23:04	3609	2406	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2863.jpg	906872fd	2017-09-10 14:39:47	\N	f
49031	1316		2017-10-03 22:23:04	4522	3015	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2870.jpg	278bd1ad	2017-09-10 14:41:37	\N	f
49032	1316		2017-10-03 22:23:04	1649	2473	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2880.jpg	2ef804c8	2017-09-10 14:57:33	\N	f
49033	1316		2017-10-03 22:23:04	5184	3456	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-2883.jpg	df7e81bf	2017-09-10 14:57:55	\N	f
49034	1316		2017-10-03 22:23:04	5184	3456	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-9575.jpg	757731f1	2017-09-10 16:11:31	\N	f
49035	1316		2017-10-03 22:23:04	5107	3405	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-9576.jpg	bcf6783a	2017-09-10 16:11:36	\N	f
49036	1316		2017-10-03 22:23:04	4326	2884	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-9582.jpg	40f49b33	2017-09-10 16:14:50	\N	f
49037	1316		2017-10-03 22:23:04	4944	3296	fotos20172018/20170908-10_IntroCamp/Sunday/20170910-IntroCamp-Martijn-9587.jpg	f846eb94	2017-09-10 16:25:59	\N	f
48785	1315		2017-10-03 22:22:34	4355	2903	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1847.jpg	f149cf7e	2017-09-09 10:08:24	0	f
48800	1315		2017-10-03 22:22:35	4576	3051	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-1894.jpg	efe6474e	2017-09-09 12:07:28	15	f
49049	1317	Enough is enough. - Beer biscuit	2017-10-03 22:30:42	1600	1200	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2622.JPG	95cc4865	2017-09-25 10:34:43	\N	f
49051	1317	Don't teach your grandma to suck an egg. - Pineapple Balls	2017-10-03 22:30:42	1600	900	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2630.JPG	7a7e2959	2017-09-25 10:34:47	\N	f
49040	1317	Every picture tells a story.	2017-10-03 22:30:42	4032	3024	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2937.JPG	c7a2b05b	2017-09-09 13:03:21	\N	f
49047	1317	You can’t get blood out of  a stone. - Pineapple Balls	2017-10-03 22:30:42	1600	900	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2626.JPG	9568a296	2017-09-25 10:34:43	\N	f
48845	1315		2017-10-03 22:22:36	3456	5184	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2010.jpg	6fb1c788	2017-09-09 15:08:36	63	f
48890	1315		2017-10-03 22:22:37	2543	1695	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-8007.jpg	118ec45e	2017-09-09 15:48:51	108	f
48935	1315		2017-10-03 22:22:38	4062	2708	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2343.jpg	3ac84510	2017-09-09 21:04:50	153	f
48980	1315		2017-10-03 22:22:39	5184	3456	fotos20172018/20170908-10_IntroCamp/Saturday/20170909-IntroCamp-Martijn-2585.jpg	3a474a21	2017-09-09 22:21:03	198	f
49052	1308		2017-10-03 22:31:42	2171	3257	fotos20172018/Regret_Repository/20170908-IntroCamp-Martijn-1364.jpg	38b59d34	2017-09-08 17:56:37	\N	f
49053	1308		2017-10-03 22:31:43	2392	3588	fotos20172018/Regret_Repository/20170908-IntroCamp-Martijn-7492.jpg	36992729	2017-09-08 21:53:05	\N	f
49054	1308		2017-10-03 22:31:43	3133	2350	fotos20172018/Regret_Repository/20170908-IntroCamp-Martijn-7633.jpg	28fbf137	2017-09-08 22:48:01	\N	f
49055	1308		2017-10-03 22:31:43	2680	4020	fotos20172018/Regret_Repository/20170908-IntroCamp-Martijn-7666.jpg	45def76b	2017-09-08 22:56:33	\N	f
49056	1308		2017-10-03 22:31:43	2304	3456	fotos20172018/Regret_Repository/20170909-IntroCamp-Martijn-1798.jpg	709e87d2	2017-09-09 00:21:02	\N	f
49057	1308		2017-10-03 22:31:43	3708	2482	fotos20172018/Regret_Repository/20170909-IntroCamp-Martijn-2505.jpg	d53a6a37	2017-09-09 12:21:35	\N	f
49058	1308		2017-10-03 22:31:43	4870	3247	fotos20172018/Regret_Repository/20170909-IntroCamp-Martijn-1929.jpg	18937baf	2017-09-09 12:22:14	\N	f
49059	1308		2017-10-03 22:31:43	2683	1789	fotos20172018/Regret_Repository/20170909-IntroCamp-Martijn-7976.jpg	b33dc671	2017-09-09 15:41:33	\N	f
49044	1317	Manners maketh man. - Liever Binnen	2017-10-03 22:30:42	1600	1200	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2619.JPG	099beb31	2017-09-25 10:34:43	\N	f
49046	1317	A barking dog never bites. - <insert_groupname_here>	2017-10-03 22:30:42	1336	752	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2612.JPG	e2e3907c	2017-09-25 10:34:43	\N	f
49042	1317	Talk is cheap. - Team Rainbow Power	2017-10-03 22:30:42	1200	1600	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2614.JPG	3b1b6955	2017-09-25 10:34:43	\N	f
49038	1313		2017-10-03 22:23:30	5060	3163	fotos20172018/20170908-10_IntroCamp/20170910-IntroCampGroup-Martijn-2715.jpg	62d38315	2017-09-10 13:51:51	0	f
49041	1317	To the victor go the spoils. - Team 10	2017-10-03 22:30:42	1200	1600	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2608.JPG	5d9c50d0	2017-09-25 10:34:41	\N	f
49050	1317	Variety is the spice of life. - Team Rainbow Power	2017-10-03 22:30:42	1200	1600	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2616.JPG	b4e0c21b	2017-09-25 10:34:43	\N	f
49048	1317	What the eyes don't see, the heart doesn't grief over. - <insert_groupname_here>	2017-10-03 22:30:42	1336	752	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2610.JPG	47c191e5	2017-09-25 10:34:43	\N	f
49045	1317	Youth is wasted on the young. - Team Rainbow Power	2017-10-03 22:30:42	1200	1600	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2615.JPG	98810e13	2017-09-25 10:34:43	\N	f
49043	1317	Talk is cheap.	2017-10-03 22:30:42	1336	752	fotos20172018/20170908-10_IntroCamp/Saturday/photo_assignment/IMG_2627.JPG	af48ccfe	2017-09-25 10:34:43	\N	f
49039	1313		2017-10-03 22:23:30	5060	3163	fotos20172018/20170908-10_IntroCamp/20170910-IntroCampGroup-Martijn-2718.jpg	d0ad3e57	2017-09-10 13:52:08	1	f
49060	1308		2017-10-03 22:31:43	2446	3654	fotos20172018/Regret_Repository/20170909-IntroCamp-Martijn-2620.jpg	51cd2cac	2017-09-09 15:42:11	\N	f
49061	1308		2017-10-03 22:31:43	3038	4557	fotos20172018/Regret_Repository/20170910-IntroCamp-Martijn-2817.jpg	0b912350	2017-09-10 14:27:29	\N	f
49104	1318		2017-10-14 16:05:38	4032	3024	fotos20172018/20170920_Di(e)scover/IMG_2885.jpg	41270a83	2017-09-20 17:05:46	\N	f
49105	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2856.jpg	a955f7e5	2017-09-20 17:06:34	\N	f
49106	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2857.jpg	ed4223a6	2017-09-20 17:06:53	\N	f
49107	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2858.jpg	3a0346b8	2017-09-20 17:07:08	\N	f
49108	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2859.jpg	308bf86d	2017-09-20 17:07:47	\N	f
49109	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2860.jpg	6eeb08cb	2017-09-20 17:09:42	\N	f
49110	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2861.jpg	10dc6b15	2017-09-20 17:09:58	\N	f
49111	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2862.jpg	e6b411d0	2017-09-20 17:10:09	\N	f
49112	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2863.jpg	1e803eb6	2017-09-20 17:13:34	\N	f
49113	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2864.jpg	fae1e727	2017-09-20 17:19:41	\N	f
49114	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2865.jpg	09355af4	2017-09-20 17:19:47	\N	f
49115	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2866.jpg	53a2f6da	2017-09-20 17:29:03	\N	f
49116	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2873.jpg	35a68fba	2017-09-20 17:29:56	\N	f
49117	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2874.jpg	ed44adac	2017-09-20 17:30:02	\N	f
49118	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2876.jpg	4c7d7513	2017-09-20 17:53:03	\N	f
49119	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2878.jpg	afb25de9	2017-09-20 17:53:28	\N	f
49120	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2886.jpg	c5dfd629	2017-09-20 18:03:44	\N	f
49121	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2890.jpg	8e68ff18	2017-09-20 18:20:35	\N	f
49122	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2896.jpg	f1adddf0	2017-09-20 19:38:12	\N	f
49123	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2901.jpg	6eb567f3	2017-09-20 19:38:32	\N	f
49124	1318		2017-10-14 16:05:38	2592	3872	fotos20172018/20170920_Di(e)scover/DSC_2916.jpg	b09ebb57	2017-09-20 19:52:15	\N	f
49125	1318		2017-10-14 16:05:38	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2949.jpg	dc89485d	2017-09-20 20:18:45	\N	f
49126	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2958.jpg	a4b68908	2017-09-20 20:19:11	\N	f
49127	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2965.jpg	f575bb9f	2017-09-20 20:19:26	\N	f
49128	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2979.jpg	bbe61493	2017-09-20 20:22:47	\N	f
49129	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_2992.jpg	05a0aa1e	2017-09-20 20:24:42	\N	f
49130	1318		2017-10-14 16:05:39	2829	2094	fotos20172018/20170920_Di(e)scover/DSC_3000.jpg	c14d9b3b	2017-09-20 20:24:52	\N	f
49131	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3001.jpg	1291549b	2017-09-20 20:25:00	\N	f
49132	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3011.jpg	bf0ea2a5	2017-09-20 20:25:27	\N	f
49133	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3019.jpg	9561dc3f	2017-09-20 20:27:55	\N	f
49134	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3022.jpg	a4ffa91a	2017-09-20 20:28:11	\N	f
49135	1318		2017-10-14 16:05:39	2592	3872	fotos20172018/20170920_Di(e)scover/DSC_3031.jpg	fe01f1da	2017-09-20 22:01:16	\N	f
49136	1318		2017-10-14 16:05:39	3088	2320	fotos20172018/20170920_Di(e)scover/IMG_2903.jpg	a7fc533f	2017-09-20 22:07:50	\N	f
49137	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3041.jpg	c7a65e47	2017-09-20 22:13:07	\N	f
49138	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3051.jpg	f02c23ca	2017-09-20 22:20:33	\N	f
49139	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3067.jpg	63984c09	2017-09-20 22:24:51	\N	f
49140	1318		2017-10-14 16:05:39	4032	2279	fotos20172018/20170920_Di(e)scover/IMG_2904.jpg	8b9dd717	2017-09-20 22:50:50	\N	f
49141	1318		2017-10-14 16:05:39	4032	3024	fotos20172018/20170920_Di(e)scover/IMG_2917.jpg	0c994a23	2017-09-21 00:25:51	\N	f
49142	1318		2017-10-14 16:05:39	4032	3024	fotos20172018/20170920_Di(e)scover/IMG_2932.jpg	a1d71be5	2017-09-21 00:40:13	\N	f
49143	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3104.jpg	b725d0d9	2017-09-21 00:42:03	\N	f
49144	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3114.jpg	0707400d	2017-09-21 00:43:47	\N	f
49145	1318		2017-10-14 16:05:39	3872	2592	fotos20172018/20170920_Di(e)scover/DSC_3119.jpg	84ab08bf	2017-09-21 00:44:05	\N	f
49146	1308		2017-10-14 16:09:14	3872	2592	fotos20172018/Regret_Repository/DSC_2871.jpg	8ea78143	2017-09-20 17:29:33	\N	f
49147	1308		2017-10-14 16:09:14	2320	3088	fotos20172018/Regret_Repository/IMG_2899.jpg	ee68228f	2017-09-20 20:16:04	\N	f
49148	1308		2017-10-14 16:09:14	3872	2592	fotos20172018/Regret_Repository/DSC_3010.jpg	aa324453	2017-09-20 20:25:11	\N	f
49149	1308		2017-10-14 16:09:14	3024	4032	fotos20172018/Regret_Repository/IMG_2979.jpg	337d575f	2017-09-25 23:24:43	\N	f
49150	1308		2017-10-14 16:09:14	3088	2320	fotos20172018/Regret_Repository/IMG_2987.jpg	d22e7621	2017-09-26 00:16:13	\N	f
49151	1308		2017-10-14 16:09:14	3024	4032	fotos20172018/Regret_Repository/IMG_3165.jpg	1add06e2	2017-10-04 23:27:55	\N	f
49152	1319		2017-10-14 16:11:23	1181	1200	fotos20172018/20170926_TAD-TalkAtos/IMG_3012.jpg	33867d32	2017-09-26 12:05:15	\N	f
49153	1319		2017-10-14 16:11:23	1600	1200	fotos20172018/20170926_TAD-TalkAtos/IMG_3009.jpg	6e96da0f	2017-10-14 15:54:47	\N	f
49154	1319		2017-10-14 16:11:23	1600	1200	fotos20172018/20170926_TAD-TalkAtos/IMG_3008.jpg	af7a7441	2017-10-14 15:54:47	\N	f
49155	1319		2017-10-14 16:11:23	1600	1200	fotos20172018/20170926_TAD-TalkAtos/IMG_3010.jpg	43813db9	2017-10-14 15:54:47	\N	f
49156	1319		2017-10-14 16:11:23	1600	1200	fotos20172018/20170926_TAD-TalkAtos/IMG_3011.jpg	d3f2bfbc	2017-10-14 15:54:47	\N	f
49157	1320		2017-10-14 16:13:00	1743	2320	fotos20172018/20170925_IntroCampAfterparty/IMG_2987.jpg	42afe221	2017-09-26 00:16:13	\N	f
49158	1320		2017-10-14 16:13:00	3088	2320	fotos20172018/20170925_IntroCampAfterparty/IMG_2994.jpg	07ea52c6	2017-09-26 01:56:39	\N	f
49159	1320		2017-10-14 16:13:00	4032	3024	fotos20172018/20170925_IntroCampAfterparty/IMG_2998.jpg	98a09ff6	2017-09-26 01:56:51	\N	f
49160	1321		2017-10-14 16:14:55	4032	3024	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3120.jpg	6794d982	2017-10-04 13:12:53	\N	f
49161	1321		2017-10-14 16:14:55	2099	1272	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3121.jpg	2b4b6e48	2017-10-04 13:13:10	\N	f
49162	1321		2017-10-14 16:14:55	4032	3024	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3122.jpg	a238d607	2017-10-04 14:15:11	\N	f
49163	1321		2017-10-14 16:14:55	4032	3024	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3124.jpg	e62447f6	2017-10-04 14:15:19	\N	f
49164	1321		2017-10-14 16:14:55	2320	2254	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3125.jpg	cf14cfc3	2017-10-04 14:15:33	\N	f
49165	1321		2017-10-14 16:14:55	4032	3024	fotos20172018/20171004_BrainstormWithTheBoard/IMG_3126.jpg	37766c76	2017-10-04 14:24:18	\N	f
49166	1322		2017-10-14 16:18:10	3024	4032	fotos20172018/20171004_DisruptTheSocial/IMG_3141.jpg	05bf6abc	2017-10-04 21:57:14	\N	f
49167	1322		2017-10-14 16:18:10	3088	2320	fotos20172018/20171004_DisruptTheSocial/IMG_3142.jpg	4f7a4313	2017-10-04 22:11:42	\N	f
49168	1322		2017-10-14 16:18:10	3088	2320	fotos20172018/20171004_DisruptTheSocial/IMG_3144.jpg	856c7e21	2017-10-04 22:11:56	\N	f
49169	1322		2017-10-14 16:18:10	4032	3024	fotos20172018/20171004_DisruptTheSocial/IMG_3145.jpg	c89a4ace	2017-10-04 23:08:16	\N	f
49170	1322		2017-10-14 16:18:10	4032	3024	fotos20172018/20171004_DisruptTheSocial/IMG_3146.jpg	36cd3abe	2017-10-04 23:08:23	\N	f
49171	1322		2017-10-14 16:18:10	4032	3024	fotos20172018/20171004_DisruptTheSocial/IMG_3150.jpg	09e82931	2017-10-04 23:08:28	\N	f
49172	1322		2017-10-14 16:18:10	3024	4032	fotos20172018/20171004_DisruptTheSocial/IMG_3162.jpg	8152f6fa	2017-10-04 23:27:50	\N	f
49173	1322		2017-10-14 16:18:10	4032	1144	fotos20172018/20171004_DisruptTheSocial/IMG_3168.jpg	781503eb	2017-10-04 23:28:12	\N	f
49174	1322		2017-10-14 16:18:10	4032	3024	fotos20172018/20171004_DisruptTheSocial/IMG_3176.jpg	85608be8	2017-10-04 23:41:51	\N	f
49175	1322		2017-10-14 16:18:10	1913	2303	fotos20172018/20171004_DisruptTheSocial/IMG_3179.jpg	5754bb2f	2017-10-04 23:42:03	\N	f
49176	1322		2017-10-14 16:18:10	1200	1600	fotos20172018/20171004_DisruptTheSocial/IMG_3180.jpg	84c188a2	2017-10-14 16:17:33	\N	f
49555	1327		2017-10-15 13:31:57	2320	3088	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3254.jpg	3c68143b	2017-10-13 18:03:32	\N	f
49556	1327		2017-10-15 13:31:57	3088	2320	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3255.jpg	ca1e803a	2017-10-13 18:04:08	\N	f
49557	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3256.jpg	dac3676e	2017-10-13 18:04:40	\N	f
49558	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3257.jpg	b7f7061c	2017-10-13 18:04:45	\N	f
49559	1327		2017-10-15 13:31:57	3547	3020	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3260.jpg	0671063d	2017-10-13 18:05:08	\N	f
49560	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3261.jpg	719a34a6	2017-10-13 18:05:27	\N	f
49561	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3272.jpg	7ed41f93	2017-10-13 18:05:41	\N	f
49562	1327		2017-10-15 13:31:57	3024	4032	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3276.jpg	b5bf6b29	2017-10-13 18:08:17	\N	f
49563	1327		2017-10-15 13:31:57	3024	4032	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3278.jpg	53d78954	2017-10-13 18:10:24	\N	f
49564	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3281.jpg	f4003011	2017-10-13 18:24:14	\N	f
49565	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3282.jpg	db8e5636	2017-10-13 18:24:29	\N	f
49566	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3283.jpg	e9b3072f	2017-10-13 18:24:42	\N	f
49567	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3284.jpg	5d9b1b78	2017-10-13 18:24:45	\N	f
49568	1327		2017-10-15 13:31:57	7586	3848	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3285.jpg	1fd00337	2017-10-13 18:24:54	\N	f
49569	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3288.jpg	6e75ae33	2017-10-13 18:36:55	\N	f
49570	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3291.jpg	f41479b4	2017-10-13 18:37:22	\N	f
49571	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3293.jpg	f548265c	2017-10-13 18:38:33	\N	f
49572	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3295.jpg	e7f8e323	2017-10-13 18:38:40	\N	f
49573	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3316.jpg	3ca00d82	2017-10-13 18:47:45	\N	f
49574	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3344.jpg	dec27a36	2017-10-13 19:44:04	\N	f
49575	1327		2017-10-15 13:31:57	3024	4032	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3348.jpg	07edd9c7	2017-10-13 19:44:27	\N	f
49576	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3351.jpg	7f2cd1fb	2017-10-13 19:45:23	\N	f
49577	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3358.jpg	b84036d8	2017-10-13 19:45:24	\N	f
49578	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3379.jpg	6e91324e	2017-10-13 19:45:26	\N	f
49579	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3388.jpg	76f3b5b7	2017-10-13 19:45:27	\N	f
49580	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3389.jpg	a7c254f2	2017-10-13 19:45:51	\N	f
49581	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3390.jpg	55af9f0e	2017-10-13 19:46:00	\N	f
49582	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3393.jpg	f5563a3d	2017-10-13 19:58:48	\N	f
49583	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3394.jpg	95d27bf5	2017-10-13 20:00:05	\N	f
49584	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3395.jpg	2a528132	2017-10-13 20:00:19	\N	f
49585	1327		2017-10-15 13:31:57	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3400.jpg	08cfdf4c	2017-10-13 20:03:03	\N	f
49586	1327		2017-10-15 13:31:58	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3402.jpg	8146cd13	2017-10-13 20:03:17	\N	f
49587	1327		2017-10-15 13:31:58	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3405.jpg	2d6aae2e	2017-10-13 20:06:02	\N	f
49588	1327		2017-10-15 13:31:58	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3406.jpg	d83bfabf	2017-10-13 20:08:26	\N	f
49589	1327		2017-10-15 13:31:58	3088	2320	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3409.jpg	2c52550f	2017-10-13 20:43:23	\N	f
49590	1327		2017-10-15 13:31:58	2320	3088	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3419.jpg	86ca6a8e	2017-10-13 20:47:02	\N	f
49591	1327		2017-10-15 13:31:58	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Friday/IMG_3420.jpg	221584e7	2017-10-13 20:56:26	\N	f
49592	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3422.jpg	2ee47d87	2017-10-14 14:12:03	\N	f
49593	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3423.jpg	e518b8f1	2017-10-14 14:12:09	\N	f
49594	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3424.jpg	a897252a	2017-10-14 14:12:23	\N	f
49595	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3425.jpg	58f54bad	2017-10-14 14:12:56	\N	f
49596	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3427.jpg	1e190542	2017-10-14 14:13:50	\N	f
49597	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3428.jpg	75000bc3	2017-10-14 14:13:58	\N	f
49598	1328		2017-10-15 13:33:29	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3429.jpg	0355f642	2017-10-14 14:14:34	\N	f
49599	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3432.jpg	8d04e1d4	2017-10-14 14:14:50	\N	f
49600	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3434.jpg	dfbf3efa	2017-10-14 14:14:58	\N	f
49601	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3436.jpg	3ec7ffd8	2017-10-14 14:15:21	\N	f
49602	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3522.jpg	977ff0f0	2017-10-14 14:15:53	\N	f
49603	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3530.jpg	3f320a74	2017-10-14 19:08:59	\N	f
49604	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3532.jpg	6151f56c	2017-10-14 19:09:10	\N	f
49605	1328		2017-10-15 13:33:30	3024	4032	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3534.jpg	7cb5ac77	2017-10-14 19:22:13	\N	f
49606	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3536.jpg	c358a28a	2017-10-14 20:05:05	\N	f
49607	1328		2017-10-15 13:33:30	3024	4032	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3537.jpg	b689a699	2017-10-14 20:05:48	\N	f
49608	1328		2017-10-15 13:33:30	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Saturday/IMG_3540.jpg	d7851d5f	2017-10-14 20:26:56	\N	f
49609	1330		2017-10-15 13:38:40	593	737	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1230.jpg	eb549206	2017-04-26 08:45:12	\N	f
49610	1330		2017-10-15 13:38:40	553	690	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1233.jpg	df31e7b1	2017-04-26 08:45:14	\N	f
49611	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1249.jpg	96b5eaac	2017-04-26 12:18:21	\N	f
49612	1330		2017-10-15 13:38:40	2592	1944	fotos20162017/20170426-31_ExCeeAntwerp/Siger001.jpg	8076b009	2017-04-26 13:13:21	\N	f
49613	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger002.jpg	51e0062c	2017-04-26 13:32:21	\N	f
49614	1330		2017-10-15 13:38:40	2320	3088	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1258.jpg	12a8e3ca	2017-04-26 15:29:35	\N	f
49615	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger003.jpg	70612a52	2017-04-26 17:27:38	\N	f
49616	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1265.jpg	14f14e53	2017-04-26 22:27:48	\N	f
49617	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1266.jpg	838026e6	2017-04-26 22:28:32	\N	f
49618	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1270.jpg	910cb516	2017-04-26 23:00:14	\N	f
49619	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1274.jpg	2ef2205c	2017-04-26 23:31:33	\N	f
49620	1330		2017-10-15 13:38:40	1944	2592	fotos20162017/20170426-31_ExCeeAntwerp/Siger004.jpg	4c2ebfb5	2017-04-26 23:59:52	\N	f
49621	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1277.jpg	7c996286	2017-04-27 00:08:54	\N	f
49622	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1287.jpg	5771842a	2017-04-27 11:03:19	\N	f
49623	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger005.jpg	f8117c08	2017-04-27 11:55:24	\N	f
49624	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger006.jpg	c62ddf6f	2017-04-27 12:09:25	\N	f
49625	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1289.jpg	5434f575	2017-04-27 12:45:59	\N	f
49626	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger007.jpg	c90fe332	2017-04-27 12:46:16	\N	f
49627	1330		2017-10-15 13:38:40	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger008.jpg	d84b73e5	2017-04-27 16:16:46	\N	f
49628	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1294.jpg	6d73b027	2017-04-27 16:20:47	\N	f
49629	1330		2017-10-15 13:38:40	2592	1944	fotos20162017/20170426-31_ExCeeAntwerp/Siger009.jpg	e6e7f190	2017-04-27 16:38:29	\N	f
49630	1330		2017-10-15 13:38:40	2592	1944	fotos20162017/20170426-31_ExCeeAntwerp/Siger010.jpg	9767b2ff	2017-04-27 16:42:44	\N	f
49631	1330		2017-10-15 13:38:40	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger011.jpg	049b099a	2017-04-27 16:44:30	\N	f
49632	1330		2017-10-15 13:38:40	6768	3808	fotos20162017/20170426-31_ExCeeAntwerp/Siger012.jpg	7010f70c	2017-04-27 17:01:19	\N	f
49633	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger013.jpg	37e3238d	2017-04-27 18:11:14	\N	f
49634	1330		2017-10-15 13:38:41	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1298.jpg	63f3eb6c	2017-04-27 21:25:10	\N	f
49635	1330		2017-10-15 13:38:41	2320	3088	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1300.jpg	3d53be47	2017-04-27 23:34:39	\N	f
49636	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger014.jpg	52b12d8a	2017-04-27 23:38:32	\N	f
49637	1330		2017-10-15 13:38:41	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1302.jpg	d7539a33	2017-04-28 00:10:00	\N	f
49638	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger015.jpg	274ac326	2017-04-28 00:40:25	\N	f
49639	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger016.jpg	008f171b	2017-04-28 00:46:51	\N	f
49640	1330		2017-10-15 13:38:41	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1305.jpg	95ebf78a	2017-04-28 02:19:15	\N	f
49641	1330		2017-10-15 13:38:41	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1306.jpg	f9ad1f21	2017-04-28 02:27:33	\N	f
49642	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1328.jpg	d9a4257c	2017-04-28 13:02:19	\N	f
49643	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1329.jpg	2106e657	2017-04-28 14:02:26	\N	f
49644	1330		2017-10-15 13:38:41	5824	3680	fotos20162017/20170426-31_ExCeeAntwerp/Siger017.jpg	48498187	2017-04-28 14:04:10	\N	f
49645	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger018.jpg	1bbf09b1	2017-04-28 14:08:52	\N	f
49646	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger019.jpg	f762560c	2017-04-28 14:09:12	\N	f
49647	1330		2017-10-15 13:38:41	5760	3728	fotos20162017/20170426-31_ExCeeAntwerp/Siger021.jpg	b0c6ee41	2017-04-28 14:35:32	\N	f
49648	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger022.jpg	39d373fa	2017-04-28 14:37:16	\N	f
49649	1330		2017-10-15 13:38:41	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1330.jpg	fe40a8d0	2017-04-28 15:13:39	\N	f
49650	1330		2017-10-15 13:38:41	1944	2592	fotos20162017/20170426-31_ExCeeAntwerp/Siger023.jpg	dff8aa4a	2017-04-28 17:50:52	\N	f
49651	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1333.jpg	20e3e9df	2017-04-28 19:23:53	\N	f
49652	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1334.jpg	a16dc7e9	2017-04-28 19:25:53	\N	f
49653	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1335.jpg	0fa9896e	2017-04-28 20:12:40	\N	f
49654	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger024.jpg	ceaaefe9	2017-04-28 20:15:09	\N	f
49655	1330		2017-10-15 13:38:41	4032	2519	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1336.jpg	567265a6	2017-04-28 20:16:33	\N	f
49656	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1337.jpg	a2c041d9	2017-04-28 22:25:44	\N	f
49657	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1338.jpg	da48182c	2017-04-28 22:26:15	\N	f
49658	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1339.jpg	8fe17e88	2017-04-28 22:26:52	\N	f
49659	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1340.jpg	759bab0c	2017-04-28 22:31:13	\N	f
49660	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1342.jpg	ccccd76a	2017-04-28 22:31:15	\N	f
49661	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1344.jpg	a0ea56f5	2017-04-28 22:32:47	\N	f
49662	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1346.jpg	56442c70	2017-04-28 22:33:12	\N	f
49663	1330		2017-10-15 13:38:41	2320	3088	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1347.jpg	5e96d747	2017-04-28 22:33:42	\N	f
49664	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1349.jpg	03ca31bd	2017-04-28 22:34:35	\N	f
49665	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1356.jpg	81562fd7	2017-04-29 11:21:52	\N	f
49666	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger025.jpg	91551329	2017-04-29 11:31:55	\N	f
49667	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger026.jpg	8ca96199	2017-04-29 11:31:59	\N	f
49668	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1357.jpg	c2d01f57	2017-04-29 11:32:04	\N	f
49669	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger027.jpg	141c707a	2017-04-29 11:33:47	\N	f
49670	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger028.jpg	fda71a68	2017-04-29 11:37:15	\N	f
49671	1330		2017-10-15 13:38:41	9504	3968	fotos20162017/20170426-31_ExCeeAntwerp/Siger029.jpg	42dbd34c	2017-04-29 11:40:24	\N	f
49672	1330		2017-10-15 13:38:41	12668	3738	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1358.jpg	02ccf635	2017-04-29 11:41:02	\N	f
49673	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger030.jpg	8c96abcd	2017-04-29 11:41:41	\N	f
49674	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger031.jpg	77299a8a	2017-04-29 11:41:56	\N	f
49675	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger032.jpg	1d0af220	2017-04-29 11:42:01	\N	f
49676	1330		2017-10-15 13:38:41	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger033.jpg	1720592c	2017-04-29 11:49:10	\N	f
49677	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger034.jpg	38b6a2b2	2017-04-29 12:31:41	\N	f
49678	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger035.jpg	bf80f316	2017-04-29 12:43:05	\N	f
49679	1330		2017-10-15 13:38:41	11328	3904	fotos20162017/20170426-31_ExCeeAntwerp/Siger036.jpg	613a76b1	2017-04-29 12:51:56	\N	f
49680	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger037.jpg	ae4c96da	2017-04-29 13:00:51	\N	f
49681	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1363.jpg	79514834	2017-04-29 13:00:56	\N	f
49682	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1365.jpg	cb01b324	2017-04-29 13:09:20	\N	f
49683	1330		2017-10-15 13:38:41	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1366.jpg	da866512	2017-04-29 13:09:35	\N	f
49684	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger038.jpg	ad3971db	2017-04-29 14:36:12	\N	f
49685	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger039.jpg	41953888	2017-04-29 14:38:41	\N	f
49686	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1368.jpg	2afd7720	2017-04-29 14:41:54	\N	f
49687	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger040.jpg	4d8d4ae3	2017-04-29 15:04:32	\N	f
49688	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger041.jpg	1cf1a015	2017-04-29 15:06:18	\N	f
49689	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger042.jpg	173017cd	2017-04-29 15:09:00	\N	f
49690	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger043.jpg	5adab136	2017-04-29 15:21:41	\N	f
49691	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger044.jpg	1114c82c	2017-04-29 15:21:58	\N	f
49692	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger045.jpg	0d404493	2017-04-29 15:25:43	\N	f
49693	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger046.jpg	3b02f9a4	2017-04-29 15:48:22	\N	f
49694	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/Siger047.jpg	8a23148d	2017-04-29 23:41:17	\N	f
49695	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1378.jpg	9fad24ca	2017-04-29 23:41:35	\N	f
49696	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/Siger049.jpg	41330d95	2017-04-30 10:45:06	\N	f
49697	1330		2017-10-15 13:38:42	4032	3024	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1383.jpg	05fad3b3	2017-04-30 11:45:49	\N	f
49698	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1388.jpg	c8023fcf	2017-04-30 14:20:57	\N	f
49699	1330		2017-10-15 13:38:42	1200	1600	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1232.jpg	82bcdd5f	2017-10-14 16:58:59	\N	f
49700	1330		2017-10-15 13:38:42	1600	1200	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1237.jpg	ada5929d	2017-10-14 16:58:59	\N	f
49701	1330		2017-10-15 13:38:42	1280	720	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1236.jpg	286e36eb	2017-10-14 16:58:59	\N	f
49702	1330		2017-10-15 13:38:42	899	1599	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1229.jpg	9dc725f4	2017-10-14 16:58:59	\N	f
49703	1330		2017-10-15 13:38:42	640	1136	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1248.jpg	36f73786	2017-10-14 16:58:59	\N	f
49704	1330		2017-10-15 13:38:42	1600	1200	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1231.jpg	8952cbd0	2017-10-14 16:58:59	\N	f
49705	1330		2017-10-15 13:38:42	729	1296	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1255.jpg	c5ddf6dc	2017-10-14 16:59:00	\N	f
49706	1330		2017-10-15 13:38:42	1200	1600	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1254.jpg	8e4406eb	2017-10-14 16:59:00	\N	f
49707	1330		2017-10-15 13:38:42	1296	972	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1259.jpg	0896b248	2017-10-14 16:59:01	\N	f
49708	1330		2017-10-15 13:38:42	3724	2096	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1261.jpg	d5a84d85	2017-10-14 16:59:01	\N	f
49709	1330		2017-10-15 13:38:42	3024	4032	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1275.jpg	c95cb7bd	2017-10-14 16:59:03	\N	f
49710	1330		2017-10-15 13:38:42	3088	2320	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1276.jpg	c048808a	2017-10-14 16:59:04	\N	f
49711	1330		2017-10-15 13:38:42	1296	972	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1296.jpg	82635afb	2017-10-14 16:59:06	\N	f
49712	1330		2017-10-15 13:38:42	3840	2160	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1303.jpg	045b979a	2017-10-14 16:59:08	\N	f
49713	1330		2017-10-15 13:38:42	1200	1600	fotos20162017/20170426-31_ExCeeAntwerp/IMG_1373.jpg	d57c04e5	2017-10-14 16:59:33	\N	f
49714	1329		2017-10-16 12:17:52	2320	3088	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3545.jpg	12857079	2017-10-15 13:51:21	\N	f
49715	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3547.jpg	f45c98e7	2017-10-15 13:51:52	\N	f
49716	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3550.jpg	6ea0d292	2017-10-15 13:52:12	\N	f
49717	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3552.jpg	e11770ca	2017-10-15 13:53:03	\N	f
49718	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3585.jpg	d5a08005	2017-10-15 13:53:06	\N	f
49719	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3642.jpg	4723e8dc	2017-10-15 13:53:23	\N	f
49720	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3661.jpg	c601f817	2017-10-15 13:53:25	\N	f
49721	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3676.jpg	0bca2f9e	2017-10-15 13:56:14	\N	f
49722	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3706.jpg	61c20483	2017-10-15 14:22:20	\N	f
49723	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3711.jpg	3f541392	2017-10-15 14:25:47	\N	f
49724	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3713.jpg	2ebc689b	2017-10-15 14:26:12	\N	f
49725	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3716.jpg	89bce7de	2017-10-15 14:26:26	\N	f
49726	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3718.jpg	92755bd6	2017-10-15 14:26:57	\N	f
49727	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3721.jpg	f922fc29	2017-10-15 14:27:07	\N	f
49728	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3726.jpg	5685063d	2017-10-15 14:46:02	\N	f
49729	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3731.jpg	b9c9891a	2017-10-15 14:55:29	\N	f
49730	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3738.jpg	a0247203	2017-10-15 15:08:30	\N	f
49731	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3744.jpg	5e7df3a3	2017-10-15 15:16:00	\N	f
49732	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3754.jpg	6ed70e40	2017-10-15 15:58:44	\N	f
49733	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3764.jpg	cda3dd98	2017-10-15 16:13:10	\N	f
49734	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3774.jpg	a5ac646d	2017-10-15 16:45:34	\N	f
49735	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3777.jpg	658f4843	2017-10-15 16:47:42	\N	f
49736	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3778.jpg	cbebf97e	2017-10-15 16:47:47	\N	f
49737	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3786.jpg	517bf660	2017-10-15 16:50:20	\N	f
49738	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3788.jpg	c6afb8ee	2017-10-15 16:50:37	\N	f
49739	1329		2017-10-16 12:17:52	3413	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3789.jpg	a5ca19fe	2017-10-15 16:54:14	\N	f
49740	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3795.jpg	6d8ffc5e	2017-10-15 16:54:32	\N	f
49741	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3797.jpg	6b6b68e2	2017-10-15 16:54:34	\N	f
49743	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3807.jpg	40ab7eae	2017-10-15 16:55:25	\N	f
49744	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3808.jpg	04c84c37	2017-10-15 16:55:26	\N	f
49745	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3810.jpg	5b1843be	2017-10-15 16:55:36	\N	f
49746	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3812.jpg	79d3a010	2017-10-15 16:55:37	\N	f
49747	1329		2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3813.jpg	6feaebd4	2017-10-15 16:55:44	\N	f
49742	1329	The number 2!	2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3801.jpg	2425d1d2	2017-10-15 16:54:47	\N	f
49748	1329	The proud number 1!	2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3818.jpg	ec344d8d	2017-10-15 16:55:55	\N	f
49785	1333	The proud winner of the StudCee tutoring system naming contest.	2017-11-16 17:09:19	1200	1600	fotos20172018/Submitted_Photos/JelleEgbers-winner-studcee-contest1.jpg	0d61aed2	2017-11-16 17:07:02	\N	f
49749	1329	And of course, the very capable number 3.	2017-10-16 12:17:52	4032	3024	fotos20172018/20171013-15_HackathonBelsimpel/Sunday/IMG_3829.jpg	4047e218	2017-10-15 16:56:34	\N	f
49750	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_121438.jpg	261d88bc	2017-10-27 12:14:38	\N	f
49751	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_121440.jpg	546816c7	2017-10-27 12:14:40	\N	f
49752	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_130004.jpg	490a9910	2017-10-27 13:00:04	\N	f
49753	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_140940.jpg	38486bbf	2017-10-27 14:09:39	\N	f
49754	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_141637.jpg	8c196133	2017-10-27 14:16:37	\N	f
49755	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_141638_1.jpg	b44c9ee7	2017-10-27 14:16:38	\N	f
49756	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_141657.jpg	dd1d635c	2017-10-27 14:16:57	\N	f
49757	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_141711.jpg	46195a94	2017-10-27 14:17:11	\N	f
49758	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_141722.jpg	5aec00da	2017-10-27 14:17:22	\N	f
49759	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_141801.jpg	2f36df24	2017-10-27 14:18:01	\N	f
49760	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_141950.jpg	e38161ae	2017-10-27 14:19:50	\N	f
49761	1332		2017-10-27 23:57:30	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_142008.jpg	6ffa6aae	2017-10-27 14:20:08	\N	f
49762	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_142532.jpg	09683370	2017-10-27 14:25:32	\N	f
49763	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_142536.jpg	661c1b12	2017-10-27 14:25:36	\N	f
49764	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_142738.jpg	c7616580	2017-10-27 14:27:38	\N	f
49765	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_144428.jpg	645fddce	2017-10-27 14:44:28	\N	f
49766	1332		2017-10-27 23:57:30	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_144517.jpg	8637a33f	2017-10-27 14:45:17	\N	f
49767	1332		2017-10-27 23:57:31	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_171537.jpg	34889c4f	2017-10-27 17:15:37	\N	f
49768	1332		2017-10-27 23:57:31	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_174001.jpg	c3ff9593	2017-10-27 17:40:01	\N	f
49769	1332		2017-10-27 23:57:31	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_174007.jpg	6bcbb989	2017-10-27 17:40:07	\N	f
49770	1332		2017-10-27 23:57:31	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_174251.jpg	ad834fad	2017-10-27 17:42:51	\N	f
49771	1332		2017-10-27 23:57:31	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_175124.jpg	1df0d5d0	2017-10-27 17:51:24	\N	f
49772	1332		2017-10-27 23:57:31	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_182141.jpg	aff8cd8f	2017-10-27 18:21:41	\N	f
49773	1332		2017-10-27 23:57:31	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_182147.jpg	456d86fb	2017-10-27 18:21:47	\N	f
49774	1332		2017-10-27 23:57:31	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_191328.jpg	d6c907e3	2017-10-27 19:13:28	\N	f
49775	1332		2017-10-27 23:57:31	3016	4032	fotos20172018/20171027-Delft/IMG_20171027_193404.jpg	ccbde60c	2017-10-27 19:34:04	\N	f
49776	1332		2017-10-27 23:57:31	4032	3016	fotos20172018/20171027-Delft/IMG_20171027_193507.jpg	7d523fb0	2017-10-27 19:35:07	\N	f
49777	1320		2017-11-04 01:16:25	5184	3456	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9617.jpg	5b630c2d	2017-09-25 23:24:55	\N	f
49778	1320		2017-11-04 01:16:25	5184	3456	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9619.jpg	1a1f8178	2017-09-25 23:26:25	\N	f
49779	1320		2017-11-04 01:16:25	4911	3274	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9626.jpg	66406522	2017-09-25 23:26:52	\N	f
49780	1320		2017-11-04 01:16:25	4534	3023	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9629.jpg	9ff4183f	2017-09-25 23:27:28	\N	f
49781	1320		2017-11-04 01:16:25	5089	3393	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9634.jpg	45df28fc	2017-09-25 23:36:29	\N	f
49782	1320		2017-11-04 01:16:25	3352	3352	fotos20172018/20170925_IntroCampAfterparty/20170925-IntrocampAfterparty-Martijn-9639.jpg	1cffe8e8	2017-09-25 23:37:57	\N	f
49783	1320		2017-11-04 01:16:25	4821	3214	fotos20172018/20170925_IntroCampAfterparty/20170926-IntrocampAfterparty-Martijn-9655.jpg	3580c246	2017-09-26 00:14:00	\N	f
49784	1320		2017-11-04 01:16:25	4895	3263	fotos20172018/20170925_IntroCampAfterparty/20170926-IntrocampAfterparty-Martijn-9659.jpg	3dc9ad71	2017-09-26 00:14:17	\N	f
49786	1333	The proud winner of the StudCee tutoring system naming contest.	2017-11-16 17:09:19	1200	1600	fotos20172018/Submitted_Photos/JelleEgbers-winner-studcee-contest2.jpg	db9464f6	2017-11-16 17:07:07	\N	f
49787	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/01585fc435fbb01bb49c5a0429faa51994f1afa690.jpg	031c9365	2017-05-24 19:17:13	\N	f
49788	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/012f6a6fa22a2498d04b04df347083d2ad98b1cdce.jpg	b7d18c45	2017-05-24 19:17:19	\N	f
49789	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/019a3c25bbd7ec5813b214d05b20d23277a43b856c.jpg	2276b676	2017-05-24 19:20:04	\N	f
49790	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/01695a7b62934f80d6de07af56fcbaecb52ff82511.jpg	cb7e26fd	2017-05-24 19:20:49	\N	f
49791	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/012f72bbb3a3003787eefd9b6369cc2bded17b9564.jpg	771b2fac	2017-05-24 19:21:02	\N	f
49792	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/0163e44a667e4110cccfea96371d9efeb337aa5768.jpg	f7ad1973	2017-05-24 19:21:25	\N	f
49793	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/018f7f319993d410e40f58bd7f7d1a2d509a6125ff.jpg	ed76586d	2017-05-24 19:22:43	\N	f
49794	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/017f7efc004407b1784723e7a8a85489f8affd2bad.jpg	6d821ec6	2017-05-24 19:22:56	\N	f
49795	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/01c599601108e5b06169193af87332c8d65882f457_00001.jpg	1bf7ecb2	2017-05-24 19:24:06	\N	f
49796	1336		2017-11-27 16:39:20	2049	1537	fotos20172018/20170524_StaffBBQ/01fb25a38094511e7c269e46c6bd2e4e5ede335321.jpg	c2141e82	2017-05-24 19:24:25	\N	f
49797	1337		2017-12-05 12:14:39	2049	1537	fotos20172018/20170524_StaffBBQ/01585fc435fbb01bb49c5a0429faa51994f1afa690.jpg	031c9365	2017-05-24 19:17:13	\N	f
49798	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/012f6a6fa22a2498d04b04df347083d2ad98b1cdce.jpg	b7d18c45	2017-05-24 19:17:19	\N	f
49799	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/019a3c25bbd7ec5813b214d05b20d23277a43b856c.jpg	2276b676	2017-05-24 19:20:04	\N	f
49800	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/01695a7b62934f80d6de07af56fcbaecb52ff82511.jpg	cb7e26fd	2017-05-24 19:20:49	\N	f
49801	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/012f72bbb3a3003787eefd9b6369cc2bded17b9564.jpg	771b2fac	2017-05-24 19:21:02	\N	f
49802	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/0163e44a667e4110cccfea96371d9efeb337aa5768.jpg	f7ad1973	2017-05-24 19:21:25	\N	f
49803	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/018f7f319993d410e40f58bd7f7d1a2d509a6125ff.jpg	ed76586d	2017-05-24 19:22:43	\N	f
49804	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/017f7efc004407b1784723e7a8a85489f8affd2bad.jpg	6d821ec6	2017-05-24 19:22:56	\N	f
49805	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/01c599601108e5b06169193af87332c8d65882f457_00001.jpg	1bf7ecb2	2017-05-24 19:24:06	\N	f
49806	1337		2017-12-05 12:14:40	2049	1537	fotos20172018/20170524_StaffBBQ/01fb25a38094511e7c269e46c6bd2e4e5ede335321.jpg	c2141e82	2017-05-24 19:24:25	\N	f
49808	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3586.jpg	5c0655a1	2016-12-06 23:11:41	1	f
49809	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3588.jpg	fe82ab88	2016-12-06 23:14:44	2	f
49810	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3592.jpg	9f8c3fa4	2016-12-06 23:16:51	3	f
49811	1338		2017-12-20 17:37:05	2592	3872	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3593.jpg	fccc9f74	2016-12-06 23:17:02	4	f
49812	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3594.jpg	51f85e20	2016-12-06 23:17:51	5	f
49813	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3597.jpg	1d9feaff	2016-12-06 23:23:03	6	f
49814	1338		2017-12-20 17:37:05	2592	3872	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3601.jpg	702b5bf7	2016-12-06 23:24:36	7	f
49815	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3603.jpg	ee5a07c2	2016-12-06 23:27:19	8	f
49816	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3604.jpg	37099fbe	2016-12-06 23:29:13	9	f
49817	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3608.jpg	07a913b7	2016-12-06 23:34:28	10	f
49818	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3612.jpg	9ce9056b	2016-12-06 23:38:14	11	f
49821	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0011.jpg	2854a4d1	2017-12-06 22:50:32	12	f
49822	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0015.jpg	84171b9b	2017-12-06 22:53:46	13	f
49823	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0018.jpg	37249f63	2017-12-06 22:56:47	14	f
49824	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0020.jpg	88c413cb	2017-12-06 22:57:58	15	f
49825	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0028.jpg	6a0f4917	2017-12-06 23:05:50	16	f
49826	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0031.jpg	02c52064	2017-12-06 23:06:31	17	f
49827	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0037.jpg	950acf5b	2017-12-06 23:08:32	18	f
49828	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0041.jpg	21937d1d	2017-12-06 23:09:00	19	f
49829	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0044.jpg	36eb648a	2017-12-06 23:10:02	20	f
49830	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0045.jpg	c0461b40	2017-12-06 23:10:51	21	f
49831	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0046.jpg	85faa098	2017-12-06 23:11:00	22	f
49832	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0047.jpg	1809295e	2017-12-06 23:11:23	23	f
49833	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0054.jpg	6868ff05	2017-12-06 23:13:19	24	f
49834	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0055.jpg	02d8f016	2017-12-06 23:14:56	25	f
49835	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0059.jpg	8f784b06	2017-12-06 23:16:12	26	f
49836	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0070.jpg	58dd51c9	2017-12-06 23:27:18	27	f
49837	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0079.jpg	49112425	2017-12-06 23:33:13	28	f
49838	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0081.jpg	99f515a0	2017-12-06 23:34:21	29	f
49839	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0083.jpg	72a4b541	2017-12-06 23:34:36	30	f
49840	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0088.jpg	b3c40e56	2017-12-06 23:35:47	31	f
49819	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3616.jpg	3bb9c6b9	2016-12-06 23:47:54	65	f
49820	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3617.jpg	2b705480	2016-12-06 23:48:10	66	f
49807	1338		2017-12-20 17:37:05	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_3568.jpg	828aa0d2	2016-12-06 22:15:40	0	f
49851	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0124.jpg	3e71e6ba	2017-12-06 23:48:24	42	f
49852	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0129.jpg	612e71fb	2017-12-06 23:49:59	43	f
49853	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0131.jpg	9a070185	2017-12-06 23:51:08	44	f
49854	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0137.jpg	4cf00466	2017-12-06 23:52:10	45	f
49855	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0138.jpg	5690c9e7	2017-12-06 23:52:29	46	f
49856	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0139.jpg	a8c3f4f0	2017-12-06 23:52:35	47	f
49857	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0140.jpg	617d603e	2017-12-06 23:52:55	48	f
49858	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0147.jpg	3d64ffb1	2017-12-06 23:57:32	49	f
49859	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0156.jpg	e29eb15c	2017-12-06 23:57:48	50	f
49860	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0158.jpg	0b5f18e5	2017-12-06 23:57:51	51	f
49861	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0174.jpg	870225c2	2017-12-07 00:03:45	52	f
49862	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0179.jpg	c74e683c	2017-12-07 00:04:59	53	f
49863	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0192.jpg	86d337d3	2017-12-07 00:09:21	54	f
49864	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0196.jpg	4bfff231	2017-12-07 00:10:26	55	f
49865	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0200.jpg	4730249d	2017-12-07 00:11:06	56	f
49866	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0207.jpg	1da87b03	2017-12-07 00:15:18	57	f
49867	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0211.jpg	cdeb70d2	2017-12-07 00:16:24	58	f
49868	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0215.jpg	cd1e8a98	2017-12-07 00:16:50	59	f
49869	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0224.jpg	0c36d73b	2017-12-07 00:20:10	60	f
49870	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0227.jpg	640481f6	2017-12-07 00:20:37	61	f
49871	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0233.jpg	a541ec79	2017-12-07 00:43:39	62	f
49872	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0235.jpg	0d09a396	2017-12-07 00:44:27	63	f
49873	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0237.jpg	9b9e926c	2017-12-07 00:44:50	64	f
49879	1308		2018-01-11 13:42:02	6000	4000	fotos20172018/Regret_Repository/_GAL5695.jpg	5f8b09e5	2017-12-22 07:37:35	\N	f
49880	1339		2018-01-11 13:42:44	3096	2064	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3354.jpg	17f28d7c	2017-12-22 02:19:02	\N	f
49881	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3355.jpg	afa5749f	2017-12-22 02:19:15	\N	f
49882	1339		2018-01-11 13:42:44	4257	2838	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3356.jpg	ed245eed	2017-12-22 02:19:32	\N	f
49883	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3360.jpg	9b3f2331	2017-12-22 02:19:56	\N	f
49884	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3361.jpg	f6869433	2017-12-22 02:20:13	\N	f
49885	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3363.jpg	22acfd5f	2017-12-22 02:22:56	\N	f
49874	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0243.jpg	11b152ac	2017-12-07 00:52:14	67	f
49875	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0249.jpg	a6c38afe	2017-12-07 00:53:37	68	f
49876	1338		2017-12-20 17:37:07	2592	3872	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0253.jpg	b927ae11	2017-12-07 00:55:11	69	f
49877	1338		2017-12-20 17:37:07	2592	3872	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0254.jpg	6eaf36f2	2017-12-07 00:55:18	70	f
49878	1338		2017-12-20 17:37:07	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0260.jpg	481ccf26	2017-12-07 01:03:05	71	f
49841	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0090.jpg	832cac81	2017-12-06 23:35:54	32	f
49842	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0093.jpg	fa5ee65d	2017-12-06 23:36:10	33	f
49843	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0094.jpg	22acbb03	2017-12-06 23:36:19	34	f
49844	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0095.jpg	4bac1cb3	2017-12-06 23:36:32	35	f
49845	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0096.jpg	9a63734b	2017-12-06 23:36:38	36	f
49846	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0104.jpg	c0ee30bf	2017-12-06 23:40:17	37	f
49847	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0110.jpg	8841cee6	2017-12-06 23:42:13	38	f
49848	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0114.jpg	780049ed	2017-12-06 23:43:18	39	f
49849	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0115.jpg	23dd3733	2017-12-06 23:44:10	40	f
49850	1338		2017-12-20 17:37:06	3872	2592	fotos20172018/20171206_UglyChristmasSweaterSocial/DSC_0119.jpg	7a009da6	2017-12-06 23:46:24	41	f
49886	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3364.jpg	628509f1	2017-12-22 02:23:13	\N	f
49887	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3367.jpg	628233d4	2017-12-22 02:23:23	\N	f
49888	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3371.jpg	6ca5f510	2017-12-22 02:24:05	\N	f
49889	1339		2018-01-11 13:42:44	5184	3456	fotos20172018/20171221_Gala/WalkAroundCamera/_MG_3377.jpg	aff062e5	2017-12-22 02:24:12	\N	f
49890	1339		2018-01-11 13:42:44	2080	3120	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5579.jpg	1de0a39a	2017-12-22 06:02:58	\N	f
49891	1339		2018-01-11 13:42:44	4809	3206	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5580.jpg	fb1c2d25	2017-12-22 06:04:02	\N	f
49892	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5581.jpg	9e53c50d	2017-12-22 06:06:44	\N	f
49893	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5582.jpg	60d1f09e	2017-12-22 06:07:46	\N	f
49894	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5583.jpg	ae32447b	2017-12-22 06:09:48	\N	f
49895	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5584.jpg	365d09d1	2017-12-22 06:09:51	\N	f
49896	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5585.jpg	048f61d8	2017-12-22 06:15:41	\N	f
49897	1339		2018-01-11 13:42:44	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5586.jpg	102c4e6a	2017-12-22 06:17:17	\N	f
49898	1339		2018-01-11 13:42:44	5350	3567	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5587.jpg	9587b651	2017-12-22 06:17:33	\N	f
49899	1339		2018-01-11 13:42:44	5002	3335	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5588.jpg	1180ca82	2017-12-22 06:17:43	\N	f
49900	1339		2018-01-11 13:42:44	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5589.jpg	6851da44	2017-12-22 06:18:09	\N	f
49901	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5590.jpg	64d61f98	2017-12-22 06:18:13	\N	f
49902	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5591.jpg	bad3b15b	2017-12-22 06:18:17	\N	f
49903	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5592.jpg	ec3a04ec	2017-12-22 06:18:49	\N	f
49904	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5593.jpg	b86509f1	2017-12-22 06:18:53	\N	f
49905	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5594.jpg	2f8e8c53	2017-12-22 06:19:00	\N	f
49906	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5595.jpg	94073112	2017-12-22 06:19:04	\N	f
49907	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5596.jpg	bb0bc4c6	2017-12-22 06:19:08	\N	f
49908	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5599.jpg	ed5b04e9	2017-12-22 06:19:37	\N	f
49909	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5602.jpg	b8b1783d	2017-12-22 06:20:30	\N	f
49910	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5605.jpg	c9ccd4e4	2017-12-22 06:21:15	\N	f
49911	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5607.jpg	45aa8083	2017-12-22 06:24:14	\N	f
49912	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5608.jpg	81aa0980	2017-12-22 06:24:39	\N	f
49913	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5610.jpg	0541e410	2017-12-22 06:25:07	\N	f
49914	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5612.jpg	feb98340	2017-12-22 06:26:16	\N	f
49915	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5613.jpg	325eff68	2017-12-22 06:27:16	\N	f
49916	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5614.jpg	ded23c00	2017-12-22 06:27:30	\N	f
49917	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5615.jpg	3912ba7c	2017-12-22 06:27:37	\N	f
49918	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5616.jpg	ae98516b	2017-12-22 06:28:02	\N	f
49919	1339		2018-01-11 13:42:45	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5617.jpg	624f71d9	2017-12-22 06:28:15	\N	f
49920	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5619.jpg	529d5d5a	2017-12-22 06:28:37	\N	f
49921	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5625.jpg	fcc5c06f	2017-12-22 06:32:08	\N	f
49922	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5628.jpg	3e8f8a4e	2017-12-22 06:32:56	\N	f
49923	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5630.jpg	fa3140f5	2017-12-22 06:33:14	\N	f
49924	1339		2018-01-11 13:42:45	4497	2998	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5632.jpg	15198b15	2017-12-22 06:36:35	\N	f
49925	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5633.jpg	0455197c	2017-12-22 06:37:45	\N	f
49926	1339		2018-01-11 13:42:45	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5637.jpg	dced864f	2017-12-22 06:42:47	\N	f
49927	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5639.jpg	8be6f405	2017-12-22 06:43:25	\N	f
49928	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5640.jpg	87730986	2017-12-22 06:44:04	\N	f
49929	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5642.jpg	e288142b	2017-12-22 06:44:46	\N	f
49930	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5647.jpg	938a792b	2017-12-22 06:48:50	\N	f
49931	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5648.jpg	69a0c45b	2017-12-22 06:50:51	\N	f
49932	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5650.jpg	41619895	2017-12-22 07:02:54	\N	f
49933	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5651.jpg	6cd6d1b2	2017-12-22 07:03:21	\N	f
49934	1339		2018-01-11 13:42:46	4479	2986	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5653.jpg	06b25b82	2017-12-22 07:04:22	\N	f
49935	1339		2018-01-11 13:42:46	4533	3022	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5656.jpg	83886140	2017-12-22 07:07:57	\N	f
49936	1339		2018-01-11 13:42:46	2665	3998	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5657.jpg	e8fd9b85	2017-12-22 07:09:39	\N	f
49937	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5661.jpg	77fc027b	2017-12-22 07:11:16	\N	f
49938	1339		2018-01-11 13:42:46	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5662.jpg	a8aa29b0	2017-12-22 07:11:31	\N	f
49939	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5663.jpg	0ac85789	2017-12-22 07:11:53	\N	f
49940	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5666.jpg	aeae76f6	2017-12-22 07:18:33	\N	f
49941	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5667.jpg	7b8c6ee3	2017-12-22 07:20:27	\N	f
49942	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5668.jpg	56e2233a	2017-12-22 07:20:31	\N	f
49943	1339		2018-01-11 13:42:46	4969	3313	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5669.jpg	dc8cb0ce	2017-12-22 07:21:00	\N	f
49944	1339		2018-01-11 13:42:46	3026	3026	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5671.jpg	5a3fea82	2017-12-22 07:22:18	\N	f
49945	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5672.jpg	0fded693	2017-12-22 07:22:51	\N	f
49946	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5673.jpg	ca99b036	2017-12-22 07:22:55	\N	f
49947	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5674.jpg	aa1e8dd6	2017-12-22 07:23:07	\N	f
49948	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5675.jpg	ac28fc01	2017-12-22 07:23:41	\N	f
49949	1339		2018-01-11 13:42:46	4766	3212	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5677.jpg	67abfd0b	2017-12-22 07:26:48	\N	f
49950	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5678.jpg	93c8944f	2017-12-22 07:27:09	\N	f
49951	1339		2018-01-11 13:42:46	4420	2947	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5681.jpg	97b9a579	2017-12-22 07:29:34	\N	f
49952	1339		2018-01-11 13:42:46	4536	3024	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5682.jpg	123afd5c	2017-12-22 07:34:16	\N	f
49953	1339		2018-01-11 13:42:46	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5683.jpg	5c105a33	2017-12-22 07:34:25	\N	f
49954	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5684.jpg	712c259e	2017-12-22 07:34:36	\N	f
49955	1339		2018-01-11 13:42:46	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5685.jpg	d03c2891	2017-12-22 07:34:45	\N	f
49956	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5686.jpg	ac31934d	2017-12-22 07:35:03	\N	f
49957	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5696.jpg	0b079a83	2017-12-22 07:48:56	\N	f
49958	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5700.jpg	a0422cf8	2017-12-22 07:53:21	\N	f
49959	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5701.jpg	897afc43	2017-12-22 07:53:46	\N	f
49960	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5703.jpg	2cb491ed	2017-12-22 07:57:04	\N	f
49961	1339		2018-01-11 13:42:47	4116	2744	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5705.jpg	8111362f	2017-12-22 07:58:06	\N	f
49962	1339		2018-01-11 13:42:47	4792	3195	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5706.jpg	a2ff9813	2017-12-22 07:58:27	\N	f
49963	1339		2018-01-11 13:42:47	4128	2752	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5707.jpg	2503dd1b	2017-12-22 07:58:41	\N	f
49964	1339		2018-01-11 13:42:47	4601	3067	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5708.jpg	cfc42cf0	2017-12-22 07:58:57	\N	f
49965	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5709.jpg	45186dd7	2017-12-22 07:59:03	\N	f
49966	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5710.jpg	16621bc0	2017-12-22 07:59:10	\N	f
49967	1339		2018-01-11 13:42:47	4726	3151	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5711.jpg	e27124a8	2017-12-22 07:59:16	\N	f
49968	1339		2018-01-11 13:42:47	4049	2699	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5712.jpg	52b76ce6	2017-12-22 07:59:33	\N	f
49969	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5713.jpg	191bc087	2017-12-22 07:59:48	\N	f
49970	1339		2018-01-11 13:42:47	3176	2117	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5714.jpg	966f92fe	2017-12-22 08:00:03	\N	f
49971	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5719.jpg	8b9dab3c	2017-12-22 08:01:14	\N	f
49972	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5721.jpg	62e61f3c	2017-12-22 08:01:39	\N	f
49973	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5724.jpg	9308abbc	2017-12-22 08:02:24	\N	f
49974	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5726.jpg	978ccfa2	2017-12-22 08:03:34	\N	f
49975	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5728.jpg	5ebf73e4	2017-12-22 08:04:05	\N	f
49976	1339		2018-01-11 13:42:47	4591	3061	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5731.jpg	d9a08a71	2017-12-22 08:37:18	\N	f
49977	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5732.jpg	78936651	2017-12-22 08:37:40	\N	f
49978	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5733.jpg	9c439696	2017-12-22 08:37:46	\N	f
49979	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5735.jpg	7d82f19c	2017-12-22 08:37:55	\N	f
49980	1339		2018-01-11 13:42:47	3344	2229	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5736.jpg	22a72a88	2017-12-22 08:38:12	\N	f
49981	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5737.jpg	e7b53251	2017-12-22 08:38:16	\N	f
49982	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5743.jpg	59a681cc	2017-12-22 08:41:26	\N	f
49983	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5753.jpg	9acaf773	2017-12-22 08:45:21	\N	f
49984	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5755.jpg	76ef9313	2017-12-22 08:46:30	\N	f
49985	1339		2018-01-11 13:42:47	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5756.jpg	2f407472	2017-12-22 08:46:51	\N	f
49986	1339		2018-01-11 13:42:47	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5758.jpg	10a532b1	2017-12-22 08:47:04	\N	f
49987	1339		2018-01-11 13:42:47	4560	3040	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5760.jpg	b861f205	2017-12-22 08:47:48	\N	f
49988	1339		2018-01-11 13:42:47	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5766.jpg	e163da40	2017-12-22 08:51:29	\N	f
49989	1339		2018-01-11 13:42:47	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5769.jpg	0cd5ea95	2017-12-22 08:51:56	\N	f
49990	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5771.jpg	60956cdf	2017-12-22 08:52:11	\N	f
49991	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5772.jpg	b28cee14	2017-12-22 08:52:17	\N	f
49992	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5773.jpg	ad0cc1b6	2017-12-22 08:52:23	\N	f
49993	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5774.jpg	938d3150	2017-12-22 08:52:34	\N	f
49994	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5775.jpg	4397153b	2017-12-22 08:52:47	\N	f
49995	1339		2018-01-11 13:42:48	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5776.jpg	76677292	2017-12-22 08:53:02	\N	f
49996	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5777.jpg	b7bf5e28	2017-12-22 08:53:35	\N	f
49997	1339		2018-01-11 13:42:48	4768	3179	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5779.jpg	03636767	2017-12-22 08:53:50	\N	f
49998	1339		2018-01-11 13:42:48	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5780.jpg	de0d255d	2017-12-22 08:54:02	\N	f
49999	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5781.jpg	74ac7c07	2017-12-22 08:54:29	\N	f
50000	1339		2018-01-11 13:42:48	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5784.jpg	42810b9f	2017-12-22 08:55:03	\N	f
50001	1339		2018-01-11 13:42:48	5136	3424	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5785.jpg	b05d5f4f	2017-12-22 08:55:09	\N	f
50002	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5789.jpg	32547713	2017-12-22 08:55:45	\N	f
50003	1339		2018-01-11 13:42:48	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5792.jpg	5e2643d4	2017-12-22 08:56:18	\N	f
50004	1339		2018-01-11 13:42:48	5273	3515	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5793.jpg	eb9f9f09	2017-12-22 08:56:31	\N	f
50005	1339		2018-01-11 13:42:48	4572	3048	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5794.jpg	2f1f1d16	2017-12-22 08:56:57	\N	f
50006	1339		2018-01-11 13:42:48	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5797.jpg	a119bcb5	2017-12-22 08:58:40	\N	f
50007	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5800.jpg	e478183c	2017-12-22 08:59:31	\N	f
50008	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5803.jpg	ec42be93	2017-12-22 09:01:09	\N	f
50009	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5804.jpg	3723c2c7	2017-12-22 09:01:25	\N	f
50010	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5805.jpg	c82bdb02	2017-12-22 09:01:33	\N	f
50011	1339		2018-01-11 13:42:48	5166	3444	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5806.jpg	7157eef9	2017-12-22 09:01:55	\N	f
50012	1339		2018-01-11 13:42:48	4196	2797	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5809.jpg	d51612de	2017-12-22 09:02:24	\N	f
50013	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5812.jpg	9695443e	2017-12-22 09:03:05	\N	f
50014	1339		2018-01-11 13:42:48	4935	3290	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5813.jpg	dce70af8	2017-12-22 09:03:24	\N	f
50015	1339		2018-01-11 13:42:48	4586	3057	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5816.jpg	5a306d96	2017-12-22 09:04:53	\N	f
50016	1339		2018-01-11 13:42:48	4902	3268	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5817.jpg	c53706c9	2017-12-22 09:04:58	\N	f
50017	1339		2018-01-11 13:42:48	4434	2956	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5819.jpg	8f5a10c8	2017-12-22 09:05:16	\N	f
50018	1339		2018-01-11 13:42:48	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5820.jpg	b5b42c80	2017-12-22 09:05:22	\N	f
50019	1339		2018-01-11 13:42:48	3445	2297	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5825.jpg	ff7b8093	2017-12-22 09:07:40	\N	f
50020	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5830.jpg	a2408281	2017-12-22 09:09:28	\N	f
50021	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5833.jpg	2edb973b	2017-12-22 09:11:02	\N	f
50022	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5834.jpg	ea7b9498	2017-12-22 09:11:05	\N	f
50023	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5835.jpg	622a6648	2017-12-22 09:11:10	\N	f
50024	1339		2018-01-11 13:42:49	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5836.jpg	6332a9cd	2017-12-22 09:11:43	\N	f
50025	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5838.jpg	54cc36c7	2017-12-22 09:13:06	\N	f
50026	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5842.jpg	6a0e49b4	2017-12-22 09:14:36	\N	f
50027	1339		2018-01-11 13:42:49	4630	3087	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5843.jpg	c6d1df41	2017-12-22 09:14:59	\N	f
50028	1339		2018-01-11 13:42:49	3868	2494	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5850.jpg	83ef354e	2017-12-22 09:18:47	\N	f
50029	1339		2018-01-11 13:42:49	4215	2810	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5852.jpg	6c309d51	2017-12-22 09:19:36	\N	f
50030	1339		2018-01-11 13:42:49	4000	6000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5853.jpg	5f024f8d	2017-12-22 09:19:59	\N	f
50031	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5855.jpg	0eb6c413	2017-12-22 09:20:23	\N	f
50032	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5857.jpg	864b26bb	2017-12-22 09:36:25	\N	f
50033	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5858.jpg	6cdc1a8d	2017-12-22 09:36:28	\N	f
50034	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5866.jpg	9a683053	2017-12-22 09:42:55	\N	f
50035	1339		2018-01-11 13:42:49	6000	4000	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5868.jpg	250d6c4a	2017-12-22 09:43:22	\N	f
50036	1339		2018-01-11 13:42:49	4100	2958	fotos20172018/20171221_Gala/WalkAroundCamera/_GAL5871.jpg	d5b55fd7	2017-12-22 09:43:37	\N	f
50037	1313	Aftermovie	2018-01-15 19:09:33	1200	800	icons/video.gif	d542d15d	1970-01-01 01:00:00	2	f
50038	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0002.jpg	8d77e00d	2018-01-15 17:53:44	\N	f
50039	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0005.jpg	44795fa0	2018-01-15 17:58:18	\N	f
50040	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0007.jpg	73417c95	2018-01-15 18:24:34	\N	f
50041	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0009.jpg	89089de8	2018-01-15 18:44:48	\N	f
50042	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0010.jpg	a1ecc60e	2018-01-15 18:45:48	\N	f
50043	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0012.jpg	a9bb2f92	2018-01-15 18:46:21	\N	f
50044	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0013.jpg	73a08831	2018-01-15 18:46:30	\N	f
50045	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0015.jpg	82354e00	2018-01-15 18:47:42	\N	f
50046	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0016.jpg	1bc32811	2018-01-15 18:47:51	\N	f
50047	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0017.jpg	534d8ac8	2018-01-15 18:48:05	\N	f
50048	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0018.jpg	47d0ea8a	2018-01-15 18:48:49	\N	f
50049	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0021.jpg	a84c6642	2018-01-15 18:52:47	\N	f
50050	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0022.jpg	7992576f	2018-01-15 18:53:03	\N	f
50051	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0026.jpg	9b48c0b6	2018-01-15 19:45:45	\N	f
50052	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0028.jpg	026c9d2f	2018-01-15 19:46:09	\N	f
50053	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0031.jpg	b2a584b8	2018-01-15 19:50:48	\N	f
50054	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0033.jpg	e35332b9	2018-01-15 19:55:41	\N	f
50055	1340		2018-01-25 12:14:02	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0036.jpg	c4f34ce6	2018-01-15 20:08:08	\N	f
50056	1340		2018-01-25 12:14:03	3872	2592	fotos20172018/20180115_GeneralAssembly/DSC_0040.jpg	9ac6ef95	2018-01-15 20:34:58	\N	f
50057	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0095.jpg	4af83686	2018-03-05 20:00:41	\N	f
50058	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0096.jpg	8ca2b4a4	2018-03-05 20:00:43	\N	f
50059	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0097.jpg	a01575e8	2018-03-05 20:01:12	\N	f
50060	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0098.jpg	5f769f53	2018-03-05 20:01:13	\N	f
50061	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0099.jpg	e5185fa6	2018-03-05 20:01:57	\N	f
50062	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0100.jpg	28945801	2018-03-05 20:02:32	\N	f
50063	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0101.jpg	3df2cec1	2018-03-05 20:03:02	\N	f
50064	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0104.jpg	44568d70	2018-03-05 20:05:02	\N	f
50065	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0105.jpg	51e2758f	2018-03-05 20:05:09	\N	f
50066	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0106.jpg	5fb643e1	2018-03-05 20:05:16	\N	f
50067	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0108.jpg	11491b7a	2018-03-05 20:05:34	\N	f
50068	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0109.jpg	3f538f42	2018-03-05 20:06:07	\N	f
50069	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0111.jpg	a99d8085	2018-03-05 20:06:51	\N	f
50070	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0112.jpg	8e0c2154	2018-03-05 20:07:11	\N	f
50071	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0113.jpg	9f87db2c	2018-03-05 20:08:41	\N	f
50072	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0114.jpg	cab25733	2018-03-05 20:09:47	\N	f
50073	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0115.jpg	3502a985	2018-03-05 20:09:59	\N	f
50074	1342		2018-03-08 11:30:16	3872	2592	PhotoCee-Meetings/2018-03-05/DSC_0116.jpg	e57e372a	2018-03-05 20:11:02	\N	f
50075	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0117.jpg	06830f49	2018-03-07 22:27:50	\N	f
50076	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0121.jpg	758d6d0e	2018-03-07 22:52:15	\N	f
50077	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0123.jpg	85206d27	2018-03-07 22:53:03	\N	f
50078	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0132.jpg	62883e4f	2018-03-07 23:09:21	\N	f
50079	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0139.jpg	4c4620cc	2018-03-07 23:28:56	\N	f
50080	1343		2018-03-09 16:31:29	3872	2592	fotos20172018/20180207_Social/DSC_0140.jpg	f9ca95c7	2018-03-07 23:37:37	\N	f
50081	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0143.jpg	b4a66735	2018-03-07 23:40:43	\N	f
50082	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0145.jpg	f37da210	2018-03-07 23:42:10	\N	f
50083	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0147.jpg	feb62c74	2018-03-07 23:42:37	\N	f
50084	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0149.jpg	85b81f9d	2018-03-07 23:48:13	\N	f
50085	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0150.jpg	b8eb6cce	2018-03-07 23:48:14	\N	f
50086	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0151.jpg	02e0fd09	2018-03-07 23:48:16	\N	f
50087	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0158.jpg	11a041c6	2018-03-07 23:48:49	\N	f
50088	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0172.jpg	fd8729d7	2018-03-07 23:51:11	\N	f
50089	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0173.jpg	76e5428a	2018-03-07 23:52:53	\N	f
50090	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0174.jpg	3d9c76fb	2018-03-07 23:57:23	\N	f
50091	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0175.jpg	5254808a	2018-03-07 23:57:36	\N	f
50092	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0181.jpg	b2a642ba	2018-03-08 00:05:29	\N	f
50093	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0182.jpg	a7aef330	2018-03-08 00:05:43	\N	f
50094	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0183.jpg	83f75b8b	2018-03-08 00:05:58	\N	f
50095	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0185.jpg	59b39b00	2018-03-08 00:06:16	\N	f
50096	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0187.jpg	8e23c649	2018-03-08 00:06:28	\N	f
50097	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0188.jpg	1b791399	2018-03-08 00:06:40	\N	f
50098	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0189.jpg	ca866d63	2018-03-08 00:07:05	\N	f
50099	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0199.jpg	258856bf	2018-03-08 00:14:03	\N	f
50100	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0201.jpg	011eac36	2018-03-08 00:14:40	\N	f
50101	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0203.jpg	52ccf5ab	2018-03-08 00:15:12	\N	f
50102	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0205.jpg	f114a266	2018-03-08 00:15:42	\N	f
50103	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0209.jpg	f96833c7	2018-03-08 00:17:19	\N	f
50104	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0217.jpg	c9d7f1be	2018-03-08 00:21:24	\N	f
50105	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0221.jpg	985fac9e	2018-03-08 00:26:53	\N	f
50106	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0223.jpg	353f7faa	2018-03-08 00:34:35	\N	f
50107	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0229.jpg	4b64d1fe	2018-03-08 00:35:14	\N	f
50108	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0231.jpg	876e23ee	2018-03-08 00:35:50	\N	f
50109	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0232.jpg	20ca6485	2018-03-08 00:36:04	\N	f
50110	1343		2018-03-09 16:31:30	2864	2590	fotos20172018/20180207_Social/DSC_0234.jpg	86906523	2018-03-08 00:36:29	\N	f
50111	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0235.jpg	890b0733	2018-03-08 00:36:47	\N	f
50112	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0236.jpg	34a42b64	2018-03-08 00:36:49	\N	f
50113	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0240.jpg	3bf00508	2018-03-08 00:37:55	\N	f
50114	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0241.jpg	15bcb9b7	2018-03-08 00:38:04	\N	f
50115	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0248.jpg	87824606	2018-03-08 00:39:09	\N	f
50116	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0251.jpg	7cffe881	2018-03-08 00:39:26	\N	f
50117	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0252.jpg	2d97a402	2018-03-08 00:39:38	\N	f
50118	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0253.jpg	27cc8f0a	2018-03-08 00:39:48	\N	f
50119	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0258.jpg	e7877f5f	2018-03-08 00:40:41	\N	f
50120	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0259.jpg	4fae6de5	2018-03-08 00:40:52	\N	f
50121	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0262.jpg	9c0b7de3	2018-03-08 00:42:00	\N	f
50122	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0264.jpg	fd5ef875	2018-03-08 00:42:11	\N	f
50123	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0266.jpg	cebe9554	2018-03-08 00:42:22	\N	f
50124	1343		2018-03-09 16:31:30	1619	2590	fotos20172018/20180207_Social/DSC_0267.jpg	aa12e274	2018-03-08 00:42:29	\N	f
50125	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0271.jpg	3b4ed6f1	2018-03-08 00:43:23	\N	f
50126	1343		2018-03-09 16:31:30	2592	3872	fotos20172018/20180207_Social/DSC_0274.jpg	8c7801be	2018-03-08 00:44:17	\N	f
50127	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0275.jpg	4749fb0c	2018-03-08 00:44:48	\N	f
50128	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0276.jpg	203608fe	2018-03-08 00:45:00	\N	f
50129	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0278.jpg	adf82438	2018-03-08 00:45:22	\N	f
50130	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0280.jpg	edda9cec	2018-03-08 00:46:12	\N	f
50131	1343		2018-03-09 16:31:30	3872	2592	fotos20172018/20180207_Social/DSC_0281.jpg	ebc9e0ea	2018-03-08 00:46:36	\N	f
50132	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0283.jpg	5ec36fca	2018-03-08 00:47:01	\N	f
50133	1343		2018-03-09 16:31:31	2592	3872	fotos20172018/20180207_Social/DSC_0289.jpg	56b989b1	2018-03-08 00:48:03	\N	f
50134	1343		2018-03-09 16:31:31	2592	3872	fotos20172018/20180207_Social/DSC_0290.jpg	123f5b19	2018-03-08 00:48:04	\N	f
50135	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0292.jpg	04aa18ff	2018-03-08 00:48:54	\N	f
50136	1343		2018-03-09 16:31:31	2592	3872	fotos20172018/20180207_Social/DSC_0293.jpg	feb0a722	2018-03-08 00:49:06	\N	f
50137	1343		2018-03-09 16:31:31	2592	3872	fotos20172018/20180207_Social/DSC_0294.jpg	959329dd	2018-03-08 00:49:29	\N	f
50138	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0295.jpg	042fbce8	2018-03-08 00:50:00	\N	f
50139	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0299.jpg	c94327e9	2018-03-08 00:59:07	\N	f
50140	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0301.jpg	8912fab6	2018-03-08 01:03:10	\N	f
50141	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0302.jpg	0e23d5b1	2018-03-08 01:03:23	\N	f
50142	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0304.jpg	58761133	2018-03-08 01:04:25	\N	f
50143	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0305.jpg	10d5a750	2018-03-08 01:04:38	\N	f
50144	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0306.jpg	002366a4	2018-03-08 01:04:59	\N	f
50145	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0308.jpg	9e822e1b	2018-03-08 01:05:10	\N	f
50146	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0309.jpg	6a300da1	2018-03-08 01:05:19	\N	f
50147	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0312.jpg	f5461b5e	2018-03-08 01:11:30	\N	f
50148	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0313.jpg	6618b0bb	2018-03-08 01:11:36	\N	f
50149	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0314.jpg	99a2c7b0	2018-03-08 01:11:56	\N	f
50150	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0315.jpg	2c207312	2018-03-08 01:14:52	\N	f
50151	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0316.jpg	d190474b	2018-03-08 01:16:05	\N	f
50152	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0319.jpg	594cdbe0	2018-03-08 01:16:45	\N	f
50153	1343		2018-03-09 16:31:31	3872	2592	fotos20172018/20180207_Social/DSC_0320.jpg	5b0f9600	2018-03-08 01:17:21	\N	f
50154	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9923.JPG	0868e56a	2018-03-19 18:42:38	\N	f
50155	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9926.JPG	0d6751e7	2018-03-19 18:43:38	\N	f
50156	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9927.JPG	efc9fd09	2018-03-19 18:56:37	\N	f
50157	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9929.JPG	91891f6b	2018-03-19 19:01:50	\N	f
50158	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9935.JPG	86ff5128	2018-03-19 19:04:57	\N	f
50159	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9946.JPG	e9807bca	2018-03-19 19:40:02	\N	f
50160	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9951.JPG	869686cf	2018-03-19 19:47:55	\N	f
50161	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9958.JPG	536a42dd	2018-03-19 19:51:35	\N	f
50162	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9960.JPG	2db1cbd9	2018-03-19 19:54:02	\N	f
50163	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9962.JPG	e309f8e2	2018-03-19 19:54:23	\N	f
50164	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9964.JPG	44bbe04b	2018-03-19 19:55:02	\N	f
50165	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9966.JPG	e3320564	2018-03-19 19:55:07	\N	f
50166	1344		2018-03-24 16:17:48	4032	3024	fotos20172018/20180319_GeneralAssembly/IMG_9970.JPG	3ee69f0b	2018-03-19 20:39:24	\N	f
50167	1344		2018-03-24 16:17:48	3024	4032	fotos20172018/20180319_GeneralAssembly/IMG_9973.JPG	e79fe4fe	2018-03-19 20:40:21	\N	f
50168	1345		2018-03-29 15:37:30	4640	3480	fotos20172018/20171121_MxCee_America/IMG_20171121_214047.jpg	67ee8e27	2017-11-21 21:40:47	\N	f
50169	1345		2018-03-29 15:37:30	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171122_005029.jpg	6b9c9e6b	2017-11-22 00:50:29	\N	f
50170	1345		2018-03-29 15:37:30	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171122_011357.jpg	9db6414f	2017-11-22 01:13:57	\N	f
50171	1345		2018-03-29 15:37:31	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-1.jpg	76818457	2017-11-22 03:13:10	\N	f
50172	1345		2018-03-29 15:37:31	1920	1080	fotos20172018/20171121_MxCee_America/20171122_121221.jpg	89f37e8d	2017-11-22 12:12:20	\N	f
50173	1345		2018-03-29 15:37:31	1280	960	fotos20172018/20171121_MxCee_America/IMG_7239.jpg	8e69619d	2017-11-22 13:11:24	\N	f
50174	1345		2018-03-29 15:37:31	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7247.jpg	0dbb6ddc	2017-11-22 14:25:50	\N	f
50175	1345		2018-03-29 15:37:31	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7248.jpg	9f55a032	2017-11-22 14:25:57	\N	f
50176	1345		2018-03-29 15:37:31	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171122_151755.jpg	67667d3a	2017-11-22 15:17:55	\N	f
50177	1345		2018-03-29 15:37:31	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7257.jpg	fc287da4	2017-11-22 17:15:44	\N	f
50178	1345		2018-03-29 15:37:31	5817	3878	fotos20172018/20171121_MxCee_America/Amerika_AO-5.jpg	eb54ac5d	2017-11-22 23:14:20	\N	f
50179	1345		2018-03-29 15:37:31	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0038.NEF.jpg	92562952	2017-11-23 02:20:25	\N	f
50180	1345		2018-03-29 15:37:31	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0057.NEF.jpg	4f1cedc4	2017-11-23 04:45:57	\N	f
50181	1345		2018-03-29 15:37:31	4640	3480	fotos20172018/20171121_MxCee_America/IMG_20171123_070955.jpg	b51148d9	2017-11-23 07:09:55	\N	f
50182	1345		2018-03-29 15:37:31	3395	2546	fotos20172018/20171121_MxCee_America/IMG_20171123_101207.jpg	b2311b31	2017-11-23 10:12:07	\N	f
50183	1345		2018-03-29 15:37:31	4640	3480	fotos20172018/20171121_MxCee_America/IMG_20171123_102942.jpg	ebdc9df3	2017-11-23 10:29:42	\N	f
50184	1345		2018-03-29 15:37:31	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171123_121606-12.jpg	abe957f6	2017-11-23 12:16:08	\N	f
50185	1345		2018-03-29 15:37:31	3120	4208	fotos20172018/20171121_MxCee_America/IMG_20171123_125251-13.jpg	7c80f072	2017-11-23 12:52:53	\N	f
50186	1345		2018-03-29 15:37:31	3120	4208	fotos20172018/20171121_MxCee_America/IMG_20171123_140357-16.jpg	26854daf	2017-11-23 14:03:59	\N	f
50187	1345		2018-03-29 15:37:31	5618	3745	fotos20172018/20171121_MxCee_America/Amerika_AO-11.jpg	635abef9	2017-11-23 20:22:06	\N	f
50188	1345		2018-03-29 15:37:31	4640	3480	fotos20172018/20171121_MxCee_America/IMG_20171124_115005.jpg	d49670d4	2017-11-24 11:50:06	\N	f
50189	1345		2018-03-29 15:37:31	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171124_122909-20.jpg	b6412ccd	2017-11-24 12:29:10	\N	f
50190	1345		2018-03-29 15:37:31	5344	3006	fotos20172018/20171121_MxCee_America/IMG_20171124_150340659.jpg	0a1790dc	2017-11-24 15:03:40	\N	f
50191	1345		2018-03-29 15:37:31	5344	3006	fotos20172018/20171121_MxCee_America/IMG_20171124_150436520.jpg	116fefa7	2017-11-24 15:04:36	\N	f
50192	1345		2018-03-29 15:37:31	1280	960	fotos20172018/20171121_MxCee_America/IMG_7345.jpg	3ac22823	2017-11-24 15:09:10	\N	f
50193	1345		2018-03-29 15:37:31	11222	3732	fotos20172018/20171121_MxCee_America/0D70CAEA-9006-4EB5-B2D8-1B1E60FF4AF5.jpg	ba329d6c	2017-11-24 17:12:05	\N	f
50194	1345		2018-03-29 15:37:31	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7389.jpg	d2e14039	2017-11-24 17:17:22	\N	f
50195	1345		2018-03-29 15:37:31	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-13.jpg	c61b5c0d	2017-11-25 01:03:41	\N	f
50196	1345		2018-03-29 15:37:31	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-14.jpg	733dbd3a	2017-11-25 01:04:41	\N	f
50197	1345		2018-03-29 15:37:31	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0097.NEF.jpg	b0c6b0ef	2017-11-25 01:05:56	\N	f
50198	1345		2018-03-29 15:37:32	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0103.NEF.jpg	f8260729	2017-11-25 01:07:21	\N	f
50199	1345		2018-03-29 15:37:32	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0105.NEF.jpg	4feacf2a	2017-11-25 01:08:51	\N	f
50200	1345		2018-03-29 15:37:32	3714	5571	fotos20172018/20171121_MxCee_America/Amerika_AO-18.jpg	c2a3bf44	2017-11-25 01:10:42	\N	f
50201	1345		2018-03-29 15:37:32	5929	3953	fotos20172018/20171121_MxCee_America/Amerika_AO-19.jpg	997075e2	2017-11-25 01:11:15	\N	f
50202	1345		2018-03-29 15:37:32	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0120.NEF.jpg	fe936fc7	2017-11-25 01:14:22	\N	f
50203	1345		2018-03-29 15:37:32	5887	3925	fotos20172018/20171121_MxCee_America/Amerika_AO-22.jpg	4afec9ce	2017-11-25 01:46:10	\N	f
50204	1345		2018-03-29 15:37:32	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-27.jpg	def31571	2017-11-25 03:05:20	\N	f
50205	1345		2018-03-29 15:37:32	5683	3789	fotos20172018/20171121_MxCee_America/Amerika_AO-34.jpg	f0e22be7	2017-11-25 04:12:21	\N	f
50206	1345		2018-03-29 15:37:32	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171125_191107.jpg	4ecc8338	2017-11-25 19:11:07	\N	f
50207	1345		2018-03-29 15:37:32	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171125_203724.jpg	0bfc64f3	2017-11-25 20:37:24	\N	f
50208	1345		2018-03-29 15:37:32	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171126_100402.jpg	bc4c1412	2017-11-26 10:04:02	\N	f
50209	1345		2018-03-29 15:37:32	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171126_144827.jpg	ff9a0c65	2017-11-26 14:48:27	\N	f
50210	1345		2018-03-29 15:37:32	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171126_153808-30.jpg	f67b0a73	2017-11-26 15:38:09	\N	f
50211	1345		2018-03-29 15:37:32	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171126_154851.jpg	ea24b8e2	2017-11-26 15:48:51	\N	f
50212	1345		2018-03-29 15:37:32	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171126_210828.jpg	001a759c	2017-11-26 21:08:28	\N	f
50213	1345		2018-03-29 15:37:32	1836	3264	fotos20172018/20171121_MxCee_America/20171127_112427.jpg	6beff69a	2017-11-27 11:24:27	\N	f
50214	1345		2018-03-29 15:37:32	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171127_114920.jpg	7425c318	2017-11-27 11:49:20	\N	f
50215	1345		2018-03-29 15:37:32	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171127_125548.jpg	e632c3fd	2017-11-27 12:55:49	\N	f
50216	1345		2018-03-29 15:37:32	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7524.jpg	ff73d175	2017-11-27 12:58:54	\N	f
50217	1345		2018-03-29 15:37:32	3264	2448	fotos20172018/20171121_MxCee_America/IMG_20171127_131149.jpg	9cd9ea67	2017-11-27 13:11:50	\N	f
50218	1345		2018-03-29 15:37:32	1944	2592	fotos20172018/20171121_MxCee_America/IMG_20171127_132227.jpg	3858467e	2017-11-27 13:22:27	\N	f
50219	1345		2018-03-29 15:37:32	3456	4608	fotos20172018/20171121_MxCee_America/IMG_20171127_132341.jpg	cd45cda9	2017-11-27 13:23:41	\N	f
50220	1345		2018-03-29 15:37:32	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171127_132642.jpg	fc5863ed	2017-11-27 13:26:42	\N	f
50221	1345		2018-03-29 15:37:33	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171127_133054.jpg	85b49a12	2017-11-27 13:30:54	\N	f
50222	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171127_133058.jpg	7435c662	2017-11-27 13:30:58	\N	f
50223	1345		2018-03-29 15:37:33	4608	3456	fotos20172018/20171121_MxCee_America/IMG_20171127_145333.jpg	a9b19fe1	2017-11-27 14:53:33	\N	f
50224	1345		2018-03-29 15:37:33	7696	2936	fotos20172018/20171121_MxCee_America/IMG_E7555.jpg	c95f4f3e	2017-11-27 14:56:48	\N	f
50225	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7556.jpg	49cf9fa7	2017-11-27 14:57:09	\N	f
50226	1345		2018-03-29 15:37:33	3120	4208	fotos20172018/20171121_MxCee_America/IMG_20171127_152901-38.jpg	6350e6cc	2017-11-27 15:29:03	\N	f
50227	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171127_155540.jpg	754330df	2017-11-27 15:55:40	\N	f
50228	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171127_162836.jpg	11c03fe7	2017-11-27 16:28:36	\N	f
50229	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171127_163337.jpg	8556a200	2017-11-27 16:33:37	\N	f
50230	1345		2018-03-29 15:37:33	5742	3828	fotos20172018/20171121_MxCee_America/Amerika_AO-39.jpg	f58b6743	2017-11-27 19:12:29	\N	f
50231	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/guus_mxcee_kleiner_0018.jpg	2d8e606d	2017-11-27 19:15:59	\N	f
50232	1345		2018-03-29 15:37:33	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0191.NEF.jpg	8c5e1b94	2017-11-27 21:16:27	\N	f
50233	1345		2018-03-29 15:37:33	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0217.NEF.jpg	e8d35ad4	2017-11-27 23:12:52	\N	f
50234	1345		2018-03-29 15:37:33	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0255.NEF.jpg	7fc1cd95	2017-11-28 00:36:20	\N	f
50235	1345		2018-03-29 15:37:33	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0270.NEF.jpg	eab629f9	2017-11-28 00:49:59	\N	f
50236	1345		2018-03-29 15:37:33	5861	3907	fotos20172018/20171121_MxCee_America/Amerika_AO-41.jpg	0b28a77d	2017-11-28 01:01:29	\N	f
50237	1345		2018-03-29 15:37:33	5827	3885	fotos20172018/20171121_MxCee_America/Amerika_AO-43.jpg	a4669f5f	2017-11-28 01:14:33	\N	f
50238	1345		2018-03-29 15:37:33	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0286.NEF.jpg	40c169a2	2017-11-28 01:18:39	\N	f
50239	1345		2018-03-29 15:37:33	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0292.NEF.jpg	6f6491cf	2017-11-28 01:42:43	\N	f
50240	1345		2018-03-29 15:37:33	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0299.NEF.jpg	0afd1f6d	2017-11-28 01:44:56	\N	f
50241	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7621.jpg	5ca223ac	2017-11-28 08:52:07	\N	f
50242	1345		2018-03-29 15:37:33	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171128_095455-39.jpg	394f77c7	2017-11-28 09:54:57	\N	f
50243	1345		2018-03-29 15:37:33	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171128_095823.jpg	1fac6fe2	2017-11-28 09:58:23	\N	f
50244	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7627.jpg	a00cedc6	2017-11-28 09:59:40	\N	f
50245	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7634.jpg	d35929e6	2017-11-28 10:27:34	\N	f
50246	1345		2018-03-29 15:37:33	2448	3264	fotos20172018/20171121_MxCee_America/IMG_7641.jpg	5383d9aa	2017-11-28 10:30:34	\N	f
50247	1345		2018-03-29 15:37:33	2448	3264	fotos20172018/20171121_MxCee_America/IMG_7644.jpg	002783b5	2017-11-28 10:32:17	\N	f
50248	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171128_130530.jpg	710db2df	2017-11-28 13:05:30	\N	f
50249	1345		2018-03-29 15:37:33	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171128_131102.jpg	6ba8aed3	2017-11-28 13:11:02	\N	f
50250	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7661.jpg	cc0c60c7	2017-11-28 15:04:19	\N	f
50251	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171128_150546.jpg	e4d1c45f	2017-11-28 15:05:46	\N	f
50252	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171129_101613.jpg	7ac475c4	2017-11-29 10:16:13	\N	f
50253	1345		2018-03-29 15:37:33	3264	2448	fotos20172018/20171121_MxCee_America/IMG_7679.jpg	57df541c	2017-11-29 13:14:58	\N	f
50254	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171129_141745.jpg	76bc7f82	2017-11-29 14:17:45	\N	f
50255	1345		2018-03-29 15:37:33	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171129_141803.jpg	b7875007	2017-11-29 14:18:03	\N	f
50256	1345		2018-03-29 15:37:33	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171129_143302.jpg	f58ece92	2017-11-29 14:33:02	\N	f
50257	1345		2018-03-29 15:37:33	3854	2885	fotos20172018/20171121_MxCee_America/78D1F32D-F4F7-4654-9562-4E187D9F2F09.jpg	ac32b104	2017-11-29 14:50:25	\N	f
50258	1345		2018-03-29 15:37:33	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171129_163024.jpg	cfb0d7d1	2017-11-29 16:30:25	\N	f
50259	1345		2018-03-29 15:37:34	5580	3720	fotos20172018/20171121_MxCee_America/Amerika_AO-49.jpg	5a777494	2017-11-29 21:45:31	\N	f
50260	1345		2018-03-29 15:37:34	3456	4608	fotos20172018/20171121_MxCee_America/IMG_20171130_124029.jpg	114b8fee	2017-11-30 12:40:29	\N	f
50261	1345		2018-03-29 15:37:34	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171130_124542.jpg	0fe2475a	2017-11-30 12:45:42	\N	f
50262	1345		2018-03-29 15:37:34	4039	2995	fotos20172018/20171121_MxCee_America/IMG_20171130_131428-40.jpg	7631f9f3	2017-11-30 13:14:30	\N	f
50263	1345		2018-03-29 15:37:34	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171130_132804.jpg	9b5098f5	2017-11-30 13:28:05	\N	f
50264	1345		2018-03-29 15:37:34	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171130_144924.jpg	a3ac8fb9	2017-11-30 14:49:25	\N	f
50265	1345		2018-03-29 15:37:34	4608	3456	fotos20172018/20171121_MxCee_America/IMG_20171130_163759_01.jpg	0d7cf266	2017-11-30 16:38:00	\N	f
50266	1345		2018-03-29 15:37:34	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171130_164138-41.jpg	3f7a92cc	2017-11-30 16:41:41	\N	f
50267	1345		2018-03-29 15:37:34	5801	3867	fotos20172018/20171121_MxCee_America/Amerika_AO-50.jpg	0d9037f9	2017-11-30 23:05:30	\N	f
50268	1345		2018-03-29 15:37:34	5877	3918	fotos20172018/20171121_MxCee_America/Amerika_AO-51.jpg	4b625ca4	2017-11-30 23:11:31	\N	f
50269	1345		2018-03-29 15:37:34	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-52.jpg	11059448	2017-11-30 23:22:34	\N	f
50270	1345		2018-03-29 15:37:34	5710	3807	fotos20172018/20171121_MxCee_America/Amerika_AO-53.jpg	180f7ae8	2017-11-30 23:33:12	\N	f
50271	1345		2018-03-29 15:37:34	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-55.jpg	c6c0162f	2017-12-01 01:45:24	\N	f
50272	1345		2018-03-29 15:37:34	5721	3814	fotos20172018/20171121_MxCee_America/Amerika_AO-57.jpg	664c2b9e	2017-12-01 02:52:40	\N	f
50273	1345		2018-03-29 15:37:34	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-58.jpg	29078788	2017-12-01 02:56:27	\N	f
50274	1345		2018-03-29 15:37:34	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171201_103635.jpg	dc2d27c3	2017-12-01 10:36:35	\N	f
50275	1345		2018-03-29 15:37:34	4143	3072	fotos20172018/20171121_MxCee_America/IMG_20171201_110727-42.jpg	497e2108	2017-12-01 11:07:29	\N	f
50276	1345		2018-03-29 15:37:35	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171201_111719.jpg	5c08c721	2017-12-01 11:17:19	\N	f
50277	1345		2018-03-29 15:37:35	2448	3264	fotos20172018/20171121_MxCee_America/IMG_7803.jpg	d5264350	2017-12-01 11:37:05	\N	f
50278	1345		2018-03-29 15:37:35	2448	3264	fotos20172018/20171121_MxCee_America/IMG_7804.jpg	3c918aef	2017-12-01 11:37:42	\N	f
50279	1345		2018-03-29 15:37:35	5783	3855	fotos20172018/20171121_MxCee_America/Amerika_AO-59.jpg	187fa64c	2017-12-01 21:17:47	\N	f
50280	1345		2018-03-29 15:37:35	5593	3729	fotos20172018/20171121_MxCee_America/Amerika_AO-60.jpg	dc595965	2017-12-01 21:24:37	\N	f
50281	1345		2018-03-29 15:37:35	5672	3781	fotos20172018/20171121_MxCee_America/Amerika_AO-62.jpg	55edc55a	2017-12-01 23:33:35	\N	f
50282	1345		2018-03-29 15:37:35	5001	3334	fotos20172018/20171121_MxCee_America/Amerika_AO-64.jpg	6dc0a6a5	2017-12-01 23:44:03	\N	f
50283	1345		2018-03-29 15:37:35	3456	4608	fotos20172018/20171121_MxCee_America/IMG_20171202_125353.jpg	a5b17e2e	2017-12-02 12:53:53	\N	f
50284	1345		2018-03-29 15:37:35	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171203_113903-50.jpg	267343da	2017-12-03 11:39:04	\N	f
50285	1345		2018-03-29 15:37:35	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171203_141329.jpg	5ae14292	2017-12-03 14:13:29	\N	f
50286	1345		2018-03-29 15:37:35	1728	2304	fotos20172018/20171121_MxCee_America/IMG_20171203_152958.jpg	fc5fd646	2017-12-03 15:29:58	\N	f
50287	1345		2018-03-29 15:37:35	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171203_163044.jpg	dda72f79	2017-12-03 16:30:44	\N	f
50288	1345		2018-03-29 15:37:35	2448	3264	fotos20172018/20171121_MxCee_America/IMG_7915.jpg	5cccf291	2017-12-03 16:59:55	\N	f
50289	1345		2018-03-29 15:37:35	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171203_173704.jpg	e107aa0b	2017-12-03 17:37:04	\N	f
50290	1345		2018-03-29 15:37:35	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171204_094444.jpg	c6cc718b	2017-12-04 09:44:44	\N	f
50291	1345		2018-03-29 15:37:35	2592	1944	fotos20172018/20171121_MxCee_America/IMG_20171204_125657-53.jpg	35b1d038	2017-12-04 12:56:58	\N	f
50292	1345		2018-03-29 15:37:35	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171204_133209_01.jpg	63845bfe	2017-12-04 13:32:10	\N	f
50293	1345		2018-03-29 15:37:35	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171204_133216.jpg	307a08c6	2017-12-04 13:32:16	\N	f
50294	1345		2018-03-29 15:37:36	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171204_140949-55.jpg	564b1330	2017-12-04 14:09:51	\N	f
50295	1345		2018-03-29 15:37:36	2592	1944	fotos20172018/20171121_MxCee_America/IMG_20171204_141639.jpg	04e2dfd8	2017-12-04 14:16:39	\N	f
50296	1345		2018-03-29 15:37:36	2976	3968	fotos20172018/20171121_MxCee_America/IMG_20171204_142448.jpg	3b6e8684	2017-12-04 14:24:50	\N	f
50297	1345		2018-03-29 15:37:36	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171204_154723.jpg	88aa282f	2017-12-04 15:47:23	\N	f
50298	1345		2018-03-29 15:37:36	3264	2448	fotos20172018/20171121_MxCee_America/IMG_20171204_155856.jpg	2d48377b	2017-12-04 15:58:57	\N	f
50299	1345		2018-03-29 15:37:36	4032	3024	fotos20172018/20171121_MxCee_America/9470EF7F-4703-41A8-BA53-37182ADF2C6E.jpg	5e9e44c0	2017-12-04 16:19:36	\N	f
50300	1345		2018-03-29 15:37:36	3024	4032	fotos20172018/20171121_MxCee_America/DB13C4E1-045C-4F7C-8CFE-0C81F136EE74.jpg	62614cb0	2017-12-04 16:30:33	\N	f
50301	1345		2018-03-29 15:37:36	3264	2448	fotos20172018/20171121_MxCee_America/IMG_8007.jpg	a5dd258e	2017-12-04 17:29:52	\N	f
50302	1345		2018-03-29 15:37:36	4032	3024	fotos20172018/20171121_MxCee_America/IMG_7654.jpg	0a2d90a4	2017-12-04 20:38:51	\N	f
50303	1345		2018-03-29 15:37:36	5732	3821	fotos20172018/20171121_MxCee_America/Amerika_AO-69.jpg	5ea57564	2017-12-05 02:37:11	\N	f
50304	1345		2018-03-29 15:37:36	2304	1728	fotos20172018/20171121_MxCee_America/IMG_20171205_094821.jpg	2954df1f	2017-12-05 09:48:21	\N	f
50305	1345		2018-03-29 15:37:36	3006	5344	fotos20172018/20171121_MxCee_America/IMG_20171205_141001796.jpg	6652aed3	2017-12-05 14:10:01	\N	f
50306	1345		2018-03-29 15:37:36	3264	2448	fotos20172018/20171121_MxCee_America/IMG_8066.jpg	7dbabe7e	2017-12-05 14:11:18	\N	f
50307	1345		2018-03-29 15:37:36	2448	3264	fotos20172018/20171121_MxCee_America/IMG_8068.jpg	f935c98b	2017-12-05 14:11:36	\N	f
50308	1345		2018-03-29 15:37:36	4160	3120	fotos20172018/20171121_MxCee_America/guus_mxcee_kleiner_0026.jpg	0d97386d	2017-12-05 14:23:22	\N	f
50309	1345		2018-03-29 15:37:36	2610	4640	fotos20172018/20171121_MxCee_America/IMG_20171205_144828.jpg	f5d52088	2017-12-05 14:48:28	\N	f
50310	1345		2018-03-29 15:37:36	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171205_145238.jpg	844faf99	2017-12-05 14:52:38	\N	f
50311	1345		2018-03-29 15:37:36	3006	5344	fotos20172018/20171121_MxCee_America/IMG_20171205_145413018.jpg	f57fb85f	2017-12-05 14:54:13	\N	f
50312	1345		2018-03-29 15:37:36	3264	1836	fotos20172018/20171121_MxCee_America/20171205_150049.jpg	0a155ab4	2017-12-05 15:00:48	\N	f
50313	1345		2018-03-29 15:37:37	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171205_151349.jpg	78086160	2017-12-05 15:13:49	\N	f
50314	1345		2018-03-29 15:37:37	5344	3006	fotos20172018/20171121_MxCee_America/IMG_20171205_155850637.jpg	123e8ca0	2017-12-05 15:58:50	\N	f
50315	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0470.NEF.jpg	5c03a389	2017-12-05 23:52:42	\N	f
50316	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0481.NEF.jpg	3da059c5	2017-12-06 00:38:01	\N	f
50317	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0489.NEF.jpg	1270ec06	2017-12-06 00:48:04	\N	f
50318	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0492.NEF.jpg	5f693218	2017-12-06 00:50:59	\N	f
50319	1345		2018-03-29 15:37:37	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-71.jpg	925793dd	2017-12-06 01:01:41	\N	f
50320	1345		2018-03-29 15:37:37	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-72.jpg	dc33b616	2017-12-06 01:03:54	\N	f
50321	1345		2018-03-29 15:37:37	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0511.NEF.jpg	714901fb	2017-12-06 01:12:38	\N	f
50322	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0523.NEF.jpg	bc814cc3	2017-12-06 01:16:13	\N	f
50323	1345		2018-03-29 15:37:37	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0527.NEF.jpg	a08b2479	2017-12-06 01:17:18	\N	f
50324	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0534.NEF.jpg	0974adeb	2017-12-06 01:20:09	\N	f
50325	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0543.NEF.jpg	692e1fcf	2017-12-06 01:26:36	\N	f
50326	1345		2018-03-29 15:37:37	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0547.NEF.jpg	ea11e1ff	2017-12-06 01:33:53	\N	f
50327	1345		2018-03-29 15:37:37	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-75.jpg	8569b68c	2017-12-06 02:00:51	\N	f
50328	1345		2018-03-29 15:37:38	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0581.NEF.jpg	fab82f5b	2017-12-06 02:14:30	\N	f
50329	1345		2018-03-29 15:37:38	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-76.jpg	4a4c28ec	2017-12-06 09:34:57	\N	f
50330	1345		2018-03-29 15:37:38	2994	3987	fotos20172018/20171121_MxCee_America/guus_mxcee_kleiner_0030.jpg	775c6dca	2017-12-06 12:24:11	\N	f
50331	1345		2018-03-29 15:37:38	4085	3029	fotos20172018/20171121_MxCee_America/IMG_20171206_125136-61.jpg	8d20820c	2017-12-06 12:51:37	\N	f
50332	1345		2018-03-29 15:37:38	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171206_135827.jpg	9b196ddd	2017-12-06 13:58:27	\N	f
50333	1345		2018-03-29 15:37:38	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171206_143754-64.jpg	42dbc6d9	2017-12-06 14:37:56	\N	f
50334	1345		2018-03-29 15:37:38	5556	3704	fotos20172018/20171121_MxCee_America/Amerika_AO-77.jpg	b0ddcd66	2017-12-06 22:45:54	\N	f
50335	1345		2018-03-29 15:37:38	5740	3827	fotos20172018/20171121_MxCee_America/Amerika_AO-80.jpg	1332a43b	2017-12-06 22:57:14	\N	f
50336	1345		2018-03-29 15:37:38	5584	3723	fotos20172018/20171121_MxCee_America/Amerika_AO-81.jpg	69bd88f2	2017-12-06 22:57:25	\N	f
50337	1345		2018-03-29 15:37:38	4483	2989	fotos20172018/20171121_MxCee_America/Amerika_AO-82.jpg	23aee26d	2017-12-06 22:58:06	\N	f
50338	1345		2018-03-29 15:37:38	5944	3963	fotos20172018/20171121_MxCee_America/Amerika_AO-84.jpg	5abcfa16	2017-12-07 00:55:01	\N	f
50339	1345		2018-03-29 15:37:38	5887	3925	fotos20172018/20171121_MxCee_America/Amerika_AO-87.jpg	69601531	2017-12-07 01:01:44	\N	f
50340	1345		2018-03-29 15:37:38	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171207_160140.jpg	c70c2436	2017-12-07 16:01:40	\N	f
50341	1345		2018-03-29 15:37:38	1536	1152	fotos20172018/20171121_MxCee_America/IMG_20171207_210204.jpg	c47c9828	2017-12-07 21:02:04	\N	f
50342	1345		2018-03-29 15:37:38	5909	3939	fotos20172018/20171121_MxCee_America/Amerika_AO-91.jpg	23b5a5a5	2017-12-08 02:06:04	\N	f
50343	1345		2018-03-29 15:37:38	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-93.jpg	f331cf3c	2017-12-08 03:03:51	\N	f
50344	1345		2018-03-29 15:37:38	2592	1944	fotos20172018/20171121_MxCee_America/IMG_20171208_123905-71.jpg	bb928ab8	2017-12-08 12:39:05	\N	f
50345	1345		2018-03-29 15:37:38	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171208_171025.jpg	42c53780	2017-12-08 17:10:25	\N	f
50346	1345		2018-03-29 15:37:38	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171208_220623.jpg	b63ac89c	2017-12-08 22:06:23	\N	f
50347	1345		2018-03-29 15:37:38	5677	3785	fotos20172018/20171121_MxCee_America/Amerika_AO-95.jpg	12d6f465	2017-12-09 03:32:43	\N	f
50348	1345		2018-03-29 15:37:38	4640	2610	fotos20172018/20171121_MxCee_America/IMG_20171209_094142.jpg	bc27401e	2017-12-09 09:41:42	\N	f
50349	1345		2018-03-29 15:37:38	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171209_130908.jpg	63e9f6a3	2017-12-09 13:09:08	\N	f
50350	1345		2018-03-29 15:37:38	4085	3029	fotos20172018/20171121_MxCee_America/IMG_20171209_132100-74.jpg	6aa246c8	2017-12-09 13:21:01	\N	f
50351	1345		2018-03-29 15:37:39	4160	3120	fotos20172018/20171121_MxCee_America/guus_mxcee_kleiner_0032.jpg	11e8df3f	2017-12-09 15:41:44	\N	f
50352	1345		2018-03-29 15:37:39	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171210_122128.jpg	f46fe191	2017-12-10 12:21:29	\N	f
50353	1345		2018-03-29 15:37:39	4608	3456	fotos20172018/20171121_MxCee_America/IMG_20171210_155217.jpg	92a9506f	2017-12-10 15:52:17	\N	f
50354	1345		2018-03-29 15:37:39	3264	2448	fotos20172018/20171121_MxCee_America/IMG_8272.jpg	b078e562	2017-12-10 16:27:19	\N	f
50355	1345		2018-03-29 15:37:39	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-100.jpg	7634b04d	2017-12-10 21:12:26	\N	f
50356	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0685.NEF.jpg	ba1f2755	2017-12-10 23:54:27	\N	f
50357	1345		2018-03-29 15:37:39	5881	3921	fotos20172018/20171121_MxCee_America/Amerika_AO-104.jpg	1a2c3113	2017-12-11 00:33:52	\N	f
50358	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0690.NEF.jpg	d6ccfe3d	2017-12-11 00:36:36	\N	f
50359	1345		2018-03-29 15:37:39	5757	3838	fotos20172018/20171121_MxCee_America/Amerika_AO-108.jpg	e430c075	2017-12-11 00:37:12	\N	f
50360	1345		2018-03-29 15:37:39	4012	6034	fotos20172018/20171121_MxCee_America/DSC_0698.NEF.jpg	282159c5	2017-12-11 00:40:08	\N	f
50361	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0713.NEF.jpg	9df9da72	2017-12-11 00:42:52	\N	f
50362	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0715.NEF.jpg	c37ffa57	2017-12-11 00:43:31	\N	f
50363	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0725.NEF.jpg	c44f2b03	2017-12-11 00:58:51	\N	f
50364	1345		2018-03-29 15:37:39	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-111.jpg	a6780490	2017-12-11 01:20:41	\N	f
50365	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0751.NEF.jpg	1788c503	2017-12-11 01:40:35	\N	f
50366	1345		2018-03-29 15:37:39	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-114.jpg	6df887a4	2017-12-11 01:58:08	\N	f
50367	1345		2018-03-29 15:37:39	6000	4000	fotos20172018/20171121_MxCee_America/Amerika_AO-115.jpg	a8043c0d	2017-12-11 02:00:11	\N	f
50368	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0780.NEF.jpg	f6c9b029	2017-12-11 02:24:09	\N	f
50369	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0783.NEF.jpg	75ec1e15	2017-12-11 02:45:52	\N	f
50370	1345		2018-03-29 15:37:39	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171211_153236.jpg	714ad549	2017-12-11 15:32:36	\N	f
50371	1345		2018-03-29 15:37:39	5361	3574	fotos20172018/20171121_MxCee_America/Amerika_AO-118.jpg	0465d31c	2017-12-11 21:40:18	\N	f
50372	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0798.NEF.jpg	87aadd6d	2017-12-11 21:44:24	\N	f
50373	1345		2018-03-29 15:37:39	5627	3751	fotos20172018/20171121_MxCee_America/Amerika_AO-121.jpg	72734824	2017-12-11 22:26:10	\N	f
50374	1345		2018-03-29 15:37:39	6034	4012	fotos20172018/20171121_MxCee_America/DSC_0847.NEF.jpg	bc90c586	2017-12-11 23:35:59	\N	f
50375	1345		2018-03-29 15:37:39	2448	3264	fotos20172018/20171121_MxCee_America/IMG_8351.jpg	c7f544e0	2017-12-12 11:50:00	\N	f
50376	1345		2018-03-29 15:37:39	3120	4160	fotos20172018/20171121_MxCee_America/IMG_20171212_123610.jpg	b29015c3	2017-12-12 12:36:11	\N	f
50377	1345		2018-03-29 15:37:39	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171212_124556.jpg	73df8d3a	2017-12-12 12:45:56	\N	f
50378	1345		2018-03-29 15:37:40	5344	3006	fotos20172018/20171121_MxCee_America/IMG_20171212_135718432.jpg	1759d6f0	2017-12-12 13:57:18	\N	f
50379	1345		2018-03-29 15:37:40	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171212_150857-82.jpg	79da6dd6	2017-12-12 15:08:58	\N	f
50380	1345		2018-03-29 15:37:40	4160	3120	fotos20172018/20171121_MxCee_America/IMG_20171212_180655.jpg	efdcbd5f	2017-12-12 18:06:56	\N	f
50381	1345		2018-03-29 15:37:40	5505	3670	fotos20172018/20171121_MxCee_America/Amerika_AO-123.jpg	e43dfcf0	2017-12-12 22:09:26	\N	f
50382	1345		2018-03-29 15:37:40	4208	3120	fotos20172018/20171121_MxCee_America/IMG_20171213_144302-85.jpg	be217c78	2017-12-13 14:43:07	\N	f
50383	1345		2018-03-29 15:37:40	1200	1600	fotos20172018/20171121_MxCee_America/IMG-20171127-WA0011.jpg	056dbfdb	2018-03-22 18:16:32	\N	f
50384	1345		2018-03-29 15:37:40	1600	1200	fotos20172018/20171121_MxCee_America/IMG-20171202-WA0035.jpg	1a7e7fb1	2018-03-22 18:16:32	\N	f
50385	1345		2018-03-29 15:37:40	1124	1500	fotos20172018/20171121_MxCee_America/IMG-20171123-WA0024.jpg	8abecf3e	2018-03-22 18:16:32	\N	f
50386	1345		2018-03-29 15:37:40	960	1280	fotos20172018/20171121_MxCee_America/IMG-20171211-WA0012.jpg	0a1630c2	2018-03-22 18:16:32	\N	f
50387	1345		2018-03-29 15:37:40	1600	1200	fotos20172018/20171121_MxCee_America/IMG_7393.jpg	08271da7	2018-03-22 18:16:39	\N	f
50388	1345		2018-03-29 15:37:40	1600	1200	fotos20172018/20171121_MxCee_America/IMG_7694.jpg	df760541	2018-03-22 18:16:45	\N	f
50389	1345		2018-03-29 15:37:40	1328	747	fotos20172018/20171121_MxCee_America/IMG_7838.jpg	ec41b450	2018-03-22 18:16:54	\N	f
50390	1345		2018-03-29 15:37:40	1328	747	fotos20172018/20171121_MxCee_America/IMG_7837.jpg	7120d14a	2018-03-22 18:16:54	\N	f
50391	1345		2018-03-29 15:37:40	720	1280	fotos20172018/20171121_MxCee_America/IMG_8101.jpg	7665a8d3	2018-03-22 18:16:56	\N	f
50392	1345		2018-03-29 15:37:40	3024	4032	fotos20172018/20171121_MxCee_America/AAC949AC-A139-488F-9B61-05EEEC78BD90.jpg	4ffbe0d6	2018-03-22 18:16:57	\N	f
50393	1345		2018-03-29 15:37:40	750	1334	fotos20172018/20171121_MxCee_America/46D53D28-1E12-46E4-9213-72AB2A839C1C.jpg	385d9158	2018-03-22 18:16:57	\N	f
50394	1345		2018-03-29 15:37:40	750	1334	fotos20172018/20171121_MxCee_America/B17D8396-0912-4FAD-97DB-E2481456FE82.jpg	33197626	2018-03-22 18:16:57	\N	f
50395	1346		2018-04-03 16:53:28	2924	1949	fotos20172018/20171221_Gala/PhotoBooth/_MG_2893.jpg	09872d0c	2017-12-22 00:14:19	\N	f
50396	1346		2018-04-03 16:53:28	3692	2461	fotos20172018/20171221_Gala/PhotoBooth/_MG_2894.jpg	626e230b	2017-12-22 00:14:26	\N	f
50397	1346		2018-04-03 16:53:28	4096	2731	fotos20172018/20171221_Gala/PhotoBooth/_MG_2903.jpg	e58cd158	2017-12-22 00:16:12	\N	f
50398	1346		2018-04-03 16:53:28	3661	2441	fotos20172018/20171221_Gala/PhotoBooth/_MG_2906.jpg	89a4cb24	2017-12-22 00:16:52	\N	f
50399	1346		2018-04-03 16:53:28	2664	1776	fotos20172018/20171221_Gala/PhotoBooth/_MG_2909.jpg	76dbb38d	2017-12-22 00:20:40	\N	f
50400	1346		2018-04-03 16:53:28	3434	2289	fotos20172018/20171221_Gala/PhotoBooth/_MG_2913.jpg	1d45e952	2017-12-22 00:21:16	\N	f
50401	1346		2018-04-03 16:53:28	3096	2064	fotos20172018/20171221_Gala/PhotoBooth/_MG_2914.jpg	fe4a5b48	2017-12-22 00:22:17	\N	f
50402	1346		2018-04-03 16:53:28	3446	2297	fotos20172018/20171221_Gala/PhotoBooth/_MG_2916.jpg	6460e06a	2017-12-22 00:22:19	\N	f
50403	1346		2018-04-03 16:53:28	3081	2054	fotos20172018/20171221_Gala/PhotoBooth/_MG_2917.jpg	91efd046	2017-12-22 00:22:22	\N	f
50404	1346		2018-04-03 16:53:28	3424	2283	fotos20172018/20171221_Gala/PhotoBooth/_MG_2919.jpg	d1a8408b	2017-12-22 00:23:38	\N	f
50405	1346		2018-04-03 16:53:28	4519	3013	fotos20172018/20171221_Gala/PhotoBooth/_MG_2923.jpg	34e332ad	2017-12-22 00:26:03	\N	f
50406	1346		2018-04-03 16:53:28	2712	1808	fotos20172018/20171221_Gala/PhotoBooth/_MG_2926.jpg	a67b6c49	2017-12-22 00:27:18	\N	f
50407	1346		2018-04-03 16:53:28	4223	2815	fotos20172018/20171221_Gala/PhotoBooth/_MG_2927.jpg	676393e9	2017-12-22 00:28:04	\N	f
50408	1346		2018-04-03 16:53:28	2007	3010	fotos20172018/20171221_Gala/PhotoBooth/_MG_2930.jpg	6cab96ed	2017-12-22 00:29:44	\N	f
50409	1346		2018-04-03 16:53:28	4178	2785	fotos20172018/20171221_Gala/PhotoBooth/_MG_2937.jpg	bae164f1	2017-12-22 00:30:48	\N	f
50410	1346		2018-04-03 16:53:28	2651	3977	fotos20172018/20171221_Gala/PhotoBooth/_MG_2941.jpg	1bbe135d	2017-12-22 00:31:31	\N	f
50411	1346		2018-04-03 16:53:28	1665	2498	fotos20172018/20171221_Gala/PhotoBooth/_MG_2942.jpg	3d97b099	2017-12-22 00:34:12	\N	f
50412	1346		2018-04-03 16:53:28	3064	2043	fotos20172018/20171221_Gala/PhotoBooth/_MG_2947.jpg	2be86525	2017-12-22 00:35:26	\N	f
50413	1346		2018-04-03 16:53:28	4245	2830	fotos20172018/20171221_Gala/PhotoBooth/_MG_2951.jpg	ee312909	2017-12-22 00:37:01	\N	f
50414	1346		2018-04-03 16:53:28	3578	2385	fotos20172018/20171221_Gala/PhotoBooth/_MG_2954.jpg	1fa25591	2017-12-22 00:37:17	\N	f
50415	1346		2018-04-03 16:53:28	4741	3160	fotos20172018/20171221_Gala/PhotoBooth/_MG_2957.jpg	3ac49362	2017-12-22 00:37:50	\N	f
50416	1346		2018-04-03 16:53:28	3773	2515	fotos20172018/20171221_Gala/PhotoBooth/_MG_2961.jpg	d73c233b	2017-12-22 00:38:03	\N	f
50417	1346		2018-04-03 16:53:29	2196	3294	fotos20172018/20171221_Gala/PhotoBooth/_MG_2968.jpg	a23add49	2017-12-22 00:40:35	\N	f
50418	1346		2018-04-03 16:53:29	4484	2989	fotos20172018/20171221_Gala/PhotoBooth/_MG_2973.jpg	1ef8a4db	2017-12-22 00:41:31	\N	f
50419	1346		2018-04-03 16:53:29	3831	2554	fotos20172018/20171221_Gala/PhotoBooth/_MG_2977.jpg	5cc65845	2017-12-22 00:42:10	\N	f
50420	1346		2018-04-03 16:53:29	3770	2513	fotos20172018/20171221_Gala/PhotoBooth/_MG_2978.jpg	8bcea6d1	2017-12-22 00:43:43	\N	f
50421	1346		2018-04-03 16:53:29	1895	2842	fotos20172018/20171221_Gala/PhotoBooth/_MG_2980.jpg	90c78e7b	2017-12-22 00:44:06	\N	f
50422	1346		2018-04-03 16:53:29	2187	3281	fotos20172018/20171221_Gala/PhotoBooth/_MG_2983.jpg	32ee48dd	2017-12-22 00:46:12	\N	f
50423	1346		2018-04-03 16:53:29	1361	2041	fotos20172018/20171221_Gala/PhotoBooth/_MG_2984.jpg	0754eed6	2017-12-22 00:47:33	\N	f
50424	1346		2018-04-03 16:53:29	2801	1867	fotos20172018/20171221_Gala/PhotoBooth/_MG_2987.jpg	ab7cfa52	2017-12-22 00:49:36	\N	f
50425	1346		2018-04-03 16:53:29	3174	2116	fotos20172018/20171221_Gala/PhotoBooth/_MG_2988.jpg	846d593b	2017-12-22 00:49:37	\N	f
50426	1346		2018-04-03 16:53:29	3141	2094	fotos20172018/20171221_Gala/PhotoBooth/_MG_2989.jpg	9af0ec4d	2017-12-22 00:49:38	\N	f
50427	1346		2018-04-03 16:53:29	3026	2017	fotos20172018/20171221_Gala/PhotoBooth/_MG_2990.jpg	a0b77a5a	2017-12-22 00:49:44	\N	f
50428	1346		2018-04-03 16:53:29	2304	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_2992.jpg	8418d83d	2017-12-22 00:52:42	\N	f
50429	1346		2018-04-03 16:53:29	2070	3105	fotos20172018/20171221_Gala/PhotoBooth/_MG_2997.jpg	7e497ab3	2017-12-22 00:53:27	\N	f
50430	1346		2018-04-03 16:53:29	4771	3181	fotos20172018/20171221_Gala/PhotoBooth/_MG_2998.jpg	eb2917b1	2017-12-22 00:54:04	\N	f
50431	1346		2018-04-03 16:53:29	4666	2995	fotos20172018/20171221_Gala/PhotoBooth/_MG_3000.jpg	a5c87f79	2017-12-22 00:54:06	\N	f
50432	1346		2018-04-03 16:53:29	4637	3091	fotos20172018/20171221_Gala/PhotoBooth/_MG_3002.jpg	aaa45bc1	2017-12-22 00:54:21	\N	f
50433	1346		2018-04-03 16:53:29	3814	2543	fotos20172018/20171221_Gala/PhotoBooth/_MG_3003.jpg	613bc49d	2017-12-22 00:56:02	\N	f
50434	1346		2018-04-03 16:53:29	2635	3029	fotos20172018/20171221_Gala/PhotoBooth/_MG_3004.jpg	a8d78e5d	2017-12-22 00:56:12	\N	f
50435	1346		2018-04-03 16:53:29	4101	2734	fotos20172018/20171221_Gala/PhotoBooth/_MG_3009.jpg	7af9e3b1	2017-12-22 00:56:34	\N	f
50436	1346		2018-04-03 16:53:29	1981	2971	fotos20172018/20171221_Gala/PhotoBooth/_MG_3011.jpg	9ce5ee4c	2017-12-22 00:58:21	\N	f
50437	1346		2018-04-03 16:53:29	4393	2929	fotos20172018/20171221_Gala/PhotoBooth/_MG_3014.jpg	a8b22f4d	2017-12-22 00:59:01	\N	f
50438	1346		2018-04-03 16:53:29	4144	2763	fotos20172018/20171221_Gala/PhotoBooth/_MG_3015.jpg	e4b57e8b	2017-12-22 00:59:05	\N	f
50439	1346		2018-04-03 16:53:29	4388	2925	fotos20172018/20171221_Gala/PhotoBooth/_MG_3019.jpg	95a8e30f	2017-12-22 01:00:06	\N	f
50440	1346		2018-04-03 16:53:29	3602	2401	fotos20172018/20171221_Gala/PhotoBooth/_MG_3020.jpg	b094d855	2017-12-22 01:00:25	\N	f
50441	1346		2018-04-03 16:53:29	3340	2227	fotos20172018/20171221_Gala/PhotoBooth/_MG_3022.jpg	26a4925c	2017-12-22 01:00:26	\N	f
50442	1346		2018-04-03 16:53:29	3530	2353	fotos20172018/20171221_Gala/PhotoBooth/_MG_3024.jpg	3b67288a	2017-12-22 01:01:32	\N	f
50443	1346		2018-04-03 16:53:29	4274	2849	fotos20172018/20171221_Gala/PhotoBooth/_MG_3026.jpg	1f9bdd53	2017-12-22 01:04:55	\N	f
50444	1346		2018-04-03 16:53:29	4331	2887	fotos20172018/20171221_Gala/PhotoBooth/_MG_3029.jpg	c490ea08	2017-12-22 01:05:15	\N	f
50445	1346		2018-04-03 16:53:29	4704	3136	fotos20172018/20171221_Gala/PhotoBooth/_MG_3031.jpg	9725e83d	2017-12-22 01:05:32	\N	f
50446	1346		2018-04-03 16:53:29	2537	3805	fotos20172018/20171221_Gala/PhotoBooth/_MG_3034.jpg	fda8add8	2017-12-22 01:06:33	\N	f
50447	1346		2018-04-03 16:53:29	2129	3194	fotos20172018/20171221_Gala/PhotoBooth/_MG_3035.jpg	e404b810	2017-12-22 01:06:35	\N	f
50448	1346		2018-04-03 16:53:30	3534	2356	fotos20172018/20171221_Gala/PhotoBooth/_MG_3039.jpg	8086d033	2017-12-22 01:07:28	\N	f
50449	1346		2018-04-03 16:53:30	3137	2091	fotos20172018/20171221_Gala/PhotoBooth/_MG_3042.jpg	004a057f	2017-12-22 01:08:10	\N	f
50450	1346		2018-04-03 16:53:30	2798	1865	fotos20172018/20171221_Gala/PhotoBooth/_MG_3043.jpg	95ce155a	2017-12-22 01:08:22	\N	f
50451	1346		2018-04-03 16:53:30	2483	2748	fotos20172018/20171221_Gala/PhotoBooth/_MG_3044.jpg	8bcd7bc9	2017-12-22 01:08:28	\N	f
50452	1346		2018-04-03 16:53:30	2063	2611	fotos20172018/20171221_Gala/PhotoBooth/_MG_3046.jpg	6c07e231	2017-12-22 01:08:30	\N	f
50453	1346		2018-04-03 16:53:30	3419	2279	fotos20172018/20171221_Gala/PhotoBooth/_MG_3049.jpg	6a948e69	2017-12-22 01:08:55	\N	f
50454	1346		2018-04-03 16:53:30	4567	2592	fotos20172018/20171221_Gala/PhotoBooth/_MG_3052.jpg	41ce964e	2017-12-22 01:12:33	\N	f
50455	1346		2018-04-03 16:53:30	3120	2041	fotos20172018/20171221_Gala/PhotoBooth/_MG_3055.jpg	0c354795	2017-12-22 01:12:45	\N	f
50456	1346		2018-04-03 16:53:30	3083	2120	fotos20172018/20171221_Gala/PhotoBooth/_MG_3057.jpg	33a1cf26	2017-12-22 01:12:47	\N	f
50457	1346		2018-04-03 16:53:30	4161	2774	fotos20172018/20171221_Gala/PhotoBooth/_MG_3064.jpg	5141aea5	2017-12-22 01:14:19	\N	f
50458	1346		2018-04-03 16:53:30	3250	2167	fotos20172018/20171221_Gala/PhotoBooth/_MG_3066.jpg	b51592be	2017-12-22 01:15:33	\N	f
50459	1346		2018-04-03 16:53:30	1842	2763	fotos20172018/20171221_Gala/PhotoBooth/_MG_3067.jpg	f4bc96de	2017-12-22 01:16:24	\N	f
50460	1346		2018-04-03 16:53:30	4667	2545	fotos20172018/20171221_Gala/PhotoBooth/_MG_3068.jpg	80800ee9	2017-12-22 01:16:29	\N	f
50461	1346		2018-04-03 16:53:30	2789	2398	fotos20172018/20171221_Gala/PhotoBooth/_MG_3074.jpg	fd220c6f	2017-12-22 01:16:37	\N	f
50462	1346		2018-04-03 16:53:30	3355	2237	fotos20172018/20171221_Gala/PhotoBooth/_MG_3075.jpg	e6cb5ace	2017-12-22 01:17:08	\N	f
50463	1346		2018-04-03 16:53:30	3606	2404	fotos20172018/20171221_Gala/PhotoBooth/_MG_3081.jpg	c0354a04	2017-12-22 01:18:48	\N	f
50464	1346		2018-04-03 16:53:30	3642	2428	fotos20172018/20171221_Gala/PhotoBooth/_MG_3086.jpg	7a7c465a	2017-12-22 01:19:58	\N	f
50465	1346		2018-04-03 16:53:30	3617	2411	fotos20172018/20171221_Gala/PhotoBooth/_MG_3087.jpg	4f387b3f	2017-12-22 01:19:59	\N	f
50466	1346		2018-04-03 16:53:30	3544	2363	fotos20172018/20171221_Gala/PhotoBooth/_MG_3088.jpg	3e757a13	2017-12-22 01:20:00	\N	f
50467	1346		2018-04-03 16:53:30	3818	2545	fotos20172018/20171221_Gala/PhotoBooth/_MG_3089.jpg	a0edb12e	2017-12-22 01:20:02	\N	f
50468	1346		2018-04-03 16:53:30	3336	2224	fotos20172018/20171221_Gala/PhotoBooth/_MG_3092.jpg	5575f187	2017-12-22 01:20:12	\N	f
50469	1346		2018-04-03 16:53:30	4022	2681	fotos20172018/20171221_Gala/PhotoBooth/_MG_3094.jpg	8e814659	2017-12-22 01:20:31	\N	f
50470	1346		2018-04-03 16:53:30	3740	2493	fotos20172018/20171221_Gala/PhotoBooth/_MG_3096.jpg	111bb6d7	2017-12-22 01:20:34	\N	f
50471	1346		2018-04-03 16:53:30	4301	2867	fotos20172018/20171221_Gala/PhotoBooth/_MG_3097.jpg	9a69133e	2017-12-22 01:21:50	\N	f
50472	1346		2018-04-03 16:53:30	4191	2794	fotos20172018/20171221_Gala/PhotoBooth/_MG_3098.jpg	90c5734f	2017-12-22 01:21:53	\N	f
50473	1346		2018-04-03 16:53:30	3654	2436	fotos20172018/20171221_Gala/PhotoBooth/_MG_3102.jpg	8ddc4cf4	2017-12-22 01:22:37	\N	f
50474	1346		2018-04-03 16:53:30	3493	2329	fotos20172018/20171221_Gala/PhotoBooth/_MG_3103.jpg	8c2ea3d1	2017-12-22 01:22:38	\N	f
50475	1346		2018-04-03 16:53:30	3698	2465	fotos20172018/20171221_Gala/PhotoBooth/_MG_3105.jpg	b5f7b37b	2017-12-22 01:22:39	\N	f
50476	1346		2018-04-03 16:53:30	3337	2225	fotos20172018/20171221_Gala/PhotoBooth/_MG_3108.jpg	88d4bce7	2017-12-22 01:22:42	\N	f
50477	1346		2018-04-03 16:53:30	3057	3057	fotos20172018/20171221_Gala/PhotoBooth/_MG_3110.jpg	6e05296f	2017-12-22 01:23:31	\N	f
50478	1346		2018-04-03 16:53:30	3197	3197	fotos20172018/20171221_Gala/PhotoBooth/_MG_3113.jpg	b4611028	2017-12-22 01:23:54	\N	f
50479	1346		2018-04-03 16:53:30	3359	2926	fotos20172018/20171221_Gala/PhotoBooth/_MG_3114.jpg	26c860b4	2017-12-22 01:23:56	\N	f
50480	1346		2018-04-03 16:53:30	4119	2746	fotos20172018/20171221_Gala/PhotoBooth/_MG_3119.jpg	25077952	2017-12-22 01:24:23	\N	f
50481	1346		2018-04-03 16:53:30	4453	2552	fotos20172018/20171221_Gala/PhotoBooth/_MG_3122.jpg	b620bf36	2017-12-22 01:24:29	\N	f
50482	1346		2018-04-03 16:53:30	3764	2509	fotos20172018/20171221_Gala/PhotoBooth/_MG_3126.jpg	0b75c389	2017-12-22 01:24:32	\N	f
50483	1346		2018-04-03 16:53:30	4627	2015	fotos20172018/20171221_Gala/PhotoBooth/_MG_3131.jpg	c905b542	2017-12-22 01:24:42	\N	f
50484	1346		2018-04-03 16:53:30	3928	2619	fotos20172018/20171221_Gala/PhotoBooth/_MG_3142.jpg	3cfbeab5	2017-12-22 01:25:26	\N	f
50485	1346		2018-04-03 16:53:31	3854	2569	fotos20172018/20171221_Gala/PhotoBooth/_MG_3144.jpg	8e59e5e1	2017-12-22 01:25:26	\N	f
50486	1346		2018-04-03 16:53:31	4344	2896	fotos20172018/20171221_Gala/PhotoBooth/_MG_3147.jpg	b3cb58b1	2017-12-22 01:25:56	\N	f
50487	1346		2018-04-03 16:53:31	4109	2739	fotos20172018/20171221_Gala/PhotoBooth/_MG_3155.jpg	f538b197	2017-12-22 01:26:17	\N	f
50488	1346		2018-04-03 16:53:31	4222	2815	fotos20172018/20171221_Gala/PhotoBooth/_MG_3156.jpg	d28342e8	2017-12-22 01:26:19	\N	f
50489	1346		2018-04-03 16:53:31	3813	2542	fotos20172018/20171221_Gala/PhotoBooth/_MG_3163.jpg	f2c41f63	2017-12-22 01:26:23	\N	f
50490	1346		2018-04-03 16:53:31	3310	2207	fotos20172018/20171221_Gala/PhotoBooth/_MG_3174.jpg	20ae7346	2017-12-22 01:27:12	\N	f
50491	1346		2018-04-03 16:53:31	2989	1903	fotos20172018/20171221_Gala/PhotoBooth/_MG_3180.jpg	cb94d658	2017-12-22 01:27:43	\N	f
50492	1346		2018-04-03 16:53:31	2879	1919	fotos20172018/20171221_Gala/PhotoBooth/_MG_3183.jpg	c1047c28	2017-12-22 01:27:51	\N	f
50493	1346		2018-04-03 16:53:31	3360	2240	fotos20172018/20171221_Gala/PhotoBooth/_MG_3184.jpg	43051db3	2017-12-22 01:27:52	\N	f
50494	1346		2018-04-03 16:53:31	3465	2310	fotos20172018/20171221_Gala/PhotoBooth/_MG_3187.jpg	ccfba9db	2017-12-22 01:27:59	\N	f
50495	1346		2018-04-03 16:53:31	3274	2183	fotos20172018/20171221_Gala/PhotoBooth/_MG_3190.jpg	5e01c8f0	2017-12-22 01:28:19	\N	f
50496	1346		2018-04-03 16:53:31	4202	2801	fotos20172018/20171221_Gala/PhotoBooth/_MG_3192.jpg	399bdb9e	2017-12-22 01:28:21	\N	f
50497	1346		2018-04-03 16:53:31	3636	2424	fotos20172018/20171221_Gala/PhotoBooth/_MG_3195.jpg	d9b5122a	2017-12-22 01:28:35	\N	f
50498	1346		2018-04-03 16:53:31	3594	2396	fotos20172018/20171221_Gala/PhotoBooth/_MG_3196.jpg	b84021dc	2017-12-22 01:28:37	\N	f
50499	1346		2018-04-03 16:53:31	3427	2285	fotos20172018/20171221_Gala/PhotoBooth/_MG_3198.jpg	207e38d4	2017-12-22 01:28:46	\N	f
50500	1346		2018-04-03 16:53:31	2094	2921	fotos20172018/20171221_Gala/PhotoBooth/_MG_3206.jpg	e9928b56	2017-12-22 01:29:18	\N	f
50501	1346		2018-04-03 16:53:31	4060	2227	fotos20172018/20171221_Gala/PhotoBooth/_MG_3208.jpg	025e8bb8	2017-12-22 01:29:51	\N	f
50502	1346		2018-04-03 16:53:31	2113	1409	fotos20172018/20171221_Gala/PhotoBooth/_MG_3211.jpg	d2c3b80f	2017-12-22 01:29:59	\N	f
50503	1346		2018-04-03 16:53:31	3120	2080	fotos20172018/20171221_Gala/PhotoBooth/_MG_3212.jpg	b0874171	2017-12-22 01:30:02	\N	f
50504	1346		2018-04-03 16:53:31	4114	2743	fotos20172018/20171221_Gala/PhotoBooth/_MG_3213.jpg	8e343acf	2017-12-22 01:30:22	\N	f
50505	1346		2018-04-03 16:53:31	3440	2293	fotos20172018/20171221_Gala/PhotoBooth/_MG_3214.jpg	134127b0	2017-12-22 01:30:26	\N	f
50506	1346		2018-04-03 16:53:31	4136	2757	fotos20172018/20171221_Gala/PhotoBooth/_MG_3216.jpg	e443b961	2017-12-22 01:30:29	\N	f
50507	1346		2018-04-03 16:53:32	2304	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_3223.jpg	96d6e65c	2017-12-22 01:30:57	\N	f
50508	1346		2018-04-03 16:53:32	2775	2910	fotos20172018/20171221_Gala/PhotoBooth/_MG_3229.jpg	40da40d2	2017-12-22 01:31:30	\N	f
50509	1346		2018-04-03 16:53:32	2142	3095	fotos20172018/20171221_Gala/PhotoBooth/_MG_3231.jpg	6855cb06	2017-12-22 01:31:32	\N	f
50510	1346		2018-04-03 16:53:32	3581	2387	fotos20172018/20171221_Gala/PhotoBooth/_MG_3234.jpg	48a988b6	2017-12-22 01:44:12	\N	f
50511	1346		2018-04-03 16:53:32	3636	2424	fotos20172018/20171221_Gala/PhotoBooth/_MG_3235.jpg	1251c89e	2017-12-22 01:44:16	\N	f
50512	1346		2018-04-03 16:53:32	2926	1951	fotos20172018/20171221_Gala/PhotoBooth/_MG_3238.jpg	b7c2df83	2017-12-22 01:44:23	\N	f
50513	1346		2018-04-03 16:53:32	2907	1938	fotos20172018/20171221_Gala/PhotoBooth/_MG_3243.jpg	89b90e2a	2017-12-22 01:44:48	\N	f
50514	1346		2018-04-03 16:53:32	3021	2014	fotos20172018/20171221_Gala/PhotoBooth/_MG_3246.jpg	48837e4b	2017-12-22 01:44:54	\N	f
50515	1346		2018-04-03 16:53:32	4530	3020	fotos20172018/20171221_Gala/PhotoBooth/_MG_3250.jpg	205ad2ca	2017-12-22 01:47:17	\N	f
50516	1346		2018-04-03 16:53:32	4916	2434	fotos20172018/20171221_Gala/PhotoBooth/_MG_3253.jpg	c5805ea2	2017-12-22 01:47:25	\N	f
50517	1346		2018-04-03 16:53:32	4304	2869	fotos20172018/20171221_Gala/PhotoBooth/_MG_3255.jpg	9771748c	2017-12-22 01:47:43	\N	f
50518	1346		2018-04-03 16:53:32	2415	3623	fotos20172018/20171221_Gala/PhotoBooth/_MG_3259.jpg	3d4335f9	2017-12-22 01:48:08	\N	f
50519	1346		2018-04-03 16:53:32	3456	2304	fotos20172018/20171221_Gala/PhotoBooth/_MG_3263.jpg	3084b328	2017-12-22 01:48:17	\N	f
50520	1346		2018-04-03 16:53:32	3212	4818	fotos20172018/20171221_Gala/PhotoBooth/_MG_3264.jpg	e392b538	2017-12-22 01:48:28	\N	f
50521	1346		2018-04-03 16:53:32	4267	2779	fotos20172018/20171221_Gala/PhotoBooth/_MG_3267.jpg	11c19152	2017-12-22 01:48:55	\N	f
50522	1346		2018-04-03 16:53:32	3928	2619	fotos20172018/20171221_Gala/PhotoBooth/_MG_3268.jpg	1f0ce5d2	2017-12-22 01:48:59	\N	f
50523	1346		2018-04-03 16:53:32	3326	2217	fotos20172018/20171221_Gala/PhotoBooth/_MG_3270.jpg	dc2369d3	2017-12-22 01:49:04	\N	f
50524	1346		2018-04-03 16:53:32	4302	2868	fotos20172018/20171221_Gala/PhotoBooth/_MG_3271.jpg	f018cc18	2017-12-22 01:49:05	\N	f
50525	1346		2018-04-03 16:53:32	3456	5184	fotos20172018/20171221_Gala/PhotoBooth/_MG_3272.jpg	945f13c2	2017-12-22 01:49:17	\N	f
50526	1346		2018-04-03 16:53:32	2304	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_3273.jpg	057c954a	2017-12-22 01:49:30	\N	f
50527	1346		2018-04-03 16:53:32	3456	4443	fotos20172018/20171221_Gala/PhotoBooth/_MG_3279.jpg	a216d1b6	2017-12-22 01:50:53	\N	f
50528	1346		2018-04-03 16:53:32	4065	2710	fotos20172018/20171221_Gala/PhotoBooth/_MG_3281.jpg	88397faa	2017-12-22 01:50:58	\N	f
50529	1346		2018-04-03 16:53:32	4110	2740	fotos20172018/20171221_Gala/PhotoBooth/_MG_3282.jpg	ad8c9da4	2017-12-22 01:51:00	\N	f
50530	1346		2018-04-03 16:53:32	4222	2815	fotos20172018/20171221_Gala/PhotoBooth/_MG_3283.jpg	6e6b3639	2017-12-22 01:51:10	\N	f
50531	1346		2018-04-03 16:53:32	3769	2513	fotos20172018/20171221_Gala/PhotoBooth/_MG_3284.jpg	ea1d38fb	2017-12-22 01:51:12	\N	f
50532	1346		2018-04-03 16:53:32	3681	2454	fotos20172018/20171221_Gala/PhotoBooth/_MG_3285.jpg	399e308d	2017-12-22 01:51:15	\N	f
50533	1346		2018-04-03 16:53:32	3456	4293	fotos20172018/20171221_Gala/PhotoBooth/_MG_3287.jpg	631fa6af	2017-12-22 01:51:30	\N	f
50534	1346		2018-04-03 16:53:32	4064	2709	fotos20172018/20171221_Gala/PhotoBooth/_MG_3288.jpg	e04bc8ad	2017-12-22 01:53:03	\N	f
50535	1346		2018-04-03 16:53:32	3982	2654	fotos20172018/20171221_Gala/PhotoBooth/_MG_3290.jpg	df0715ea	2017-12-22 01:53:24	\N	f
50536	1346		2018-04-03 16:53:32	3917	2611	fotos20172018/20171221_Gala/PhotoBooth/_MG_3291.jpg	ac1b6761	2017-12-22 01:53:26	\N	f
50537	1346		2018-04-03 16:53:32	3931	2621	fotos20172018/20171221_Gala/PhotoBooth/_MG_3292.jpg	623c3065	2017-12-22 01:53:28	\N	f
50538	1346		2018-04-03 16:53:32	3869	2579	fotos20172018/20171221_Gala/PhotoBooth/_MG_3293.jpg	d313864f	2017-12-22 01:53:30	\N	f
50539	1346		2018-04-03 16:53:32	3399	2266	fotos20172018/20171221_Gala/PhotoBooth/_MG_3295.jpg	1dcc89fc	2017-12-22 01:53:33	\N	f
50540	1346		2018-04-03 16:53:32	4220	2813	fotos20172018/20171221_Gala/PhotoBooth/_MG_3297.jpg	ea2d2daa	2017-12-22 01:53:47	\N	f
50541	1346		2018-04-03 16:53:32	3891	2594	fotos20172018/20171221_Gala/PhotoBooth/_MG_3300.jpg	2b780979	2017-12-22 01:53:51	\N	f
50542	1346		2018-04-03 16:53:32	4076	2717	fotos20172018/20171221_Gala/PhotoBooth/_MG_3301.jpg	2e3b43b1	2017-12-22 01:53:58	\N	f
50543	1346		2018-04-03 16:53:32	1801	2701	fotos20172018/20171221_Gala/PhotoBooth/_MG_3305.jpg	5ecf1545	2017-12-22 02:00:30	\N	f
50544	1346		2018-04-03 16:53:32	2335	3503	fotos20172018/20171221_Gala/PhotoBooth/_MG_3307.jpg	d2bb738e	2017-12-22 02:00:37	\N	f
50545	1346		2018-04-03 16:53:33	3456	2304	fotos20172018/20171221_Gala/PhotoBooth/_MG_3310.jpg	217fc1a0	2017-12-22 02:01:07	\N	f
50546	1346		2018-04-03 16:53:33	3935	2623	fotos20172018/20171221_Gala/PhotoBooth/_MG_3317.jpg	2ce44d6c	2017-12-22 02:01:58	\N	f
50547	1346		2018-04-03 16:53:33	4090	2727	fotos20172018/20171221_Gala/PhotoBooth/_MG_3320.jpg	eff9a375	2017-12-22 02:02:28	\N	f
50548	1346		2018-04-03 16:53:33	3926	2617	fotos20172018/20171221_Gala/PhotoBooth/_MG_3321.jpg	764aa0a1	2017-12-22 02:02:40	\N	f
50549	1346		2018-04-03 16:53:33	2772	2772	fotos20172018/20171221_Gala/PhotoBooth/_MG_3324.jpg	bb4b3a75	2017-12-22 02:02:42	\N	f
50550	1346		2018-04-03 16:53:33	3123	2082	fotos20172018/20171221_Gala/PhotoBooth/_MG_3329.jpg	dbd8a2f3	2017-12-22 02:03:17	\N	f
50551	1346		2018-04-03 16:53:33	4013	2675	fotos20172018/20171221_Gala/PhotoBooth/_MG_3332.jpg	b36562f6	2017-12-22 02:03:33	\N	f
50552	1346		2018-04-03 16:53:33	3043	2029	fotos20172018/20171221_Gala/PhotoBooth/_MG_3336.jpg	ae030820	2017-12-22 02:04:49	\N	f
50553	1346		2018-04-03 16:53:33	2544	1696	fotos20172018/20171221_Gala/PhotoBooth/_MG_3343.jpg	bf631dde	2017-12-22 02:05:42	\N	f
50554	1346		2018-04-03 16:53:33	3524	2349	fotos20172018/20171221_Gala/PhotoBooth/_MG_3344.jpg	805e2c14	2017-12-22 02:05:44	\N	f
50555	1346		2018-04-03 16:53:33	1809	2714	fotos20172018/20171221_Gala/PhotoBooth/_MG_3349.jpg	aabb4f1b	2017-12-22 02:08:10	\N	f
50556	1346		2018-04-03 16:53:33	4028	2685	fotos20172018/20171221_Gala/PhotoBooth/_MG_3351.jpg	31cdf9d2	2017-12-22 02:08:16	\N	f
50557	1346		2018-04-03 16:53:33	2803	1869	fotos20172018/20171221_Gala/PhotoBooth/_MG_3378.jpg	a93127a2	2017-12-22 02:24:54	\N	f
50558	1346		2018-04-03 16:53:33	1632	2243	fotos20172018/20171221_Gala/PhotoBooth/_MG_3387.jpg	581a2282	2017-12-22 02:47:37	\N	f
50559	1346		2018-04-03 16:53:33	2740	1827	fotos20172018/20171221_Gala/PhotoBooth/_MG_3391.jpg	835aaf13	2017-12-22 02:47:46	\N	f
50560	1346		2018-04-03 16:53:33	3222	2148	fotos20172018/20171221_Gala/PhotoBooth/_MG_3396.jpg	82a0d95d	2017-12-22 02:48:11	\N	f
50561	1346		2018-04-03 16:53:33	2304	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_3405.jpg	4079604e	2017-12-22 02:48:47	\N	f
50562	1346		2018-04-03 16:53:33	2989	1993	fotos20172018/20171221_Gala/PhotoBooth/_MG_3409.jpg	eb777499	2017-12-22 02:48:51	\N	f
50563	1346		2018-04-03 16:53:33	3150	2100	fotos20172018/20171221_Gala/PhotoBooth/_MG_3410.jpg	d3f4b680	2017-12-22 02:49:30	\N	f
50564	1346		2018-04-03 16:53:33	3698	2465	fotos20172018/20171221_Gala/PhotoBooth/_MG_3411.jpg	8e52c46c	2017-12-22 02:49:32	\N	f
50565	1346		2018-04-03 16:53:33	3433	2289	fotos20172018/20171221_Gala/PhotoBooth/_MG_3416.jpg	64f32c39	2017-12-22 02:49:44	\N	f
50566	1346		2018-04-03 16:53:33	2358	2891	fotos20172018/20171221_Gala/PhotoBooth/_MG_3419.jpg	f07de00c	2017-12-22 02:49:49	\N	f
50567	1346		2018-04-03 16:53:33	2276	3415	fotos20172018/20171221_Gala/PhotoBooth/_MG_3425.jpg	fad6cc63	2017-12-22 02:50:09	\N	f
50568	1346		2018-04-03 16:53:33	5184	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_3426.jpg	ae77c4ee	2017-12-22 02:50:15	\N	f
50569	1346		2018-04-03 16:53:33	3466	2311	fotos20172018/20171221_Gala/PhotoBooth/_MG_3428.jpg	241585ff	2017-12-22 04:06:06	\N	f
50570	1346		2018-04-03 16:53:33	3470	2313	fotos20172018/20171221_Gala/PhotoBooth/_MG_3430.jpg	0d45f137	2017-12-22 04:06:07	\N	f
50571	1346		2018-04-03 16:53:34	3667	2917	fotos20172018/20171221_Gala/PhotoBooth/_MG_3433.jpg	ccac54b2	2017-12-22 04:07:44	\N	f
50572	1346		2018-04-03 16:53:34	5184	3456	fotos20172018/20171221_Gala/PhotoBooth/_MG_3435.jpg	8f30c678	2017-12-22 04:08:12	\N	f
50573	1346		2018-04-03 16:53:34	2662	3993	fotos20172018/20171221_Gala/PhotoBooth/_MG_3437.jpg	13fa186b	2017-12-22 04:11:01	\N	f
50574	1346		2018-04-03 16:53:34	3051	4577	fotos20172018/20171221_Gala/PhotoBooth/_MG_3459.jpg	80813791	2017-12-22 04:18:25	\N	f
50575	1346		2018-04-03 16:55:11	1000	667	fotos20172018/20171221_Gala/PhotoBooth/Gifje.gif	ce99bc58	1970-01-01 01:00:00	\N	f
50576	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0001.JPG	effdde5d	2018-03-29 14:37:50	\N	f
50577	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0002.JPG	74f688f9	2018-03-29 14:38:47	\N	f
50578	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0003.JPG	97b02b20	2018-03-29 14:38:54	\N	f
50579	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0004.JPG	38ad96d9	2018-03-29 14:39:02	\N	f
50580	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0005.JPG	869bbdc3	2018-03-29 14:39:21	\N	f
50581	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0006.JPG	b406eefc	2018-03-29 14:39:33	\N	f
50582	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0007.JPG	f217a6e2	2018-03-29 14:39:47	\N	f
50583	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0008.JPG	1e06f208	2018-03-29 14:39:53	\N	f
50584	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0009.JPG	4a6ef1a3	2018-03-29 14:40:00	\N	f
50585	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0010.JPG	b5db2510	2018-03-29 14:40:17	\N	f
50586	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0011.JPG	7b027983	2018-03-29 14:41:52	\N	f
50587	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0012.JPG	129b3df2	2018-03-29 14:42:08	\N	f
50588	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0013.JPG	93185c47	2018-03-29 14:42:23	\N	f
50589	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0014.JPG	98482ed6	2018-03-29 14:42:33	\N	f
50590	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0015.JPG	328ddfdf	2018-03-29 14:42:54	\N	f
50591	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0016.JPG	f2082779	2018-03-29 14:42:58	\N	f
50592	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0017.JPG	142bec01	2018-03-29 14:44:22	\N	f
50593	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0018.JPG	53a9a373	2018-03-29 14:44:54	\N	f
50594	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0019.JPG	349c3dd4	2018-03-29 14:47:00	\N	f
50595	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0020.JPG	4169b515	2018-03-29 14:47:25	\N	f
50596	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0021.JPG	d85dfc10	2018-03-29 14:47:40	\N	f
50597	1347		2018-04-04 15:12:20	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0022.JPG	6e0fd7d0	2018-03-29 14:47:50	\N	f
50598	1347		2018-04-04 15:12:20	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0023.JPG	6335e8c3	2018-03-29 14:48:08	\N	f
50599	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0028.JPG	7636a293	2018-03-29 15:05:30	\N	f
50600	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0029.JPG	3ee9e769	2018-03-29 15:05:46	\N	f
50601	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0030.JPG	712af0f8	2018-03-29 15:06:00	\N	f
50602	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0031.JPG	b71003ca	2018-03-29 15:06:09	\N	f
50603	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0032.JPG	09542671	2018-03-29 15:06:34	\N	f
50604	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0033.JPG	979e9b51	2018-03-29 15:07:54	\N	f
50605	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0034.JPG	5b5bde62	2018-03-29 15:08:08	\N	f
50606	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0035.JPG	ebc65bbf	2018-03-29 15:09:06	\N	f
50607	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0036.JPG	39611d4e	2018-03-29 15:09:22	\N	f
50608	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0037.JPG	e98abcee	2018-03-29 15:09:29	\N	f
50609	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0038.JPG	81d979c4	2018-03-29 15:09:43	\N	f
50610	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0039.JPG	8320cbf7	2018-03-29 15:10:03	\N	f
50611	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0040.JPG	bba0d5d1	2018-03-29 15:10:47	\N	f
50612	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0041.JPG	6e47ffde	2018-03-29 15:11:46	\N	f
50613	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0042.JPG	fc7965a9	2018-03-29 15:32:44	\N	f
50614	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0053.JPG	8000309e	2018-03-29 15:51:26	\N	f
50615	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0054.JPG	69a5660e	2018-03-29 15:51:29	\N	f
50616	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0055.JPG	beed3df2	2018-03-29 15:51:30	\N	f
50617	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0056.JPG	29ab3786	2018-03-29 15:51:31	\N	f
50618	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0058.JPG	db2f7d51	2018-03-29 15:52:21	\N	f
50619	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0059.JPG	244c81dd	2018-03-29 15:52:22	\N	f
50620	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0060.JPG	a3eb13d9	2018-03-29 15:52:23	\N	f
50621	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0061.JPG	40004380	2018-03-29 15:52:46	\N	f
50622	1347		2018-04-04 15:12:21	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0062.JPG	ff4bfb50	2018-03-29 15:52:47	\N	f
50623	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0063.JPG	f2485385	2018-03-29 15:53:15	\N	f
50624	1347		2018-04-04 15:12:21	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0064.JPG	9a2bf7a7	2018-03-29 15:54:29	\N	f
50625	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0065.JPG	e2f755c1	2018-03-29 15:54:30	\N	f
50626	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0066.JPG	5820ebaf	2018-03-29 15:54:31	\N	f
50627	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0067.JPG	989325e4	2018-03-29 15:54:32	\N	f
50628	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0068.JPG	b34c3a6d	2018-03-29 15:54:35	\N	f
50635	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0075.JPG	ac57a9ac	2018-03-29 15:55:38	\N	f
50636	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0076.JPG	9eb39944	2018-03-29 15:55:50	\N	f
50637	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0077.JPG	6de25498	2018-03-29 15:58:23	\N	f
50638	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0078.JPG	2f639b66	2018-03-29 15:58:48	\N	f
50639	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0079.JPG	b9def0e3	2018-03-29 16:23:35	\N	f
50640	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0080.JPG	af58cdfa	2018-03-29 16:23:58	\N	f
50641	1347		2018-04-04 15:12:22	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0081.JPG	cdb88b71	2018-03-29 16:25:27	\N	f
50642	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0082.JPG	4d2e88ea	2018-03-29 16:43:04	\N	f
50643	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0083.JPG	88c0daf0	2018-03-29 16:43:05	\N	f
50644	1347		2018-04-04 15:12:22	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0085.JPG	76513a4a	2018-03-29 16:43:06	\N	f
50645	1347		2018-04-04 15:12:23	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0084.JPG	2ccf1dfc	2018-03-29 16:43:06	\N	f
50646	1347		2018-04-04 15:12:23	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0086.JPG	b88e15f4	2018-03-29 16:43:07	\N	f
50647	1347		2018-04-04 15:12:23	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0087.JPG	7a1b5c86	2018-03-29 16:43:09	\N	f
50648	1347		2018-04-04 15:12:23	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0088.JPG	38c7baa3	2018-03-29 16:43:13	\N	f
50649	1347		2018-04-04 15:12:23	4000	6000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0091.JPG	e359be9c	2018-03-29 16:43:21	\N	f
50650	1347		2018-04-04 15:12:23	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0092.JPG	6ed74024	2018-03-29 16:43:44	\N	f
50651	1347		2018-04-04 15:12:23	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0093.JPG	3c853382	2018-03-29 16:43:45	\N	f
50652	1347		2018-04-04 15:12:23	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0095.JPG	d83b0b1b	2018-03-29 16:44:47	\N	f
50653	1347		2018-04-04 15:12:23	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0096.JPG	32d69445	2018-03-29 16:44:56	\N	f
50654	1347		2018-04-04 15:12:23	6000	4000	fotos20172018/20180329_FirstDayOfNewCameraTAD/IMG_0097.JPG	454bc1d4	2018-03-29 16:45:02	\N	f
50666	1348		2018-04-04 15:24:53	4442	2961	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0687.jpg	0c0b532f	2018-03-23 23:10:30	11	f
50655	1348		2018-04-04 15:24:52	4894	3263	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0638.jpg	55bed99a	2018-03-23 19:00:45	0	f
50656	1348		2018-04-04 15:24:52	4977	3318	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0640.jpg	1470afa6	2018-03-23 19:10:54	1	f
50657	1348		2018-04-04 15:24:52	3938	2625	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0645.jpg	811bd129	2018-03-23 19:40:33	2	f
50658	1348		2018-04-04 15:24:52	4423	2949	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0648.jpg	0459ab6c	2018-03-23 20:28:05	3	f
50659	1348		2018-04-04 15:24:52	4044	2696	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0654.jpg	30b0ad76	2018-03-23 21:32:17	4	f
50660	1348		2018-04-04 15:24:52	3858	2572	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0656.jpg	0aad4d08	2018-03-23 21:32:43	5	f
50661	1348		2018-04-04 15:24:52	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0658.jpg	a2984991	2018-03-23 22:07:42	6	f
50662	1348		2018-04-04 15:24:52	4984	3323	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0661.jpg	449b26dc	2018-03-23 22:07:58	7	f
50664	1348		2018-04-04 15:24:53	4919	3279	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0678.jpg	7bbf3450	2018-03-23 23:09:09	10	f
50665	1348	Aaaand... Time for the next one!	2018-04-04 15:24:53	5112	3408	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0685.jpg	8ba00578	2018-03-23 23:10:23	9	f
50667	1348		2018-04-04 15:24:53	5083	3389	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0694.jpg	d9279dc3	2018-03-23 23:11:21	12	f
50668	1348		2018-04-04 15:24:53	4574	3049	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0696.jpg	004fea41	2018-03-24 17:02:19	13	f
50669	1348		2018-04-04 15:24:53	5077	3385	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0702.jpg	9b4336ec	2018-03-24 17:07:52	14	f
50670	1348		2018-04-04 15:24:53	2191	3286	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0708.jpg	3472117e	2018-03-24 17:11:21	15	f
50671	1348		2018-04-04 15:24:53	3456	5184	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0715.jpg	e59a098d	2018-03-24 17:22:11	16	f
50672	1348		2018-04-04 15:24:53	3192	4788	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0721.jpg	5ea4a58d	2018-03-24 17:24:07	17	f
50673	1348		2018-04-04 15:24:53	3456	5184	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0727.jpg	e3a9e03a	2018-03-24 17:30:38	18	f
50674	1348		2018-04-04 15:24:53	3299	4948	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0732.jpg	b8c444f0	2018-03-24 17:31:49	19	f
50675	1348		2018-04-04 15:24:53	4998	3332	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0733.jpg	0217512a	2018-03-24 17:32:03	20	f
50676	1348		2018-04-04 15:24:53	5123	3415	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0735.jpg	e1afc6e8	2018-03-24 17:33:43	21	f
50677	1348		2018-04-04 15:24:53	2792	4188	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0738.jpg	3ecaeb20	2018-03-24 17:34:30	22	f
50678	1348		2018-04-04 15:24:53	5161	3441	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0740.jpg	ae2f4af7	2018-03-24 17:35:10	23	f
50679	1348		2018-04-04 15:24:53	4442	2961	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0747.jpg	510f0781	2018-03-24 17:37:02	24	f
50680	1348		2018-04-04 15:24:53	3938	2625	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0756.jpg	c4fa03e1	2018-03-24 17:39:51	25	f
50681	1348		2018-04-04 15:24:53	3786	2524	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0772.jpg	73068737	2018-03-24 17:51:47	26	f
50682	1348		2018-04-04 15:24:53	3792	2528	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0789.jpg	6a2cca04	2018-03-24 17:59:30	27	f
50685	1348	🐑🐑	2018-04-04 15:24:53	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0837.jpg	014c0e11	2018-03-24 18:09:28	30	f
50684	1348		2018-04-04 15:24:53	4314	2876	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0814.jpg	f6060ae2	2018-03-24 18:04:49	29	f
50686	1348	🐑🐑🐑	2018-04-04 15:24:53	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0841.jpg	f7b633db	2018-03-24 18:09:47	31	f
50683	1348	🐑	2018-04-04 15:24:53	4679	3119	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0805.jpg	a6db0e60	2018-03-24 18:00:48	28	f
50688	1348		2018-04-04 15:24:53	3855	2570	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0863.jpg	5c1886b6	2018-03-24 18:15:42	33	f
50689	1348		2018-04-04 15:24:53	2446	3669	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0882.jpg	08e994cf	2018-03-24 18:27:31	34	f
50690	1348		2018-04-04 15:24:53	4453	2969	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0892.jpg	a2dbf371	2018-03-24 18:31:18	35	f
50691	1348		2018-04-04 15:24:53	2142	3213	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0899.jpg	63581c5b	2018-03-24 18:36:24	36	f
50692	1348		2018-04-04 15:24:53	3837	2558	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0905.jpg	a44a9e83	2018-03-24 18:40:11	37	f
50693	1348		2018-04-04 15:24:53	3781	2521	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0913.jpg	82678d85	2018-03-24 18:43:29	38	f
50694	1348		2018-04-04 15:24:53	3932	2621	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0916.jpg	13371256	2018-03-24 18:44:51	39	f
50695	1348		2018-04-04 15:24:53	3456	5184	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0923.jpg	aafebd80	2018-03-24 19:01:30	40	f
50697	1348		2018-04-04 15:24:53	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0929.jpg	64900dbe	2018-03-24 19:03:50	42	f
50698	1348		2018-04-04 15:24:53	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0933.jpg	7ffb2b56	2018-03-24 19:06:47	43	f
50699	1348		2018-04-04 15:24:53	3528	2352	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0936.jpg	0aa596f3	2018-03-24 19:10:22	44	f
50700	1348		2018-04-04 15:24:54	2932	4398	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0938.jpg	7dc6e181	2018-03-24 19:13:47	45	f
50701	1348		2018-04-04 15:24:54	4230	2820	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0944.jpg	ee00c1a1	2018-03-24 19:13:59	46	f
50702	1348		2018-04-04 15:24:54	4595	3063	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0948.jpg	8d52e0c0	2018-03-24 19:14:52	47	f
50703	1348		2018-04-04 15:24:54	2633	3949	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0952.jpg	9f938ed2	2018-03-24 19:15:46	48	f
50704	1348		2018-04-04 15:24:54	3148	2099	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0958.jpg	9456a011	2018-03-24 19:16:26	49	f
50705	1348		2018-04-04 15:24:54	4429	2953	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0971.jpg	fab3327e	2018-03-24 19:17:42	50	f
50706	1348		2018-04-04 15:24:54	5184	3456	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0980.jpg	7982a119	2018-03-24 19:18:25	51	f
50707	1348		2018-04-04 15:24:54	4118	2745	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0986.jpg	d82337a2	2018-03-24 19:18:46	52	f
50708	1348		2018-04-04 15:24:54	3329	3329	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0988.jpg	3fabd91a	2018-03-24 19:19:08	53	f
50709	1348		2018-04-04 15:24:54	4819	3213	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-1006.jpg	8f09836a	2018-03-24 19:22:46	54	f
50710	1348		2018-04-04 15:24:54	3221	4831	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-1009.jpg	775a0721	2018-03-24 19:24:42	55	f
50696	1348	Found it!	2018-04-04 15:24:53	5075	3383	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0926.jpg	986b6d1a	2018-03-24 19:03:21	41	f
50711	1348	🐎	2018-04-04 15:24:54	3415	5122	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-1015.jpg	4a1abd23	2018-03-24 19:29:49	56	f
50713	1348		2018-04-04 15:24:54	3500	2333	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1060.jpg	cdc4d71b	2018-03-25 00:00:40	58	f
50714	1348		2018-04-04 15:24:54	3744	2496	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1065.jpg	2c8ecedd	2018-03-25 00:09:45	59	f
50715	1348		2018-04-04 15:24:54	3753	2502	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1070.jpg	2cabce4c	2018-03-25 00:17:12	60	f
50716	1348		2018-04-04 15:24:54	1743	2614	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1078.jpg	28427eac	2018-03-25 00:24:12	61	f
50717	1348		2018-04-04 15:24:54	4192	2795	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1090.jpg	a7afb10c	2018-03-25 00:40:38	62	f
50718	1348		2018-04-04 15:24:54	2275	3413	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1094.jpg	5fdf7fd6	2018-03-25 00:46:42	63	f
50719	1348		2018-04-04 15:24:54	4855	2731	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1096.jpg	ed5bc466	2018-03-25 01:06:54	64	f
50720	1348		2018-04-04 15:24:54	3585	2390	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1099.jpg	53930db0	2018-03-25 01:07:10	65	f
50721	1348		2018-04-04 15:24:54	5134	2888	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1100.jpg	0be139e7	2018-03-25 01:07:37	66	f
50722	1348		2018-04-04 15:24:54	2659	1773	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1103.jpg	06020ad4	2018-03-25 01:12:17	67	f
50723	1348		2018-04-04 15:24:54	3555	2370	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1111.jpg	24ed0abc	2018-03-25 01:17:32	68	f
50724	1348		2018-04-04 15:24:54	3783	2522	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1115.jpg	3681b60a	2018-03-25 01:20:04	69	f
50725	1348		2018-04-04 15:24:54	3837	2558	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1128.jpg	aa2ea1ae	2018-03-25 01:27:17	70	f
50726	1348		2018-04-04 15:24:54	4938	3292	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1146.jpg	07cac5ec	2018-03-25 01:36:09	71	f
50727	1348		2018-04-04 15:24:54	1501	2251	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1153.jpg	ea0c0344	2018-03-25 01:36:59	72	f
50728	1348		2018-04-04 15:24:54	5021	3347	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1167.jpg	ed7125b0	2018-03-25 01:40:11	73	f
50729	1348		2018-04-04 15:24:54	3329	2219	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1171.jpg	fafd009b	2018-03-25 01:43:17	74	f
50730	1348		2018-04-04 15:24:54	4252	2835	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1189.jpg	09664174	2018-03-25 01:46:56	75	f
50731	1348		2018-04-04 15:24:54	4679	3119	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1194.jpg	f3f307ec	2018-03-25 01:47:56	76	f
50712	1348	🎠	2018-04-04 15:24:54	4887	3258	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-1019.jpg	a28e58b1	2018-03-24 19:30:14	57	f
50663	1348	Finished!	2018-04-04 15:24:52	4654	3103	fotos20172018/20180323-25_MemberWeekend/20180323-MemberWeekend-Martijn-0671.jpg	80f552e1	2018-03-23 22:09:09	8	f
50732	1348		2018-04-04 15:24:54	4615	3077	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1200.jpg	bc15b40d	2018-03-25 01:48:19	77	f
50733	1348		2018-04-04 15:24:54	5069	3379	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1226.jpg	6f586e13	2018-03-25 03:00:45	78	f
50734	1348		2018-04-04 15:24:54	4209	2806	fotos20172018/20180323-25_MemberWeekend/20180325-MemberWeekend-Martijn-1228.jpg	9e6132b9	2018-03-25 03:06:33	79	f
50735	1347		2018-04-04 15:34:12	400	600	fotos20172018/20180329_FirstDayOfNewCameraTAD/KevinDeVuilnisman.gif	33d67fdc	1970-01-01 01:00:00	\N	f
50687	1348	🐑🐑🐑🐑🐑🐑🐑🐑 SHEEP OVERLOAD 🐑🐑🐑🐑🐑🐑🐑🐑	2018-04-04 15:24:53	4708	2648	fotos20172018/20180323-25_MemberWeekend/20180324-MemberWeekend-Martijn-0856.jpg	48a41a59	2018-03-24 18:12:07	32	f
50942	1349		2018-04-12 17:24:52	6000	4000	fotos20172018/20180404_NeonParty/IMG_0179.JPG	40530a97	2018-04-04 21:44:56	\N	f
50943	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0183.JPG	b54ddf1b	2018-04-04 21:47:43	\N	f
50944	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0184.JPG	67fc1341	2018-04-04 21:50:46	\N	f
50945	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0185.JPG	87ac336a	2018-04-04 21:51:10	\N	f
50946	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0187.JPG	f5a75a91	2018-04-04 21:52:19	\N	f
50947	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0191.JPG	197c7d53	2018-04-04 21:55:13	\N	f
50948	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0193.JPG	89bfac4e	2018-04-04 21:57:18	\N	f
50949	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0195.JPG	12314d54	2018-04-04 21:58:14	\N	f
50950	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0197.JPG	582b082d	2018-04-04 21:59:52	\N	f
50951	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0198.JPG	c049e8be	2018-04-04 22:01:17	\N	f
50952	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0199.JPG	bf8360aa	2018-04-04 22:01:33	\N	f
50953	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0200.JPG	1e0f4724	2018-04-04 22:02:23	\N	f
50954	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0201.JPG	6e09c625	2018-04-04 22:03:06	\N	f
50955	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0205.JPG	552358ef	2018-04-04 22:05:51	\N	f
50956	1349		2018-04-12 17:24:52	6000	4000	fotos20172018/20180404_NeonParty/IMG_0207.JPG	a867e32e	2018-04-04 22:06:27	\N	f
50957	1349		2018-04-12 17:24:52	4000	6000	fotos20172018/20180404_NeonParty/IMG_0209.JPG	48f326e8	2018-04-04 22:07:28	\N	f
50958	1349		2018-04-12 17:24:52	6000	4000	fotos20172018/20180404_NeonParty/IMG_0210.JPG	2b1586d5	2018-04-04 22:07:50	\N	f
50959	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0213.JPG	36430e5e	2018-04-04 22:08:31	\N	f
50960	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0216.JPG	2c9bb826	2018-04-04 22:08:46	\N	f
50961	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0219.JPG	bc50b0fd	2018-04-04 22:09:44	\N	f
50962	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0220.JPG	c588ea4c	2018-04-04 22:09:56	\N	f
50963	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0221.JPG	5f5b7b71	2018-04-04 22:10:09	\N	f
50964	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0224.JPG	fce4ea09	2018-04-04 22:11:06	\N	f
50965	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0226.JPG	f6f1635e	2018-04-04 22:11:35	\N	f
50966	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0227.JPG	154aec36	2018-04-04 22:12:07	\N	f
50967	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0231.JPG	2ea71406	2018-04-04 22:13:22	\N	f
50968	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0232.JPG	cb706d56	2018-04-04 22:15:41	\N	f
50969	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0233.JPG	4f286215	2018-04-04 22:15:56	\N	f
50970	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0234.JPG	a02d8650	2018-04-04 22:16:12	\N	f
50971	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0240.JPG	db3dad9a	2018-04-04 22:16:44	\N	f
50972	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0243.JPG	1f6a268c	2018-04-04 22:17:42	\N	f
50973	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0246.JPG	85c7a822	2018-04-04 22:18:13	\N	f
50974	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0250.JPG	f13fc282	2018-04-04 22:18:33	\N	f
50975	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0251.JPG	28fab71c	2018-04-04 22:18:49	\N	f
50976	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0253.JPG	6246adf7	2018-04-04 22:19:14	\N	f
50977	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0256.JPG	1338b086	2018-04-04 22:21:11	\N	f
50978	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0257.JPG	de32672a	2018-04-04 22:22:29	\N	f
50979	1349		2018-04-12 17:24:53	4000	6000	fotos20172018/20180404_NeonParty/IMG_0259.JPG	4df48eb6	2018-04-04 22:23:11	\N	f
50980	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0261.JPG	aaadbd00	2018-04-04 22:26:02	\N	f
50981	1349		2018-04-12 17:24:53	6000	4000	fotos20172018/20180404_NeonParty/IMG_0264.JPG	60af7b85	2018-04-04 22:27:11	\N	f
50982	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0266.JPG	e79a862d	2018-04-04 22:28:12	\N	f
50983	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0271.JPG	43f0f38e	2018-04-04 22:29:31	\N	f
50984	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0274.JPG	cd42437a	2018-04-04 22:29:57	\N	f
50985	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0279.JPG	dd094d60	2018-04-04 22:30:47	\N	f
50986	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0282.JPG	74c04ec3	2018-04-04 22:32:00	\N	f
50987	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0286.JPG	c7f82b8b	2018-04-04 22:32:59	\N	f
50988	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0287.JPG	95860822	2018-04-04 22:34:13	\N	f
50989	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0288.JPG	d5271ce5	2018-04-04 22:34:38	\N	f
50990	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0289.JPG	d2ff767d	2018-04-04 22:34:54	\N	f
50991	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0294.JPG	9c3fa489	2018-04-04 22:35:42	\N	f
50992	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0314.JPG	1af00bd1	2018-04-04 22:51:08	\N	f
50993	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0317.JPG	40053846	2018-04-04 22:51:16	\N	f
50994	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0326.JPG	08a705d5	2018-04-04 22:52:05	\N	f
50995	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0335.JPG	f923bcaa	2018-04-04 22:52:51	\N	f
50996	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0338.JPG	b7d47bc9	2018-04-04 22:53:21	\N	f
50997	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0341.JPG	cb80e26f	2018-04-04 22:53:54	\N	f
50998	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0344.JPG	1d1b3b48	2018-04-04 23:01:09	\N	f
50999	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0355.JPG	30011f01	2018-04-04 23:07:55	\N	f
51000	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0365.JPG	a89a8cf6	2018-04-04 23:10:31	\N	f
51001	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0377.JPG	869843bd	2018-04-04 23:13:24	\N	f
51002	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0382.JPG	36ba6bab	2018-04-04 23:14:05	\N	f
51003	1349		2018-04-12 17:24:54	4000	6000	fotos20172018/20180404_NeonParty/IMG_0385.JPG	04349da7	2018-04-04 23:14:44	\N	f
51004	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0388.JPG	5b2328c3	2018-04-04 23:15:34	\N	f
51005	1349		2018-04-12 17:24:54	6000	4000	fotos20172018/20180404_NeonParty/IMG_0389.JPG	0ec05ccc	2018-04-04 23:16:20	\N	f
51006	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0390.JPG	59e41cda	2018-04-04 23:16:21	\N	f
51007	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0401.JPG	255f1628	2018-04-04 23:17:13	\N	f
51008	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0410.JPG	0127bef4	2018-04-04 23:18:52	\N	f
51009	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0414.JPG	6d321417	2018-04-04 23:19:39	\N	f
51010	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0415.JPG	037ce03b	2018-04-04 23:19:44	\N	f
51011	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0416.JPG	4bdb4e8b	2018-04-04 23:29:56	\N	f
51012	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0420.JPG	6d7d5f72	2018-04-04 23:36:09	\N	f
51013	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0423.JPG	37f1bb73	2018-04-04 23:36:41	\N	f
51014	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0427.JPG	c3bb6d98	2018-04-04 23:39:47	\N	f
51015	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0432.JPG	b7e6dcb8	2018-04-04 23:40:13	\N	f
51016	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0434.JPG	1501ae54	2018-04-04 23:40:23	\N	f
51017	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0437.JPG	d195aeb0	2018-04-04 23:42:13	\N	f
51018	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0438.JPG	046ae681	2018-04-04 23:42:35	\N	f
51019	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0448.JPG	90038bc8	2018-04-05 00:01:51	\N	f
51020	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0449.JPG	7b9900fc	2018-04-05 00:03:21	\N	f
51021	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0450.JPG	b59fc5df	2018-04-05 00:03:39	\N	f
51022	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0451.JPG	819af81b	2018-04-05 00:05:19	\N	f
51023	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0461.JPG	cd8a9d35	2018-04-05 00:08:55	\N	f
51024	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0465.JPG	d5a4fcf7	2018-04-05 00:09:17	\N	f
51025	1349		2018-04-12 17:24:55	6000	4000	fotos20172018/20180404_NeonParty/IMG_0466.JPG	5b2c6f7c	2018-04-05 00:10:10	\N	f
51026	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0467.JPG	fcb65550	2018-04-05 00:10:26	\N	f
51027	1349		2018-04-12 17:24:55	4000	6000	fotos20172018/20180404_NeonParty/IMG_0472.JPG	636167fa	2018-04-05 00:10:46	\N	f
51028	1349		2018-04-12 17:24:55	5999	4000	fotos20172018/20180404_NeonParty/IMG_0475.JPG	36cf14b4	2018-04-05 00:11:01	\N	f
51029	1349		2018-04-12 17:24:56	4000	6000	fotos20172018/20180404_NeonParty/IMG_0477.JPG	c71c649b	2018-04-05 00:11:30	\N	f
51030	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0480.JPG	0676bfcd	2018-04-05 00:13:01	\N	f
51031	1349		2018-04-12 17:24:56	4000	6000	fotos20172018/20180404_NeonParty/IMG_0483.JPG	b11ecb50	2018-04-05 00:13:18	\N	f
51032	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0494.JPG	52ee07e0	2018-04-05 00:16:52	\N	f
51033	1349		2018-04-12 17:24:56	4000	6000	fotos20172018/20180404_NeonParty/IMG_0497.JPG	9aaaf054	2018-04-05 00:17:47	\N	f
51034	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0507.JPG	f167ad62	2018-04-05 00:19:06	\N	f
51035	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0509.JPG	ab2648d4	2018-04-05 00:19:08	\N	f
51036	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0511.JPG	464d5969	2018-04-05 00:19:45	\N	f
51037	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0515.JPG	c094a218	2018-04-05 00:20:27	\N	f
51038	1349		2018-04-12 17:24:56	4000	6000	fotos20172018/20180404_NeonParty/IMG_0522.JPG	889b8c0e	2018-04-05 00:22:10	\N	f
51039	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0528.JPG	8b11cc14	2018-04-05 00:23:29	\N	f
51040	1349		2018-04-12 17:24:56	4000	6000	fotos20172018/20180404_NeonParty/IMG_0537.JPG	e2493c36	2018-04-05 00:29:18	\N	f
51041	1349		2018-04-12 17:24:56	6000	4000	fotos20172018/20180404_NeonParty/IMG_0546.JPG	3102e930	2018-04-05 00:30:52	\N	f
51042	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1072.JPG	7d9844bd	2018-05-07 19:14:00	\N	f
51043	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1073.JPG	31d32cbf	2018-05-07 19:14:01	\N	f
51044	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1075.JPG	bb04649b	2018-05-07 19:14:05	\N	f
51045	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1074.JPG	445e2146	2018-05-07 19:14:05	\N	f
51046	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1076.JPG	e070faab	2018-05-07 19:14:08	\N	f
51047	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1077.JPG	b9810d6f	2018-05-07 19:14:09	\N	f
51048	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1078.JPG	4db59fd6	2018-05-07 19:14:13	\N	f
51049	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1079.JPG	3a037698	2018-05-07 19:14:14	\N	f
51050	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1080.JPG	eb571492	2018-05-07 19:32:15	\N	f
51051	1350		2018-05-09 12:30:48	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1081.JPG	1b6bc3a1	2018-05-07 19:32:17	\N	f
51052	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1082.JPG	fb595d47	2018-05-07 19:32:17	\N	f
51053	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1083.JPG	3137cccc	2018-05-07 19:32:21	\N	f
51054	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1084.JPG	bd054059	2018-05-07 19:32:22	\N	f
51055	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1085.JPG	7cd42b9b	2018-05-07 19:48:29	\N	f
51056	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1086.JPG	36e39689	2018-05-07 19:48:31	\N	f
51057	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1087.JPG	68e6b78a	2018-05-07 19:48:36	\N	f
51058	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1088.JPG	3b309082	2018-05-07 19:48:37	\N	f
51059	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1089.JPG	59c05567	2018-05-07 19:48:48	\N	f
51060	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1090.JPG	15b6f7b3	2018-05-07 19:50:10	\N	f
51061	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1091.JPG	8d270875	2018-05-07 19:50:10	\N	f
51062	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1092.JPG	478f83ae	2018-05-07 19:50:29	\N	f
51063	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1093.JPG	aec2e2bf	2018-05-07 19:50:38	\N	f
51064	1350		2018-05-09 12:30:49	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1094.JPG	7a9109da	2018-05-07 19:50:47	\N	f
51065	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1095.JPG	8828ef95	2018-05-07 19:51:26	\N	f
51066	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1096.JPG	070399f4	2018-05-07 19:51:27	\N	f
51067	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1097.JPG	9f8b49e0	2018-05-07 19:52:43	\N	f
51068	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1098.JPG	ad44111c	2018-05-07 19:52:51	\N	f
51069	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1099.JPG	3735020d	2018-05-07 19:52:55	\N	f
51070	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1100.JPG	38fcb8fb	2018-05-07 19:53:19	\N	f
51071	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1101.JPG	f9277a8d	2018-05-07 19:53:20	\N	f
51072	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1102.JPG	89f5ba02	2018-05-07 19:53:22	\N	f
51073	1350		2018-05-09 12:30:49	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1103.JPG	a212d456	2018-05-07 19:53:26	\N	f
51074	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1104.JPG	65855be3	2018-05-07 19:53:42	\N	f
51075	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1105.JPG	4105dd76	2018-05-07 19:54:00	\N	f
51076	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1106.JPG	ecda48c7	2018-05-07 19:54:20	\N	f
51077	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1107.JPG	a0acd594	2018-05-07 19:54:28	\N	f
51078	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1108.JPG	502424a5	2018-05-07 19:57:33	\N	f
51079	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1109.JPG	60beed6e	2018-05-07 19:57:59	\N	f
51080	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1110.JPG	07e0287a	2018-05-07 20:04:14	\N	f
51081	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1111.JPG	2cf8e384	2018-05-07 20:04:32	\N	f
51082	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1112.JPG	0d1a54da	2018-05-07 20:15:35	\N	f
51083	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1113.JPG	bf0a3f99	2018-05-07 20:16:26	\N	f
51084	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1114.JPG	a37fee5d	2018-05-07 20:17:17	\N	f
51085	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1115.JPG	741eeaa6	2018-05-07 20:17:37	\N	f
51086	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1116.JPG	ad211063	2018-05-07 20:31:22	\N	f
51087	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1117.JPG	82371fb0	2018-05-07 20:31:30	\N	f
51088	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1118.JPG	9192646c	2018-05-07 20:31:39	\N	f
51089	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1119.JPG	24c7507f	2018-05-07 20:31:46	\N	f
51090	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1120.JPG	d0d714cf	2018-05-07 20:31:56	\N	f
51091	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1121.JPG	84c6a23d	2018-05-07 20:32:27	\N	f
51092	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1122.JPG	7ceb4bce	2018-05-07 20:32:41	\N	f
51093	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1123.JPG	5fe5b3ed	2018-05-07 20:32:49	\N	f
51094	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1124.JPG	db4d50a9	2018-05-07 20:34:13	\N	f
51095	1350		2018-05-09 12:30:50	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1125.JPG	ab91e785	2018-05-07 20:34:35	\N	f
51096	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1126.JPG	276aa59d	2018-05-07 20:34:50	\N	f
51097	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1127.JPG	8c459f39	2018-05-07 20:35:14	\N	f
51098	1350		2018-05-09 12:30:50	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1128.JPG	e3493dab	2018-05-07 20:35:19	\N	f
51099	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1129.JPG	6eb128d2	2018-05-07 20:35:28	\N	f
51100	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1130.JPG	707d90a7	2018-05-07 20:36:54	\N	f
51101	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1131.JPG	c326c4a5	2018-05-07 20:37:03	\N	f
51102	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1132.JPG	e2cf749b	2018-05-07 20:37:04	\N	f
51103	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1133.JPG	9c9d4d39	2018-05-07 20:37:42	\N	f
51104	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1134.JPG	4d6b9fe3	2018-05-07 20:38:00	\N	f
51105	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1135.JPG	cfbde82a	2018-05-07 20:38:29	\N	f
51106	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1136.JPG	7fa87f3a	2018-05-07 20:38:39	\N	f
51107	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1137.JPG	1a11b734	2018-05-07 20:38:46	\N	f
51108	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1138.JPG	fc22b19c	2018-05-07 20:39:05	\N	f
51109	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1139.JPG	2939bd5e	2018-05-07 20:39:09	\N	f
51110	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1140.JPG	5423576f	2018-05-07 20:40:37	\N	f
51111	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1141.JPG	f9cecab9	2018-05-07 20:40:53	\N	f
51112	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1142.JPG	feef32fa	2018-05-07 20:41:03	\N	f
51113	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1143.JPG	0bd4f4c2	2018-05-07 20:41:27	\N	f
51114	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1144.JPG	6e8222d5	2018-05-07 20:41:41	\N	f
51115	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1145.JPG	b71b0ba0	2018-05-07 20:41:42	\N	f
51116	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1146.JPG	baf9c4c4	2018-05-07 20:41:45	\N	f
51117	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1147.JPG	8c2a8853	2018-05-07 20:42:04	\N	f
51118	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1148.JPG	a44e6871	2018-05-07 20:42:10	\N	f
51119	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1149.JPG	6e219ebe	2018-05-07 20:42:29	\N	f
51120	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1150.JPG	82e6333d	2018-05-07 20:42:36	\N	f
51121	1350		2018-05-09 12:30:51	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1151.JPG	75f65e88	2018-05-07 20:42:39	\N	f
51122	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1154.JPG	cc186cb2	2018-05-07 20:43:23	\N	f
51123	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1155.JPG	fbb9c20f	2018-05-07 20:43:33	\N	f
51124	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1156.JPG	d2f4f2b7	2018-05-07 20:43:34	\N	f
51125	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1157.JPG	261ead84	2018-05-07 20:43:54	\N	f
51126	1350		2018-05-09 12:30:52	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1158.JPG	1d2dd5dc	2018-05-07 20:44:07	\N	f
51127	1350		2018-05-09 12:30:52	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1159.JPG	0cbc6d1c	2018-05-07 20:44:24	\N	f
51128	1350		2018-05-09 12:30:52	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1160.JPG	ac436d7b	2018-05-07 20:44:29	\N	f
51129	1350		2018-05-09 12:30:52	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1161.JPG	870eac6a	2018-05-07 20:44:30	\N	f
51130	1350		2018-05-09 12:30:52	4000	6000	PhotoCee-Meetings/2018_05_07/IMG_1162.JPG	3beb10e0	2018-05-07 20:44:32	\N	f
51131	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1164.JPG	1fa0780c	2018-05-07 20:45:05	\N	f
51132	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1165.JPG	79cfc472	2018-05-07 20:45:18	\N	f
51133	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1166.JPG	92bef669	2018-05-07 20:45:52	\N	f
51134	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1167.JPG	a6284f03	2018-05-07 20:46:22	\N	f
51135	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1168.JPG	82927368	2018-05-07 20:46:52	\N	f
51136	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1169.JPG	b6e8fb80	2018-05-07 20:47:05	\N	f
51137	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1170.JPG	78ba8dd1	2018-05-07 20:48:46	\N	f
51138	1350		2018-05-09 12:30:52	6000	4000	PhotoCee-Meetings/2018_05_07/IMG_1171.JPG	f0c8212d	2018-05-07 20:49:18	\N	f
51149	1353		2018-06-02 22:24:03	2652	1492	fotos20172018/20180601_StaffBBQ/IMG_20180601_200810.jpg	a3edc364	2018-06-01 20:08:10	\N	f
51150	1353		2018-06-02 22:24:03	4048	3036	fotos20172018/20180601_StaffBBQ/IMG_20180601_200816.jpg	61825da7	2018-06-01 20:08:16	\N	f
51151	1353		2018-06-02 22:24:03	4048	3036	fotos20172018/20180601_StaffBBQ/IMG_20180601_201258.jpg	7c79dda4	2018-06-01 20:12:58	\N	f
51152	1353		2018-06-02 22:24:03	4048	3036	fotos20172018/20180601_StaffBBQ/IMG_20180601_211357.jpg	cb416c75	2018-06-01 21:13:57	\N	f
51153	1353		2018-06-02 22:24:03	4048	3036	fotos20172018/20180601_StaffBBQ/IMG_20180601_211458.jpg	9e9f94b1	2018-06-01 21:14:58	\N	f
51154	1353		2018-06-02 22:24:03	3286	1849	fotos20172018/20180601_StaffBBQ/IMG_20180601_211503.jpg	6b5a2e57	2018-06-01 21:15:03	\N	f
51155	1353		2018-06-02 22:24:03	4048	3036	fotos20172018/20180601_StaffBBQ/00100sPORTRAIT_00100_BURST20180601212543474_COVER.jpg	dda35c24	2018-06-01 21:25:43	\N	f
51156	1353		2018-06-02 22:24:03	3036	4048	fotos20172018/20180601_StaffBBQ/00100sPORTRAIT_00100_BURST20180601212925270_COVER.jpg	3f41223c	2018-06-01 21:29:25	\N	f
51157	1353		2018-06-02 22:24:03	3596	2023	fotos20172018/20180601_StaffBBQ/00100sPORTRAIT_00100_BURST20180601212953662_COVER.jpg	a0e561d5	2018-06-01 21:29:53	\N	f
51158	1354		2018-06-12 17:55:52	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_232716.jpg	646c441f	2018-06-06 23:27:17	\N	f
51159	1354		2018-06-12 17:55:52	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_232732.jpg	7ac97a74	2018-06-06 23:27:33	\N	f
51160	1354		2018-06-12 17:55:52	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_232835.jpg	cff40b41	2018-06-06 23:28:36	\N	f
51161	1354		2018-06-12 17:55:52	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_232927.jpg	d287f9ea	2018-06-06 23:29:28	\N	f
51162	1354		2018-06-12 17:55:52	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_233200.jpg	59ad01c9	2018-06-06 23:32:01	\N	f
51163	1354		2018-06-12 17:55:53	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_233424.jpg	bcc56e71	2018-06-06 23:34:24	\N	f
51164	1354		2018-06-12 17:55:53	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180606_233926.jpg	aa98b595	2018-06-06 23:39:27	\N	f
51165	1354		2018-06-12 17:55:53	4608	3456	fotos20172018/20180606_JuneSocial/IMG_20180607_000903.jpg	3e4b82ac	2018-06-07 00:09:05	\N	f
51170	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_203010.jpg	e22fb083	2018-06-04 20:30:10	4	f
51171	1355		2018-06-13 00:02:14	3549	1996	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_203018.jpg	eb72ee6a	2018-06-04 20:30:18	5	f
51173	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/MVIMG_20180604_203942.jpg	4a1ccf60	2018-06-04 20:39:42	6	f
51174	1355		2018-06-13 00:02:14	3036	4048	fotos20172018/20180604_GeneralAssemblyJune/00100sPORTRAIT_00100_BURST20180604204328968_COVER.jpg	5c835fe6	2018-06-04 20:43:29	8	f
51172	1355		2018-06-13 00:02:14	3036	4048	fotos20172018/20180604_GeneralAssemblyJune/00100sPORTRAIT_00100_BURST20180604203149926_COVER.jpg	0f43c030	2018-06-04 20:31:49	7	f
51166	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_201413.jpg	df675b01	2018-06-04 20:14:14	0	f
51167	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_202224.jpg	913e5bb1	2018-06-04 20:22:24	1	f
51168	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_202228.jpg	84d9ddf3	2018-06-04 20:22:28	2	f
51169	1355		2018-06-13 00:02:14	4048	3036	fotos20172018/20180604_GeneralAssemblyJune/IMG_20180604_203006.jpg	5199eed8	2018-06-04 20:30:06	3	f
\.


--
-- Data for Name: leden; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.leden (id, voornaam, tussenvoegsel, achternaam, adres, postcode, woonplaats, email, geboortedatum, geslacht, privacy, type, machtiging, beginjaar, telefoonnummer, onderschrift, avatar, homepage, nick, taal, member_from, member_till, donor_from, donor_till) FROM stdin;
1	First	\N	Last name	Nijenborgh 9	9711AM	Groningen	test@svcover.nl	1993-09-20	o	0	1	\N	2017	\N	\N	\N	\N	\N	en	2010-01-01	\N	\N	\N
\.


--
-- Data for Name: lid_fotos; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.lid_fotos (id, lid_id, foto, foto_mtime) FROM stdin;
\.


--
-- Data for Name: mailinglijsten; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.mailinglijsten (id, naam, adres, omschrijving, publiek, toegang, commissie, type, tag, on_first_email_subject, on_first_email_message, on_subscription_subject, on_subscription_message) FROM stdin;
\.


--
-- Data for Name: mailinglijsten_abonnementen; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.mailinglijsten_abonnementen (abonnement_id, mailinglijst_id, lid_id, naam, email, ingeschreven_op, opgezegd_op) FROM stdin;
\.


--
-- Data for Name: mailinglijsten_berichten; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.mailinglijsten_berichten (id, mailinglijst, bericht, return_code, verwerkt_op, commissie, sender) FROM stdin;
\.


--
-- Data for Name: mailinglijsten_opt_out; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.mailinglijsten_opt_out (id, mailinglijst_id, lid_id, opgezegd_op) FROM stdin;
\.


--
-- Data for Name: pages; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.pages (id, committee_id, titel, content, content_en, content_de) FROM stdin;
103	0	Quintor	[h1] Quintor [/h1]\r\n\r\nQuintor is een toonaangevend bedrijf op het gebied van Agile software development, enterprise Java / .NET technologie en mobile development. Wij hebben sinds onze oprichting in 2005 een gezonde groei doorgemaakt. Vanuit onze vestigingen in Groningen en Amersfoort ondersteunen wij onze klanten bij de uitdagingen die grootschalige enterprise projecten met zich meebrengen. Quintor beschikt over een software factory waarin wij inhouse projecten voor onze klanten uitvoeren.\r\n\r\nAan onze enterprise klanten verlenen wij diensten op het gebied van ontwikkelproces (Agile / Scrum), informatieanalyse, ontwikkelstraten, geautomatiseerd testen, software development en enterprise architectuur. Quintor levert complete Agile ontwikkelteams bestaande uit analisten, architecten en developers. In haar professioneel datacenter beheert Quintor meer dan 90 servers.\r\n\r\nQuintor heeft haar grootste klanten in de financiële sector (Achmea, ABN AMRO, AEGON, ING, Rabobank en DNB), de overheid (CJIB, DUO, RDW, EZ en DJI), de energiesector (Gasunie, Gasterra, Alliander, Eneco en Enexis) en telecom (KPN en Ziggo).\r\n	[h1] Quintor [/h1]\n\nQuintor is a leading player in the fields of Agile software development, enterprise Java / .Net technology and mobile development. Since its foundation in the year 2005, Quintor has been growing steadily. From our locations in Groningen and Amersfoort, we provide support to our customers in facing the challenges that large-scale enterprise projects entail. Quintor has a software factory at its disposal, from where inhouse projects are carried out. \n\nTo our enterprise customers, we provide services in the field of software development processes (Agile/Scrum), information analysis, software factories, automated testing, software development and enterprise architecture. Quintor provides full Agile development teams consisting of analysts, architects and developers. Currently, over 90 servers are managed in Quintor's professional data center.\n\nQuintor’s biggest customers are in the financial sector (Achmea, ABN AMRO, AEGON, ING, Rabobank and DNB), public sector (CJIB, DUO, RDW, EZ en DJI), utilities (Gasunie, Gasterra, Alliander, Eneco and Enexis) and telecom (KPN and Ziggo).\n	\N
16	0	Actief worden	[h1]Actief worden bij Cover[/h1]\n\n[h3]Organiseer meer[/h3]\nBen jij enthousiast, organisatorisch, creatief, reislustig, gezellig, journalistiek of initiatiefrijk? Of wil je dat juist worden? Doe dan meer met je talent en ontwikkel jezelf en de vereniging. Word teammember van een van deze commissies:\n\n- [url=http://www.svcover.nl/show.php?id=%2015] Foetsie [/url]\nDe Foetsie zorgt ervoor dat er voor de Coverleden genoeg te knabbelen en te drinken valt. Wil je af en toe naar de Makro? Houd je van shoppen? Dan is dit misschien een commissie voor jou!\n\n- [url=http://www.svcover.nl/show.php?id=%2019] SympoCie [/url]\nEr wordt weer een SympoCie gezocht voor een groot symposium in 2011. Er wordt hiervoor gezocht naar een compleet nieuwe commissie, waarbij enkele huidige SympoCieleden ook al interesse hebben getoond. Er worden hier dus veel mensen voor gezocht, wat het een leuke commissie maakt om te doen als je al een grote groep om je heen hebt die ook actief willen worden! Bovendien staat het erg goed op je CV. Misschien is dit wel een commissie voor jou!\n\n- [url=http://www.svcover.nl/show.php?id=%2013] McCie [/url]\nDe McCie is een commissie die een Mega-Excursie organiseert naar het buitenland. Hierbij kun je denken aan landen zoals Japan of Brazilië. Eerder is al een reis naar Amerika geweest. Op dit moment is de commissie leeg, maar het is natuurlijk altijd leuk als deze commissie weer gevuld wordt. Lijkt het je leuk om zo'n gigantische reis te organiseren? Dan is dit misschien een commissie voor jou!\n\nNaast deze commissies waar mensen voor gezocht worden, zijn er natuurlijk nog veel meer commissies waar je eventueel deel vanuit kan maken. [url=http://www.svcover.nl/commissies.php] Bekijk ze hier allemaal! [/url]\n\nAls je eerst meer wilt weten over Cover, een commissie of actief worden, kun je natuurlijk altijd het [url=http://www.svcover.nl/show.php?id=0] bestuur[/url] vragen stellen. Kom een keer langs de Coverkamer, spreek ons aan bij activiteiten of stuur een mailtje naar [url=mailto:bestuur@svcover.nl]bestuur@svcover.nl[/url].	\N	\N
20	0	English	[h1]Welcome foreign visitor[/h1]\r\n\r\n[b]Welcome to the website of student union CoVer. On this part of the site you can find more information about:\r\n- CoVer\r\n- Artificial Intelligence at the Dutch University of Groningen\r\n- some interesting links\r\n- contact information\r\n[/b]\r\n\r\n[h2]CoVer[/h2]\r\nCoVer is the student union of the Artificial Intelligence department at the Dutch University of Groningen. It organizes many study-related and social events. CoVer introduces and supports their student members, sells them books with discount, and also makes and maintains contact with companies to become acquainted with the aspects of Artificial Intelligence. \r\n\r\nAn important activity is organizing excursions. These excursions have two main goals: The first is to make the participants more aware of the research and commercial activities in the area of Artificial Intelligence and the second goal is to give them an opportunity to get in touch with universities or companies that offer graduating or working opportunities. Indirectly, it helps them in the choice which aspect they want to focus on within Artificial Intelligence.\r\n\r\nCoVer has visited many other universities and companies all over the world. Some of the places weve been with our member-students are; Rome, Boston, Edinburgh, Berlin, Prague, New York and Florida. Universities and companies like IBM, MIT (Medialab and CSAIL), Harvard, Edinburgh University, Scansoft and Kennedy Space Centre made an effort in presenting their research to our students, possible future colleagues . A more exhaustive list and some travel reports can be found [url=http://www.ai.rug.nl/~mccie]here[/url] (most of them are in Dutch).\r\n\r\nThis year, as any year, we will visit new countries and other companies and universities. More information about the coming journeys can be found [url=http://www.ai.rug.nl/~excie]here[/url].\r\n\r\nWould you like us to visit your company, university or research centre? Or would you like to visit the department of Artificial Intelligence at the University in Groningen? Then we would be our pleasure to arrange a meeting or excursion. The section How to contact will provide you with the information needed to reach the board of the student union of Artificial Intelligence; CoVer.\r\n\r\n[h2]Artificial Intelligence[/h2]\r\nThe Artificial Intelligence department at the Dutch University of Groningen was found in 1993. Originally it was named TCW: Technical Cognitive Science. As this name suggests our educational and research program isnt focused on classical, logical and statistical based Artificial Intelligence. Instead we are interested in Cognitive Science and focus on it from a Technical Perspective. Therefore we use robotics, physics and computer models to learn more about intelligence.\r\n\r\nThe research is divided in four programmes: Autonomous Perceptive Systems, Multi-Agent Systems, Language and Speech Technology and Cognitive Modelling.  If you want to read more about the research at our university, you can visit the website of the [url=http://www.ai.rug.nl/alice]ALICE research group[/url]. Some of the research is done in close collaboration with the [url=http://www.rug.nl/bcn]School of Behavioural and Cognitive Neurosciences[/url].\r\n\r\nStudents are involved in Bachelor and Master programs. The Bachelor program is called Artificial Intelligence and takes 3 years. Students learn about AI, searching in algorithms, logic and statistics, physics, cognitive science, language and speech technology and robotics. During their last year they follow intensive practical courses on robotics, expert systems, human factors or language and speech technology. Next to this they conduct a small research.\r\n\r\nThe University of Groningen has two related Masters, both are a 2 year program. In the Master Artificial Intelligence students follow more courses and conduct research in the fields of robotics and multi agent systems. In the Master Man Machine Interaction students follow courses and conduct research in the fields of cognitive science, cognitive modelling, language and speech technology and interface design. Toward graduation they conduct a Master Thesis research of 6 months. All Master Thesiss can be found [url= http://www.ai.rug.nl/nl/colloquia/]here[/url]. \r\n\r\n\r\n[h2]Links[/h2]\r\n\r\n[h3]Our university[/h3]\r\n[url=http://www.rug.nl/corporate/index?lang=en]Dutch University of Groningen[/url]\r\n[url=http://www.rug.nl/ai/index?lang=en]Artificial Intelligence, Dutch University Groningen[/url]\r\n[url=http://www.ai.rug.nl/alice/]Research institute ALICE[/url]\r\n[url=www.rug.nl/bcn]School Behavioral and Cognitive Neuroscience[/url]\r\n\r\n[h3]CoVer[/h3]\r\n[url=www.amigro.nl]CoVers international Symposium: The ISB Event 2006: AmIGro (March 16th in Groningen)[/url]\r\n[url=www.ai.rug.nl/~mccie]CoVers past excursions abroad[/url]\r\n[url=www.ai.rug.nl/~excie]CoVers Excursion Committee[/url]\r\n[url=http://www.ai.rug.nl/~cover/show.php?id=11]CoVers Students Activity Committee (in Dutch)[/url]\r\n\r\n[h3]Other[/h3]\r\n[url=www.groningen.nl]Groningen city[/url]\r\n[url=www.ns.nl]The Dutch Railway companie[/url]\r\n\r\n[h2]Contact[/h2]\r\n[h3]Contact information[/h3]\r\nIf you would like more information about CoVer or the Artificial Intelligence department, please contact the board of the student union:\r\n[b]\r\nPostal adress:\r\nStudent union CoVer\r\nGrote Kruisstraat 2/1\r\n9712 TS Groningen\r\nThe Netherlands\r\n\r\ne-mail: cover@ai.rug.nl\r\nBank account:\t4383199 \r\nChamber of Commerce registration number: 40026707\r\n\r\nOur visiting address:\r\nGroote Appelstraat 23\r\nGroningen\r\n[/b]\r\n\r\n[h3]Directions[/h3]\r\n[h3]By public transport[/h3]\r\nTake the train toward Zwolle/Groningen (visit [url=www.ns.nl]The website of the Dutch Railway Companie[/url] for route planning).\r\nWhen you leave Groningen Central station, walk across the museum bridge, past the Museum of Groningen. For approximately the next 15 minutes you walk straight ahead. You will walk through the Ubbo Emmiusstraat, cross the Zuiderdiep, walk through the Folkingestraat, cross the Vismarkt, walk through the Stoeldraaiersstraat, through the Oude Kijk in `t Jatstraat, cross a bridge and finally walk into the Nieuwe Kijk in `t Jatstraat. The Groote Appelstraat is the fourth street to your left. CoVer can be found at number 23.\r\n\r\n[h3]By car:[/h3]\r\nWhen you are coming from the direction of Amsterdam, follow directions toward Assen and Groningen. (these directions are not included). When you are near Assen, stay on road A28. You wil reach a bug crossing with traffic lights called Julianaplein', go straight ahead into the centre. When you arrive at the next traffic lights, go to the left. Follow this road (Emmasingel, Eeldersingel) for about 600 meters.\r\nAt the next traffic light, you take a right, crossing a bridge. At the end you then take a right and an immediate left, driving into the Westersingel.\r\nAt the next traffic lights you go straight ahead. After the bridge you take a left. You will drive along the Noorderplantsoen (a park at your right side).\r\nTake the first street to your right: the Oranjesingel. After about 300 meters, take the first street to your right. This is the Kerklaan, which will move over into the Grote Kruisstraat. Take the first street to the left (Nieuwe Kijk in t Jatstraat) and the Groote Appelstraat is the first street to your left. You can park your car on the square behind the green fence. The student union is located at number 23.\r\n	\N	\N
73	0	Bestuur IV	[samenvatting]Bestuur IV (1996/1997)[/samenvatting]\n[h1]Coverbestuur IV[/h1]\n____BESTUURSFOTO____\n\nHet 4e bestuur van Cover is ingehamerd op 12 september 1996.\n\n[h2]Leden[/h2]\nVoorzitter: Gregor Tee (later: Diederik Roosch)\nSecretaris: Diederik Roosch (later: Arthur Perton)\nPenningmeester: Johan Kruiseman\nStudActie: Arthur Perton\nActie: Wiebe Baron		
74	0	Bestuur III	[samenvatting]Bestuur III (1995/1996)[/samenvatting]\n[h1]Coverbestuur III[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Martijn de Vries\nSecretaris: Erik vd Neut\nPenningmeester: Libbe Oosterman\nCommissaris intern: Lennart Quispel\nCommissaris extern: Desiree Houkema\n		
75	0	Bestuur II	[samenvatting]Bestuur II (1994/1995)[/samenvatting]\n[h1]Coverbestuur II[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Marc Oyserman\nSecretaris: Jeroen Kruse\nPenningmeester: Erik vd Neut\nCommissaris Intern: Jaap Bos (later: Desiree Houkema)\nCommissaris Extern: Karel de Vos\n		
15	15	Commissiepagina Foetsie	[samenvatting]De Foetsie zorgt ervoor dat er vaak iets te eten en drinken is in de Coverkamer. [/samenvatting]\r\n\r\nTegenwoordig zijn de kruisjes duurder, maar de service is nog steeds net zo goed!	[samenvatting]The Foetsie provides food and drinks in the Cover room. [/samenvatting]\r\n\r\nThe credits are more expensive these days, but we try to maintain the quality of our service!	\N
12	12	Commissiepagina AudiCee	[samenvatting]De Kascommissie controleert de financieen van Cover. Hiervan doen ze op de ALVs verslag aan de leden.[/samenvatting]\r\n\r\nDe Kascommissie controleert de financieen van Cover. Hiervan doen ze op de ALVs verslag aan de leden.\r\n\r\nDe leden van de Kascommissie worden door de jaarlijkse ALV gekozen, en kunnen worden voorgedragen door 10 leden van de ALV of de KasCie zelf. Om een goede controle en objectiviteit naar de leden toe te kunnen waarborgen, blijven deze leden voor minimaal 1 jaar commissielid. De leden hebben meestal veel ervaring met financieen, en zijn vaak voormalig penningmeesters van Cover.	[samenvatting]The AudiCee audits the financial statement every year. It will report on it at the constitutional assembly.[/samenvatting]\r\n	\N
60	0	Bestuur XVII	[samenvatting]Bestuur 17 (2008/2009)\nBestuursnaam: Infinity[/samenvatting]\n[h1]Bestuur XVII: Infinity[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Sjors Lutjeboer\nSecretaris: Ruud Henken\nPenningmeester: Dyon Veldhuis\nCommissaris Intern: Sybren Jansen\nCommissaris Extern: Ben van Os 		
61	0	Bestuur XVI	[samenvatting]Bestuur 16 (2007/2008)\nBestuursthema: De Ontwaking[/samenvatting]\n[h1]Bestuur XVI: De Ontwaking[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Joël Kuiper\nSecretaris: Daniël Karavolos\nPenningmeester: Wilco Wijbrandi\nCommissaris Intern: Margreet Vogelzang\nCommissaris Extern: Peter Jorritsma 		
63	0	Bestuur XIV	[samenvatting]Bestuur 14 (2006)\nBestuursthema: Illusie[/samenvatting]\n[h1]Bestuur XIV: Illusie[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Henrieke Quarré\nSecretaris: Roeland van Batenburg\nPenningmeester: Martijn Hartman\nCommissaris Intern: Cas van Noort\nCommissaris Extern: Nick Degens 		
25	0	ALV	[h1]ALV[/h1]\nDrie a vier keer per jaar vindt er een een Algemene Ledenvergadering (ALV) plaats. Op een ALV verantwoorden alle commissies en het bestuur zich aan de leden. Deze vergadering informeert de leden over het gevoerde beleid en geeft de leden inspraak bij besluiten en voorstellen. Het is dus erg belangrijk dat je als Coverlid de ALV's bezoekt. Wanneer je verhinderd bent, bestaat er altijd de mogelijkheid om je te laten machtigen.\n\nAlle relevante stukken van een ALV zijn minstens een week voor een ALV te vinden op deze website, onder '[url=show.php?id=30]Documenten[/url]'. Ook liggen er minimaal twee inkijkexemplaren in de Coverkamer. Een machtigingsformulier zit bij de uitnodiging die elk lid over de post ontvangt.\n\n[b]NB[/b]: Op ALV's wordt iedereen altijd gratis voorzien van gepaste drankjes en koekjes!\n\n[h2]Overgangsboekjaar (2012)[/h2]\nTijdens de ALV in mei worden de begrotingen voor het komende boekjaar besproken.\n\n[h2]Vanaf augustus 2012[/h2]\nIn oktober vindt er een overdrachts-ALV plaats, tijdens deze ledenvergadering wordt er van bestuur gewisseld. Daarnaast wordt er een afrekening gepresenteerd, presenteert het bestuur haar eindverslag en de commissies een halfjaarverslag. Tijdens deze ALV komt ook de CUOS-lijst ter stemming, zoals dat vanaf 2012 moet.\n\nErgens rond de jaarwisselingen is er een ALV waarin het bestuur haar beleidsplan presenteert, eventueel kan op deze ALV ook gestemd worden over een herziene begroting.\n\nIn maart is er een ALV waarop zowel de commissies als het bestuur halfjaarverslagen presenteren. Tijdens deze ALV wordt ook een financieel halfjaarverslag gepresenteerd.\n\nEind mei, begin juni is er een ALV om begrotingen voor het komende boekjaar goed te keuren, dan presenteren commissies ook hun jaarplanning.	\N	\N
119	41	ParentDay Committee	\N	\N	\N
38	2	Test in opera	Test in opera .. nieuwe pagina aanmaken voor de actie	\N	\N
105	1	IBM Services Center	The IBM Services Centers, are wholly owned subsidiaries of IBM. In 2013, IBM opened 4 centers in Europe - France (Lille), Germany (Magdeburg), Netherlands (Groningen) and the United Kingdom (Leicester).\nAs part of IBM’s globally integrated capability network, the Centers have been created to deliver an industry-leading range of innovative technology services and provide deep technical and industry expertise to local IBM clients across public and private sectors. The rise of Big Data, analytics, cloud and mobile technologies is changing the way companies work and interact with their customers. \n\nTo meet this increasing demand for flexible software capabilities that harness the benefits of these new technologies, the Centers provide high value application development, application maintenance and systems integration services for a range of leading\nbusinesses. \n\nFor our employees, the centers have the atmosphere of an entrepreneurial start-up, but with the support of the world's largest IT and consulting services company. The work environment is fast paced and dynamic, buzzing with innovation and invention. The atmosphere is more like a campus than a workplace. Here, people co-operate and learn from each\nother in an open and collaborative culture.\n\nThe Centers offer a unique experience for talented and ambitious graduates and professionals who are keen to build their skills fast. It’s a place for people with real enthusiasm and a passion for IT. People who are always prepared to challenge and ask questions. People who want to explore what’s possible.	The IBM Services Centers, are wholly owned subsidiaries of IBM. In 2013, IBM opened 4 centers in Europe - France (Lille), Germany (Magdeburg), Netherlands (Groningen) and the United Kingdom (Leicester).\nAs part of IBM’s globally integrated capability network, the Centers have been created to deliver an industry-leading range of innovative technology services and provide deep technical and industry expertise to local IBM clients across public and private sectors. The rise of Big Data, analytics, cloud and mobile technologies is changing the way companies work and interact with their customers. \n\nTo meet this increasing demand for flexible software capabilities that harness the benefits of these new technologies, the Centers provide high value application development, application maintenance and systems integration services for a range of leading\nbusinesses. \n\nFor our employees, the centers have the atmosphere of an entrepreneurial start-up, but with the support of the world's largest IT and consulting services company. The work environment is fast paced and dynamic, buzzing with innovation and invention. The atmosphere is more like a campus than a workplace. Here, people co-operate and learn from each\nother in an open and collaborative culture.\n\nThe Centers offer a unique experience for talented and ambitious graduates and professionals who are keen to build their skills fast. It’s a place for people with real enthusiasm and a passion for IT. People who are always prepared to challenge and ask questions. People who want to explore what’s possible.	\N
39	17	Commissiepagina Eerstejaarscie	[samenvatting]De EerstejaarsCie organiseert leuke activiteiten voor en door de eerstejaars onder jullie![/samenvatting]\n\nDe EerstejaarsCie organiseert leuke activiteiten voor en door de eerstejaars van Cover!\n\n[b]Voor contact met de EerstejaarsCie, stuur ons een mailtje![/b]\n	The FirstYearCee organises fun activities for the freshmen of Cover!\n\n[b]To contact the FirstYearCee, please send us an email![/b]\n\n	\N
76	0	Bestuur I	[samenvatting]Bestuur I (1993/1994)[/samenvatting]\n[h1]Coverbestuur I[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nMichiel Dulfer\nBruno Emans\nMarco Oyserman\nPaul Vogt\n\nDe precieze functieverdeling van het eerste Coverbestuur is onbekend.\n		
45	21	Commissiepagina LanCie	[samenvatting]De LanCie organiseert elk jaar twee enorm toffe LAN-party's![/samenvatting]\r\n\r\nDe LanCie organiseert elk jaar twee enorm toffe LAN-party's voor relatief weinig geld.	\N	\N
52	0	Sogeti	[h1]Sogeti[/h1]\n\n[h2]Over Sogeti Nederland[/h2]\n\nMet ruim 3.300 medewerkers bundelen we meer dan 35 jaar ICT-kennis en -expertise in één bedrijf. Het ontwerpen, realiseren, implementeren, testen en beheren van ICT-oplossingen behoort tot onze core-business. Eén van onze specialismen is het bouwen en beheren van informatiesystemen die 7*24 draaien en betrouwbaar moeten zijn. Sogeti-methodieken als TMap®, TPI®, DYA® en Regatta® zijn uitgegroeid tot internationale standaarden.\n\n[h2]Passie voor je vak[/h2]\n\nVakmanschap is een van de belangrijkste pijlers van onze cultuur. Ook in de structuur van onze divisies komt dit tot uiting. Onze medewerkers zijn gegroepeerd rondom expertises. Binnen een expertisegroep worden regelmatig bijeenkomsten georganiseerd. Dit biedt jou de mogelijkheid om met collega's over het vakgebied te praten, kennis op te doen en te delen wat weer bijdraagt aan je persoonlijke ontwikkeling. Daarnaast kom je zo op informele wijze met elkaar in contact. Plezier vinden we namelijk een voorwaarde om je werk goed te kunnen uitvoeren.\n\n[h2]Het heft in eigen hand[/h2]\n\nJij krijgt volop mogelijkheden om je te ontwikkelen op één van onze expertises, waarbij wij voortdurend aandacht hebben voor jouw loopbaanontwikkeling. Dat doen we onder andere door middel van coaching, opleidingen, technische meetings, congressen en beurzen. Certificering op ons vakgebied vinden wij belangrijk en hierbij faciliteren en stimuleren we je zoveel mogelijk.\n\nZelf zit je wel aan het stuur om jouw uiteindelijke bestemming binnen Sogeti en jouw vakgebied te bereiken. Wij vinden eigen verantwoordelijkheid erg belangrijk. Daarnaast verwachten we van jou dat je beschikt over goede communicatieve eigenschappen om met onze klanten te sparren. Gedrevenheid, enthousiasme en plezier in en trots voor het vakgebied.\n\n[h2]Young Professionals[/h2]\n\nWij zoeken young professionals met een ICT-gerelateerde opleiding op HBO-/WO-niveau. Young Professionals starten met een opleiding van minimaal twee maanden. De eerste maand is een basisopleiding, in samenwerking met de Ohio Universtiy. Je studeert drie weken in de Verenigde Staten en verblijft op de campus van de Ohio University. Hier leer je de laatste stand van zaken op het gebied van bedrijfskunde en ICT en je werkt daarnaast aan je persoonlijke ontwikkeling. Het tweede deel van de basisopleiding is de specialisatiemaand, waarin de vaktechnische specialisatie plaatsvindt. Na afloop van de basisopleiding ben je inzetbaar bij de klanten van één van onze divisies.\n\nKijk op [url=http://www.werkenbijsogeti.nl]werkenbijSogeti.nl[/url] voor meer informatie over Sogeti, onze vacatures en de sollicitatieprocedure.		
53	0	FINAN	[h1]Welkom bij Finan![/h1]\nDeze tekst gaat over jouw eerste baan. We hopen dat je deze bij Finan invult. Wij zijn een middengroot Nederlands softwarebedrijf in Zwolle waar bedrijfseconomen en informatici samen werken aan innovatieve oplossingen rond financieringsvraagstukken. Zoals je de laatste tijd in de media hebt gemerkt, is dat een zeer dynamisch onderwerp met complexe vraagstukken en maatschappelijke relevantie.\n\nTopicus Finan BV is een dochteronderneming van Topicus. Wij zijn een toonaangevende leverancier van software voor financiële analyse en credit risk management. De zakelijke kredietketen is enorm in beweging en Finan is daarin een belangrijke innovatieve kracht. Wij bieden standaard- en maatwerkoplossingen aan banken, financiële adviseurs, accountants, aansprekende ondernemingen en onderwijsinstellingen. Finan kenmerkt zich door een hoge mate van commerciële professionaliteit, innovatieve producten en diensten en begeleidt de klanten in de projecten van begin tot eind.\n\n[h2]ICT bij Finan[/h2]\n\nEr is bij ons geen functionele scheiding tussen ontwerpers en software engineers; wel zien we dat sommige mensen zich vooral thuis voelen in de techniek en anderen vooral graag ontwerpen. Vooral medewerkers die zich initieel op de combinatie van beide disciplines storten, blijken zich snel te ontwikkelen. In de rol van software engineer werk je bij Finan met een J2EE ontwikkelstraat, grotendeels op basis van open source componenten. Applicaties die we bouwen zijn complex van aard en daarom maken we gebruik van domeinspecifieke talen en slim geparameteriseerde logica. Wat betreft analyse en ontwerp hanteren we een informele aanpak die wordt aangepast aan het project. Maatwerk wordt bij voorkeur iteratief volgens een risk-first aanpak vormgegeven.\n\n \n\n[h2]Werken bij Finan[/h2]\n- Werken binnen een zeer snel groeiende organisatie;\n\n- Grote mate van zelfstandigheid;\n\n- Een prettige informele werksfeer met leuke collega's en 'korte lijntjes';\n\n- Coaching en extra training waar nodig;\n\n- Werken op projectbasis;\n\n- Prima arbeidsvoorwaarden;\n\n- Doorgroeimogelijkheden binnen Finan. In je carrière begin je breed en breng je steeds meer focus aan. De bedoeling is dat je na enkele jaren een duidelijke keuze kunt maken tussen 'harde' software engineering (met toenemende specialisatie), de functionele kant (functioneel of technisch ontwerp) of de leidinggevende kant (als project- of teamleider).\n\nFinan is continu op zoek naar slimme starters op HBO en WO niveau. Relevante opleidingen zijn: - Bedrijfseconomie (WO) - Informatica, informatiekunde (WO en HBO functies beschikbaar) - Toegepaste wiskunde (WO) - Econometrie (WO) - Interaction\n\nWe bieden daarnaast stageplaatsen en afstudeeropdrachten aan, mits onze begeleidingscapaciteit dat toestaat. Gemiddeld voeren twee studenten bij ons opdrachten uit, die zij meestal zelf binnen ons vakgebied definiëren aan de hand van hun eigen interesses. Heb je interesse of wil je graag meer informatie? Neem contact op met Marloes Bulthuis via telefoonnummer 088 77 88 990 of mail naar m.bulthuis@finan.nl.\n\nKijk voor actuele vacatures op: http://www.finan.nl/index.php?option=com_content&task=blogcategory&id=26&Itemid=123		
62	0	Bestuur XV	[samenvatting]Bestuur 15 (2007)\nBestuursthema: Metamorfose[/samenvatting]\n[h1]Bestuur XV: Metamorfose[/h1]\n[h2]Leden[/h2]\nVoorzitter: Neeltje de Ruijter\nSecretaris: Stefan Wierda\nPenningmeester: Ferdinand Stam (later: Anoniem)\nCommissaris Intern: (later: Eva van Viegen)\nCommissaris Extern: Anoniem		
57	0	Oude besturen	[samenvatting]Bestuur XX (2011/2012)\nBestuursnaam: Helder[/samenvatting]\n[h1]Bestuur XX: Helder[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Ben Wolf\nSecretaris: Maikel Grobbe\nPenningmeester: Laura Baakman\nCommissaris Intern: Jouke van der Weij\nCommissaris Extern: Maarten van Gijssel	\N	\N
58	0	Bestuur XIX	[samenvatting]Bestuur XIX (2010/2011)\nBestuursnaam: Stabiliteit[/samenvatting]\n[h1]Bestuur XIX: Stabiliteit[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Gabe van der Weijde\nSecretaris: Tineke Slotegraaf\nPenningmeester: Joris de Keijser\nCommissaris Intern: Diederick Kaaij\nCommissaris Extern: Wolter Peterson 		
59	0	Bestuur XVIII	[samenvatting]Bestuur 18 (2009/2010)\nBestuursnaam: Verbonden[/samenvatting]\n[h1]Bestuur XVIII: Verbonden[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Anita Drenthen\nSecretaris: Eveline Broers\nPenningmeester: Marco Bosman\nCommissaris Intern: Eric Jansen\nCommissaris Extern: Dirk Zittersteyn 		
69	0	Bestuur VIII	[samenvatting]Bestuur VIII (2000/2001)[/samenvatting]\n[h1]Coverbestuur VIII[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Willem Hibbeln\nSecretaris: (later: Marcia van Oploo)\nPenningmeester: Jan Willem Marck\nCommissaris intern: Liesbeth van der Feen\nCommissaris extern: Sebastiaan Pais (later: Douwe Terluin)\n\n2001: KI wordt een voltijdsopleiding met een complete Bachelor (niet meer alleen post-propedeutisch). De studie trekt meer studenten aan en Cover maakt vanaf dit jaar voor een aantal jaar een grote groei in ledenaantal.		
70	0	Bestuur VII	[samenvatting]Bestuur VII (1999/2000)[/samenvatting]\n[h1]Coverbestuur VII[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Peter Zwerver\nSecretaris: Marleen Schippers\nPenningmeester: Gerben Blom\nCommissaris intern: Fiona Douma\nCommissaris extern: Ernst-Jan Tissing\n		
71	0	Bestuur VI	[samenvatting]Bestuur VI (1997/1998)[/samenvatting]\n[h1]Coverbestuur VI[/h1]\n____BESTUURSFOTO____\n\nHet 6e bestuur van Cover. Ingehamerd op 12 oktober 1998.\n\n[h2]Leden[/h2]\nVoorzitter: Sjoerd Druiven\nSecretaris: Gineke ten Holt\nPenningmeester: Jan Misker\nActCie Commissaris: Arjan Stuiver\nStudCie Commissaris: Albert ter Haar\n		
72	0	Bestuur V	[samenvatting]Bestuur V (1997/1998)[/samenvatting]\n[h1]Coverbestuur V[/h1]\n____BESTUURSFOTO____\n[h2]Leden[/h2]\nVoorzitter: Wiebe Baron\nSecretaris: Maartje van der Veen\nPenningmeester: Aletta Eikelboom\nStudActie: Jan Misker\nActie: Sjoerd Druiven		
67	0	Bestuur X	[samenvatting]2003[/samenvatting]\n[h1]Bestuur X[/h1]\n[h2]Leden[/h2]\nVoorzitter: Jacob van der Blij\nSecretaris: Jan Bernard Marsman\nPenningmeester: Sjoerd de Jong\nCommissaris Intern: Jolie Lanser\nCommissaris Extern: Herman Kloosterman		
66	0	Bestuur XI	[samenvatting]Bestuur 11 (2004)\n[/samenvatting]\n[h1]Bestuur XI[/h1]\n[h2]Leden[/h2]\nVoorzitter: Stefan Renkema\nSecretaris: Maaike Schweers\nPenningmeester: Mart van de Sanden\nCommissaris Intern: Heiko Harders\nCommissaris Extern: Sander van Dijk 		
65	0	Bestuur XII	[samenvatting]Bestuur 12 (2005)\n[/samenvatting]\n[h1]Bestuur XII[/h1]\n[h2]Leden[/h2]\nVoorzitter: Bastiaan Fens\nSecretaris: Merel Oppelaar\nPenningmeester: Ferdinand Stam\nCommissaris Intern: Chris Janssen\nCommissaris Extern: Rosemarijn Looije 		
77	0	KPN Consulting	[h1]KPN - Het beste ICT bedrijf van Nederland[/h1]\n\nKPN speelt als marktleider in geïntegreerde IT- en telecommunicatiediensten een grote rol in de Nederlandse maatschappij. Dankzij onze dienstverlening kun jij je dagelijkse boodschappen pinnen, varen schepen veilig de haven van Rotterdam binnen, rijden de treinen van de NS en kan iedereen in geval van nood 112 bellen. Wij verbinden de samenleving met ICT-dienstverlening die innovatief en veilig is. \n\nOnze professionals nemen organisaties mee in de nieuwste ICT-ontwikkelingen. Door hun sectoren te begrijpen, door hun specifieke behoeften te kennen en  door hun taal te spreken. Wij adviseren onze klanten én ontwikkelen ICT-diensten die voor hen relevant zijn. Daarna implementeren en onderhouden we het ICT-netwerk, zodat opdrachtgevers hun tijd en energie zorgeloos kunnen steken in die zaken waarin zij goed zijn, om op hun beurt hun eigen klanten optimaal te bedienen. Wij zorgen dat iedereen zijn werk professioneel, efficiënt en met gemak kan doen via onze geavanceerde datacenters en op volledig geïntegreerde werkplekken. Het is niet voor niets dat KPN in 2013 door lezers van Management Team verkozen is tot beste IT-bedrijf van Nederland.\n\nOm op hoog niveau te kunnen presteren investeert KPN volop in haar professionals. Bij het ICT bedrijf van KPN hebben we vier verschillende traineeships en twee young professionaltrajecten bij het onderdeel KPN Consulting.  Wij geven onze medewerkers de ruimte om zich te ontwikkelen en bieden uitgebreide mogelijkheden om plaats- en tijdonafhankelijk te werken, omdat wij een optimale work/life balance belangrijk vinden. Bovendien is ons adviesbedrijf, KPN Consulting,  voor het tweede jaar Great Place to Work geworden.  \nKPN doet 't gewoon. Doe je mee?\n\nKijk voor onze young professional vacatures (KPN Consulting) of traineeships op www.kpn.com/itsolutions of www.kpnconsulting.nl\n		
78	0	Vacature KPN Consulting	[h1]KPN Consulting[/h1]\n[h2]Young Professional Project/Proces[/h2]\n[h3]Wat doet een Young Professional Project/Proces?[/h3]\nHet Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het 'werkende' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega's goed kennen.\n\nAlle kennis die je tijdens de introductie hebt opgedaan, kun je direct toepassen bij onze klanten in projecten waar jij de schakel tussen business en techniek zult zijn. KPN Consulting werkt samen met de 400 grootste bedrijven van Nederland zoals Shell, Nuon, Rijkswaterstaat, NS en ING. Bovendien stippel je samen met een persoonlijke coach een carrièrepad uit aan de hand van jouw kennis, ervaring en ambitie. Gedurende het jaar volg je naast je projecten nog enkele trainingen. Ter afsluiting van het traject voer je samen met je mede Young Professionalcollega's een praktijkgerichte case op het gebied van Proces- & Projectmanagement uit, die je daarna presenteert aan onze directie.\n\nNa het eerste jaar ontwikkel je je richting Proces- & Projectmanagement of service & performance management. Je kunt daarbij een vliegende start maken door als High Potential door te stromen naar een vervolgopleidingstraject, waar je in korte tijd de kennis, kunde en ervaring opdoet voor de rest van je loopbaan. Dit geeft je de mogelijkheid om uit te groeien tot een gewaardeerde consultant die organisaties met concrete adviezen naar een hoger niveau tilt.\n\n[h3]Wat heb jij ons te bieden als Young Professional?[/h3]\n- Afgeronde masteropleiding in ICT, Bedrijfskunde, of een Bèta studierichting;\n- Aantoonbare affiniteit met en kennis van ICT;\n- Goede communicatievaardigheden;\n- Helikopterview, proactief, resultaatgericht en pragmatisch;\n- Goede beheersing van de Nederlandse taal;\n- Je staat voor kwaliteit en je doet net iets meer dan afgesproken.\n\n[h3]Wat bieden wij jou?[/h3]\nWij bieden jou een carrière bij de nummer 1 in ICT-consultancy. Daarbij horen natuurlijk dingen als een marktconform salaris, een leaseauto, een telefoon, laptop, bonusregeling en goede secundaire arbeidsvoorwaarden. Veel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien, zodoende denken we met je mee in een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!\n\nDaarnaast bieden wij jou een carrière bij dé kennisleider in ICT. Inventief in infrastructuren en opinieleider in leidende standaarden gaan bij ons namelijk hand in hand. Je werkt samen met ervaren en professionele collega's op uitdagende opdrachten. Je vindt aansluiting bij collega's rondom jouw expertise waarbij je volop de ruimte en verantwoordelijkheid krijgt voor persoonlijke ontwikkeling en kennisdeling.\n\n[h3]Over KPN Consulting[/h3]\nKPN Consulting is de nummer 1 in ICT-consultancy! Wij begeleiden de top van het Nederlandse bedrijfsleven bij het implementeren van vooruitstrevende informatietechnologie. Onze expertise is gebaseerd op ervaring en we ontdoen ICT van hypes en vaagheden. We maken technologie toepasbaar. Dit levert inventieve en waardevolle toepassingen en ervaringen op. \n\nMensen en organisaties vinden steeds nieuwe mogelijkheden om met elkaar in contact te komen, samen te werken, plezier te hebben, van elkaar te leren en zaken te doen. Het is ons vak en onze passie om hiervoor de optimale infrastructuur te bieden. We analyseren de impact van veranderingen op de business, organisatie, mensen, systemen en middelen. Onze professionals zijn herkenbaar door ervaringskennis en passie voor ICT en de menselijke maat.\n\nWe zijn momenteel ruim 1100 man groot. We zien onze mensen als meer dan werknemer en investeren daar ook in. KPN Consulting stelt zich tot doel de meest aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Werken" geïmplementeerd, dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance. Daarnaast organiseren we ook dingen als de donderdagmiddagborrel, een nieuwjaarsfeest en het jaarlijkse strandfeest, gewoon omdat we dat leuk vinden.\n\n[h3]Interesse?[/h3]\nHerken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Pamela van Winterswijk, pamela.vanwinterswijk@kpn.com   06-12872367 of Mandy Klemann, Mandy.Klemann@kpn.com 06-13444246.\n\nScreening is onderdeel van het sollicitatieproces van KPN Consulting. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv.\n		
79	0	Capgemini	[h1]Capgemini[/h1]\n[h2]Welkom bij Capgemini[/h2]\nCapgemini is marktleider op het gebied van technology, outsourcing en consultancy. Onze overtuiging is dat duurzame resultaten alleen haalbaar zijn door intensieve en innovatieve vormen van  samenwerking met toonaangevende klanten, business partners en collega's. Werken bij Capgemini staat dan ook voor samenwerken in teams van ondernemende professionals, die resultaatgerichtheid combineren met onconventioneel denken. Een inspirerende omgeving met uitdagende opdrachten/projecten waarin je gewoon jezelf kunt zijn en waarin je jouw talenten optimaal kunt inzetten en ontwikkelen.\n\n[h2]Loopbaanontwikkeling[/h2]\nBij Capgemini kun je je vanuit verschillende startposities ontwikkelen. In eerste instantie leg je een stevige basis voor je toekomstige ontwikkeling. In een later stadium volgt een verdergaande specialisatie of een bredere ontwikkeling op het raakvlak van bedrijfsprocessen en ICT. Op basis van een persoonlijk ontwikkelplan volg je opleidingen en verricht je opdrachten die aansluiten op je ambities en de klantvragen. Je leert veel over het marktsegment waarin je werkt, over ICT- toepassingen, bedrijfsonderdelen en consultancy. In overleg met je manager of coach stippel je een opleidingstraject uit: de basis om een waardevolle bijdrage te leveren aan de opdracht bij je klant.\n\n[h2]Profiel Student[/h2]\nCapgemini biedt jong talent met een hbo of universitaire opleiding in de richting van ICT, techniek of bedrijfskunde een uitdagende omgeving en een aantrekkelijk toekomstperspectief. Kun jij samen met collega's en klanten ambities omzetten in resultaten? Heb jij de drive om de beste te worden door jezelf te blijven ontwikkelen? Iedereen is uniek en heeft eigen ambities. Jij kent jezelf het beste en weet waartoe je in staat bent Kom jij het verschil maken bij Capgemini?\n\n[h2]Afstuderen bij Capgemini[/h2]\nBen je nog niet helemaal klaar met je studie omdat je eerst nog moet afstuderen? Ook dan biedt Capgemini je een wereld aan mogelijkheden. Naast bestaande opdrachten die Capgemini formuleert kun je ook zelf een onderwerp voor een afstudeeropdracht aandragen. Om je afstudeeropdracht goed uit te voeren krijg je een ervaren consultant toegewezen die jou begeleidt en ondersteunt op alle vlakken. Ben jij op zoek naar een interessante plek om af te studeren? Kijk dan [url=http://www.nl.capgemini.com/werkenbij/student/afstudeerprojecten/]hier[/url] naar de  verschillende mogelijkheden die Capgemini biedt.\n\n[h2]Beleef Capgemini: XperienceDay[/h2]\nWil je eerst de sfeer proeven binnen ons bedrijf? Meld je dan aan voor een XperienceDay. Door deze dag krijg je de mogelijkheid zelf te beleven hoe het er bij ons aan toegaat: wat voor mensen werken hier, hoe is onze sfeer en waardoor kenmerkt onze cultuur zich, maar ook wat voor soort werk we doen als consultant bij Capgemini. De XperienceDay is een uitermate geschikte dag om dit te ervaren. Geïnteresseerd? Kijk  [url=http://www.nl.capgemini.com/werkenbij/evenementen/xperiencedays/]hier[/url] wanneer de eerste mogelijkheid is om deze dag te ervaren.\n\n[h2]Interesse?[/h2]\nWil je werken op het snijvlak van business en ICT en daarmee een brug slaan tussen 'klant en techniek'? Of heb je een bedrijfskundig achtergrond en ben je een specialist in jouw vakgebied? Of  zoek je een leuke afstudeeropdracht, klik dan [url=http://www.nl.capgemini.com/werkenbij/vacatures/?tab=1&page=1&subform=subform&q=junior/]hier[/url] voor de mogelijkheden die Capgemini heeft.		
85	0	Oprichting Cover	[h1]Oprichting Cover[/h1]\n20-09-1993: Cover werd opgericht.	[h1]Founding of Cover[/h1]\n20-09-1993: Cover was founded.	\N
86	0	Vacature KPN Consulting	[h1]KPN Consulting[/h1]\n[h2]Young Professional Technology[/h2]\n[h3]Wat ga je doen als Young Professional Technology?[/h3]\nHet Young Professionaltraject van KPN Consulting duurt een jaar en is een goede mix van trainingen, cursussen en praktijkervaring bij de klant. Gedurende het traject maak je op een leuke en prettige manier kennis met het 'werkende' leven. Als Young Professional start je met een uitgebreid en geheel verzorgd introductietraject van drie weken, waarna je direct inzetbaar bent. Tijdens dit traject krijg je verschillende vakinhoudelijke, business- en soft skills trainingen en workshops. In deze korte en intensieve periode leer je onze organisatie en je collega's goed kennen.\n\nAlle kennis die je tijdens de introductie hebt opgedaan, kun je direct toepassen bij onze klanten in technische ICT-projecten. KPN Consulting werkt samen met de 400 grootste bedrijven van Nederland zoals Shell, Nuon, Rijkswaterstaat, NS en ING. Bovendien stippel je samen met een persoonlijke coach een carrièrepad uit aan de hand van jouw kennis, ervaring en ambitie. Gedurende het jaar volg je naast je projecten nog enkele trainingen. Ter afsluiting van het traject voer je samen met je mede Young Professionalcollega's een praktijkgerichte case op het gebied van techniek uit, die je daarna presenteert aan onze directie.\n\nNa het eerste jaar ontwikkel je je richting techniek of architectuur. Je kunt daarbij een vliegende start maken door als High Potential door te stromen naar een vervolgopleidingstraject, waar je in korte tijd de kennis, kunde en ervaring opdoet voor de rest van je loopbaan. Dit geeft je de mogelijkheid om uit te groeien tot een gewaardeerde Technisch Consultant die organisaties met concrete adviezen naar een hoger niveau tilt.\n\n[h3]Wat heb jij ons te bieden als Young Professional Technology?[/h3]\n- Afgeronde HBO- of masteropleiding in ICT, Techniek of een andere Bèta studierichting;\n- Aantoonbare affiniteit met en kennis van ICT;\n- Goede communicatievaardigheden;\n- Helikopterview, proactief, resultaatgericht en pragmatisch;\n- Goede beheersing van de Nederlandse taal;\n- Je staat voor kwaliteit en je doet net iets meer dan afgesproken.\n\n[h3]Wat bieden wij jou?[/h3]\nWij bieden jou een carrière bij de nummer 1 in ICT-consultancy. Daarbij horen natuurlijk dingen als een marktconform salaris, een leaseauto, een telefoon, laptop, bonusregeling en goede secundaire arbeidsvoorwaarden. Veel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien, zodoende denken we met je mee in een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!\n\nDaarnaast bieden wij jou een carrière bij dé kennisleider in ICT. Inventief in infrastructuren en opinieleider in leidende standaarden gaan bij ons namelijk hand in hand. Je werkt samen met ervaren en professionele collega's op uitdagende opdrachten. Je vindt aansluiting bij collega's rondom jouw expertise waarbij je volop de ruimte en verantwoordelijkheid krijgt voor persoonlijke ontwikkeling en kennisdeling.\n\n\n[h3]Over KPN Consulting[/h3]\nKPN Consulting is de nummer 1 in ICT-consultancy! Wij begeleiden de top van het Nederlandse bedrijfsleven bij het implementeren van vooruitstrevende informatietechnologie. Onze expertise is gebaseerd op ervaring en we ontdoen ICT van hypes en vaagheden. We maken technologie toepasbaar. Dit levert inventieve en waardevolle toepassingen en ervaringen op. \n\nMensen en organisaties vinden steeds nieuwe mogelijkheden om met elkaar in contact te komen, samen te werken, plezier te hebben, van elkaar te leren en zaken te doen. Het is ons vak en onze passie om hiervoor de optimale infrastructuur te bieden. We analyseren de impact van veranderingen op de business, organisatie, mensen, systemen en middelen. Onze professionals zijn herkenbaar door ervaringskennis en passie voor ICT en de menselijke maat.\n\nWe zijn momenteel ruim 1100 man groot. We zien onze mensen als meer dan werknemer en investeren daar ook in. KPN Consulting stelt zich tot doel de meest aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Werken" geïmplementeerd, dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance. Daarnaast organiseren we ook dingen als de donderdagmiddagborrel, een nieuwjaarsfeest en het jaarlijkse strandfeest, gewoon omdat we dat leuk vinden.\n\n[h3]Interesse?[/h3]\nHerken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Pamela van Winterswijk, pamela.vanwinterswijk@kpn.com   06-12872367 of Mandy Klemann, Mandy.Klemann@kpn.com 06-13444246.\n\nScreening is onderdeel van het sollicitatieproces van KPN Consulting. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv.\n	\N	\N
87	0	Vacature KPN Consulting	[h1]KPN Consulting[/h1]\n[h2]Het Management Traineeship bij KPN IT Solutions[/h2]\nJe bent een assertieve starter met je Master op zak. Je staat te trappelen om het bedrijfsleven te overtuigen van je talenten en bent gedreven om bij de absolute top te horen. Ontwikkelingen in de IT dienstverlening vind je boeiende materie. Daarom werk jij straks als Management Trainee bij KPN IT Solutions. Vandaag high potential? Binnen de kortste tijd groei jij uit tot een beslisser binnen onze organisatie!\n\n[h3]Jij in de rol van Management Trainee[/h3]\nJe maakt deel uit van een select team dat onder begeleiding van het hoger management wordt voorgesorteerd voor een sturende rol binnen KPN IT Solutions. Je ontdekt in 18 maanden de veelzijdigheid van onze IT dienstverlening, maar vooral ook je eigen talenten en toegevoegde waarde. Je vervult opdrachten binnen verschillende disciplines van onze organisatie. Doordat je een stevig intern netwerk opbouwt, pak je de kans om zelf leidend te zijn in de invulling van je traineeship.\n\nJe start met een uitgebreid introductietraject van een maand met meerdere cursussen als ITIL, PRINCE2 en Presenteren. Na een maand vervul je binnen KPN IT Solutions achtereenvolgens 3 opdrachten van ongeveer 6 maanden. Uiteraard aangevuld met meer trainingen (zoals verandermanagement) en intervisie-sessies. Je krijgt te maken met (inter)nationale klanten en partners van KPN, zoals ING, NS, Rabobank, IBM, diverse Ministeries, Achmea en Microsoft.\n\n[h3]Jouw kwaliteiten[/h3]\nJe bent een topperformer met ambitie! In dit maatwerk traineeship pak jij elke kans die je krijgt om het beste uit jezelf te halen. Door je overtuigingskracht krijg je mensen met je mee. Projecten blijven bij jou niet bij vage plannen. Door je resultaatgerichte instelling innoveer, ontwikkel en implementeer je jouw opdrachten met als doel het best mogelijke resultaat. Je hebt:\n\n- het VWO afgerond en beschikt over een universitair Masterdiploma op het gebied van IT, Bedrijfskunde of Techniek\n- aantoonbare affiniteit met IT\n- een bovengemiddeld vermogen van systematisch denken\n- behoefte aan afwisseling en een snelle ontwikkeling\n\n[h3]Wat bieden wij jou?[/h3]\nKansen! Jij krijgt de kans om je maximaal te ontwikkelen en snel verder te groeien. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen! Je manager denkt met je mee bij het opstellen en naleven van een persoonlijk ontwikkelplan. Ook je inhoudelijke begeleiding is in goede handen. Je krijgt bij elke opdracht, een inhoudelijk begeleider aangewezen. Je werkt nauw samen met je collega-trainees. Je daagt elkaar uit en kunt bij elkaar terecht. Natuurlijk zijn een marktconform salaris, een leaseauto, laptop, telefoon, bonusregeling en goede secundaire arbeidsvoorwaarden bij de functie van Management Trainee inbegrepen.\n\nNB. In het First Employers 2013 onderzoek van Memory Magazine is KPN uitgeroepen tot 1 van de 5 beste werkgevers in de IT/Telecom branche om je loopbaan te starten.\n\n[h3]Over KPN IT Solutions[/h3]\nKPN IT Solutions is marktleider in het ontwerpen, implementeren en beheren van vooruitstrevende IT infrastructuur diensten. We zorgen dat onze klanten altijd en overal op een veilige manier over hun bedrijfsinformatie kunnen beschikken. Daarom heeft ons werk vaak een grote maatschappelijke impact.\n\nWe zien onze mensen als meer dan werknemer en investeren daar ook in. KPN IT Solutions stelt zich tot doel een aantrekkelijke werkgever te zijn voor gedreven professionals. Daarbij heeft KPN "Het Nieuwe Leven en Werken" geïmplementeerd. Dit biedt mogelijkheden om plaats- en tijdonafhankelijk te werken ten behoeve van een goede work/life balance.\n\n[h3]Meer informatie of solliciteren?[/h3]\nBen jij de management trainee die wij zoeken? Upload dan direct jouw motivatie en cv op deze pagina. Heb je vragen over de sollicitatieprocedure of het traineeship, dan kun je contact opnemen met corporate recruiter Jotte Tromp via jotte.tromp@kpn.com of Yvonne Pribnow via yvonne.pribnow@kpn.com\n	\N	\N
88	0	Vacature KPN Consulting	[h1]KPN Consulting[/h1]\n[h2]Trainee Techniek[/h2]\n[h3]Wat ga je doen?[/h3]\nBinnen KPN draait IT, naast bits, bytes en netwerken, ook om aansturen en regisseren. Daarvoor hebben we professionals nodig die snappen dat IT een middel is en beslist geen doel. Professionals die begrijpen hoe je wensen vanuit de klant kunt vertalen naar IT oplossingen. Die ervan houden om initiatief te nemen en te werken aan pittige projecten.\n\nAls "Trainee Techniek" maak je deel uit van een select team dat, met begeleiding vanuit het hoger management, kennismaakt met de verschillende facetten van onze vooruitstrevende IT dienstverlening in een dynamische business-to-business markt. Je krijgt te maken met (inter)nationale klanten en partners van KPN, zoals ING, NS, Rabobank, IBM, diverse Ministeries, Achmea en Microsoft. Je krijgt de kans om concreet en inhoudelijk bij te dragen aan onze doelstelling om het beste IT servicebedrijf van Nederland te worden.\n\nJe start met een uitgebreid introductietraject met meerdere cursussen op het gebied van kennis en soft skills (o.a. ITIL, PRINCE2, Windows 7 en Klantgericht Communiceren). In de loop van je Traineeship volg je, steeds samen met jouw mede-Trainees, vervolgens nog enkele specialistische trainingen op technisch gebied en zal je regelmatig deelnemen aan intervisie-sessies.\n\nIn een periode van één jaar werk je aan verschillende opdrachten, waarbij je het laatste half jaar actief zult zijn in het team waarbinnen je je ook ná het Traineeship verder zult bekwamen. Bij het uitvoeren van de opdrachten, zal je altijd zowel je technische kennis als je commerciële en communicatieve vaardigheden in moeten zetten. Op die manier lever jij je concrete bijdrage aan ons vakgebied: het op efficiënte wijze innoveren, ontwikkelen, bouwen, implementeren en beheren van IT services en onderliggende infrastructuren voor onze klanten.\n\n[h3]Wat heb jij ons te bieden als Trainee Techniek? [/h3]\n- Je beschikt over een HBO- of WO-diploma op het gebied van Informatica, Technische Bedrijfskunde of Bedrijfskundige Informatica.\n- Je bent in staat om klantprocessen te begrijpen en te vertalen naar technische oplossingen.\n- Doordat je geïnteresseerd bent in je vakgebied houd je jezelf continu op de hoogte van de ontwikkelingen in de ICT.\n- Je wilt hard werken aan het vinden van een optimale aansluiting van onze organisatie en ons portfolio op de wensen van de klant.\n- Je wilt uitgroeien tot een top-performer in een topfunctie binnen onze organisatie.\n- Je kunt goed samenwerken, zoekt anderen op en bent communicatief vaardig.\n- Je kunt goed analyseren en gestructureerd denken en bewaart het overzicht.\n\n\n[h3]Wat bieden wij?[/h3]\nNatuurlijk zijn een marktconform salaris, een leaseauto, laptop, telefoon, bonusregeling en goede secundaire arbeidsvoorwaarden bij de functie van Trainee Techniek inbegrepen.\nVeel belangrijker vinden wij het echter om jou de kans te bieden je te ontwikkelen en verder te groeien. Zodoende denken we met je mee met het opstellen en naleven van een persoonlijk ontwikkelplan en heb je bij ons goede doorgroei- en opleidingsmogelijkheden. Sterker nog, we verwachten van jou dat jij je blijft ontwikkelen!\n\n[h3]Interesse?[/h3]\nHerken jij je direct in bovenstaand profiel? Reageer dan snel en stuur jouw sollicitatie met CV. Voor meer informatie kun je contact opnemen met Jotte Tromp via jotte.tromp@kpn.com\nScreening is onderdeel van het sollicitatieproces van KPN Corporate Market. Meer informatie hierover kun je vinden op: http://bit.ly/qtMFUv\n	\N	\N
104	0	Nedap	[h1] Nedap [/h1]\r\nNedap is een fabrikant van intelligente technologische oplossingen voor relevante thema’s. Voldoende voedsel voor een groeiende bevolking, schoon drinkwater over de hele wereld, slimme netwerken voor duurzame energie zijn slechts een paar voorbeelden van onderwerpen waar Nedap zich mee bezighoudt.\r\n\r\nBij Nedap werken technici, productontwikkelaars, business developers en marketeers. Allemaal hebben zij hetzelfde doel: markten in beweging brengen met technologie die er toe doet! Het succes van Nedap is gebaseerd op creativiteit, fundamenteel begrip van technologie en elektronica, en een zeer goede samenwerking met onze klanten. Onze ideeën over markt en technologie vertalen wij in producten die over de hele wereld verkocht worden.\r\n\r\n[h2] Werken bij Nedap is snel schakelen [/h2]\r\nNedap heeft een open bedrijfscultuur die creativiteit en ondernemerschap stimuleert. De organisatie bestaat uit 10 marktgroepen met elk z’n eigen specialisme. Elke unit ontwikkelt voortdurend nieuwe ideeën en producten en vermarkt deze zelf. De kracht van Nedap is de interactie tussen de verschillende units. Door met je collega’s ideeën en knowhow uit te wisselen kun je binnen Nedap snel schakelen.\r\n \r\n[h2] Bij Nedap moet je het zelf maken [/h2]\r\nNedap biedt haar medewerkers de kans om te ondernemen in technologie die er toe doet. Door de platte organisatie is het niet de plaats in de hiërarchie die telt maar de kwaliteit van je argumenten. Eigen initiatief, doorzettingsvermogen en persoonlijk ondernemerschap zijn daarbij cruciaal. Persoonlijk ondernemerschap staat binnen Nedap voor het nemen van verantwoordelijkheid en het omzetten van ideeën in actie. \r\n\r\nNedap is altijd op zoek naar net die paar mensen die succesvol kunnen zijn bij ons bedrijf. Daarbij telt niet zozeer wat je de afgelopen jaren allemaal gedaan hebt, maar wat je de komende jaren nog wilt leren. Waar het om gaat is dat je je bij ons continu verder ontwikkelt en nieuwe inzichten verwerft. Dat is belangrijk voor ons en belangrijk voor jezelf. Als jij je daarin herkent, willen we graag met je praten. Voor alle actuele vacatures en een kijkje achter de schermen kijk op: www.lifeatnedap.com. Voor studenten bieden wij uitdagende stage- en afstudeermogelijkheden. Voor meer informatie neem contact op met Inge Meengs (inge.meengs@nedap.com). \r\nDe N.V. Nederlandsche Apparatenfabriek “Nedap” is opgericht in 1929, genoteerd aan de beurs sinds 1947 en is met ruim 680 medewerkers wereldwijd actief.\r\nVoor meer informatie, zie: http://www.nedap.com/nl\r\n	[h1] Nedap [/h1]\nNedap is een fabrikant van intelligente technologische oplossingen voor relevante thema’s. Voldoende voedsel voor een groeiende bevolking, schoon drinkwater over de hele wereld, slimme netwerken voor duurzame energie zijn slechts een paar voorbeelden van onderwerpen waar Nedap zich mee bezighoudt.\n\nBij Nedap werken technici, productontwikkelaars, business developers en marketeers. Allemaal hebben zij hetzelfde doel: markten in beweging brengen met technologie die er toe doet! Het succes van Nedap is gebaseerd op creativiteit, fundamenteel begrip van technologie en elektronica, en een zeer goede samenwerking met onze klanten. Onze ideeën over markt en technologie vertalen wij in producten die over de hele wereld verkocht worden.\n\n[h2] Werken bij Nedap is snel schakelen [/h2]\nNedap heeft een open bedrijfscultuur die creativiteit en ondernemerschap stimuleert. De organisatie bestaat uit 10 marktgroepen met elk z’n eigen specialisme. Elke unit ontwikkelt voortdurend nieuwe ideeën en producten en vermarkt deze zelf. De kracht van Nedap is de interactie tussen de verschillende units. Door met je collega’s ideeën en knowhow uit te wisselen kun je binnen Nedap snel schakelen.\n \n[h2] Bij Nedap moet je het zelf maken [/h2]\nNedap biedt haar medewerkers de kans om te ondernemen in technologie die er toe doet. Door de platte organisatie is het niet de plaats in de hiërarchie die telt maar de kwaliteit van je argumenten. Eigen initiatief, doorzettingsvermogen en persoonlijk ondernemerschap zijn daarbij cruciaal. Persoonlijk ondernemerschap staat binnen Nedap voor het nemen van verantwoordelijkheid en het omzetten van ideeën in actie. \n\nNedap is altijd op zoek naar net die paar mensen die succesvol kunnen zijn bij ons bedrijf. Daarbij telt niet zozeer wat je de afgelopen jaren allemaal gedaan hebt, maar wat je de komende jaren nog wilt leren. Waar het om gaat is dat je je bij ons continu verder ontwikkelt en nieuwe inzichten verwerft. Dat is belangrijk voor ons en belangrijk voor jezelf. Als jij je daarin herkent, willen we graag met je praten. Voor alle actuele vacatures en een kijkje achter de schermen kijk op: www.lifeatnedap.com. Voor studenten bieden wij uitdagende stage- en afstudeermogelijkheden. Voor meer informatie neem contact op met Inge Meengs (inge.meengs@nedap.com). \nDe N.V. Nederlandsche Apparatenfabriek “Nedap” is opgericht in 1929, genoteerd aan de beurs sinds 1947 en is met ruim 680 medewerkers wereldwijd actief.\nVoor meer informatie, zie: http://www.nedap.com/nl	\N
83	0	Bestuur XXII	[samenvatting]2013/2014\n"Enlightenment"[/samenvatting]\n[h1]Bestuur XXII: Enlightenment[/h1]\n____BESTUURSFOTO____\n[small]Foto: Anita Drenthen[/small]\n\n[h2]Leden[/h2]\nVoorzitter: Harmke Alkemade\nSecretaris: webcie van der Linde\nPenningmeester: Martijn Luinstra\nCommissaris Intern: Davey Schilling\nCommissaris Extern: Sybren Römer\n	[samenvatting]2013/2014\n"Enlightenment"[/samenvatting]\n[h1]Board XXII: Enlightenment[/h1]\n____BESTUURSFOTO____\n[small]Photo: Anita Drenthen[/small]\n\n[h2]Members[/h2]\nChairman: Harmke Alkemade\nSecretary: webcie van der Linde\nTreasurer: Martijn Luinstra\nCommissioner of Internal Affairs: Davey Schilling\nCommissioner of External Affairs: Sybren Römer\n	\N
82	0	Bestuur XXI	[samenvatting]2012/2013\n"Plus Plus"[/samenvatting]\n[h1]Bestuur XXI: Plus Plus[/h1]\n____BESTUURSFOTO____\n[small]Foto: Anita Drenthen[/small]\n\n[h2]Leden[/h2]\nVoorzitter: Marten Schutten\nSecretaris: Arnoud van der Meulen\nPenningmeester: Emma van Linge\nCommissaris Intern: Lotte Noteboom\nCommissaris Extern: Jordi van Giezen	\N	\N
84	0	Oudbesturenpagina	[h1]Vorige besturen van Cover[/h1]\nDit is de trotse geschiedenis van onze vereniging.	[h1]Former boards of Cover[/h1]\nThese are our former boards.	\N
80	0	Ortec			
19	1	Commissiepagina SympoCie	[samenvatting]De SympoCee is de commissie die elk jaar een mooi symposium neerzet.[/samenvatting]\r\nDe SympoCee is de commissie die elk jaar een mooi symposium neerzet.\r\n\r\nWil jij graag het volgende symposium organiseren? Praat/mail dan een keer met wat huidige commissieleden of met de intern van het bestuur!  :)\r\n	The SympoCee is the committee that organizes a symposium every year.	\N
89	28	Commissiepagina KISO	\N	\N	\N
92	1	Privacy Policy	[h1]Privacy Policy[/h1]\r\nCover does not sell or share your personal information with anyone outside the association except for your name and student number which we share at moments with the University of Groningen.\r\n\r\nThe website provides you with controls to manage which personal details other members can and cannot see. These preferences are honoured in both the online and the printed almanac.\r\n\r\nThe information you enter will be used to update your personal details in the Cover member administration. After leaving Cover, your profile will be deactivated and your personal details will be made inaccessible and published material will be anonymised on request.\r\n\r\nCover will not share any personal details with parties outside of Cover (except with the University of Groningen as mentioned before.) When a company wants to send a promotional e-mail to you the e-mail will be forwarded by the board of Cover.\r\n\r\nIf you have any questions regarding your privacy rights, please consult the board.	\N	\N
93	30	Commissiepagina Candidate Board	\N	\N	\N
1	1	Commissiepagina WebCie	[samenvatting]De naam AC/DCee staat voor Advanced Computing / Digitalisation Committee. De AC/DCee is verantwoordelijk voor de website van Cover (deze dus!).[/samenvatting]\n\nDe naam AC/DCee staat voor Advanced Computing / Digitalisation Committee. De AC/DCee is verantwoordelijk voor de website van Cover (deze dus!). Mocht je ergens niet uitkomen, werkt je account niet meer, vind je een probleem of heb je andere op of aanmerkingen, neem dan contact op met de AC/DCee. Of fork [url=https://bitbucket.org/cover-webcie/cover-php]de website op Bitbucket[/url] en dien een pull request in ;)\n\n[youtube=3XCw6FIqQTo]	[samenvatting]AC/DCee stands for Advanced Computing / Digitalisation Committee. The AC/DCee is responsible for maintaining Cover's website (the one you're viewing now!)[/samenvatting]\n\nAC/DCee stands for Advanced Computing / Digitalisation Committee. The AC/DCee is responsible for maintaining Cover's website (the one you're viewing now!) Are you stuck with a problem, has your account stopped working, has your keen eye spotted a bug or do you have any feedback or remarks about the website? Don't hesitate to contact the AC/DCee. Or fork [url=https://bitbucket.org/cover-webcie/cover-php]the website on Bitbucket[/url] and submit a pull request ;)\n\n[youtube=3XCw6FIqQTo]\n	
4	4	Commissiepagina AlmanacCee	[samenvatting]Ieder jaar brengt de AlmanacCee een almanak uit. Deze wordt uitgereikt op de jaarlijkse Almanakborrel. [/samenvatting]\r\n\r\nOok dit jaar zal er een almanak uitgebracht worden. De totstandkoming van dit boekwerk ligt in handen van de AlmanacCee. De verwachting is dat de almanak tegen het einde van het academisch jaar klaar zal zijn. Het thema is nog een verrassing.\r\n\r\nMocht je nog een oude almanak willen hebben, er zijn nog een aantal exemplaren van de laatste paar jaar te vinden in de Coverkamer! 	[samenvatting]Every year the Yearbookcee publishes a yearbook. It will be handed out at the annual YearbookCee borrel[/samenvatting]\n\nOok dit jaar zal er een almanak uitgebracht worden. De totstandkoming van dit boekwerk ligt in handen van de YearbookCee. De verwachting is dat de almanak tegen het einde van het academisch jaar klaar zal zijn. Het thema is nog een verrassing.\n\nMocht je nog een oude almanak willen hebben, er zijn nog een aantal exemplaren van de laatste paar jaar te vinden in de Coverkamer!	\N
81	27	Commissiepagina HEROcee	[samenvatting]De commissie die de veiligheid van de leden probeert te garanderen.[/samenvatting]\n\nDit is de veiligste commissie die Cover heeft. De leden van deze commissie kunnen gevraagd worden om als BHV'er te fungeren op verschillende activiteiten. Als er verder rondom de Coverkamer iets gebeurt waar je ons voor nodig hebt kun je natuurlijk ook altijd bij ons terecht.\n	[samenvatting]This committee tries to guarantee the safety of the members.[/samenvatting]\n\nThis is Cover's safest committee. The members of this committee can be asked to perform as an ERO at several activities. If something happens around the Cover Room we can of course also be of help.\n	\N
106	0	Ordina	[h1]Ordina[/h1]\n\n[h2]ICT. MAAR DAN VOOR MENSEN.[/h2] \nWij zijn de bedenkers, bouwers en beheerders van een betere digitale wereld. [url=http://www.ordina.nl/] Ordina [/url], opgericht in 1973, is de grootste onafhankelijke dienstverlener op het gebied van consulting, solutions en ICT in de Benelux. Wij streven naar ICT die mensen écht verder helpt. ICT die er toe doet en die tot stand is gekomen zonder verspilling van middelen. Dat doen wij door samen met onze klanten duurzaam te innoveren. We richten ons op de financiële sector, overheid, zorg en een aantal specifieke segmenten binnen de industriemarkt.\n\n[h2]Samen ontwikkelen bij Ordina als Young Professional[/h2]\nAls je klaar bent met studeren kan je aan de slag als Young Professional. Binnen Ordina hebben wij verschillende expertises, zoals Java, Oracle, Testing, SAP en Consultancy. Samen met jou gaan wij kijken wat je ambities zijn, wat je leuk vindt en waar mogelijkheden zijn binnen Ordina. \n\nWil jij je carrière in de IT écht goed starten? Bij Ordina kun jij je als ambitieuze Young Professional supersnel ontwikkelen. In ons tweejarig ontwikkelprogramma krijg je - vanzelfsprekend - een grote hoeveelheid trainingen aangeboden en ga je vooral ook praktisch aan de slag. Je maakt direct kennis met onze klanten en gaat zo snel mogelijk aan de slag op een mooi project. Ontwikkel jij met ons mee? \n\nWerken bij Ordina als Young Professional betekent voor jou\n•\t‘het beste van twee werelden’: de doorgroeimogelijkheden en mooie klanten van een grote organisatie, de betrokkenheid en samenwerking van het werken in kleine teams\n•\tde ruimte om mee te bouwen aan zichtbare projecten in onze digitale wereld\n•\teen informele en open cultuur\n•\teen arbeidsovereenkomst voor onbepaalde tijd\n•\tuitgebreide trainingsmogelijkheden aan de Ordina Academy\n•\teen goed salaris, afgestemd op je rol, ervaring en prestaties\n•\tflexibel arbeidsvoorwaardenpakket\n•\talle faciliteiten om prettig te kunnen werken, zoals een laptop, mobiel en leaseauto\n•\tmaandelijkse borrels in het Ordina café \n\nWil je meer weten over deze mogelijkheden, vacatures en wat de functies inhouden? Kijk dan op onze [url=http://www.werkenbijordina.nl/nl-nl/young-professionals/] website[/url]. \n\n[h2]Selectieproces[/h2]\nNadat je hebt gesolliciteerd naar een carrière of stage bij Ordina, bekijkt een van onze recruiters je profiel. Sluit jouw profiel goed aan, dan nemen we contact met je op om de mogelijkheden te bespreken en plannen we graag een eerste gesprek met je in bij een specifiek onderdeel van Ordina. Voordat je op gesprek komt, vragen we je wel eerst om ons online assessment succesvol af te ronden. Deze test mag je thuis in alle rust maken en wanneer het jou uitkomt (en opnieuw proberen als het toch even niet zo lekker ging). Zijn zowel de test als het gesprek daarna positief? Dan gaan we alles in orde maken om jou hier snel te laten starten!\n\n[h2]Stage mogelijkheden[/h2]\nBen je op zoek naar een stage tijdens je studie? Dat kan natuurlijk ook! Via onze website, werkenbijordina.nl, kan je kijken naar de beschikbare stages, maar als je zelf een gaaf idee hebt en dit wilt realiseren bij Ordina, dan staan wij hier altijd voor open. Wij gaan samen met jou op zoek naar de coolste stage.\nHeb je een voorstel of vraag over onze stagemogelijkheden? Neem dan contact op met onze Campus Recruiter, Lotte van den Berg en stuur een e-mail naar lotte.van.den.berg@ordina.nl\n	\N	\N
107	0	Rijksoverheid	[h1]Rijksoverheid[/h1]\r\n\r\n[h2]Geef je carrière een kickstart bij de Rijksoverheid[/h2]\r\n\r\nDe uitdagingen voor Nederland zijn groot. De Rijksoverheid traint de beste ICT-ers, technici, financials en andere starters om deze uitdagingen aan te gaan, en biedt daarom verschillende mogelijkheden voor het volgen van traineeships en stages.\r\n\r\n[h2]Keuze uit verschillende traineeships[/h2]\r\n\r\nEen traineeship is de ideale start van je carrière. Je werkt op verschillende plekken bij de Rijksoverheid in de dynamische en inhoudelijke wereld van een ministerie of uitvoeringsorganisatie. Zo combineer je interessant werk met een opleiding op hoog niveau, samen met andere enthousiaste hoogopgeleiden.\r\n\r\n[h2]Veel stage- en afstudeermogelijkheden[/h2]\r\n\r\nBen je nog met je studie bezig en zoek je een veelzijdig stage- of afstudeerplaats midden in de samenleving? Dan ben je bij de Rijksoverheid aan het goede adres. Wij hebben verschillende mogelijkheden in allerlei studierichtingen en in het hele land. Je kunt bij ons terecht voor kanten-en-klare stages. Maar je kunt ook zelf het heft in handen nemen en ons je ideeën voorleggen.\r\n\r\nKijk voor meer informatie op [url=werkenvoornederland.nl/starters]onze site[/url].	\N	\N
91	29	Commissiepagina PiraCie	Ahoy, piraat!	\N	\N
108	0	Ziuz	[h1]Ziuz[/h1]\n\nZiuZ is a leading innovative company in the development and application of visual intelligence in high-grade technology. We are a commercial company with a socially responsible attitude. We want our products to contribute to solving issues relevant to society, so we create practical, user-friendly solutions for police and security services, and for the medical world. \n\nIn order to come up with the right solution, we need to be fully familiar with our clients’ basic work processes. Only then can we make products that will really help our clients. But this demands the right collaboration and a high level of trust. \n\nOur employees are carefully screened, and handling confidential information is second nature to them. We aim to maintain sustainable relationships, not only with our clients but also with our employees. We feel that our focus should be on the long term, not on a passing whim. In addition to being a supplier, we’re also a reliable partner for clients to approach for additional services, training and advice.\n\nWant to learn more about Ziuz? Go to there [url=http://www.ziuz.com/nl] website [/url]\nWould you like to work at Ziuz? Go to our [url=https://www.svcover.nl/show.php?id=54] vacancies [/url] page!	[h1]Ziuz[/h1]\n\nZiuZ is a leading innovative company in the development and application of visual intelligence in high-grade technology. We are a commercial company with a socially responsible attitude. We want our products to contribute to solving issues relevant to society, so we create practical, user-friendly solutions for police and security services, and for the medical world. \n\nIn order to come up with the right solution, we need to be fully familiar with our clients’ basic work processes. Only then can we make products that will really help our clients. But this demands the right collaboration and a high level of trust. \n\nOur employees are carefully screened, and handling confidential information is second nature to them. We aim to maintain sustainable relationships, not only with our clients but also with our employees. We feel that our focus should be on the long term, not on a passing whim. In addition to being a supplier, we’re also a reliable partner for clients to approach for additional services, training and advice.\n\nWant to learn more about Ziuz? Go to there [url=http://www.ziuz.com/nl] website [/url]\nWould you like to work at Ziuz? Go to our [url=https://www.svcover.nl/show.php?id=54] vacancies [/url] page!	\N
109	0	Voorwaarden aanmelden	De ondergetekende meldt zich hierbij aan als lid van Cover, de studievereniging voor Kunstmatige Intelligentie en Informatica.\r\n\r\nDe ondergetekende verklaart hierbij gedurende zijn/haar lidmaatschap akkoord te gaan met de volgende eisen.\r\n\r\nDe ondergetekende verplicht zich zijn/haar gegevens correct in te vullen en wijzigingen door te geven.\r\n\r\nTevens machtigt de ondergetekende Cover jaarlijks de contributie van zijn of haar bankrekening af te schrijven. Indien gewenst zullen boekenverkopen en overige activiteiten via automatische incasso's geschieden.\r\n\r\nBij een onterechte overschrijving heeft de ondergetekende 30 dagen de tijd om deze overschrijving ongedaan te maken.	The undersigned hereby applies for a membership of Cover, the study association for Artificial Intelligence and Computing Science.\n\nThe undersigned hereby agrees to comply with the following requirements during his/her membership.\n\nThe undersigned is required to enter his/her personal details correctly and to keep them up to date.\n\nThe undersigned authorises Cover to charge the annual membership fee from his/her bank account. If desired, book sales and other activities will be payed by direct debit.\n\nIn the case of a wrongful transfer, the undersigned has 30 days to undo the transfer.	\N
116	0	Board XXIII	[samenvatting]2014/2015\n"Harmony"[/samenvatting]\n[h1]Bestuur XXIII: Harmony[/h1]\n[small]Foto: Martijn Luinstra, Annet Onnes[/small]\n\n[h2]Leden[/h2]\nVoorzitter: Robin Hermes\nSecretaris: Liese Puck Schmidt\nPenningmeester: Guus Klinkenberg\nCommissaris Intern: Tim Haarman\nCommissaris Extern: Merel Wiersma\n	[samenvatting]2014/2015\n"Harmony"[/samenvatting]\n[h1]Board XXIII: Harmony[/h1]\n[small]Photo: Martijn Luinstra, Annet Onnes[/small]\n\n[h2]Members[/h2]\nChairman: Robin Hermes\nSecretary: Liese Puck Schmidt\nTreasurer: Guus Klinkenberg\nCommissioner of Internal Affairs: Tim Haarman\nCommissioner of External Affairs: Merel Wiersma\n	\N
96	0	TNO Traineeship	[h1]TNO Traineeship [/h1]\n\n[h2]JOB DESCRIPTION[/h2]\nEager for innovation? Become a TNO Trainee!\n\nTo become a trainee at our company you have actively developed yourself during your studies on both a personal and academical level. We aim for a multidisciplinary trainee team and are therefore looking for recent university graduates with a master’s degree or PhD in one of the fields mentioned below. We are looking for highly motivated and enterprising trainees who want to invest in their personal development and career.\n\nAt our company you are the director of your own career and you will continue to develop rapidly. During your traineeship, we will challenge you to push your boundaries by making career moves outside your comfort zone, by doing courses and workshops, and by peer reviews and coaching. In two years’ time, you will enhance your expertise and competencies extensively and will gain a broad understanding of our organisation.\n\nDuring the two-year trainee programme you will work three continuous periods of 8 months in various departments and at several TNO locations. You will take on different roles and develop yourself rapidly as projectmanager, business developer, business consultant or system engineer.\n\nWorking at TNO is all about innovation and collaboration. Connecting people and knowledge is key. TNO contributes to society by using applied scientific knowledge to create solutions with impact. As a trainee you will be part of multidisciplinary project teams and work on challenging assignments for industry and governments. With your unique expertise and experience you will contribute to finding solutions for highly relevant societal issues at national and international level.\n\nWould you like to find out more about our trainees and the projects they work at? Visit our traineeblog: weblog.tno.nl/traineeship. \n\nTNO is an independent research organisation whose expertise and research make an important contribution to the competitiveness of companies and organisations, to the economy and to the quality of society as a whole. Innovation with purpose is what TNO stands for. We develop knowledge not for its own sake but for practical application. To create new products that make life more pleasant and valuable and help companies innovate. To find creative answers to the questions posed by society. We work for a variety of customers: governments, the SME sector, large companies, service providers and non-governmental organisations. Working together on new knowledge, better products and clear recommendations for policy and processes. In everything we do, impact is the key. Our product and process innovations and recommendations are only worth something if our customers can use them to boost their competitiveness. TNO concentrates on several closely related themes, each of which has a prominent place in the national and European innovation agenda. These like, Healthy living, Energy, Defence, Safety and Security, Industry and Urbanisation.\n\nWould you like to find out more about these and other themes? Then visit www.tno.nl.\n \n[h2]REQUIREMENTS[/h2]\nYou are a recent university graduate with a Master’s degree or a PhD in one of the following special fields:\n- Mathematics;\n- Econometrics;\n- Applied physics;\n- Aerospace engineering;\n- Mechanical engineering;\n- Material science;\n- Civil engineering;\n- Geoscience;\n- Chemistry;\n- Health Sciences / -economics / -psychology.\n\nYour CV stands out and you have less than two years of work experience. You are ambitious, socially driven and want to enhance your potential in a renowned research organisation such as TNO. You are, by nature, enterprising, flexible and a good team player, you have good communications skills and are creative and innovative.\n\nInterested?\nSubmit your application, upload your CV, your motivation letter in Word and your list of marks via this site. Your application may be in Dutch or in English.\nApplications must be submitted by October 26, 2014.\nImportant: Your application cannot be processed  if one of the documents is missing or if you submit your application after October 26th.\n\nSelection procedure:\nIf you pass the pre-selection on the basis of your CV, letter and motivation, you will be invited to attend the TNO Trainee Selection Day, which will take place on November 28, 2014 in Delft. We will notify you about this by November 14th at the latest.\n\nDuring the Selection Day, you will learn more about TNO and our Traineeship and we will get to know you better. Please note in advance that English will be the chosen language during this day.\n\nIf you are among the best applicants at this selection day, we will be pleased to invite you to an assessment at a psychological consultancy. The outcome of which will be used for our final selection interview.\n\nSelected candidates will receive a contract proposal for the start date of February 1st 2015.\n\nWhat if this timeline doesn't fit your personal schedule? Keep an eye on our website workingattno.nl for other challenging starter jobs at TNO or the next trainee selection round in 2015!\n\n[h2]ABOUT TNO[/h2]\nTNO is an independent research organisation whose expertise and research make an important contribution to the competitiveness of companies and organisations, to the economy and to the quality of society as a whole. Innovation with purpose is what TNO stands for. With 3500 people we develop knowledge not for its own sake but for practical application. To create new products that make life more pleasant and valuable and help companies innovate. To find creative answers to the questions posed by society. We work for a variety of customers: governments, the SME sector, large companies, service providers and non-governmental organisations. Working together on new knowledge, better products and clear recommendations for policy and processes. In everything we do, impact is the key. Our product and process innovations and recommendations are only worth something if our customers can use them to boost their competitiveness.\n\n[h2]TERMS OF EMPLOYMENT[/h2]\nEveryone has his or her own notion of what pleasurable work is. TNO wants to help you find your professional and personal balance. You want to work on your career and have an enjoyable social life at the same time. This is why we offer, for instance, à-la-carte terms and conditions of employment that enable you to swap various employment conditions on an annual basis. Flexible working times and various leave schemes also give you plenty of choice to create the package that best suits your situation. Of course, your pay is also good and the secondary conditions are excellent. TNO really is the innovation and knowledge organisation par excellence. In other words, we attach a great deal of value to your personal and professional development. There are many opportunities for you to push on with your own development: conferences, education, workshops, coaching, intervision, mentoring and job switching. You have a big say in the speed and direction of your own development.\n\n[h2]CONTACT US[/h2]\nRecruitment contact: Marja Meijers, phone number: +31 (0)88 86 68 188.	\N	\N
28	11	Zusterverenigingen	[h1]Zusterverenigingen[/h1]\r\n[table noborder]\r\n||[url=http://www.uscki.nl][/url]||[h3][url=http://www.uscki.nl]Uscki incognito[/url] ([url=mailto:incognito@uscki.nl]e-mail[/url])[/h3]\r\n\r\nCognitieve Kunstmatige Intelligentie\r\nUniversiteit Utrecht||\r\n||[url=http://svcognac.nl/][/url]||[h3][url=http://svcognac.nl/]CognAC[/url] ([url=mailto:secretaris@svcognac.nl]e-mail[/url])[/h3]\r\n\r\nKunstmatige Intelligentie\r\nKatholieke Universiteit Nijmegen||\r\n||[url=http://storm.vu/en/][/url]||[h3][url=http://storm.vu/en/]Storm[/url] ([url=mailto:bestuur@storm.vu]e-mail[/url])[/h3]\r\n\r\nWiskunde & Informatica\r\nVrije Universiteit Amsterdam||\r\n||[url=http://www.svia.nl][/url]||[h3][url=http://www.svia.nl]via[/url] ([url=mailto:via@science.uva.nl]e-mail[/url])[/h3]\r\n\r\nInformatiewetenschappen\r\nUniversiteit van Amsterdam||\r\n||[url=http://msvincognito.nl][/url]||[h3][url=http://msvincognito.nl]MSV Incognito[/url] ([url=mailto:incognito@maastrichtuniversity.nl]e-mail[/url])[/h3]\r\n\r\nKennistechnologie\r\nUniversiteit Maastricht||\r\n\r\n||[url=http://www.deleidscheflesch.nl/][/url]||[h3][url=http://www.deleidscheflesch.nl/]De Leidsche Flesch[/url] ([url=mailto:bestuur@deleidscheflesch.nl]e-mail[/url])[/h3]\r\n\r\nNatuurkunde, Sterrenkunde, Wiskunde en Informatica\r\nUniversiteit Leiden||\r\n\r\n||[url=http://ch.twi.tudelft.nl/][/url]||[h3][url=http://ch.twi.tudelft.nl/]Christiaan Huygens[/url] ([url=mailto:bestuur@ch.tudelft.nl]e-mail[/url])[/h3]\r\n\r\nWiskunde en Informatica\r\nTechnische Universiteit Delft||\r\n\r\n||[url=http://www.stickyutrecht.nl/][/url]||[h3][url=http://www.stickyutrecht.nl/]Sticky[/url] ([url=mailto:info@stickyutrecht.nl]e-mail[/url])[/h3]\r\n\r\nInformatica en Informatiekunde\r\nUniversiteit Utrecht||\r\n\r\n||[url=http://www.a-eskwadraat.nl/][/url]||[h3][url=http://www.a-eskwadraat.nl/]A-Eskwadraat[/url] ([url=mailto:bestuur@a-eskwadraat.nl]e-mail[/url])[/h3]\r\n\r\nWiskunde, Informatica, Informatiekunde en Natuur- & Sterrenkunde\r\nUniversiteit Utrecht||\r\n\r\n||[url=http://www.gewis.nl/][/url]||[h3][url=http://www.gewis.nl/]GEWIS[/url] ([url=mailto:bestuur@gewis.nl]e-mail[/url])[/h3]\r\n\r\nWiskunde en Informatica\r\nTechnische Universiteit Eindhoven||\r\n\r\n||[url=http://www.thalia.nu/][/url]||[h3][url=http://www.thalia.nu/]Thalia[/url] ([url=mailto:info@thalia.nu]e-mail[/url])[/h3]\r\n\r\nInformatica en Informatiekunde\r\nRadboud Universiteit Nijmegen||\r\n\r\n||[url=http://www.inter-actief.net/][/url]||[h3][url=http://www.inter-actief.net/]Inter-Actief[/url] ([url=mailto:contact@inter-actief.net]e-mail[/url])[/h3]\r\n\r\nInformatica, Bedrijfsinformatietechnologie en Telematica\r\nTechnische Universiteit Twente||\r\n\r\n||[url=http://www.realtime-online.nl/][/url]||[h3][url= https://www.sv-realtime.nl/]Realtime[/url] ([url=mailto:bestuur@sv-realtime.nl]e-mail[/url])[/h3]\r\n\r\nICT Hanzehogeschool Groningen||\r\n\r\n||[url=http://www.nsvki.nl/][/url]||[h3][url=http://www.nsvki.nl/]NSVKI[/url] ([url=mailto:bestuur@nsvki.nl]e-mail[/url])[/h3]\r\n\r\nDe Nederlandse StudieVereniging Kunstmatige Intelligentie is een overkoepelende organisatie voor KI verenigingen||\r\n[/table]		\N
5	5	Commissiepagina Brainstorm	[samenvatting]Tenminste drie keer per jaar ons geliefde verenigingsblad "Brainstorm" uitbrengen, dat is de missie van de Brainstormcommissie.[/samenvatting]\n\nTenminste drie keer per jaar ons geliefde verenigingsblad "Brainstorm" uitbrengen, dat is de missie van de Brainstormcommissie.\n\n[h2]De commissie[/h2]\nWat gaat er rond in de wereld van KI en Informatica en dan specifiek aan de RUG en binnen Cover? Dat is waar de Brainstorm zich mee bezig houdt. Om onze kennis met onze Coverleden te delen schrijven wij de Brainstorm. Eens in de zoveel tijd wordt deze dan naar alle Coverleden verstuurd. Ook onze donateurs en zusterverenigingen worden niet vergeten.\n\n[H2]Rubrieken[/H2]\nDe inhoud van onze Brainstorm verschilt natuurlijk per editie, maar gelukkig heb je een beetje houvast aan enkele terugkerende rubrieken.\n\nOnze huidige regelmatig terugkerende rubrieken zijn:\n[b]Alumnus[/b]\nBestaat er een leven na de RUG? Hier vertellen mensen die hier van overtuigd zijn over hun ervaringen in deze vreemde, nieuwe wereld.\n[b]Staf/AIO's[/b]\nWat zouden KI en Informatica zijn zonder onze dierbare docenten, staf en AIO's? Deze rubriek zet deze bijzondere mensen in het zonnetje.\n[b]Puzzel[/b]\nHoe intelligent ben jij? Onze puzzels stellen je op de proef.\n\nNatuurlijk zijn er ook dingen die niet zo regelmatig zijn: uitstapjes van Cover, evenementen, spontane bijdragen van onze lezers. Met mindere regelmaat maar een gelukkig niet mindere frequentie verschijnen deze artikelen tussen onze vaste rubrieken.\n\n[H2]Kopij[/H2]Wij zijn er voor onze lezers en we hopen dat zij er ook voor ons zijn. Mocht je een leuk stukje hebben voor de Brainstorm dan kun je dat naar ons mailen (brainstorm@svcover.nl). \n\n[H2]Oude edities[/H2]\n\n[H2]Archive[/H2]\n[b]2016[/b]\n\n[b]2015[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-1-play.pdf]Editie 1 - Play[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-2-smart.pdf]Editie 2 - Smart[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-3-sims.pdf]Editie 3 - Sims[/url][/ul]\n\n[b]2014[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-1-8-2-online.pdf]Editie 1 - Espionage[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-2.pdf]Editie 2 - Illusion[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-3-unicode.pdf]Editie 3 - UniCode[/url][/li][/ul]\n\n[b]2013[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-1.pdf]Editie 1 - Echo[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-2.pdf]Editie 2 - Back to the 80's[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-3.pdf]Editie 3 - Poesiealbum[/url][/li][/ul]\n\n[b]2012[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-12-1.pdf]Editie 1 - Evolutie[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2012-2-druk.pdf]Editie 2 - Interactief[/url][/li][/ul]\n\n[b]2011[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2011-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2011-03-export-1.pdf]Editie 3[/url][/li]\n[/ul]\n\n[b]2010[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2010-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2010-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2010-03.pdf]Editie 3[/url][/li]\n[/ul]\n\n[b]2009[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2009-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2009-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2009-03.pdf]Editie 3[/url][/li]\n[/ul]\n\n[b]2008[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2008-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2008-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2008-03.pdf]Editie 3[/url][/li]\n[/ul]\n\n[b]2007[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2007-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2007-03.pdf]Editie 3[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2007_december_Brainstorm.pdf]Editie 4[/url][/li]\n[/ul]\n\n[b]2006:[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2006_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_juni_Brainstorm.pdf]juni[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_oktober_Brainstorm.pdf]oktober[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_december_Brainstorm.pdf]december[/url][/li]\n[/ul]\n\n[b]2005:[/b]\n[ul]\n[li][url=http://brainstorm.svcover.nl/archief/2005_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2005_november_Brainstorm.pdf]november[/url][/li]\n[/ul]\n\n[b]2004:[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2004_juni_Brainstorm.pdf]juni[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2004_december_Brainstorm.pdf]december[/url][/li][/ul]\n\n[b]2002:[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2002_juli_Brainstorm.pdf]juli[/url][/li][/ul]\n\n[b]2000:[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2000_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2000_juli_Brainstorm.pdf]juli[/url][/li][/ul]\n\n[b]1999:[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/1999_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/1999_september_Brainstorm.pdf]september[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/1999_december_Brainstorm.pdf]december[/url][/li][/ul]	[samenvatting]At least three times a year, we're publishing our beloved magazine "Brainstorm".[/samenvatting]\n\nThe wonderful world of AI and CS yields fascinating stories at the University of Groningen and within Cover. The Brainstorm takes upon itself the task of sharing these stories and knowledge amongst Cover members and staff. \nAbout thrice a year, a Brainstorm will pop on your doormat to keep you entertained and informed. So start puzzling and enjoy the strings of words that collect to yet a new Brainstorm!  \n\n[H2]Recurring Sections[/H2]\nThe contents of each Brainstorm are unique, but luckily some sections keep coming back for that familiar feeling.\n\n[b]Alumni[/b]\nIs there such a thing as a life after AI or CS? People who tend to answer this question with "yes" tell something about this strange and sometimes new world they live in.\n[b]Staff[/b]\nWhat would AI or CS be without our staff members and PhD's? In this section we give them room to talk about their hobby or an otherwise interesting phenomena. \n[b]Puzzel[/b]\nRather than testing your intelligence level, the puzzle actually measures and rewards participation! Solve the puzzle, send in your answer and maybe you will win a prize!\n\nThere are some other spontaneous articles as well, for instance about a trip or self driving cars.\n\n[H2]Copy[/H2]We make this magazine for our members and readers and are there for you. We also hope you are there for us. So if you have a nice article for the Brainstorm or would like to help otherwise, please send us an email at [url=mailto:brainstorm@svcover.nl]brainstorm.svcover.nl[/url].\n\n[H2]Archive[/H2]\n[b]2016[/b]\n\n[b]2015[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-1-play.pdf]Edition 1 - Play[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-2-smart.pdf]Edition 2 - Smart[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2015-3-sims.pdf]Edition 3 - Sims[/url][/ul]\n\n[b]2014[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-1-8-2-online.pdf]Edition 1 - Espionage[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-2.pdf]Edition 2 - Illusion[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2014-3-unicode.pdf]Edition 3 - UniCode[/url][/li][/ul]\n\n[b]2013[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-1.pdf]Editie 1 - Echo[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-2.pdf]Editie 2 - Back to the 80's[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2013-3.pdf]Editie 3 - Poesiealbum[/url][/li][/ul]\n\n[b]2012[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/brainstorm-12-1.pdf]Editie 1 - Evolutie[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/brainstorm-2012-2-druk.pdf]Editie 2 - Interactief[/url][/li][/ul]\n\n[b]2011[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2011-01.pdf]Editie 1 - Hollands Glorie[/url][/li]\n[li][url=https://www.youtube.com/watch?v=dQw4w9WgXcQ]Editie 2 - Paradox[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2011-03-export-1.pdf]Editie 3 - Plagiaat[/url][/li][/ul]\n\n[b]2010[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2010-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2010-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2010-03.pdf]Editie 3[/url][/li][/ul]\n\n[b]2009[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2009-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2009-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2009-03.pdf]Editie 3[/url][/li][/ul]\n\n[b]2008[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2008-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2008-02.pdf]Editie 2[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2008-03.pdf]Editie 3[/url][/li][/ul]\n\n[b]2007[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2007-01.pdf]Editie 1[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2007-03.pdf]Editie 3[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2007_december_Brainstorm.pdf]Editie 4[/url][/li][/ul]\n\n[b]2006[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2006_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_juni_Brainstorm.pdf]juni[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_oktober_Brainstorm.pdf]oktober[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2006_december_Brainstorm.pdf]december[/url][/li][/ul]\n\n[b]2005[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2005_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2005_november_Brainstorm.pdf]november[/url][/li][/ul]\n\n[b]2004[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2004_juni_Brainstorm.pdf]juni[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2004_december_Brainstorm.pdf]december[/url][/li][/ul]\n\n[b]2002[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2002_juli_Brainstorm.pdf]juli[/url][/li][/ul]\n\n[b]2000[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/2000_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/2000_juli_Brainstorm.pdf]juli[/url][/li][/ul]\n\n[b]1999[/b]\n[ul][li][url=http://brainstorm.svcover.nl/archief/1999_april_Brainstorm.pdf]april[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/1999_september_Brainstorm.pdf]september[/url][/li]\n[li][url=http://brainstorm.svcover.nl/archief/1999_december_Brainstorm.pdf]december[/url][/li][/ul]	\N
41	0	De studie	[h1]Computing Science[/h1]\n\n[h1]De studie[/h1]\nGeen wetenschap is een groter drijfveer achter maatschappelijke veranderingen dan informatica. Er is bijna geen sector te bedenken waar informatica geen belangrijke, vernieuwende rol speelt. Ben je geïnteresseerd in informatica, maar ook andere vakken, dan kun je als je Computing Science gestudeerd hebt altijd het vak combineren met één of meer van je andere interesses. Zeker met de flexibele bachelor structuur die we in Groningen recent hebben ingevoerd, waardoor je veel mogelijkheden krijgt om al tijdens je studie aandacht te besteden aan andere vakken.\n\nMeer informatie over de studie vind je op [url=http://www.rug.nl/bachelors/computing-science/] deze site[/url].\n\n[h2] Praktische informatie en studieondersteuning [/h2]\nJe rooster is te vinden op [url=http://www.rug.nl/fwn/roosters/2014/in/] deze site [/url]. Controleer wel even het jaartal bovenaan de pagina! Meer praktische informatie vind je [url=https://www.svcover.nl/show.php?id=27] hier [/url]. Op diezelfde pagina zijn ook allerlei opties voor studieondersteuning te vinden. Samenvattingen en oude tentamens kun je [url=https://studysupport.svcover.nl/] hier [/url] vinden. Is er iets nog niet duidelijk of persoonlijk advies nodig? Ga dan even langs bij je studieadviseur, [url=http://www.rug.nl/staff/j.h.niessink/]Hanneke Niessink[/url]\n\n[h2] Studeren in het buitenland [/h2]\nTijdens je studie is het ook mogelijk om voor een bepaalde tijd, bijvoorbeeld tijdens je master, in het buitenland te studeren. Voor meer informatie kun je het beste naar de Exchange Office gaan. Deze vind je op het volgende adres: Bernoulliborg, kamer 5161.0050, Nijenborgh 9, 9747 AG Groningen. Je kan ook contact opnemen via de mail met Eloïse Daumerie (e.daumerie@rug.nl) of Henriëtte Mulder(henriette.mulder@rug.nl).\n\n[h2] Afstuderen en na je studie [/h2]\nBen je opzoek naar een afstudeerstage? Kijk dan op [url=https://www.svcover.nl/show.php?id=31]deze pagina[/url]! Ben je opzoek naar een baan? Kijk dan [url=https://www.svcover.nl/show.php?id=54]hier[/url]! Je kan ook altijd contact opnemen met het bestuur (board@svcover.nl), zij weten meer bedrijven die nieuwe werknemers zoeken. 	[h1]Computing Science[/h1]\n\nNo science other than Computing Science is a greater driving force behind social changes. It is difficult to think of a field where Computing Science does not play an important and innovative role.\n\nIf you are interested in computer science, but also in other occupations then you can often combine your computer science study with one or more of your other interests. Especially with the flexibel bachelor fase that has been recently introduced at the University of Groningen which will grant you the ability to also focus on other areas of interest.\n\nFor more information go to http://www.rug.nl/bachelors/computing-science/\n	\N
110	0	Opmerkingen aanmelden	[ul]\n[li]Contributie wordt zolang je lid bent van Cover jaarlijks van je bankrekening afgeschreven.[/li]\n[li]Je bent lid af wanneer je afstudeert of je lidmaatschap opzegt.[/li]\n[li]Opzegging van het lidmaatschap moet schriftelijk gedaan worden bij de secretaris.[/li]\n[li]Een wijziging in je gegevens kun je mailen naar board@svcover.nl, schriftelijk melden bij de secretaris of in je profiel op de Cover website aanpassen.[/li]\n[li]De contributie bedraagt € 15,- per jaar.[/li]\n[/ul]	[ul]\n[li]Membership fee will be charged annually from your bank account for as long as you are a member of Cover.[/li]\n[li]You are a member until you graduate or cancel your membership.[/li]\n[li]Termination of membership shall be done in writing to the secretary.[/li]\n[li]You can update your personal details by sending an email to board@svcover.nl, by writing to the secretary or by updating your profile on the Cover website.[/li]\n[li]The membership fee is € 15,- per year.[/li]\n[/ul]	\N
6	6	Commissiepagina Excie	[samenvatting]The ExCee will organise another great trip this year to some other great location within Europe.[/samenvatting]\n\nCheck out our crazy [url=http://excee.svcover.nl]website[/url]!\n	[samenvatting]The ExCee will take you to a cool location within Europe![/samenvatting]\nWhich city will we visit this year? Stay tuned!\n\nFor more information on our trip look at [url= http://excee.svcover.nl]our website[/url]!\n	[commissie_foto]
121	0	Rabobank	Jouw toekomst bij de Rabobank\n\nSamen sta je sterker. Bereik je meer dan alleen. Dat is in het kort onze coöperatieve gedachte. Wat \nwe bereikt hebben? De Rabobank is uitgegroeid tot een van de meest betrouwbare en innovatieve \nfinanciële dienstverleners ter wereld. Met duurzame klantrelaties en oog voor de samenleving. Een \ncoöperatieve bank waar fatsoenlijk en succesvol zakendoen hand in hand gaan. Geworteld in \nNederland en inmiddels in ruim 40 landen actief. In Nederland ligt de nadruk op brede financiële \ndienstverlening, de internationale focus ligt vooral op de food- en agribusiness. Met al onze \nmedewerkers werken we aan duurzame relaties met onze klanten voor het realiseren van ambities. \nWerken samen aan de toekomst. Jouw toekomst?\n\nWaar start jij?\n\nDe Rabobank biedt jou een uitdagend traineeship: Rabo Global Traineeship. Maar ook ambitieuze \nstartersfuncties: Mergers & Acquisitions en Equity Capital Markets, Mid Office Product Control en het \nYoung Professional Programme IT.  Waar je ook start, je krijgt verantwoordelijkheid en ruimte voor \neigen initiatief. Een loopbaanpad dat is afgestemd op jouw toekomst bij de Rabobank. \n\nKijk vooruit \n\nAls starter heb je een droom. We bieden jou kansen om die te realiseren. En ruimte om je persoonlijk \nen professioneel te ontwikkelen. Een onderscheidend cv en een gezonde portie ambitie zijn een prima \nstartkapitaal. Durf jij verantwoordelijkheid te nemen en overtuigend je visie uit te dragen? Dan maken \nwe graag kennis. We kijken uit naar jonge mensen met lef en een aansprekende persoonlijkheid, met \nambitie en de gave om anderen te inspireren. We zijn benieuwd of we samen onze én jouw toekomst \nkunnen vormgeven. \n\nBekijk onze traineeships, programma’s en startersfuncties op rabobank.nl/graduates	Jouw toekomst bij de Rabobank\n\nSamen sta je sterker. Bereik je meer dan alleen. Dat is in het kort onze coöperatieve gedachte. Wat \nwe bereikt hebben? De Rabobank is uitgegroeid tot een van de meest betrouwbare en innovatieve \nfinanciële dienstverleners ter wereld. Met duurzame klantrelaties en oog voor de samenleving. Een \ncoöperatieve bank waar fatsoenlijk en succesvol zakendoen hand in hand gaan. Geworteld in \nNederland en inmiddels in ruim 40 landen actief. In Nederland ligt de nadruk op brede financiële \ndienstverlening, de internationale focus ligt vooral op de food- en agribusiness. Met al onze \nmedewerkers werken we aan duurzame relaties met onze klanten voor het realiseren van ambities. \nWerken samen aan de toekomst. Jouw toekomst?\n\nWaar start jij?\n\nDe Rabobank biedt jou een uitdagend traineeship: Rabo Global Traineeship. Maar ook ambitieuze \nstartersfuncties: Mergers & Acquisitions en Equity Capital Markets, Mid Office Product Control en het \nYoung Professional Programme IT.  Waar je ook start, je krijgt verantwoordelijkheid en ruimte voor \neigen initiatief. Een loopbaanpad dat is afgestemd op jouw toekomst bij de Rabobank. \n\nKijk vooruit \n\nAls starter heb je een droom. We bieden jou kansen om die te realiseren. En ruimte om je persoonlijk \nen professioneel te ontwikkelen. Een onderscheidend cv en een gezonde portie ambitie zijn een prima \nstartkapitaal. Durf jij verantwoordelijkheid te nemen en overtuigend je visie uit te dragen? Dan maken \nwe graag kennis. We kijken uit naar jonge mensen met lef en een aansprekende persoonlijkheid, met \nambitie en de gave om anderen te inspireren. We zijn benieuwd of we samen onze én jouw toekomst \nkunnen vormgeven. \n\nBekijk onze traineeships, programma’s en startersfuncties op rabobank.nl/graduates	\N
3	3	Commissiepagina BookCee	[samenvatting]Als je lid bent van Cover dan kun je je boeken bij ons bestellen. [/samenvatting]\r\n\r\nAls je lid bent van Cover dan kun je je boeken bij ons bestellen. Op de Engelstalige boeken krijg je dan sowieso 11% korting op de verkoopprijs van onze leverancier! Elke periode kun je via de website je bestelling doorgeven van de boeken die jij denkt nodig te hebben in die periode.\r\n \r\nDe boekenverkoop is eens [b]per periode[/b]. Al je boeken kun je via deze [b][url=http://www.svcover.nl/boeken.php]website[/url] [/b] bestellen. Voor vragen hierover kun je mailen met de BoekCie. [url=mailto:bookcee@svcover.nl]bookcee@svcover.nl[/url]	[samenvatting]You can order your study books at us if you are a member of Cover. [/samenvatting]\n\nIf you are a member of Cover you can order your books at us. You will get a 11% discount on all of your English study books from our supplier! Every period you can order the books you need each period at the website.\n\nThe book sale is once [b] per term [/b]. You can order all of the books [b][url=http://www.svcover.nl/boeken.php]here[/url][/b]. If you have any questions about this please email us at [url=mailto:bookcee@svcover.nl]bookcee@svcover.nl[/url].\n	\N
7	7	Commissiepagina PhotoCee	[samenvatting]\nDe PhotoCee is de commissie die uiteraard foto's maakt bij van alles en nog wat en die daarnaast ook dingen die met fotografie te maken hebben organiseert!\n[/samenvatting]\n\nDe PhotoCee maakt mooie foto's en soms ook filmpjes bij verschillende activiteiten. Ook is er elk jaar een Fotowedstrijd en een Fotodag Soms hebben we ook nog een fotoquiz! Verder maken we graag almanak- bestuurs- of commissiefoto's en worden we soms gevraagd om bij gala's e.d. te fotograferen. \n\nTevens beschikt de PhotoCee over een eigen camera, je kan dus ook gerust in de commissie als je geen camera hebt. We leren je daarbij, als je wilt, ook direct de kneepjes van het vak!\n\nVolg ons nu ook op Facebook en Twitter!\n\n[b]Linkjes:[/b]\nFoto's: [url=http://www.svcover.nl/fotoboek.php]klik[/url] en [url=http://photocee.svcover.nl]klik[/url].\nFilmpjes: [url=http://www.youtube.com/user/fotocie]Youtube[/url]!\nFotowedstrijd: [url=http://photocee.svcover.nl/] PhotoCee pagina[/url].\nTwitter: [url=http://twitter.com/photocee]klik[/url].\nFacebook [url=https://www.facebook.com/fotocie]klik[/url].\n	[samenvatting]\nThe PhotoCee is the committee that takes pictures during the various activities of Cover and organises photography related activities!\n[/samenvatting]\n\nThe PhotoCee takes astonishingly beautiful pictures and sometimes even video's during the various activities of Cover. In addition, we organise the annual Photo Contest and Photo Day. Sometimes we even organise a Photo Quiz or a Photo Chain Letter. We love to take almanac-, board- or committee pictures and are occasionally asked to take pictures at galas or similar activities.\n\nThe PhotoCee owns a camera, the Covercamera, so even if you don't own one yourself it is still possible to join us! If you want to, we would like to teach you all the ins and outs of photography!\n\nFollow us on Facebook and Twitter!\n\n[b]Links:[/b]\nPictures: [url=http://www.svcover.nl/fotoboek.php]click[/url] and [url=http://photocee.svcover.nl]click[/url].\nVideos: [url=http://www.youtube.com/user/fotocie]Youtube[/url]!\nPhoto Contest: [url=http://photocee.svcover.nl/] PhotoCee website[/url].\nTwitter: [url=http://twitter.com/photocee]click[/url].\nFacebook [url=https://www.facebook.com/fotocie]click[/url].\n	\N
2	2	Commissiepagina ActiviTee	[samenvatting]ActiviTee staat voor de activiteitencommissie. Elke maand organiseren we minstens drie activiteiten zoals de borrel, filmavond en spellenavond.[/samenvatting]\r\n\r\nActiviTee staat voor activiteitencommissie. Elke maand organiseren we sowieso een borrel op de eerste woensdag van de maand in 't Gat van Groningen.\r\nOok organiseren we een hoop andere activiteiten:\r\n\r\n- Een kleine activiteit;\r\n- Een grote activiteit wat van alles kan zijn; van lasergamen tot een drankspellenavond.\r\n\r\n\r\nVerder organiseren we aan het einde van het kalender jaar een groot eindfeest en aan het einde van het college jaar een speciale activiteit.	[samenvatting]"ActiviTee" represents activities committee. Every month, we organize at least three activities: a "borrel", a movie night or (traditional) game night and a big activity.[/samenvatting]\r\n\r\n"ActiviTee" represents activities committee. Every month, we organize at least three activities:\r\n[ul]\r\n[li]a "borrel" every first Wednesday of the month in 't Gat van Groningen.[/li]\r\n[li]a small activity [/li]\r\n[li]a big activity [/li]\r\n[/ul]\r\n\r\nBesides this, we organize a big end of the year party in december and a special big activity before the summer break.\r\n	\N
18	0	Lidmaatschap en Donateurschap	[H1]Lidmaatschap en Donateurschap[/H1]\n[H2]Hoe kun je...[/H2]\n[H3]Lid worden:[/H3]\n[ul]\n[li]Vul het [url=lidworden.php] online lidmaatschapsformulier[/url] in.[/li]\n[/ul]\n\nHet lidmaatschap bedraagt 15 euro per jaar. Word je na 1 februari lid, dan betaal je voor het resterende deel van het verenigingsjaar 7,50 euro.\n\n[H3]Lid-af worden:[/H3]\n[ul]\n[li]Wanneer je afstudeert zorgen wij ervoor dat je uitgeschreven wordt.[/li]\n[li]In alle andere gevallen dien je minimaal een maand voor het einde van het verenigingsjaar te melden dat je je lidmaatschap op wilt zeggen. Dit kan door een mail te sturen naar [url=mailto:secretary@svcover.nl]secretary@svcover.nl[/url] of een brief te sturen naar:\n\nStudievereniging Cover \nPostbus 407\n9700 AK Groningen\n\nHet verenigingsjaar loopt tot 1 september[/li]\n[/ul]\n\nLet wel, onze ledenadministratie staat geheel los van die van KI en informatica.\n\n[H3]Donateur worden:[/H3]\nAls donateur steun je Cover door middel van een jaarlijkse gift van een zelf bepaalde hoogte, die tenminste €15 bedraagt. In ruil hiervoor ontvang je ons verenigingsblad, de Brainstorm.\nWil je donateur worden? Download dan [url=documenten/ContributorForm.pdf]dit formulier[/url] en stuur het op naar Studievereniging Cover, Postbus 407, 9700 AK Groningen of leg het in ons postvakje op de derde verdieping van de Bernoulliborg.\n\n	[H1]Membership and becoming a contributor[/H1]\n[H2]How can I…[/H2]\n[H3]Become a member:[/H3]\n[ul]\n[li]Just submit the [url=lidworden.php]online form to apply for membership[/url].[/li]\n[/ul]\n\nThe membership fee is €15,- annually. If you apply for membership after the first of February, you only have to pay for the second half of the academic year, just €7,50.\n\n[H3]Stop my membership:[/H3]\n[ul]\n[li]When you graduate from your Artificial Intelligence or Computing Science bachelor or master studies, your membership will automatically end.[/li]\n[li]In all other cases you should send a letter or an email to secretary@svcover.nl at least one month before the end of the academic year. You can send the letter to:\n\nstudy association Cover \nPostbus 407\n9700 AK Groningen\n\nThe academic year ends on the first of September.[/li]\n[/ul]\n\nPlease note that our membership administration is completely separate from that of the university.\n\n[H3]Become a contributor:[/H3]\nAs a contributor you support Cover through an annual financial gift of at least €15,- per donation. In return you will receive our association's magazine, the Brainstorm. If you donate at least €50,- you will also receive our yearbook.\n\nDo you want to become a contributor? [url=documenten/ContributorForm.pdf]Download the contributor form[/url], fill it in and send it to study association Cover, Postbus 407, 9700 AK Groningen. Or you could drop it in our postbox on the third floor of the Bernoulliborg.\n	\N
31	26	Stages/Afstudeerplaatsen	[h1]Afstudeerplaatsen[/h1]\r\nWelkom op de afstudeer en bedrijfsstage bedrijvenpagina! Ben jij op zoek naar een stageplaats of studentassistentschap, neem dan contact op met een van de onderstaande bedrijven of instanties. Staat er niets bij wat je leuk lijkt? Neem dan contact op met het bestuur (bestuur@svcover.nl). Zij kunnen jou vertellen welke bedrijven je kan benaderen voor een stageplaats.\r\n\r\nKijk voor meer informatie ook in de studiegids, op [url=http://www.rug.nl/frw/education/more-education/degree-certificates] de site van de RuG[/url] of op de site van de career service van de RuG, [url=http://www.rug.nl/next/] NEXT[/url].\r\n\r\n\r\n[table noborder]\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/nl/career/vacatures/computer-vision-engineer-den-haag/a0sb000000xz5wteai/]Computer Vision Engineer[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-implementation-of-secure-spectral-clustering/a0sb000000a8i7meau/]Internship: Implementation of Secure Spectral Clustering[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-building-the-next-generation-video-streaming-protocols/a0sb000000tskiyea3/]Internship: Building The Next Generation Video Streaming Protocols ([i]Den Haag[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/nl/career/vacatures/stage-afstudeerplek-gsm-security/a0sb000000nzpj1eab/]Stage/Afstudeerplek GSM Security ([i]Den Haag[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-graduation-project-automated-ict-infrastructure-modeling-for-cyber-security-analysies/a0sb000000nzpwleaz/]Internship/Graduation Project Automated ICT Infrastructure Modeling For Cyber Security Analysis ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor1.docx]Stel je eigen uitdagende opdracht samen![/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(1).docx] Microsoft goes linux: evaluation please! [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(2).docx] Cutting edge Front End Development [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(3).docx] AFSTUDEEROPDRACHT SuperCandidate  [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(4).docx] AFSTUDEEROPDRACHT QuintorWEB 2.0  [/url][/h3]||\r\n\r\n|| [h2]CIT, Centrum voor Informatietechnologie[/h2]|| [h3][url=https://www.svcover.nl/documenten/Vacatures/SustainableDataCenters.pdf]Opdracht: Sustainable Data Centers[/url][/h3]||\r\n\r\n[/table]\r\n	[h1]Internships / Graduate programs[/h1]\r\nAre you looking for an internship, graduate program or do you want to be an student-assistent? Contact one of the companies/ institutions below! If there is nothing you like, contact the board (board@svcover.nl) they know which companies you can contact. \r\n\r\nTake a look at your studyguide or [url=http://www.rug.nl/frw/education/more-education/degree-certificates]the site of our university[/url], the site of the career services of the RUG, [url=http://www.rug.nl/next/] NEXT[/url] for more information.\r\n\r\n\r\n[table noborder]\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/nl/career/vacatures/computer-vision-engineer-den-haag/a0sb000000xz5wteai/]Computer Vision Engineer[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-implementation-of-secure-spectral-clustering/a0sb000000a8i7meau/]Internship: Implementation of Secure Spectral Clustering[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-building-the-next-generation-video-streaming-protocols/a0sb000000tskiyea3/]Internship: Building The Next Generation Video Streaming Protocols ([i]Den Haag[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/nl/career/vacatures/stage-afstudeerplek-gsm-security/a0sb000000nzpj1eab/]Internship/Graduation Project GSM Security ([i]Den Haag[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-graduation-project-automated-ict-infrastructure-modeling-for-cyber-security-analysies/a0sb000000nzpwleaz/]Internship/Graduation Project Automated ICT Infrastructure Modeling For Cyber Security Analysis ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor1.docx]Compose your own challenging assignment [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(1).docx] Microsoft goes linux: evaluation please! [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(2).docx] Cutting edge Front End Development [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(3).docx] Graduation Project: SuperCandidate  [/url][/h3]||\r\n\r\n|| [h2]Quintor[/h2]|| [h3][url=https://www.svcover.nl/documenten/Quintor14102016(4).docx] Graduation Project: QuintorWEB 2.0  [/url][/h3]||\r\n\r\n|| [h2]CIT, Centrum voor Informatietechnologie[/h2]|| [h3][url=https://www.svcover.nl/documenten/Vacatures/SustainableDataCenters.pdf]Assignment: Sustainable Data Centers[/url][/h3]||\r\n\r\n[/table]\r\n	\N
30	0	Documenten	[h1]Documenten[/h1]\n\n[h2]Standaarddocumenten[/h2]\nAlle publieke documenten van Cover, zoals het briefpapier, logo's, sjablonen, handleidingen en het archief [url=https://sd.svcover.nl/]zijn te vinden op de SD[/url]. Hieronder vind je een selectie van belangrijke documenten van Cover zoals reglementen en ALV stukken.\n\n[h2]Reglementen[/h2] \n1. Statuten ([url=https://sd.svcover.nl/Association/Statuten.pdf]pdf[/url]) \n2. Huishoudelijk reglement ([url=https://sd.svcover.nl/Association/Huishoudelijk_reglement.pdf]pdf[/url]) \n3. Richtlijnen commissies ([url=https://sd.svcover.nl/Association/Richtlijnen_commissies.pdf]pdf[/url])\n\n[h2]Nieuwsbrieven[/h2] \nAlle nieuwsbrieven vind je [url=https://sd.svcover.nl/Newsletters]in de standaarddocumenten[/url].\n\n[h2]Jaarplanning[/h2] \nDe jaarplanning voor dit jaar vind je [url=https://sd.svcover.nl/Archive/Year%20Schedules/Year%20Schedule%202014-2015.pdf]hier[/url].\nEen overzicht van alle jaarplanningen vind je [url=https://sd.svcover.nl/Archive/Year%20Schedules]hier[/url].\n\n[h2]Begroting[/h2]\nJe kan de huidige begroting van Cover [url=https://sd.svcover.nl/Archive/Budgets/]hier[/url] vinden. Let op: je moet wel ingelogd zijn.\n\n[h2]Afrekening[/h2]\nDe afrekening van 2013 zoals hij op de ALV goedgekeurd is kun je [url=https://sd.svcover.nl/Archive/General%20Assemblies/2013-09-26/09_afrekening.pdf]hier[/url] vinden. Let op: je moet wel ingelogd zijn.\n\n[h2]ALV-documenten[/h2]\nDe ALV-documenten van afgelopen ALV's zijn terug te vinden [url=https://sd.svcover.nl/Archive/General%20Assemblies/]op de SD[/url]. Hiervoor dien je wel ingelogd te zijn. Wens je de documenten te ontvangen zonder in te loggen? Mail dan naar bestuur@svcover.nl.	[h1]Documents[/h1]\n\n[h2]Documents & Templates[/h2]\nAll of the public resources of Cover, such as stationery, logo's, templates, manuals and archives [url=https://sd.svcover.nl/]can be found on the SD[/url]. Below you can find a selection of important documents of Cover such as the regulations and General Assembly documents.\n\n[h2]Regulations[/h2]\n1. Articles of Association (in Dutch) ([url=https://sd.svcover.nl/Association/Statuten.pdf]pdf[/url]) \n2. Rules and regulations (in Dutch) ([url=https://sd.svcover.nl/Association/Rules%20and%20Regulations_Dutch%20%28binding%20version%29.pdf]pdf[/url]) \n3. Committee guidelines (in Dutch) ([url=https://sd.svcover.nl/Association/Richtlijnen_commissies.pdf]pdf[/url])\n\n[h2]Newsletters[/h2] \nA history of the newsletters can be found [url=https://sd.svcover.nl/Newsletters]on the SD[/url].\n\n[h2]Year schedule[/h2] \nThe year schedule of this year can be found [url=https://sd.svcover.nl/Archive/Year%20Schedules/Year%20Schedule%202015-2016.pdf]here[/url].\nA history of the year schedules of past years can be found [url=https://sd.svcover.nl/Archive/Year%20Schedules/]here[/url].\n\n[h2]Budget[/h2]\nYou can find the current budget of Cover [url=https://sd.svcover.nl/Archive/Budgets/]here[/url].\n\n[h2]Accounts[/h2]\nThe financial account of 2013-2014 can be found [url=https://sd.svcover.nl/Archive/General%20Assemblies/2014-09-25/10a%20Year%20Account%202013-2014.pdf]here[/url].\n\n[h2]General Assemblies[/h2]\nThe documents of the past General Assemblies can be found [url=https://sd.svcover.nl/Archive/General%20Assemblies/]on the SD[/url]. To access these you need to log in. If you wish to access them without loggin in, please email the board at board@svcover.nl.	\N
64	0	Bestuur XIII	[samenvatting]Bestuur 13 (2006)\n[/samenvatting]\n[h1]Bestuur XIII[/h1]\n[h2]Leden[/h2]\nVoorzitter: Bastiaan Fens\nSecretaris: Lise Pijl\nPenningmeester: Cas van Noort 		
55	0	Topicus	[h1]Over Topicus[/h1]\nTopicus is een ICT dienstverlener met meer dan 550 medewerkers gevestigd in het centrum van Deventer, Enschede en Zwolle. \n\n[h2]Wat we doen[/h2]\nWij bouwen volledig web gebaseerde ICT-systemen voor de sectoren zorg, onderwijs, finance en overheid in Java of .NET. Dankzij onze op Agile en SCRUM gebaseerde projectaanpak, de sterke technische basis en uitgebreide domeinkennis zijn we in staat snel en doelgericht projecten te verwerven en te realiseren. Bij het realiseren van deze systemen puzzelen we graag met vraagstukken over security, privacy, architectuur, requirements, big data en usability en maken daarbij graag gebruik van de nieuwste technologieën. \n\n[h2]Topicus voor studenten en young professionals[/h2]\nTopicus voor studenten en young professionals \nTopicus begeleidt jaarlijks circa 50 WO- en HBO studenten met hun stage- of afstudeeropdracht. Voor startende ICT professionals hebben wij altijd vacatures open staan binnen het vakgebied software ontwikkeling en -analyse. Als teamlid maak je actief deel uit van een projectteam, waarin je meewerkt EN meedenkt naar nieuwe oplossingen voor uitdagende ICT-projecten. \n\n[h2]Ontwikkel je talent[/h2]\nOnze medewerkers laten doorgroeien is de sleutel voor ons toekomstige succes. Dat dit werkt hebben we gemerkt: nauwelijks verloop en een goede instroom van jong talent. Talent werkt graag met talent. Bij Topicus krijg je de ruimte om je te ontwikkelen en het beste uit jezelf te halen. Topicus werkt daarvoor samen met Universiteiten, Hogescholen en specialistische onderwijsinstellingen. \nDe deur staat voor slimme analisten en ontwikkelaars altijd wagenwijd open. Voor meer informatie over Topicus kun je kijken op www.topicus.nl, [url=https://nl-nl.facebook.com/Topicusbv]Facebook[/url], \n[url=https://twitter.com/topicusbv]Twitter[/url] of [url=https://www.youtube.com/channel/UCdJk34VxGqDJQVq4NFXQ24Q]Youtube[/url] of neem contact op met Sabine Oude Booijink via sabine.oude.booijink@topicus.nl. 		\N
49	25	Commissiepagina AphrodiTee	[samenvatting]The AphrodiTee is named after the Greek goddess Aphrodite, she's the goddess of beauty, fertility and sex and love. The AphrodiTee takes care of the feminine side of the association.[/samenvatting]\r\n\r\nThe AphrodiTee is named after the Greek goddess Aphrodite, she's the goddess of beauty, fertility and sex and love. The AphrodiTee takes care of the feminine side of the association.\r\n\r\nThe AphrodiTee is one of Cover's youngest and most feminine committee and they make sure that the women of the association also get enough fun activities to participate in. These activities range from sporty and active, such as a ballroom dancing course, to relaxed, such as a Valentine’s dinner.	[samenvatting]The AphrodiTee is named after the Greek goddess Aphrodite, she's the goddess of beauty, fertility and sex and love. The AphrodiTee takes care of the feminine side of the association.[/samenvatting]\r\n\r\nThe AphrodiTee is named after the Greek goddess Aphrodite, she's the goddess of beauty, fertility and sex and love. The AphrodiTee takes care of the feminine side of the association.\r\n\r\nThe AphrodiTee is one of Cover's youngest and most feminine committee and they make sure that the women of the association also get enough fun activities to participate in. These activities range from sporty and active, such as a ballroom dancing course, to relaxed, such as a Valentine’s dinner.	\N
111	35	Corporate identity	\N	[samenvatting]The working group Corporate Identity is engaged with creating a consistent style for all visual expressions of Cover. Visual expressions are for example the website, stationery, posters, business cards, emails, the newsletter, stickers, flyers and presentations. The current goal of the working group is creating a document explaining our corporate identity and how it should be implemented i.e. which colors do we use, what are the dimensions of the logo etcetera.\n[/samenvatting]\n\nThe working group Corporate Identity is engaged with creating a consistent style for all visual expressions of Cover. Visual expressions are for example the website, stationery, posters, business cards, emails, the newsletter, stickers, flyers and presentations. The current goal of the working group is creating a document explaining our corporate identity and how it should be implemented i.e. which colors do we use, what are the dimensions of the logo etcetera.\n	\N
44	20	Commissiepagina SporTee	[samenvatting]Het doel van 'de conditie' is om de conditie van coverleden wat op peil te houden doormiddel van een sportieve doch gezellige activiteiten.[/samenvatting]\r\n\r\nHet doel van de Conditie is om de conditie van coverleden wat op peil te houden door middel van sportieve doch gezellige activiteiten.\r\n\r\nEen van de activiteiten zal zijn deelnemen aan de Wampex met enkele teams van Cover.\r\n	[samenvatting]The goal of the SporTee is to keep the members of Cover in shape by organising sporty and fun activities.[/samenvatting]\r\n\r\nThe goal of the SporTee is to keep the members of Cover in shape by organising sporty and fun activities.\r\n\r\nOne of such activities is partaking in the Wampex with a couple of Cover teams.\r\n	\N
13	13	Commissiepagina MxCee	[samenvatting]De megaexcursiecommissie (kort: MxCee) organiseert grote studiereizen buiten Europa. [/samenvatting]\r\n\r\nNa lange tijd is er weer een groep mensen gevonden die samen de MxCee vormen: de megaexcursiecommissie die een grote studiereis naar een bestemming buiten Europa organiseert. De bestemming is nog geheim en het moment van de reis is nog lang niet daar maar we zijn al hard en vol enthousiasme bezig.\r\n\r\n[h2]Archief[/h2]\r\nMeer informatie over oude MxCee-reizen tot 2004 kan je vinden op [url=http://www.ai.rug.nl/~mccie]www.ai.rug.nl/~mccie[/url]\r\n	[samenvatting]De mega excursion committee (short: MxCee) organises study related excursions to destinations outside Europe. [/samenvatting]\n\nIn the year 2015 the MxCee and 13 volunteers went on an epic quest to New Zealand. They had a hell of a time, and would gladly return sometime soon.\n\nNow a new committee has been formed. They are working hard to organise a trip to... well, you might be able to guess looking at the picture!\n\n[h2]Archive[/h2]\nHere you can find more information of past MxCee trips on the old websites:\n\n[url=http://www.ai.rug.nl/~mccie]www.ai.rug.nl/~mccie[/url].\n\n[url=https://mxcee.svcover.nl/mccie14/]mxcee.svcover.nl[/url].\n	\N
97	32	Commissiepagina External Committees	[samenvatting]Cover heeft een aantal externe commissies, waarbij samengewerkt wordt met andere andere verenigingen. Hier vind je de afgevaardigden[/samenvatting]\n\nCover organiseert samen met verscheidene andere verenigingen activiteiten, hier vind je de afgevaardigden.\n	[samenvatting]Cover has a couple of external committees, where we work together with other study associations to organize activities.[/samenvatting]\n\nCover has a couple of external committees, where we work together with other study associations to organize activities. On this page you can find our representatives for these committees.	\N
98	33	CSS	\N	\N	\N
117	40	Knights	\N	\N	\N
8	8	Commissiepagina IntroCee	[samenvatting]De Introductiecommissie heeft de taak om de toekomstige KI'ers en Informatici te introduceren bij elkaar, ouderejaars, staf en Cover.[/samenvatting]\n\n\n\nDe Introductie Commissie heeft de taak om de toekomstige KI-ers en informatici te introduceren bij elkaar, ouderejaars, staf en Cover. Hiervoor organiseren ze jaarlijks een introductiedag in Groningen, en een drie dagen durend kamp. Het thema van komend jaar staat nog niet vast. Zodra er meer informatie bekend is, komt deze op www.introcie.nl te staan!\n	[samenvatting]It is the job of the Introduction Committee of Cover to introduce the new AI and CS students to each other, Cover and staff.[/samenvatting]\n\n\n\nIt is the job of the Introduction Committee of Cover to introduce the new AI and CS students to each other, Cover and staff. To do this, they organise an introduction day in Groningen and a weekend called Introcamp. The theme of this year's Introcamp has yet to be revealed! For more information go to introcie.nl!	\N
56	0	Sponsormogelijkheden	[h1]Sponsormogelijkheden[/h1]\nStudievereniging Cover biedt bedrijven de mogelijkheid tot sponsoren van verschillende evenementen en verschillende media. Hiervoor kunt u contact opnemen met de Commissaris Extern op [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]\n\nOok hebben wij een [url=https://www.svcover.nl/documenten/StudieverenigingCover.pdf]bedrijvenfolder[/url]. Deze bevat een korte introductie van de vereniging, de twee studies die we representeren, en een aantal mogelijkheden die we u kunnen aanbieden. 	\N	\N
21	0	Startpagina	[h1]Studievereniging Cover[/h1]\r\n\r\nCover is de studievereniging voor Kunstmatige Intelligentie en Informatica aan de Rijksuniversiteit Groningen. De studievereniging telt meer dan 600 leden.\r\n\r\nWil je weten wat Cover allemaal doet binnenkort, kijk dan op de [url=./agenda.php]agenda[/url].	[h1]Study Association Cover[/h1]\r\n\r\nCover is the study association for Artificial Intelligence and Computing Science at the University of Groningen. The study association counts over 600 members.\r\n\r\nAre you interested in the upcoming activities of Cover? Take a look at [url=./agenda.php]our agenda[/url].	\N
118	0	Student Representation Information		Our university and faculty have many representative organs. On this page, we will explain the many types of student representation that you can find during your study. There are three categories of student representation: programme specific, faculty specific and university specific organs, each functioning by cooperating with each other. These student representation organs all serve to make life at the university easier for the students and staff, and make sure every body in our university does their work as correct and efficient as possible.  \r\n\r\n[h2]Degree Programme[/h2]\r\n\r\n[h3]Programme Committee[/h3]\r\n\r\nThe programme committee is the organ that evaluates the degree programme. This organ consists of six students (three Bachelor and three Master) and five or six lecturers/professors, who meet approximately six times a year to discuss the positive and negative aspects of the programme. \r\n\r\nIf there are any outstandingly positive or negative remarks about the degree programme, the programme committee is supposed to pick them up, and make sure something happens with these comments. You can think of topics such as looking at the quality of a course, discussing with lecturers how their course could be improved and addressing programme wide issues like the amount of students who receive a positive/negative BSA. Their main sources of information include the evaluation forms handed out at the end of a course and the information gathered at student meetings. Of course, they also receive their information by generally talking to students and use their own course experience.\r\n\r\nThe programme committee is also responsible for checking the teaching and exam regulations, the rules and regulations of your study programme. In addition to these regulatory tasks, they also organize the teacher of the year elections, which take place once a year. \r\n\r\nThe students of the programme committee can be reached by sending an email to oc@ai.rug.nl (Artificial Intelligence) or ocinformatica@betastuf.nl (Computing Science).\r\n\r\n[h3]Curriculum committee[/h3]\r\n\r\nThe curriculum committee is a subcommittee of the programme committee. Whereas the degree programme committee evaluates the quality of the programme, the curriculum committee evaluates the contents. The curriculum committee meets approximately twice a year. If you wish to contact the curriculum committee, you can reach them by sending an email to the students of the programme committee.\r\n\r\n[h3]Year Representatives[/h3]\r\nThe Year Representatives help the programme Committee and Cover by giving their opinion about the degree programme and the Cover activities. As a Year Representative there are 4 meetings a year, where you get the chance to evaluate the courses and the Cover activities. The Year Representatives can be seen as a sort of trendwatchers within their year .\r\n\r\nIf you are interested in becoming a Year Representative or if you want more information you can mail to board@svcover.nl.\r\n\r\n[h2]Faculty Wide[/h2]\r\n\r\n[h3]Undergraduate School of Science[/h3]\r\n\r\nThe Undergraduate School of Science (USS) is the overarching structure of all the bachelor degree programmes at the FSE.  They discuss general issues regarding the bachelor education. As most of the students at our faculty are Bachelor students, this is in practise also the school that is involved with the study associations and activating the programme committees. The USS has two student members, along with mostly degree programme coordinators.\r\n\r\n[h3]Graduate School of Science[/h3]\r\n\r\nThe Graduate School of Science (GSS) is the overarching structure for all the master degree programmes and PHD-related matters at the FSE. They discuss issues regarding the master education, as well as discussing PHD research. As Master education is generally more closely connected to research, if there are any research-related matters, they are usually discussed within the GSS. The GSS has a student member and a PHD member, along with mostly degree programme coordinators.\r\n\r\n[h2]Faculty Council[/h2]\r\n\r\nThe faculty council is the representative organ that advises the faculty board on all kinds of matters relating to faculty management. The council consists of nine staff and nine student members, elected by their peers every two years and one year respectively (via a Progress election). The faculty council approves things such as the faculty teaching- and exam regulations and looks at the evaluation of organisatory structures (such as the ESC). \r\n\r\nThe faculty council also thinks about matters such as the faculty finances, faculty-wide education and the faculty building management (e.g. safety regulations). The student body of the faculty council can be reached by sending an email to fast@betastuf.nl. \r\n\r\n[h2]Faculty Board[/h2]\r\n\r\nThe faculty board is the main decision making organ of our faculty. This board consists of five members: the dean (and the director of research), the vice-dean (director of education), the managing director, an advising student member and the faculty secretary. The faculty board manages matters such as the faculty agenda and general decisions that affect our faculty as a whole, but is also responsible for the management of the faculty. They also relay with the central board of the university concerning university manners.\r\n\r\n[h2]Umbrella Organisations[/h2]\r\n\r\nUmbrella organisations in our faculty are the organisations that are used to bind all the different student participation organs together. The most well-known example of this is the [URL=https://www.betastuf.nl]Bètastuf[/URL] (board@betastuf.nl). They meet approximately every six weeks to discuss faculty-wide policy with all the different organs, to enable students to voice a joint opinion on faculty matters.  \r\n\r\n[h2]University Wide[/h2]\r\n\r\n[h3]University Council[/h3]\r\n\r\nWhere the faculty council is the council that advises the faculty board, the university council is the council that advises the central board of the university. The council consists of twelve staff and twelve student member, elected by their peers every two years and one year respectively (via a Progress election).\r\n\r\n[h3]Executive Board of the University[/h3]\r\n\r\nThe central board of the university is responsible for managing the university. Topics that are discussed here are the strategy plan of the university, the university finances and for example approving the faculty regulations and tackling university-wide problems. The board of the university consists of five members: a president, a vice-president and the rector magnificus, an advising student member and a secretary.\r\n	\N
68	0	Bestuur IX	[samenvatting]Bestuur IX (2001/2002)[/samenvatting]\r\n[h1]Coverbestuur IX[/h1]\r\n____BESTUURSFOTO____\r\n[h2]Leden[/h2]\r\nVoorzitter: Liesbeth\r\nSecretaris: Berto Bojink\r\nPenningmeester: Gerben Blom\r\nCommissaris Intern: Daan Reid\r\nCommissaris Extern: Douwe Terluin		
50	26	Commissiepagina ComExA	[samenvatting]De belangrijkste taak van de ComExA is om contact te leggen en te behouden met bedrijven.[/samenvatting]\r\n\r\nComExA staat voor Committee of External Affairs. Deze commissie ondersteunt de commissaris Extern door contact te leggen en te behouden met bedrijven gerelateerd aan ons vakgebied. Dit is belangrijk voor zowel de vereniging als de leden. Cover heeft sponsoring nodig om toffe dingen te organiseren. Jij krijgt de kans een beeld van een bedrijf te krijgen en misschien zelfs een afstudeer plaats of baan te vinden. \r\n	[samenvatting]The most important task of the ComExa is to make contact with companies.[/samenvatting]\r\n\r\nComExA stands for Committee of External Affairs. This committee supports the commissioner of external affairs by making and keeping contact with companies. This is important for both the members and the association. Cover needs sponsorship to do all kind of awesome things. You get the chance to get to know a company and maybe even find a company for a final project or for a job.\r\n	\N
14	14	Commissiepagina BoA	[samenvatting]De Raad van Advies adviseert het bestuur, zodat zij het wiel niet opnieuw uit hoeven vinden.[/samenvatting]\r\n\r\nElk jaar wisselt Cover van bestuur. En elk jaar komt er weer een oud-bestuur met heel veel ervaring bij. Om dit nieuwe bestuur te ondersteunen en om de kennis van de oude besturen niet verloren te laten gaan, bestaat er de Raad van Advies. Hierin nemen oud-bestuursleden plaats en zij stellen zich ten doel het bestuur daar waar nodig te voorzien van zowel gevraagd als ongevraagd advies.	[samenvatting]The Board of Advisors advises the board, so they don't have to reinvent the wheel.[/samenvatting]\r\n\r\nEvery year, the Cover board changes. And every year, the group of former board members with lots of experience grows. To support the new board and to prevent the loss of the cumulative knowledge of previous boards, we have the Board of Advisors. This committee consists of former board members, who aim to supply the board with solicited as well as unsolicited advice when they deem it necessary.	\N
26	3	Boeken bestellen	[h1]Boeken bestellen[/h1]\r\nWelkom bij onze boekenwebwinkel. De webwinkel is te vinden door [url=https://cover.itdepartment.nl/nl/home]hier[/url], of op de knop hieronder, te klikken.\r\n\r\nAls je een vraag hebt, stuur dan gerust een mail naar education@svcover.nl.\r\n\r\n[b]Om de webshop te gebruiken heb je een account bij IT Department nodig (ondanks de misleidende tekst op de website die suggereert dat de registratie op de website eentje bij Cover is, gaat het in feite om een registratie voor het webwinkelsysteem, dat gebruik maakt van een ander account).[/b]\r\n\r\nZodra je geregistreerd bent bij IT Department, kun je je boeken bestellen. Ben je op zoek naar een boek wat niet op de lijst staat, maar wel nodig is voor één van je vakken? Stuur dan een email naar het bestuur op education@svcover.nl. We zullen dan kijken of we dit boek alsnog voor je kunnen regelen.\r\n\r\n[b] Let op: je hebt waarschijnlijk niet ieder boek nodig wat voor jouw jaar in het lijstje staat. Het bestuur neemt aan dat je zelf nadenkt over de boeken die je nodig hebt. Als je per ongeluk boeken bestelt die je niet nodig hebt (en hiervoor dus betaalt), kun je deze boeken retourneren via de optie "Retouren" in je webwinkel account. [/b]	[h1]Order books[/h1]\r\nWelcome to the book webstore. You can find the webstore by clicking [url=https://cover.itdepartment.nl/en/home]here[/url], or on the button below.\r\n\r\nIf you have any questions, please send a mail to education@svcover.nl.\r\n\r\n[b]In order to use the webshop, you will need to register an account with IT Department (despite misleading messages on the website that it is a registration for Cover, it in fact means the book shop of Cover, which uses a different account).[/b]\r\n \r\nAs soon as you have registered, you can order your books. Is the book that you need not on the list? Then please send an e-mail to the board on education@svcover.nl They might be able to arrange something for you.\r\n\r\n[b] Do note: you may not need every book that is listed for your year. The board assumes that you think about the books that you will be needing yourself. If you accidentally order a book (and pay for it) and it appears that you do not need the book, you can always return it via the "Returns" option in your webstore account. [/b]	\N
48	24	Commissiepagina Promotie	[samenvatting]De PubliciTee draagt zorg voor de posters van activiteiten. Dus heb je een activiteit, dan zorgen wij voor een passende poster![/samenvatting]\r\n\r\nDe PubliciTee is de commissie die posters voor je maakt of posters voor je drukt, eigenlijk de commissie die je ergens vanaf het moment dat je een poster nodig hebt, tot het moment dat je hem wilt drukken kunt inschakelen. Zo is er een beetje controle op alle posters en hoeven commissies die hun aandacht liever ergens anders hebben zich geen zorgen meer te maken over hoe het nou met de posters moet.\r\n\r\nHeeft jouw commissie een poster nodig? Vraag hem aan via onze [url=https://promotie.svcover.nl]website[/url]!\r\n	[samenvatting]The PubliciTee creates posters to promote Cover's activities. Do you organise an activity? We will make you an awesome poster![/samenvatting]\r\n\r\nThe PubliciTee is the committee that creates and/or prints posters for you. Actually, we are the committee that you can call somewhere in between the moment you need a poster and the moment you want to print it. In that way, the quality of the Cover posters is assured and committees are able to focus on other tasks, without having to worry about posters. \r\n\r\nDoes your committee need a poster? Request one on our [url=https://promotie.svcover.nl]website[/url]!\r\n	\N
124	43	SNiC		SNiC is an acronym for Stichting Nationaal informatica Congres (Foundation National Computer Science Conference). \r\nThis organisation was established in 2004 with the purpose of stimulating the interest in IT knowledge, IT applications and the business side of IT.  Every year, a conference with an IT related subject is organised. For each conference one study association is chosen to take care of the organisation.\r\n\r\nThis year the conference will take place at the 15th of november with Disruptive Technology as theme and is organised by 7 members of Cover.	\N
101	0	Nedap	Nedap is een fabrikant van intelligente technologische oplossingen voor relevante thema’s. Voldoende voedsel voor een groeiende bevolking, schoon drinkwater over de hele wereld, slimme netwerken voor duurzame energie zijn slechts een paar voorbeelden van onderwerpen waar Nedap zich mee bezighoudt.\n\nBij Nedap werken technici, productontwikkelaars, business developers en marketeers. Allemaal hebben zij hetzelfde doel: markten in beweging brengen met technologie die er toe doet! Het succes van Nedap is gebaseerd op creativiteit, fundamenteel begrip van technologie en elektronica, en een zeer goede samenwerking met onze klanten. Onze ideeën over markt en technologie vertalen wij in producten die over de hele wereld verkocht worden.\n\n[h2] Werken bij Nedap is snel schakelen [/h2]\nNedap heeft een open bedrijfscultuur die creativiteit en ondernemerschap stimuleert. De organisatie bestaat uit 10 marktgroepen met elk z’n eigen specialisme. Elke unit ontwikkelt voortdurend nieuwe ideeën en producten en vermarkt deze zelf. De kracht van Nedap is de interactie tussen de verschillende units. Door met je collega’s ideeën en knowhow uit te wisselen kun je binnen Nedap snel schakelen. \n\n[h2] Bij Nedap moet je het zelf maken [/h2]\nNedap biedt haar medewerkers de kans om te ondernemen in technologie die er toe doet. Door de platte organisatie is het niet de plaats in de hiërarchie die telt maar de kwaliteit van je argumenten. Eigen initiatief, doorzettingsvermogen en persoonlijk ondernemerschap zijn daarbij cruciaal. Persoonlijk ondernemerschap staat binnen Nedap voor het nemen van verantwoordelijkheid en het omzetten van ideeën in actie. \nNedap is altijd op zoek naar net die paar mensen die succesvol kunnen zijn bij ons bedrijf. Daarbij telt niet zozeer wat je de afgelopen jaren allemaal gedaan hebt, maar wat je de komende jaren nog wilt leren. Waar het om gaat is dat je je bij ons continu verder ontwikkelt en nieuwe inzichten verwerft. Dat is belangrijk voor ons en belangrijk voor jezelf. Als jij je daarin herkent, willen we graag met je praten. Voor alle actuele vacatures en een kijkje achter de schermen kijk op: www.lifeatnedap.com. Voor studenten bieden wij uitdagende stage- en afstudeermogelijkheden. Voor meer informatie neem contact op met Inge Meengs (inge.meengs@nedap.com). \n\nDe N.V. Nederlandsche Apparatenfabriek “Nedap” is opgericht in 1929, genoteerd aan de beurs sinds 1947 en is met ruim 680 medewerkers wereldwijd actief.\n\nVoor meer informatie, zie: http://www.nedap.com/nl\n	\N	\N
100	0	JUNIOR CONSULTANT SECURITY	[h1]JUNIOR CONSULTANT SECURITY(Groningen)[/h1]\n\n[h2] FUNCTIEOMSCHRIJVING [/h2]\nTNO helpt bedrijven, overheden en (semi-)publieke organisaties succesvol te innoveren met ICT. Hierbij staat de waardecreatie voor de klant centraal en ligt onze toegevoegde waarde in een combinatie van innovatieve kracht en diepgaande kennis. Wij benaderen innovatie integraal en praktisch.\n \nBedrijven worden in hun functioneren steeds meer afhankelijk van allerlei soorten informatie. In toenemende mate zien we dat die informatie opgeslagen is in gedistribueerde systemen en uitgewisseld wordt tussen verschillende partijen. Uit het oogpunt van informatiebeveiliging kunnen daardoor ongewenste situaties ontstaan.\n \nOm toch de gewenste vertrouwelijkheid, integriteit en beschikbaarheid te waarborgen moet een veelheid van maatregelen getroffen worden. Binnen de afdeling Security werken 30 professionals die beschikken over diepgaande kennis en praktische ervaring op het gebied van methodieken en technologieën om in iedere situatie een passend niveau van informatiebeveiliging te realiseren. Om gebruik te kunnen maken van de nieuwste technologische ontwikkelingen zijn er verschillende samenwerkingsverbanden. Voorbeelden hiervan zijn de deelname in internationale onderzoeksprogramma’s en de samenwerking met de universiteit Twente en Philips Natlab binnen het NVSO. Voorbeelden hiervan zijn de deelname in internationale onderzoeksprogramma’s en de samenwerking met diverse universiteiten en bedrijfslaboratoria.\n\n[h3] Functieomschrijving [/h3]\nWe zoeken een nieuwe collega voor de functie Junior Consultant Security binnen het expertisegebied Technical Sciences. Daarin werken wij bevlogen samen aan technologische doorbraken ten behoeve van innovaties in belangrijke maatschappelijke en economische thema’s. Onze projecten hebben veelal een grote internationale impact en we beschikken over unieke faciliteiten.\n \nWij ondersteunen klanten bij het inrichten en beheren van security en business continuity op basis van een systematische aanpak en weloverwogen keuzes. Ons aandachtsgebied strekt zich uit van de beveiliging van IT-systemen en  -netwerken, tot die van telecommunicatienetwerken en -services.\nBedrijven worden steeds meer afhankelijk van informatie- en communicatietechnologie. Daarbij zijn maatregelen nodig om de vertrouwelijkheid, integriteit en beschikbaarheid van informatie te waarborgen. Doordat ICT voortdurend complexer wordt is beveiliging een grote uitdaging geworden die diepgaande kennis en ervaring van actuele beveiligingsmethoden en -technologieën vereist.\n\n[h2] FUNCTIE-EISEN [/h2]\nAcademisch werk- en denk niveau bij voorkeur richting Informatica of Elektrotechniek;\nAffiniteit met Informatiebeveiliging.\nWerken bij TNO is niet voor iedereen weggelegd. Je bent er niet met een mooi diploma en een keurige cijferlijst. Je wordt bij TNO continu gestimuleerd om alles uit jezelf te halen, je komt in een klimaat te werken dat alles vraagt van je talent en een aantal belangrijke karaktereigenschappen. Het werk bij TNO vraagt veel verschillende kwaliteiten: je moet beschikken over een sterk ontwikkeld analytisch vermogen, je moet uitstekend met mensen overweg kunnen en je moet in je activiteiten altijd het belang van de klant en het resultaat voorop blijven stellen. Bovendien kom je als nieuwe medewerker al vrij snel terecht in een functie met veel verantwoordelijkheid. Daar moet je, zowel zelfstandig als in teamverband, goed mee kunnen omgaan.\n\nVoor deze positie moet een screening bij de AIVD worden ondergaan. Deze screening vereist dat men dient te beschikken over een Nederlandse nationaliteit. \nBEDRIJFSINFORMATIE\nTNO is een onafhankelijke onderzoeksorganisatie die op basis van haar expertise en onderzoek een belangrijke bijdrage levert aan de concurrentiekracht van bedrijven en organisaties, aan de economie en aan de kwaliteit van de samenleving als geheel. Met zo’n 3500 medewerkers werken we voor uiteenlopende opdrachtgevers: overheden, het mkb, grote bedrijven, dienstverleners en maatschappelijke organisaties. Samen werken we aan nieuwe kennis, betere producten, heldere adviezen over beleid en processen. Bij alles wat we doen, draait het om impact. Onze product- en procesinnovaties en onze adviezen hebben pas écht zin als de opdrachtgever daarmee zijn concurrentiepositie kan verbeteren. Als de overheid daarmee haar beleid doelmatiger kan inrichten. En als het mensen en organisaties daadwerkelijk verder helpt.\n\n[h2] ARBEIDSVOORWAARDEN [/h2]\nWerken met plezier betekent voor iedereen iets anders. TNO wil invulling geven aan jouw balans tussen werk en privé. Jij wilt werken aan je carrière en tegelijkertijd een leuk sociaal leven leiden. Daarom bieden wij bijvoorbeeld arbeidsvoorwaarden à-la-carte, waarbij je gedurende het kalenderjaar diverse arbeidsvoorwaarden tegen elkaar kunt uitruilen. Flexibele werktijden en verschillende verlofregelingen geven je ook veel keuzevrijheid, zo kun je zelf een pakket creëren dat optimaal aansluit bij jouw situatie. Vanzelfsprekend werk je voor een goed salaris en uitstekende secundaire arbeidsvoorwaarden.\nTNO is dé innovatie- en kennisorganisatie bij uitstek. Dat betekent dat we ook veel waarde hechten aan jouw persoonlijke en professionele ontwikkeling. Er zijn vele mogelijkheden om met je eigen ontwikkeling aan de slag te gaan: congressen, opleidingen, workshops, coaching, intervisie, mentoring en functiewisseling. Je hebt veel inbreng in de snelheid en richting van jouw eigen ontwikkeling.\n\n[h2] CONTACTINFORMATIE [/h2]\nManager, Paul de Jager, 088 - 866 7721\nContactpersoon HR: Recruiter, Ilona van Maanen, 06 - 4801 3968	[h1]JUNIOR CONSULTANT SECURITY(Groningen)[/h1]\n\n[h2] FUNCTIEOMSCHRIJVING [/h2]\nTNO helpt bedrijven, overheden en (semi-)publieke organisaties succesvol te innoveren met ICT. Hierbij staat de waardecreatie voor de klant centraal en ligt onze toegevoegde waarde in een combinatie van innovatieve kracht en diepgaande kennis. Wij benaderen innovatie integraal en praktisch.\n \nBedrijven worden in hun functioneren steeds meer afhankelijk van allerlei soorten informatie. In toenemende mate zien we dat die informatie opgeslagen is in gedistribueerde systemen en uitgewisseld wordt tussen verschillende partijen. Uit het oogpunt van informatiebeveiliging kunnen daardoor ongewenste situaties ontstaan.\n \nOm toch de gewenste vertrouwelijkheid, integriteit en beschikbaarheid te waarborgen moet een veelheid van maatregelen getroffen worden. Binnen de afdeling Security werken 30 professionals die beschikken over diepgaande kennis en praktische ervaring op het gebied van methodieken en technologieën om in iedere situatie een passend niveau van informatiebeveiliging te realiseren. Om gebruik te kunnen maken van de nieuwste technologische ontwikkelingen zijn er verschillende samenwerkingsverbanden. Voorbeelden hiervan zijn de deelname in internationale onderzoeksprogramma’s en de samenwerking met de universiteit Twente en Philips Natlab binnen het NVSO. Voorbeelden hiervan zijn de deelname in internationale onderzoeksprogramma’s en de samenwerking met diverse universiteiten en bedrijfslaboratoria.\n\n[h3] Functieomschrijving [/h3]\nWe zoeken een nieuwe collega voor de functie Junior Consultant Security binnen het expertisegebied Technical Sciences. Daarin werken wij bevlogen samen aan technologische doorbraken ten behoeve van innovaties in belangrijke maatschappelijke en economische thema’s. Onze projecten hebben veelal een grote internationale impact en we beschikken over unieke faciliteiten.\n \nWij ondersteunen klanten bij het inrichten en beheren van security en business continuity op basis van een systematische aanpak en weloverwogen keuzes. Ons aandachtsgebied strekt zich uit van de beveiliging van IT-systemen en  -netwerken, tot die van telecommunicatienetwerken en -services.\nBedrijven worden steeds meer afhankelijk van informatie- en communicatietechnologie. Daarbij zijn maatregelen nodig om de vertrouwelijkheid, integriteit en beschikbaarheid van informatie te waarborgen. Doordat ICT voortdurend complexer wordt is beveiliging een grote uitdaging geworden die diepgaande kennis en ervaring van actuele beveiligingsmethoden en -technologieën vereist.\n\n[h2] FUNCTIE-EISEN [/h2]\nAcademisch werk- en denk niveau bij voorkeur richting Informatica of Elektrotechniek;\nAffiniteit met Informatiebeveiliging.\nWerken bij TNO is niet voor iedereen weggelegd. Je bent er niet met een mooi diploma en een keurige cijferlijst. Je wordt bij TNO continu gestimuleerd om alles uit jezelf te halen, je komt in een klimaat te werken dat alles vraagt van je talent en een aantal belangrijke karaktereigenschappen. Het werk bij TNO vraagt veel verschillende kwaliteiten: je moet beschikken over een sterk ontwikkeld analytisch vermogen, je moet uitstekend met mensen overweg kunnen en je moet in je activiteiten altijd het belang van de klant en het resultaat voorop blijven stellen. Bovendien kom je als nieuwe medewerker al vrij snel terecht in een functie met veel verantwoordelijkheid. Daar moet je, zowel zelfstandig als in teamverband, goed mee kunnen omgaan.\n\nVoor deze positie moet een screening bij de AIVD worden ondergaan. Deze screening vereist dat men dient te beschikken over een Nederlandse nationaliteit. \nBEDRIJFSINFORMATIE\nTNO is een onafhankelijke onderzoeksorganisatie die op basis van haar expertise en onderzoek een belangrijke bijdrage levert aan de concurrentiekracht van bedrijven en organisaties, aan de economie en aan de kwaliteit van de samenleving als geheel. Met zo’n 3500 medewerkers werken we voor uiteenlopende opdrachtgevers: overheden, het mkb, grote bedrijven, dienstverleners en maatschappelijke organisaties. Samen werken we aan nieuwe kennis, betere producten, heldere adviezen over beleid en processen. Bij alles wat we doen, draait het om impact. Onze product- en procesinnovaties en onze adviezen hebben pas écht zin als de opdrachtgever daarmee zijn concurrentiepositie kan verbeteren. Als de overheid daarmee haar beleid doelmatiger kan inrichten. En als het mensen en organisaties daadwerkelijk verder helpt.\n\n[h2] ARBEIDSVOORWAARDEN [/h2]\nWerken met plezier betekent voor iedereen iets anders. TNO wil invulling geven aan jouw balans tussen werk en privé. Jij wilt werken aan je carrière en tegelijkertijd een leuk sociaal leven leiden. Daarom bieden wij bijvoorbeeld arbeidsvoorwaarden à-la-carte, waarbij je gedurende het kalenderjaar diverse arbeidsvoorwaarden tegen elkaar kunt uitruilen. Flexibele werktijden en verschillende verlofregelingen geven je ook veel keuzevrijheid, zo kun je zelf een pakket creëren dat optimaal aansluit bij jouw situatie. Vanzelfsprekend werk je voor een goed salaris en uitstekende secundaire arbeidsvoorwaarden.\nTNO is dé innovatie- en kennisorganisatie bij uitstek. Dat betekent dat we ook veel waarde hechten aan jouw persoonlijke en professionele ontwikkeling. Er zijn vele mogelijkheden om met je eigen ontwikkeling aan de slag te gaan: congressen, opleidingen, workshops, coaching, intervisie, mentoring en functiewisseling. Je hebt veel inbreng in de snelheid en richting van jouw eigen ontwikkeling.\n\n[h2] CONTACTINFORMATIE [/h2]\nManager, Paul de Jager, 088 - 866 7721\nContactpersoon HR: Recruiter, Ilona van Maanen, 06 - 4801 3968	\N
24	0	Alumni	[h1]Alumni[/h1]\n\n[h2]Kunstmatige Intelligentie[/h2]\nSinds 2005 heeft de studie Kunstmatige Intelligentie (voorheen Technische Cognitiewetenschappen) een zelfstandige alumnivereniging genaamd Axon.\n\nBen jij alumnus van Kunstmatige Intelligentie of Technische Cognitiewetenschappen en ben je benieuwd naar het reilen en zeilen van de studie en je oud-studiegenoten? Wordt dan nu lid van Axon. De lidmaatschapskosten bedragen slechts 5 euro per jaar.\n\nVoor meer informatie, kijk op onze site [url=http://www.axonline.nl]http://www.axonline.nl[/url] of mail naar [url=mailto:info@axonline.nl]info@axonline.nl[/url].\n\n[h2]Alumni Informatica[/h2]\nDe alumnivereniging voor Computing Science heet Invariant. Zij zijn eind 2013 opgericht en sindsdien staan zij open voor alle (oud) Computing Science studenten met een Bachelor of Master in Comuting Science.\n\nJe kan lid worden voor maar 10 euro per jaar en ze organiseren jaarlijke uitstapjes naar leuke bedrijven. Meer informatie kan je op de [url=http://invariant.nl/]website[/url] vinden.\n	[h1]Alumni[/h1]\n\n[h2]Artificial Intelligence[/h2]\nSince 2005 the study Artificial Intelligence (previously named Technische Cognitiewetenschappen) an independent alumni association named Axon.\n\nAre you an alumnus of Artificial Intelligence or Technische Cognitiewetenschappen and interested in the ins and outs of the study and your fellow old students? Become a member of Axon. The membership fee is only 5 euros per year.\n\nFor more information go to our website [url=http://www.axonline.nl]http://www.axonline.nl[/url] or send an email to [url=mailto:info@axonline.nl]info@axonline.nl[/url].\n\n[h2]Computer Science[/h2]\nThe alumni association for Computing Science is called Invariant. Since the end of 2013 they are open for all the old Computing Science students of the University of Groningen. \n\nIf you have a bachelor's or master's degree in Computing Science, you can become a member for only 10 euros per year.\n\nFor more information go to [url=http://invariant.nl/]the website of Invariant[/url].\n	\N
102	0	TNO	[h1]TNO[/h1]\r\n\r\n[h2]DISCOVER TNO[/h2]\r\nDo you want to do ground-breaking work in multidisciplinary projects, seek out new knowledge? Do you want to help customers with innovative, practical and smart solutions? Are you ambitious, energetic, a thinker and a doer?\r\n\r\n[h2]FLYING START[/h2]\r\nAs a starter at TNO you have many options. You can specialize or you can develop in a broader sense. You could become a consultant or top researcher. Or maybe you see yourself as an R&amp;D engineer or leading a project. TNO offers you fantastic prospects in which you have a great deal of freedom and responsibility in shaping your career. From the moment you join us, your career development is in the spotlight, in part through the special Talent Development Program or the Traineeship. As a new colleague you get an extensive\r\nintroduction and education program so that you can feel quickly at home at TNO and get off to a flying start.\r\n\r\n[h2]YOUR DEVELOPMENT[/h2]\r\nTNO is an innovation and knowledge organization without parallel. This means that we also attach great value to your personal and professional development. From the moment you join us, your career development is in the spotlight and as a new colleague you get an extensive introduction program. An important part of this is training in self-management so that you can steer your career path in a specific direction, such as applied technology, commerce, consultancy, project or line management or corporate support. \r\n\r\nInterested? Check your possibilities at [url=www.tno.nl/career]www.tno.nl/career[/url]\r\n\r\nRecruiter:\r\nYvonne Pribnow\r\nYvonne.pribnow@tno.nl\r\n06 11097594	[h1]TNO[/h1]\r\n\r\n[h2]DISCOVER TNO[/h2]\r\nDo you want to do ground-breaking work in multidisciplinary projects, seek out new knowledge? Do you want to help customers with innovative, practical and smart solutions? Are you ambitious, energetic, a thinker and a doer?\r\n\r\n[h2]FLYING START[/h2]\r\nAs a starter at TNO you have many options. You can specialize or you can develop in a broader sense. You could become a consultant or top researcher. Or maybe you see yourself as an R&amp;D engineer or leading a project. TNO offers you fantastic prospects in which you have a great deal of freedom and responsibility in shaping your career. From the moment you join us, your career development is in the spotlight, in part through the special Talent Development Program or the Traineeship. As a new colleague you get an extensive\r\nintroduction and education program so that you can feel quickly at home at TNO and get off to a flying start.\r\n\r\n[h2]YOUR DEVELOPMENT[/h2]\r\nTNO is an innovation and knowledge organization without parallel. This means that we also attach great value to your personal and professional development. From the moment you join us, your career development is in the spotlight and as a new colleague you get an extensive introduction program. An important part of this is training in self-management so that you can steer your career path in a specific direction, such as applied technology, commerce, consultancy, project or line management or corporate support. \r\n\r\nInterested? Check your possibilities at [url=www.tno.nl/career]www.tno.nl/career[/url]\r\n\r\nRecruiter:\r\nYvonne Pribnow\r\nYvonne.pribnow@tno.nl\r\n06 11097594	\N
0	11	Bestuurspagina	[H1]Coverbestuur XXV (2016–2017)[/H1]\r\n\r\nWil je contact opnemen met Cover? Zie de [url=show.php?id=17]de manieren waarop je ons kunt bereiken[/url].\r\n\r\n[b]Mail[/b]\r\nHet hele bestuur - bestuur@svcover.nl\r\nKevin Gevers - chairman@svcover.nl\r\nYannick Stoffers - secretary@svcover.nl & education@svcover.nl\r\nNico Stegeman - treasurer@svcover.nl\r\nMarie-Claire Lankhorst - intern@svcover.nl\r\nMaureen van der Grinten - extern@svcover.nl	[H1]Board XXVI of Cover (2017–2018)[/H1]\r\n\r\nWant to contact Cover? Please see [url=show.php?id=17]the ways you can contact us[/url].\r\n\r\n[b]Mail[/b]\r\nThe complete board - board@svcover.nl\r\nKevin Gevers - chairman@svcover.nl\r\nYannick Stoffers - secretary@svcover.nl & education@svcover.nl\r\nNico Stegeman - treasurer@svcover.nl\r\nMarie-Claire Lankhorst - intern@svcover.nl\r\nMaureen van der Grinten - extern@svcover.nl\r\n	\N
23	2	De Studie	[h1]Artificial Intelligence[/h1]\nBij Artificial Intelligence aan de RuG houden we ons bezig met zowel menselijke als kunstmatige intelligentie. Aan de ene kant wordt er gebruik gemaakt van computermodellen om meer kennis te krijgen van hoe de menselijke geest werkt. Aan de andere kant wordt er gebruik gemaakt van de kennis die we al hebben over de menselijke geest om intelligente en gebruiksvriendelijke programma's te ontwikkelen.\n\nMeer informatie over de studie vind je op [url=http://www.rug.nl/bachelors/artificial-intelligence/] deze site[/url].\n\n[h2] Praktische informatie en studieondersteuning [/h2]\nJe rooster is te vinden op [url=http://www.rug.nl/fwn/roosters/2014/ki/] deze site [/url]. Controleer wel even het jaartal bovenaan de pagina! Meer praktische informatie vind je [url=https://www.svcover.nl/show.php?id=27] hier [/url]. Op diezelfde pagina zijn ook allerlei opties voor studieondersteuning te vinden. Samenvattingen en oude tentamens kun je [url=https://studysupport.svcover.nl/] hier [/url] vinden. Is er iets nog niet duidelijk of persoonlijk advies nodig? Ga dan even langs bij je studieadviseur, [url=http://www.rug.nl/staff/r.m.van.der.kaaij/]Rachel van der Kaaij[/url]\n\n[h2] Studeren in het buitenland [/h2]\nTijdens je studie is het ook mogelijk om voor een bepaalde tijd, bijvoorbeeld tijdens je master, in het buitenland te studeren. Voor meer informatie kun je het beste naar de exchange office gaan. Deze vind je op het volgende adres: Bernoulliborg, kamer 5161.0050, Nijenborgh 9, 9747 AG Groningen. Je kan ook contact opnemen via de mail met Eloïse Daumerie (e.daumerie@rug.nl) of Henriëtte Mulder(henriette.mulder@rug.nl).\n\n[h2] Afstuderen en na je studie [/h2]\nBen je opzoek naar een afstudeerstage? Kijk dan op [url=https://www.svcover.nl/show.php?id=31]deze pagina[/url]! Ben je opzoek naar een baan? Kijk dan [url=https://www.svcover.nl/show.php?id=54]hier[/url]! Je kan ook altijd contact opnemen met het bestuur (board@svcover.nl), zij weten meer bedrijven die nieuwe werknemers zoeken. \n	[h1]The Study[/h1]\nAt Artificial Intelligence at the RUG we focus on both human as well as artificial intelligence. On one hand we use computer models to gain more knowledge of how the human mind works. On the other hand we use the knowledge of the human mind we already have to develop intelligent and usable computer programs.\n\nFor more information about the study go to http://www.rug.nl/bachelors/artificial-intelligence/\n	\N
131	11	Board XXV: BOLD	[samenvatting]2016/2017\r\n"BOLD"[/samenvatting]\r\n[h1]Bestuur XXV: BOLD[/h1]\r\n[small]Foto: Martijn Luinstra, Annet Onnes[/small]\r\n\r\n[h2]Leden[/h2]\r\nVoorzitter: Tineke Jelsma\r\nSecretaris: Jelle Egbers\r\nPenningmeester: Stijn Kramer\r\nCommissaris Intern: Lisa Deckers\r\nCommissaris Extern: Robin Twickler	[samenvatting]2016/2017\r\n"BOLD"[/samenvatting]\r\n[h1]Board XXV: BOLD[/h1]\r\n[small]Photo: Martijn Luinstra, Annet Onnes[/small]\r\n\r\n[h2]Members[/h2]\r\nChairman: Tineke Jelsma\r\nSecretary: Jelle Egbers\r\nTreasurer: Stijn Kramer\r\nCommissioner of Internal Affairs: Lisa Deckers\r\nCommissioner of External Affairs: Robin Twickler	\N
123	11	Board XXIV: Evolution	[samenvatting]2015/2016\r\n"Evolution"[/samenvatting]\r\n[h1]Bestuur XXIV: Evolution[/h1]\r\n[small]Foto: Martijn Luinstra, Annet Onnes[/small]\r\n\r\n[h2]Leden[/h2]\r\nVoorzitter: Jonathan Hogervorst\r\nSecretaris: Annet Onnes\r\nPenningmeester: Jip Maijers\r\nCommissaris Intern: Sanne Bouwmeester\r\nCommissaris Extern: Diederik Eilers	[samenvatting]2015/2016\r\n"Evolution"[/samenvatting]\r\n[h1]Board XXIV: Evolution[/h1]\r\n[small]Photo: Martijn Luinstra, Annet Onnes[/small]\r\n\r\n[h2]Members[/h2]\r\nChairman: Jonathan Hogervorst\r\nSecretary: Annet Onnes\r\nTreasurer: Jip Maijers\r\nCommissioner of Internal Affairs: Sanne Bouwmeester\r\nCommissioner of External Affairs: Diederik Eilers	\N
51	0	Bedrijvenpagina	[h1]Bedrijven[/h1]\r\n\r\n[h2]ASML[/h2]\r\n[url=https://www.asml.com][img.company-logo=/documenten/ASML_Holding_N.V._Be Part of Progress_logo_blauw_Internet_41247.jpg][/url] [b]BE PART OF PROGRESS [/b] \r\nWe make machines that make chips; the hearts of the devices that keep us informed, entertained and safe. Devices that improve our quality of life and help to tackle the world’s toughest problems. We build some of the most amazing machines that you will ever see, and the software to run them. Never satisfied, we measure our performance in units that begin with pico or nano. [url=https://www.svcover.nl/show.php?view=read&id=138]Read more...[/url]\r\n\r\n[h2]TNO[/h2]\r\n[url=http://www.tno.nl/career][img.company-logo=/images/banners/tno.jpg][/url] [b]DISCOVER TNO[/b]\r\nDo you want to do ground-breaking work in multidisciplinary projects, seek out new knowledge? Do you want to help customers with innovative, practical and smart solutions? Are you ambitious, energetic, a thinker and a doer?[url=/show.php?id=102]Read more...[/url]\r\n\r\n[h2]Holland Trading Group[/h2]\r\n[url=https://www.hollandtradinggroup.com/][img.company-logo=/documenten/HTG - Logo.png][/url] Holland Trading Group is a leading international distributor and wholesaler of high-end branded products. With its global network of subsidiaries and a network of strategically located warehouses around the world, HTG offers a competitive, worldwide advantage in adding value to its customers. The combination of these warehouses and subsidiaries with state of the art IT and cost efficient process enables us to realize competitively priced global brand coverage. [url=https://www.svcover.nl/show.php?view=read&id=133]Read more...[/url]\r\n\r\n[h2]ING[/h2]\r\n[url=https://www.ing.jobs/Global/Careers.htm/][img.company-logo=/documenten/ING_Logo_FC_A5_digitalprinting.jpg][/url]With 40 million customers depending on our technologies, ING is Europe’s biggest online bank and the largest employer of IT professionals in The Netherlands. ING puts IT at the centre of everything we do. In your four-year traineeship you’ll learn from the industry’s most talented and experienced specialists, working on important and large scale projects with the best technologies and latest methodologies. You will make a difference. You’ll also be provided intensive training and ongoing support to expand and evolve your skills. Add the complexity of working across international environments and the challenges and rewards of an IT career at ING quickly stack up.[url=https://www.svcover.nl/show.php?view=read&id=135]Read more...[/url]\r\n\r\n[h2]Dataprovider.com[/h2]\r\n[url=https://www.dataprovider.com/][img.company-logo=/documenten/dataprovider-logo-dark.png][/url] We are Dataprovider.com, a leading data science company from Groningen. Our knowledge, hard work and passion for data have enabled us to extract and structure data from over 280 million websites from 50 different countries every month. [url=https://www.svcover.nl/show.php?view=read&id=134]Read more...[/url]\r\n\r\n[h2]Quintor[/h2]\r\n[url=http://www.quintor.nl][img.company-logo=/images/banners/quintor.jpg][/url]Quintor is een toonaangevend bedrijf op het gebied van Agile software development, enterprise Java / .NET technologie en mobile development. Wij hebben sinds onze oprichting in 2005 een gezonde groei doorgemaakt. Vanuit onze vestigingen in Groningen en Amersfoort ondersteunen wij onze klanten bij de uitdagingen die grootschalige enterprise projecten met zich meebrengen. Quintor beschikt over een software factory waarin wij inhouse projecten voor onze klanten uitvoeren. [url=/show.php?id=103]meer...[/url]\r\n\r\n[h2]Topicus[/h2]\r\n[url=https://www.werkenbijtopicus.nl/][img.company-logo=/documenten/logo-Topicus-PMS-Blauw.JPG][/url] Nergens wordt zo enthousiast gewerkt aan digitale oplossingen die de zelfredzaamheid van de burger vergroten. Wij richten ons op belangrijke maatschappelijke vlakken als Zorg, Finance, Overheid, Legal, Security en Onderwijs. Bijna iedereen in Nederland komt in aanraking met onze nuttige en vooruitstrevende digitale producten. [url=https://www.svcover.nl/show.php?view=read&id=137]Lees meer...[/url]	[h1]Companies[/h1]\r\n\r\n[h2]TNO[/h2]\r\n[url=http://www.tno.nl/career][img.company-logo=/images/banners/tno.jpg][/url] [b]DISCOVER TNO[/b]\r\nDo you want to do ground-breaking work in multidisciplinary projects, seek out new knowledge? Do you want to help customers with innovative, practical and smart solutions? Are you ambitious, energetic, a thinker and a doer?[url=/show.php?id=102]Read more...[/url]\r\n\r\n[h2]Holland Trading Group[/h2]\r\n[url=https://www.hollandtradinggroup.com/][img.company-logo=/documenten/HTG - Logo.png][/url] Holland Trading Group is a leading international distributor and wholesaler of high-end branded products. With its global network of subsidiaries and a network of strategically located warehouses around the world, HTG offers a competitive, worldwide advantage in adding value to its customers. The combination of these warehouses and subsidiaries with state of the art IT and cost efficient process enables us to realize competitively priced global brand coverage. [url=https://www.svcover.nl/show.php?view=read&id=133]Read more...[/url]\r\n\r\n[h2]ING[/h2]\r\n[url=https://www.ing.jobs/Global/Careers.htm/][img.company-logo=/documenten/ING_Logo_FC_A5_digitalprinting.jpg][/url]With 40 million customers depending on our technologies, ING is Europe’s biggest online bank and the largest employer of IT professionals in The Netherlands. ING puts IT at the centre of everything we do. In your four-year traineeship you’ll learn from the industry’s most talented and experienced specialists, working on important and large scale projects with the best technologies and latest methodologies. You will make a difference. You’ll also be provided intensive training and ongoing support to expand and evolve your skills. Add the complexity of working across international environments and the challenges and rewards of an IT career at ING quickly stack up.[url=https://www.svcover.nl/show.php?view=read&id=135]Read more...[/url]\r\n\r\n[h2]Dataprovider.com[/h2]\r\n[url=https://www.dataprovider.com/][img.company-logo=/documenten/dataprovider-logo-dark.png][/url] We are Dataprovider.com, a leading data science company from Groningen. Our knowledge, hard work and passion for data have enabled us to extract and structure data from over 280 million websites from 50 different countries every month. [url=https://www.svcover.nl/show.php?view=read&id=134]Read more...[/url]\r\n\r\n[h2]ASML[/h2]\r\n[url=https://www.asml.com][img.company-logo=/documenten/ASML_Holding_N.V._Be Part of Progress_logo_blauw_Internet_41247.jpg][/url] [b]BE PART OF PROGRESS [/b] \r\nWe make machines that make chips; the hearts of the devices that keep us informed, entertained and safe. Devices that improve our quality of life and help to tackle the world’s toughest problems. We build some of the most amazing machines that you will ever see, and the software to run them. Never satisfied, we measure our performance in units that begin with pico or nano. [url=https://www.svcover.nl/show.php?view=read&id=138]Read more...[/url]\r\n\r\n[h2]Quintor[/h2]\r\n[url=http://www.quintor.nl][img.company-logo=https://www.svcover.nl/images/banners/quintor.jpg][/url] Quintor is a leading player in the fields of Agile software development, enterprise Java / .Net technology and mobile development. Since its foundation in the year 2005, Quintor has been growing steadily. From our locations in Groningen and Amersfoort, we provide support to our customers in facing the challenges that large-scale enterprise projects entail. Quintor has a software factory at its disposal, from where inhouse projects are carried out. [url=https://www.svcover.nl/show.php?id=103]more...[/url]\r\n\r\n[h2]Topicus[/h2]\r\n[url=https://www.werkenbijtopicus.nl/][img.company-logo=/documenten/logo-Topicus-PMS-Blauw.JPG][/url] Nowhere else are people working so enthusiastically on solutions that increase people’s selfreliance. We focus on important social sectors such as Healthcare, Finance, Legal, Government and Education. Nearly everyone in the Netherlands will encounter our useful and innovative digital products from time to time.[url=https://www.svcover.nl/show.php?view=read&id=137]Read More...[/url]\r\n	
11	11	Commissiepagina ABCee	[samenvatting]De Academic Broadening Committee (ABCee) organiseert regelmatig studiegerelateerde activiteiten, zoals lezingen en excursies naar KI- en Informatica gerelateerde bedrijven.[/samenvatting]\r\n\r\n[b]De Academic Broadening Committee (ABCee) organiseert regelmatig studiegerelateerde activiteiten, zoals lezingen, excursies naar KI- en Informatica gerelateerde bedrijven en natuurlijk jaarlijks een reis naar een buitenlandse stad.[/b]\r\n\r\nAls Informatica- of KI-student kom je regelmatig in aanraking met onderzoek: de docenten van je vakken (en andere wetenschappers) zijn enthousiast over hun werk. De kans is echter groot dat je na het behalen van je bul geen onderzoeksbaan krijgt (of wilt) en daarom is het belangrijk om te weten wat je verder met je opleiding kunt doen.\r\n\r\nHierom probeert de ABCee studiegerelateerde activiteiten te organiseren. Op deze manier blijf je op de hoogte van de huidige ontwikkelingen binnen Informatica en KI, kom je te weten waar je interesses liggen en waar je uiteindelijk zou willen werken na je afstuderen. Kom dus eens naar een van onze activiteiten en laat je verrassen door de toekomstperspectieven die KI en Informatica bieden!\r\n	[samenvatting]The Academic Broadening Committee regularly arranges study-related activities such as lectures and trips to AI- and CS-related companies and institutes.[/samenvatting]\r\n\r\n[b]The Academic Broadening Committee (ABCee) regularly organises study-related activities such as lectures, trips to AI- and CS-related companies and institutes, and of course a yearly trip to a city in a foreign country.[/b]\r\n\r\nAs a student of the course Computer Science or Artificial Intelligence you will regularly come in contact with research. Your courses' teachers are all scientists and very enthusiastic about their research. However, many of us will not (want to) get a job at a university after graduation, which is why it is important to know what else you can do with your degree.\r\n\r\nThis is why the ABCee organizes a study-related activity every one to two weeks. This way, you get to know about what's currently going on in the fields of AI and CS, which parts interest you, and where you might eventually want to start working after you graduate. So by all means come take a look at one of these activities and be surprised by all the things you can do with AI and CS.	\N
27	0	Student-info	[h1]Informatie voor studenten[/h1]\r\nOp deze pagina vind je een overzicht van (links naar) praktische informatie voor je studie en ter ondersteuning van je studie. Ben je opzoek naar informatie over je studie? Kijk [url=https://www.svcover.nl/show.php?id=23] hier [/url] voor Artificial Intelligence en [url=https://www.svcover.nl/show.php?id=41] hier [/url] voor Computing Science. Ben je opzoek naar een stage of afstudeerplaats kijk dan [url=https://www.svcover.nl/show.php?id=31] hier [/url] en voor banen kun je [url=https://www.svcover.nl/show.php?id=54] hier [/url] terecht.\r\n\r\n[h2] Praktische informatie [/h2]\r\n\r\nEerst een overzichtje van links die je voortdurend nodig hebt:\r\n- [url=https://nestor.rug.nl/]Nestor[/url]: het communicatie platform van alle vakken met de studenten. Hierop worden bijvoorbeeld de slides van de colleges en de practicumopdrachten geplaatst.\r\n- [url=https://www.rug.nl/ocasys/]Ocasys[/url]: De 'wet' voor regels met betrekking op de vakken. Hier staat bijvoorbeeld de enige waarheid over hoe je cijfer voor een vak berekend wordt.\r\n- [url=https://progresswww.nl/rug/]Progress[/url]: Hier schrijf je je in voor de vakken en vind je een overzicht van je cijfers. De cijfers die hier staan zijn je daadwerkelijke cijfers!\r\n\r\nRegels over studiefinanciering veranderen voortdurend. Voor de meest actuele informatie kun je het beste bij [url=https://duo.nl/particulieren/student-hbo-of-universiteit/studiefinanciering/weten-hoe-het-werkt.asp] Duo[/url]  terecht.\r\n\r\nVoor de meest uiteenlopende vragen over het aan/afmelden voor vakken en collegegeld kun je terecht bij de 'University Student Desk', ook wel de [url=http://www.rug.nl/education/usd/]USD[/url].\r\n\r\nIs er nu nog steeds iets waar je nergens een antwoord op kan vinden? Of spreek je liever met iemand in persoon op de Bernoulliborg? Twijfel dan niet om een Coverlid/het Coverbestuur, je mentor of je studieadviseur om hulp/het antwoord te vragen. Voor Artificial Intelligence is de studieadviseur Rachel van der Kaaij [url=https://rachelvdkaaij.youcanbook.me/](klik hier voor het maken van een afspraak)[/url] en voor Informatica Hanneke Niessink [url=https://jhniessink.youcanbook.me/](klik hier voor het maken van een afspraak)[/url].\r\n\r\n[h2] Studieondersteuning [/h2]\r\nSamenvattingen en oude tentamens kun je [url=https://studysupport.svcover.nl/]hier[/url] vinden. Als je nog andere hebt die er niet tussen staan dan ontvangen we ze graag! Je kan ze mailen naar studcee@svcover.nl of in onze verenigingskamer neerleggen.\r\n\r\nBen je niet opzoek naar bijles maar naar een ander soort cursus, zoals een cursus voor faalangst, het leren beantwoorden van meerkeuze tentamens, presenteren, artikelen schrijven of Engels, dan kun je terecht bij de [url=http://www.rug.nl/education/find-out-more/other-student-facilities/student-service-centre] SSC [/url].\r\n\r\n[h2] Internationale studenten[/h2]\r\nBij het International Service Desk kun je meer als internationale student meer informatie krijgen over verzekeringen, huisvesting of andere praktische zaken. Op [url=http://www.rug.nl/education/international-students/international-service-desk]deze pagina[/url] vind je contactgegevens.\r\n\r\nStuderen aan de Rijksuniversiteit Groningen bestaat niet alleen uit studieboeken. Je kan [url=http://www.rug.nl/education/international-students/]hier[/url] veel informatie vinden die erbij komen kijken als je gaat studeren. Mocht je specifieke vragen hebben dan kun je altijd mailen naar het bestuur (board@svcover.nl).\r\n\r\n[h2]Studentenrepresentatie[/h2]\r\nVoor meer informatie over welke organen op welk niveau jou als student vertegenwoordigen, [url=show.php?id=118]ga naar de pagina over studentrepresentatie[/url].	[h1]Student info[/h1]\r\nOn this page you can find an overview of (links to) practical information for your study. Are you looking for information about your study? Look [url=https://www.svcover.nl/show.php?id=23] here [/url] for Artificial Intelligence and [url=https://www.svcover.nl/show.php?id=41] here[/url] for Computing Science. Are you looking for an internship or graduate program, then check [url=https://www.svcover.nl/show.php?id=31] this [/url] page, and when you're looking for a job you can go [url=https://www.svcover.nl/show.php?id=54] here [/url].\r\n\r\n[h2] Practical information [/h2]\r\n\r\nFirst of all, an overview of links you will often need:\r\n- [url=https://nestor.rug.nl/]Nestor[/url]: The communication platform for all courses to students. Here you can find for instance lecture slides and practical assignments.\r\n- [url=https://www.rug.nl/ocasys/]Ocasys[/url]: The 'law' for rules and regulations regarding courses. Here you can for example find the definite way your grade for the course will be calculated.\r\n- [url=https://progresswww.nl/rug/]Progress[/url]: Here you can sign up for courses and exams, and you will be able to find the list of your grades here as well. The grades that are displayed on Progress are you final, definite grades!\r\n\r\nRules about study financing change constantly. For the most up to date information you can best go to [url=https://duo.nl/particulieren/student-hbo-of-universiteit/studiefinanciering/weten-hoe-het-werkt.asp] Duo[/url].\r\n\r\nFor the most diverse range of questions  about (de)enrollment in the program & tuition fees you can go to the 'University Student Desk', also known as the [url=http://www.rug.nl/education/usd/]USD[/url].\r\n\r\nIs there still something that you can't find the answer to? Or would you rather speak to someone in person at the Bernoulliborg? Then don't hesitate to ask a Cover member/the Board, your mentor or your study advisor for help or to answer your questions. For Artificial Intelligence the study advisor is Rachel van der Kaaij [url=https://rachelvdkaaij.youcanbook.me/] (make an appointment here)[/url] and for Computing Science Hanneke Niessink [url=https://jhniessink.youcanbook.me/](make an appointment here)[/url].\r\n\r\n[h2] Study Support [/h2]\r\nSummaries and old exams can be found at [url=https://studysupport.svcover.nl/]here[/url]. If you have any other summaries or exams that aren't there yet we would love to recieve them! You can mail them to studcee@svcover.nl or drop them off at our association room.\r\n\r\nAre you not looking for tutoring but for a different kind of course, such as a course to help you with performance anxiety, learning how to answer multiple choice exams, presenting, writing articles or English, you can go to the [url=http://www.rug.nl/education/find-out-more/other-student-facilities/student-service-centre] SSC [/url].\r\n\r\n[h2] International students[/h2]\r\nAs an international student you can get more information about insurances, housing or other practical things at the International Student Desk.  On [url=http://www.rug.nl/education/international-students/international-service-desk]this page[/url] you can find contact info.\r\n\r\nStudying at the University of Groningen doesn't just mean diving into study books. You can find a lot of information about things regarding studying [url=http://www.rug.nl/education/international-students/]here[/url]. If you still have any questions you can always mail the board of Cover at board@svcover.nl.\r\n\r\n[h2]Student representation[/h2]\r\nOur university and faculty have many representative organs. There are three categories of student representation: programme specific, faculty specific and university specific organs, each functioning by cooperating with each other. These student representation organs all serve to make life at the university easier for the students and staff, and make sure every body in our university does their work as correct and efficient as possible. [url=show.php?id=118]Read more about student representation[/url].\r\n\r\n	\N
125	0	committee_battle		Battle your way to the top of the committee battle. Your committee can earn points by completing a variety of tasks. The winner will be crowned at the end of the year during the LLAMA. The complete set of rules can be found [url=https://sd.svcover.nl/Association/Committee%20Battle%202017-2018.pdf]on the SD[/url].	\N
17	11	Contact	[H1]Contactinformatie[/H1]\r\n\r\n[H2]Cover[/H2]\r\n[ [url=mailto:bestuur@svcover.nl]Mail Cover[/url] ][ [url=mailto:noreply@svcover.nl]Mail Webmaster[/url] ]\r\n\r\n[H2]Telefonisch contact[/H2]\r\n[H3]Bedrijven[/H3]\r\nWilt u informatie over wat Cover voor uw bedrijf kan betekenen (bijv. lezingen, advertenties), neem dan contact op met Maureen van der Grinten, onze Commissaris Extern.\r\n \r\nmail: [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]\r\ntel: +31 6 13447473\r\n\r\n[H3]Leden en overigen[/H3]\r\nVoor vragen of opmerkingen, neemt u contact op met het bestuur:\r\n\r\nmail: [url=mailto:board@svcover.nl]board@svcover.nl[/url]\r\ntel: 050 363 6898. \r\n\r\nVoor dringende zaken kunt u rechtstreeks contact opnemen met Kevin Gevers, onze Voorzitter: +31 6 48680791\r\n\r\n[H2]Postadres[/H2]\r\nStudievereniging Cover\r\nPostbus 407\r\n9700 AK Groningen\r\n\r\n[H2]Bezoekadres[/H2]\r\nNijenborgh 9\r\nkamer 041a\r\n9747 AG Groningen\r\n\r\nRekeningnummer: 1037.96.940\r\nIBAN: NL54RABO0103796940\r\nBIC: RABONL2U\r\n\r\nKamer van Koophandel: 40026707\r\n\r\n[H2]Routebeschrijving[/H2]\r\n[b]Routebeschrijving per fiets[/b]\r\nOp de fiets vanuit het centrum van Groningen rijd je naar het Zerniketerrein via de Prinsesseweg, later Zonnelaan. Aangekomen op het Zerniketerrein rijd je net zo lang rechtdoor totdat je op het Zernikeplein komt. Aan de Oostkant staat een groot, blauw gebouw. Dit is de Bernoulliborg.\r\n\r\n[b]Routebeschrijving per openbaar vervoer[/b]\r\nAangekomen op het hoofdstation van Groningen neem je bus 11 of 15 naar Zernike. Uitstappen bij halte Nijenborgh.\r\n\r\nHet is overigens ook mogelijk bus 11 richting Zernike te nemen vanaf station Groningen Noord. De route is dan verder hetzelfde.\r\n\r\nVoor mensen die met de stoptrein vanuit Leeuwarden komen is het soms sneller om uit te stappen in Zuidhorn en de bus naar Groningen Centraal te nemen. Deze stopt ook bij halte Nijenborgh.\r\n\r\n[b]Per auto[/b]\r\n[i]Vanuit Assen (A28)[/i]\r\n[ul]\r\n[li]Bij Julianaplein linksaf richting Leeuwarden/Drachten.[/li]\r\n[li]Eerste afslag rechts ringweg richting Bedum/Zuidhorn.[/li]\r\n[li]Bij rotonde rechtsaf richting Bedum/Zuidhorn.[/li]\r\n[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]\r\n[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n\r\n[i]Vanuit Drachten/Leeuwarden (A7)[/i]\r\n[ul]\r\n[li]Eerste afslag (Groningen-West) nemen, vervolgens de westelijke ringweg nemen richting Bedum.[/li]\r\n[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]\r\n[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n\r\n[i]Vanuit richting Winschoten/Nieuweschans[/i]\r\n[ul]\r\n[li]Bij kruising Europaplein linksaf richting Drachten/Leeuwarden (A7/N28).[/li]\r\n[li]Bij het Julianaplein rechtdoor.[/li]\r\n[li]Eerste afslag rechts ringweg richting Bedum/Zuidhorn.[/li]\r\n[li]Bij rotonde rechtsaf slaan richting Bedum/Zuidhorn.[/li]\r\n[li]Ringweg volgen, afslag Bedum/Winsum, uiterste rechter rijbaan aanhouden, beneden einde afrit links, weg vervolgen richting Zernikecomplex.[/li]\r\n[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n\r\n[i]Vanuit Delfzijl[/i]\r\n[ul]\r\n[li]Bij einde Rijksweg (N41) rechtsaf richting Bedum (N28, oostelijke ringweg).[/li]\r\n[li]Ring volgen richting Drachten tot afslag Paddepoel.[/li]\r\n[li]Deze afslag nemen en meteen rechtsaf slaan richting Zernikecomplex.[/li]\r\n[li]Bij het busplein staat de BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]	[H1]Contact information[/H1]\r\n\r\n[H2]Cover[/H2]\r\n[ [url=mailto:bestuur@svcover.nl]Mail Cover[/url] ][ [url=mailto:noreply@svcover.nl]Mail Webmaster[/url] ]\r\n\r\n[H2]Phone numbers[/H2]\r\n[H3]Companies[/H3]\r\nAre you interested in what Cover can do for your company (e.g. lectures, advertisement), please contact Maureen van der Grinten our Commissioner External Relations.\r\n \r\nmail: [url=mailto:extern@svcover.nl]extern@svcover.nl[/url]\r\ntel: +31 6 13447473\r\n\r\n[H3]Members & others[/H3]\r\nFor questions or remarks, please contact the board.\r\n\r\nmail: [url=mailto:board@svcover.nl]board@svcover.nl[/url]\r\ntel: +31 50 363 6898. \r\n\r\nFor more urgent matters you can contact our chairman, Kevin Gevers, directly: +31 6 48680791\r\n\r\n[H2]Postal address[/H2]\r\nStudievereniging Cover\r\nPostbus 407\r\n9700 AK Groningen\r\nthe Netherlands\r\n\r\n[H2]Visitors address[/H2]\r\nNijenborgh 9\r\nRoom 041a\r\n9747 AG Groningen\r\nthe Netherlands\r\n\r\n[h2]Other information[/h2]\r\nBank account number: 1037.96.940\r\nIBAN: NL54RABO0103796940\r\nBIC: RABONL2U\r\n\r\nChamber of Commerce number: 40026707\r\n\r\n[H2]Directions[/H2]\r\n[b]Directions by bike[/b]\r\nTo bike from Groningen city center to the Zernike Campus you go via the Prinsesseweg, which will become the Zonnelaan. Arriving at the Zernike Campus, continue straight ahead until you reach Zernikeplein. On the east side is a large blue building. This is the Bernoulliborg.\r\n\r\n[b]Directions by public transport[/b]\r\nWhen you have arrived at the main station of Groningen, take busline 15 to Zernike. Get off at the stop Nijenborgh. You will see a large blue building. This is the Bernouilliborg.\r\n\r\nIt is also possible to take busline 1 or 171 towards Zernike from station Groningen Noord. Get off at the stop Nijenborgh. You will see a large blue building. This is the Bernouilliborg.\r\n\r\nFor people arriving by train from Leeuwarden, sometimes it is faster to get to Zuidhorn by train and then take the bus to Groningen. This bus also stops at the Nijenborgh stop.\r\n\r\n[b]By car[/b]\r\n[i]From Assen (A28)[/i]\r\n[ul]\r\n[li]At the Julianaplein turn left towards Leeuwarden/Drachten.[/li]\r\n[li]Take the first exit, turn right in the direction of Bedum/Zuidhorn.[/li]\r\n[li]At the roundabout, turn right towards Bedum/Zuidhorn.[/li]\r\n[li]Follow the Ringweg, exit Bedum / Winsum, keep the right-hand lane. [/li]\r\n[li]At the end of the exit, turn left, follow the road to Zernikecomplex.[/li]\r\n[li]At the bus stop is the BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n[i]From Drachten/Leeuwarden (A7)[/i]\r\n[ul]\r\n[li]Take the first exit (Groningen-West), go straight at the first roundabout, then take the western ring road towards Bedum.[/li]\r\n[li]Follow the Ringweg, exit Bedum / Winsum, keep the right-hand lane. [/li]\r\n[li]At the end of the exit, turn left, follow the road to Zernikecomplex.[/li]\r\n[li]At the bus stop is the BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n[i]From direction Winschoten/Nieuweschans[/i]\r\n[ul]\r\n[li]At the junction Europaplein, turn left towards Drachten/Leeuwarden (A7 / N28).[/li]\r\n[li]Then go straight ahead on Julianaplein.[/li]\r\n[li]Take the first exit, turn right in the direction of Bedum/Zuidhorn.[/li]\r\n[li]At the roundabout, turn right towards Bedum/Zuidhorn.[/li]\r\n[li]Follow the Ringweg, exit Bedum / Winsum, keep the right-hand lane. [/li]\r\n[li]At the end of the exit, turn left, follow the road to Zernikecomplex.[/li]\r\n[li]At the bus stop is the BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]\r\n\r\n[i]From Delfzijl[/i]\r\n[ul]\r\n[li]At the end of the Rijksweg (N41) turn right towards Bedum (N28, east ring road).[/li]\r\n[li]Follow Ring towards Drachten to exit Paddepoel.[/li]\r\n[li]Take this exit and turn right to Zernikecomplex.[/li]\r\n[li]At the bus stop is the BernoulliBorg (Nijenborgh 9).[/li]\r\n[/ul]	\N
94	31	Commissiepagina StudCee	[samenvatting]The StudCee organises activities to help you with your courses.[/samenvatting]\r\n\r\nThe StudCee is here to provide study support! Either by organizing support lectures, hosting old exams and summaries online, or maintaining an awesome tutoring system, we try to give students that little extra edge on a more personal level, and make sure to have fun in the process.\r\n\r\nThe StudCee also run the tutoring system [url=https://tutoring.svcover.nl/]CACTuS[/url] (Cover's Advanced Course Tutoring System) which allows people that need tutoring and people that would like to give tutoring to quickly find each other and get in contact. If you're looking for tutoring you can find a tutor on [url=https://tutoring.svcover.nl/]CACTuS[/url].\r\n	[samenvatting]The StudCee organises activities to help you with your courses.[/samenvatting]\r\n\r\nThe StudCee is here to provide study support! Either by organizing support lectures, hosting old exams and summaries online, or maintaining an awesome tutoring system, we try to give students that little extra edge on a more personal level, and make sure to have fun in the process.\r\n\r\nThe StudCee also run the tutoring system [url=https://tutoring.svcover.nl/]CACTuS[/url] (Cover's Advanced Course Tutoring System) which allows people that need tutoring and people that would like to give tutoring to quickly find each other and get in contact. If you're looking for tutoring you can find a tutor on [url=https://tutoring.svcover.nl/]CACTuS[/url].\r\n\r\n	\N
9	9	Commissiepagina Lustrumcie	[samenvatting]De LustrumCee organiseert de festiviteiten uit de Lustrumweek. Het eerstvolgende (vijfde) lustrum zal gevierd worden in 2018.[/samenvatting]\r\n\r\n[b]Cover haar 25ste verjaardag zal op 20 september 2018 zijn. Dit geeft ons een hele goede reden om een week lang aan feestelijke activiteiten te organiseren in de vorm van een lustrumweek! Gedurende deze week zullen er allerlei verschillende activiteiten worden georganiseerd. Denk aan studie en carrière gerelateerde activiteiten, maar ook een of meerdere feesten! Als je mee wil helpen met het organiseren van deze geweldige week in september 2018, ga dan bij de LustrumCee.[/b]\r\n\r\nOp 20 september 2013 wordt Cover alweer 20 jaar! Om deze prachtige verjaardag op gepaste wijze te vieren organiseert de LustrumCie van 16 t/m 20 september een geweldige lustrumweek met als thema: Lust, Rum & Rock 'n Roll!\r\n\r\nIn 2008 bestond Cover 15 jaar. 15 t/m 19 september is daarom een prachtig lustrum neergezet. \r\n\r\n\r\n	[samenvatting]The LustrumCee organizes the festivities in the lustrum week. The coming study year we will have our 5th lustrum week! [/samenvatting]\r\n\r\n[b]At the 20th of September 2018 Cover will become 25 years old, which gives us a nice reason to have a week of celebrations in the form of a lustrum week. During this week of all kinds of activities will be organized. Think of study and career related activities as well as one or multiple parties! Join the LustrumCee if you want to help to organize this amazing week during September 2018. [/b]\r\n\r\nAt our 4th lustrum in 2013 Cover was 20 years old. To celebrate Cover's birthday we had an awesome lustrum week full of activities from the 16th till the 20th of September. The theme of this lustrum week was: Lust, Rum & Rock 'n Roll! \r\n\r\nIn 2008  Cover was 15 years old. From the 15th till the 19th of September the former LustrumCie also put together an awesome lustrum week. 	\N
132	0	Companyprofile_dataprovider	[h1]Dataprovider.com[/h1]\r\n\r\nIf you are looking for a shoe store in Los Angeles, your search engine has you covered. But what if you want to have overview of all shoe stores in the Los Angeles area? Or what about all shoe stores including contact details within the United States? You could browse through endless pages of information, but it would take some time. That’s why we’ve created Dataprovider.com. Our spider indexes over 280 million domains in 50 countries all over the world. Combine this with the 125 variables per domain we collect, and you get an impressive amount of data. We structure this data and offer it to you in a clean and searchable database. This way you can make your own selection of our data, perfectly tailored to your needs.\r\n\r\nLooking for a job or just interested in what we do and want to learn more?\r\n\r\n[url=https://www.dataprovider.com/about/] Meet the team [/url]	[h1]Dataprovider.com[/h1]\r\n\r\nIf you are looking for a shoe store in Los Angeles, your search engine has you covered. But what if you want to have overview of all shoe stores in the Los Angeles area? Or what about all shoe stores including contact details within the United States? You could browse through endless pages of information, but it would take some time. That’s why we’ve created Dataprovider.com. Our spider indexes over 280 million domains in 50 countries all over the world. Combine this with the 125 variables per domain we collect, and you get an impressive amount of data. We structure this data and offer it to you in a clean and searchable database. This way you can make your own selection of our data, perfectly tailored to your needs.\r\n\r\nLooking for a job or just interested in what we do and want to learn more?\r\n\r\n[url=https://www.dataprovider.com/about/] Meet the team [/url]	\N
133	0	HTG	[h1]Holland Trading Group[/h1]\r\n\r\nHolland Trading Group is a leading international distributor and wholesaler of high-end branded products. With its global network of subsidiaries and a network of strategically located warehouses around the world, HTG offers a competitive, worldwide advantage in adding value to its customers. The combination of these warehouses and subsidiaries with state of the art IT and cost efficient process enables us to realize competitively priced global brand coverage. \r\n\r\nDo you want to get started in a major international distribution company? Are you in for a new challenge? Do you want to continue your development? We are always looking for adventurous colleagues who allow further growth of HTG. We think it is important that you take your responsibility, be versatile, speak different languages and take care of business. In contrast, we offer an enthusiastic and close team, extensive growth opportunities and many more favorable working conditions. \r\n\r\nMost of our employees came in as a Trainee and so could you! Starting as a Trainee means you follow a personal trainee program, completely focused on your growth and development in our organization n. Want to be part of our next generation Young Professionals? On our website www.hollandtradinggroup.com you can check all our ICT vacancies. The first step in our selection procedure is a Skype meeting, to see if you are a match with our organization. When this is positive, we invite you for a personal meeting at our office. \r\n\r\nFacts:\r\n•\t1400 employees at B&S International worldwide\r\n•\t350 employees at HTG \r\n•\t10-15 internships per year\r\n•\tHead office HTG located in Delfzijl\r\n•\t€818 million turnover in 2016\r\n•\tStriving forward with the newest IT solutions \r\n	[h1]Holland Trading Group[/h1]\r\n\r\nHolland Trading Group is a leading international distributor and wholesaler of high-end branded products. With its global network of subsidiaries and a network of strategically located warehouses around the world, HTG offers a competitive, worldwide advantage in adding value to its customers. The combination of these warehouses and subsidiaries with state of the art IT and cost efficient process enables us to realize competitively priced global brand coverage. \r\n\r\nDo you want to get started in a major international distribution company? Are you in for a new challenge? Do you want to continue your development? We are always looking for adventurous colleagues who allow further growth of HTG. We think it is important that you take your responsibility, be versatile, speak different languages and take care of business. In contrast, we offer an enthusiastic and close team, extensive growth opportunities and many more favorable working conditions. \r\n\r\nMost of our employees came in as a Trainee and so could you! Starting as a Trainee means you follow a personal trainee program, completely focused on your growth and development in our organization n. Want to be part of our next generation Young Professionals? On our website www.hollandtradinggroup.com you can check all our ICT vacancies. The first step in our selection procedure is a Skype meeting, to see if you are a match with our organization. When this is positive, we invite you for a personal meeting at our office. \r\n\r\nFacts:\r\n•\t1400 employees at B&S International worldwide\r\n•\t350 employees at HTG \r\n•\t10-15 internships per year\r\n•\tHead office HTG located in Delfzijl\r\n•\t€818 million turnover in 2016\r\n•\tStriving forward with the newest IT solutions \r\n	\N
134	0	dataprovider	[h1]Dataprovider.com[/h1]\r\n\r\nIf you are looking for a shoe store in Los Angeles, your search engine has you covered. But what if you want to have overview of all shoe stores in the Los Angeles area? Or what about all shoe stores including contact details within the United States? You could browse through endless pages of information, but it would take some time. That’s why we’ve created Dataprovider.com. Our spider indexes over 280 million domains in 50 countries all over the world. Combine this with the 125 variables per domain we collect, and you get an impressive amount of data. We structure this data and offer it to you in a clean and searchable database. This way you can make your own selection of our data, perfectly tailored to your needs.\r\n\r\nLooking for a job or just interested in what we do and want to learn more?\r\n\r\n[url=https://www.dataprovider.com/about/]Meet the team[/url]	[h1]Dataprovider.com[/h1]\r\n\r\nIf you are looking for a shoe store in Los Angeles, your search engine has you covered. But what if you want to have overview of all shoe stores in the Los Angeles area? Or what about all shoe stores including contact details within the United States? You could browse through endless pages of information, but it would take some time. That’s why we’ve created Dataprovider.com. Our spider indexes over 280 million domains in 50 countries all over the world. Combine this with the 125 variables per domain we collect, and you get an impressive amount of data. We structure this data and offer it to you in a clean and searchable database. This way you can make your own selection of our data, perfectly tailored to your needs.\r\n\r\nLooking for a job or just interested in what we do and want to learn more?\r\n\r\n[url=https://www.dataprovider.com/about/]Meet the team[/url]	\N
42	18	Commissiepagina Memory	[samenvatting] Wij zorgen voor de CACHE![/samenvatting]\r\n\r\nDe Memory heeft als taak om de Coverkamer a.k.a. CACHE open te houden, te voorzien van snacks en drankjes. De kamer is een fijne plek om tijd te verdoen en onze hoogste prioriteit is om dat zo te houden. Iedere donderdagmiddag om 16 uur is er in de kamer een borrel, de TAD. Kom eens langs voor een biertje, wijntje of fris!	[samenvatting] We take care of the CACHE![/samenvatting]\r\n\r\nThe Memory is responsible for making sure the Cover room (otherwise known as the CACHE) is kept open and fully stocked with snacks and drinks. The room is a nice place to waste your time and keeping it that way is our number one priority. Every thursday at 16:00, there is a social in the room, the TAD. You're invited to come by for a beer, wine or soda!\r\n	\N
135	0	ING	[h2]The best start to your IT career[/h2]\r\n\r\nLong before it became fashionable to talk about the need to be ‘digital first’, ING has recognised and invested in the power of new technologies to transform our customers’ banking experience.\r\n\r\n[h3]Make a difference[/h3]\r\nWith 40 million customers depending on our technologies, ING is Europe’s biggest online bank and the largest employer of IT professionals in The Netherlands. ING puts IT at the centre of everything we do. In your four-year traineeship you’ll learn from the industry’s most talented and experienced specialists, working on important and large scale projects with the best technologies and latest methodologies. You will make a difference. You’ll also be provided intensive training and ongoing support to expand and evolve your skills. Add the complexity of working across international environments and the challenges and rewards of an IT career at ING quickly stack up. \r\n\r\n[h3]Invent the future[/h3]\r\nTo work in IT at ING is to be surrounded by inspiring colleagues who insist constantly on being more tomorrow than we are today: not just by embracing a digital future — but by inventing it. Who work continually to optimise pioneering, reliable and feasible technologies to improve our customers’ banking experience and the working lives of our people. The IT traineeship is part of ING’s International Talent Programme (ITP). For a sense of what an ING traineeship has in store, read the blogs of other trainees and about our agile Way of Working.\r\n\r\n[h3]Interested to know more?[/h3]\r\nDo you want to see your future office and experience what life as an ING trainee is like? Sign up for [url=https://www.ing.jobs/Netherlands/Traineeships/Meet-a-trainee.htm]Meet a Trainee[/url] and we’ll connect you to one of our trainees for a one-on- one meeting. Or check out [url=https://www.ing.jobs/netherlands/traineeships.htm]ing.nl/traineeship[/url] for the [url=https://www.ing.jobs/Netherlands/Why-ING/Meet-us/Calendar.htm]upcoming events[/url].\r\n\r\nFor questions you can always contact the wonderfully helpful Jessie de Groot (Jessie.de.groot@ing.nl); our IT Campus Recruiter.	[h2]The best start to your IT career[/h2]\r\n\r\nLong before it became fashionable to talk about the need to be ‘digital first’, ING has recognised and invested in the power of new technologies to transform our customers’ banking experience.\r\n\r\n[h3]Make a difference[/h3]\r\nWith 40 million customers depending on our technologies, ING is Europe’s biggest online bank and the largest employer of IT professionals in The Netherlands. ING puts IT at the centre of everything we do. In your four-year traineeship you’ll learn from the industry’s most talented and experienced specialists, working on important and large scale projects with the best technologies and latest methodologies. You will make a difference. You’ll also be provided intensive training and ongoing support to expand and evolve your skills. Add the complexity of working across international environments and the challenges and rewards of an IT career at ING quickly stack up. \r\n\r\n[h3]Invent the future[/h3]\r\nTo work in IT at ING is to be surrounded by inspiring colleagues who insist constantly on being more tomorrow than we are today: not just by embracing a digital future — but by inventing it. Who work continually to optimise pioneering, reliable and feasible technologies to improve our customers’ banking experience and the working lives of our people. The IT traineeship is part of ING’s International Talent Programme (ITP). For a sense of what an ING traineeship has in store, read the blogs of other trainees and about our agile Way of Working.\r\n\r\n[h3]Interested to know more?[/h3]\r\nDo you want to see your future office and experience what life as an ING trainee is like? Sign up for [url=https://www.ing.jobs/Netherlands/Traineeships/Meet-a-trainee.htm]Meet a Trainee[/url] and we’ll connect you to one of our trainees for a one-on- one meeting. Or check out [url=https://www.ing.jobs/netherlands/traineeships.htm]ing.nl/traineeship[/url] for the [url=https://www.ing.jobs/Netherlands/Why-ING/Meet-us/Calendar.htm]upcoming events[/url].\r\n\r\nFor questions you can always contact the wonderfully helpful Jessie de Groot (Jessie.de.groot@ing.nl); our IT Campus Recruiter.	\N
136	44	DisCover		[samenvatting]DisCover is the name of Cover's new magazine and of the committee behind it.[/samenvatting]\r\nDisCover is the name of Cover's new magazine and of the committee behind it. We are currently planning to release a magazine in April. The committee is responsible for gathering, creating, and editing content, and determining what goes into the magazine. \r\n\r\nIf you have an idea for an article, feel free to mail it to us! We are open to ideas.	\N
137	0	Topicus	[h1]Topicus[/h1]\r\nNergens wordt zo enthousiast gewerkt aan digitale oplossingen die de zelfredzaamheid van de burger vergroten. Wij richten ons op belangrijke maatschappelijke vlakken als Zorg, Finance, Overheid, Legal, \r\nSecurity en Onderwijs. Bijna iedereen in Nederland komt in aanraking met onze nuttige en vooruitstrevende digitale producten. \r\n\r\n[h2]Stage en afstuderen[/h2]\r\nOm tot nieuwe inzichten te komen begeleiden we jaarlijks zo'n 100 wo- en hbo studenten met hun [url=https://www.werkenbijtopicus.nl/stages-afstuderen-bijbaan/]stage- of afstudeeropdracht[/url]. Je krijgt de kans om meteen volwaardig mee te werken aan een van onze uitdagende projecten. Daar leer je het snelst van je ervaren teamleden én kun je echt meewerken aan nieuwe ideeën voor uitdagende ICT-projecten. We werken nauw samen met diverse onderwijsinstellingen en universiteiten en kennen de geldende kaders van jouw opleiding. Ook brengen we je in contact met collega-studenten zodat je er nooit alleen voor staat. Je eindpresentatie wordt er een om nooit te vergeten, want je die doe je tijdens ons drukbezochte stage- afstudeercongres. Om je daarop voor te bereiden krijg je van ons een uiterst nuttige training presentatietechniek cadeau.\r\n\r\n[h2]Kansen volop![/h2]\r\nDe sfeer is hier ongedwongen en informeel. Toch wordt er serieus en professioneel aan kleine en grote projecten gewerkt. Maar er is ook ruimte voor ontwikkeling van ondernemerschap en creatieve uitspattingen. We vinden het te gek als je het experiment niet schuwt. Van [url=https://www.youtube.com/watch?v=2c8dwMEyYNE]Hackathons[/url] (kom maar op met je ideeën) tot het brouwen van een biertje ([url=https://www.youtube.com/watch?v=jumtJyBnheQ&&t=1s]onze eigen Gifkikker[/url]). Die combinatie maakt dat we een gewilde werkplek voor veel studenten en starters zijn. Sowieso kennen we weinig verloop, dus bedenk goed of je bij ons binnen wilt stappen :-). \r\n\r\n[h2]Laat je zien[/h2]\r\nBij Topicus kun je echt laten zien waar jij goed in bent. Werk mee aan vraagstukken die ons dagelijks leven echt raken. Zet jezelf in beeld, kijk op [url=www.werkenbijtopicus.nl]www.werkenbijtopicus.nl[/url] of neem telefonisch contact op met Monica Verkerk via 0570-662662 of mail naar [url=monica.verkerk@topicus.nl]monica.verkerk@topicus.nl[/url]. \r\n	[h1]Topicus[/h1]\r\nNowhere else are people working so enthusiastically on solutions that increase people’s selfreliance. We focus on important social sectors such as Healthcare, Finance, Legal, Government and Education. Nearly everyone in the Netherlands will encounter our useful and innovative digital products from time to time.\r\n\r\n[h2]Internship and graduation[/h2]\r\nIn order to gain new insights, we supervise around 100 university- and college-level students every year during their internship or final project. We offer you a chance to start working on one of our challenging projects right away as a full-fledged member of the team. You can learn a lot from your experienced team members and truly contribute to new ideas for challenging IT projects. We work closely together with several educational institutes and universities and we are familiar with the frameworks set by your course. We also put you in contact with fellow students, so you are never on your own. Your final presentation will be one to remember for the rest of your life, because you will give it during our popular internship/graduation convention. To prepare you for this challenge, we will give you a very useful presentation training beforehand. \r\n\r\n[h2]Plenty of opportunities![/h2]\r\nThe atmosphere at Topicus is relaxed and informal. Nevertheless, we work on major and minor projects in a serious and professional manner. There is also plenty of room to develop your entrepreneurial skills and release your creative outbursts. We love people who are unafraid of experimentation. From [url=https://www.youtube.com/watch?v=2c8dwMEyYNE]Hackathons[/url] (let’s hear all your brilliant ideas) to brewing our own beer ([url=https://www.youtube.com/watch?v=jumtJyBnheQ&&t=1s]the one and only Gifkikker[/url]). This combination makes us a desirable place to work in the eyes of many students and starters. Not many who join our team leave, so make sure you are ready for us...\r\n\r\n[h2]Show yourself[/h2]\r\nAt Topicus, you can truly show off what you are best at. You get a chance to work on solutions to issues that genuinely affect our daily lives. Put yourself in the picture, go to [url=www.werkenbijtopicus.nl]www.werkenbijtopicus.nl[/url] or contact us directly by calling 0570-662662 or sending an email to [url=sollicitatie@topicus.nl]sollicitatie@topicus.nl[/url]. 	\N
138	0	ASML	[b]BE PART OF PROGRESS[/b]\r\nWe make machines that make chips; the hearts of the devices that keep us informed, entertained and safe. Devices that improve our quality of life and help to tackle the world’s toughest problems. We build some of the most amazing machines that you will ever see, and the software to run them. Never satisfied, we measure our performance in units that begin with pico or nano.\r\n\r\n[b]GRADUATES: START YOUR CAREER AT ASML[/b]\r\nASML offers opportunities for graduates from practically every technical field. You will be working with people from around the world. We believe that those different backgrounds, nationalities and perspectives make us better. We know that an open mind is a creative mind. We support networks such as Young ASML, Women@ASML, Pink ASML or Seniors@ASML. And because there’s more to life than work, you can join your colleagues in anything from running, cycling, football, sailing or skiing in one of the sports teams.\r\n\r\nWe offer a fulfilling career, not just a job. We reward employees competitively and provide coaching, training and personal career development. Flexibility, enthusiasm, ambition and customer focus are the foundation for a world of opportunity.\r\n\r\nCheck it out at [url=www.workingatasml.com/students]www.workingatasml.com/students.[/url]	[b]BE PART OF PROGRESS[/b]\r\nWe make machines that make chips; the hearts of the devices that keep us informed, entertained and safe. Devices that improve our quality of life and help to tackle the world’s toughest problems. We build some of the most amazing machines that you will ever see, and the software to run them. Never satisfied, we measure our performance in units that begin with pico or nano.\r\n\r\n[b]GRADUATES: START YOUR CAREER AT ASML[/b]\r\nASML offers opportunities for graduates from practically every technical field. You will be working with people from around the world. We believe that those different backgrounds, nationalities and perspectives make us better. We know that an open mind is a creative mind. We support networks such as Young ASML, Women@ASML, Pink ASML or Seniors@ASML. And because there’s more to life than work, you can join your colleagues in anything from running, cycling, football, sailing or skiing in one of the sports teams.\r\n\r\nWe offer a fulfilling career, not just a job. We reward employees competitively and provide coaching, training and personal career development. Flexibility, enthusiasm, ambition and customer focus are the foundation for a world of opportunity.\r\n\r\nCheck it out at [url=www.workingatasml.com/students]www.workingatasml.com/students.[/url]\r\n	\N
139	45	PhDee	\N	\N	\N
54	26	Vacatures	[h1]Vacatures[/h1]\r\n[b]Welkom op de vacaturepagina! Ben jij op zoek naar een baan voor na je bachelor of master, reageer dan op één of meerdere van de onderstaande vacatures. Staat er niets bij wat bij je past? Neem dan contact op met het bestuur (bestuur@svcover.nl). Zij kunnen jou verder helpen met je zoektocht naar werk.[/b]\r\n\r\n[table noborder]\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/back-end-developer/nl] Back-end Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/front-end-developer/nl] Front-end Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/php-developer/nl] PHP Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/developer/nl] Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]||||[h3][url=https://www.tno.nl/en/career/vacancies/tno-trainee-our-graduate-program-start-date-1-sept-2018/vacid-a0s0x0000106y4euae/]TNO Traineeship ([i]Den Haag[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-malware-analysis/a0sb000000qws5peag/] Afstudeerstage: Malware analyse [/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-advanced-cyber-attacks/a0sb000000qwsalea4/] Afstudeerstage: Geavanceerde Cyberaanvallen [/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]||||[h3][url=https://www.tno.nl/en/career/vacancies/internship-graduationproject-automated-ict-infrastructure-modeling-for-cyber-security-analysis/a0sb000000nzpwleaz/]Internship/Graduation Project Automated ICT Infrastructure Modeling for Cyber Security ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/211/junior-business-process-analist] Junior Business Process Analist ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/212/junior-system-engineer] Junior System Engineer ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/218/junior-software-engineer] Junior Software Engineer ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n[/table]	[h1]Vacancies[/h1]\r\n[b]Welcome to our vacancy page! Are you looking for a job for after your bachelors or masters, feel free to apply to one or more of the vacancies shown below. Can't find anything suited to your interests, please contact the governing board (board@svcover.nl); they might be able to further help you find a job.[/b] \r\n\r\n[table noborder]\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/back-end-developer/nl] Back-end Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/front-end-developer/nl] Front-end Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/php-developer/nl] PHP Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Belsimpel.nl[/h2]|| [h3][url=http://werkenbijbelsimpel.nl/developer/nl] Developer ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]||||[h3][url=https://www.tno.nl/en/career/vacancies/tno-trainee-our-graduate-program-start-date-1-sept-2018/vacid-a0s0x0000106y4euae/]TNO Traineeship ([i]The Hague[/i])[/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-malware-analysis/a0sb000000qws5peag/] Internship: Malware Analysis [/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]|| [h3][url=https://www.tno.nl/en/career/vacancies/internship-advanced-cyber-attacks/a0sb000000qwsalea4/] Internship: Advanced Cyberattacks [/url][/h3]||\r\n\r\n|| [h2]TNO[/h2]||||[h3][url=https://www.tno.nl/en/career/vacancies/internship-graduationproject-automated-ict-infrastructure-modeling-for-cyber-security-analysis/a0sb000000nzpwleaz/]Internship/Graduation Project Automated ICT Infrastructure Modeling for Cyber Security ([i]Groningen[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/211/junior-business-process-analist][Dutch only] Junior Business Process Analist ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/212/junior-system-engineer][Dutch only] Junior System Engineer ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n|| [h2]Holland Trading Group[/h2]||||[h3][url=https://www.hollandtradinggroup.com/jobs-careers/vacatures/218/junior-software-engineer][Dutch only] Junior Software Engineer ([i]Delfzijl[/i])[/url][/h3]||\r\n\r\n[/table]	
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.password_reset_tokens (key, member_id, created_on) FROM stdin;
\.


--
-- Data for Name: passwords; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.passwords (lid_id, password) FROM stdin;
\.


--
-- Data for Name: pollopties; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.pollopties (id, pollid, optie, stemmen) FROM stdin;
\.


--
-- Data for Name: pollvoters; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.pollvoters (lid, poll) FROM stdin;
\.


--
-- Data for Name: profielen_privacy; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.profielen_privacy (id, field) FROM stdin;
1	adres
2	postcode
3	woonplaats
4	geboortedatum
5	beginjaar
8	email
9	foto
7	telefoonnummer
0	naam
\.


--
-- Data for Name: registrations; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.registrations (confirmation_code, data, registerd_on, confirmed_on) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.sessions (session_id, member_id, created_on, ip_address, last_active_on, timeout, application, override_member_id, override_committees) FROM stdin;
\.


--
-- Data for Name: sign_up_entries; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.sign_up_entries (id, form_id, member_id, created_on) FROM stdin;
\.


--
-- Data for Name: sign_up_entry_values; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.sign_up_entry_values (entry_id, field_id, value) FROM stdin;
\.


--
-- Data for Name: sign_up_fields; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.sign_up_fields (id, form_id, name, type, properties, sort_index, deleted) FROM stdin;
\.


--
-- Data for Name: sign_up_forms; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.sign_up_forms (id, committee_id, agenda_id, created_on, open_on, closed_on) FROM stdin;
\.


--
-- Data for Name: stickers; Type: TABLE DATA; Schema: public; Owner: webcie
--

COPY public.stickers (id, label, omschrijving, lat, lng, toegevoegd_op, toegevoegd_door, foto, foto_mtime) FROM stdin;
\.


--
-- Name: actieveleden_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.actieveleden_id_seq', 4986, true);


--
-- Name: agenda_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.agenda_id_seq', 2947, true);


--
-- Name: announcements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.announcements_id_seq', 266, true);


--
-- Name: bedrijven_adres_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.bedrijven_adres_id_seq', 29, true);


--
-- Name: bedrijven_contactgegevens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.bedrijven_contactgegevens_id_seq', 1, false);


--
-- Name: bedrijven_stageplaatsen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.bedrijven_stageplaatsen_id_seq', 10, true);


--
-- Name: besturen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.besturen_id_seq', 26, true);


--
-- Name: commissies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.commissies_id_seq', 45, true);



--
-- Name: forum_acl_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_acl_id_seq', 104, true);


--
-- Name: forum_group_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_group_id_seq', 13, true);


--
-- Name: forum_group_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_group_member_id_seq', 43, true);


--
-- Name: forum_header_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_header_id_seq', 8, true);


--
-- Name: forum_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_messages_id_seq', 11783, true);


--
-- Name: forum_threads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forum_threads_id_seq', 1500, true);


--
-- Name: forums_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.forums_id_seq', 49, true);


--
-- Name: foto_boeken_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.foto_boeken_id_seq', 1355, true);


--
-- Name: foto_faces_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.foto_faces_id_seq', 52730, true);


--
-- Name: foto_reacties_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.foto_reacties_id_seq', 6147, true);


--
-- Name: foto_reacties_likes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.foto_reacties_likes_id_seq', 62, true);


--
-- Name: fotos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.fotos_id_seq', 51174, true);


--
-- Name: gastenboek_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.gastenboek_id_seq', 316342, true);


--
-- Name: lid_fotos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.lid_fotos_id_seq', 1336, true);


--
-- Name: links_categorie_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.links_categorie_id_seq', 2, true);


--
-- Name: links_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.links_id_seq', 8, true);


--
-- Name: mailinglijsten_berichten_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.mailinglijsten_berichten_id_seq', 1891, true);


--
-- Name: mailinglijsten_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.mailinglijsten_id_seq', 41, true);


--
-- Name: mailinglijsten_opt_out_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.mailinglijsten_opt_out_id_seq', 441, true);


--
-- Name: pages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.pages_id_seq', 139, true);


--
-- Name: pollopties_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.pollopties_id_seq', 1669, true);


--
-- Name: profielen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.profielen_id_seq', 1789, true);


--
-- Name: sign_up_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.sign_up_entries_id_seq', 12, true);


--
-- Name: sign_up_fields_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.sign_up_fields_id_seq', 14, true);


--
-- Name: sign_up_forms_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.sign_up_forms_id_seq', 2, true);


--
-- Name: so_documenten_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.so_documenten_id_seq', 31, true);


--
-- Name: so_vakken_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.so_vakken_id_seq', 33, true);


--
-- Name: stickers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.stickers_id_seq', 451, true);


--
-- Name: taken_id_seq; Type: SEQUENCE SET; Schema: public; Owner: webcie
--

SELECT pg_catalog.setval('public.taken_id_seq', 85, true);


--
-- Name: committee_members actieveleden_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.committee_members
    ADD CONSTRAINT actieveleden_pkey PRIMARY KEY (id);


--
-- Name: agenda agenda_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.agenda
    ADD CONSTRAINT agenda_pkey PRIMARY KEY (id);


--
-- Name: announcements announcements_pk; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pk PRIMARY KEY (id);


--
-- Name: applications applications_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_pkey PRIMARY KEY (key);


--
-- Name: commissies commissies_login_key; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.commissies
    ADD CONSTRAINT commissies_login_key UNIQUE (login);


--
-- Name: commissies commissies_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.commissies
    ADD CONSTRAINT commissies_pkey PRIMARY KEY (id);


--
-- Name: committee_email committee_email_uniq; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.committee_email
    ADD CONSTRAINT committee_email_uniq UNIQUE (committee_id, email);


--
-- Name: configuratie configuratie_key_key; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.configuratie
    ADD CONSTRAINT configuratie_key_key UNIQUE (key);


--
-- Name: configuratie configuratie_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.configuratie
    ADD CONSTRAINT configuratie_pkey PRIMARY KEY (key);


--
-- Name: email_confirmation_tokens email_confirmation_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.email_confirmation_tokens
    ADD CONSTRAINT email_confirmation_tokens_pkey PRIMARY KEY (key);


--
-- Name: forum_group_member forum_group_member_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_group_member
    ADD CONSTRAINT forum_group_member_pkey PRIMARY KEY (id);


--
-- Name: forum_threads forum_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_threads
    ADD CONSTRAINT forum_messages_pkey PRIMARY KEY (id);


--
-- Name: forum_messages forum_replies_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_messages
    ADD CONSTRAINT forum_replies_pkey PRIMARY KEY (id);


--
-- Name: forum_sessionreads forum_sessionreads_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_sessionreads
    ADD CONSTRAINT forum_sessionreads_pkey PRIMARY KEY (lid_id, forum_id, thread_id);


--
-- Name: forum_visits forum_visits_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_visits
    ADD CONSTRAINT forum_visits_pkey PRIMARY KEY (lid_id, forum_id);


--
-- Name: forums forums_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forums
    ADD CONSTRAINT forums_pkey PRIMARY KEY (id);


--
-- Name: foto_boeken_custom_visit foto_boeken_custom_visit_pk; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken_custom_visit
    ADD CONSTRAINT foto_boeken_custom_visit_pk PRIMARY KEY (boek_id, lid_id);


--
-- Name: foto_boeken foto_boeken_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken
    ADD CONSTRAINT foto_boeken_pkey PRIMARY KEY (id);


--
-- Name: foto_boeken_visit foto_boeken_visit_pk; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken_visit
    ADD CONSTRAINT foto_boeken_visit_pk PRIMARY KEY (boek_id, lid_id);


--
-- Name: foto_faces foto_faces_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_faces
    ADD CONSTRAINT foto_faces_pkey PRIMARY KEY (id);


--
-- Name: foto_hidden foto_hidden_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_hidden
    ADD CONSTRAINT foto_hidden_pkey PRIMARY KEY (foto_id, lid_id);


--
-- Name: foto_likes foto_likes_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_likes
    ADD CONSTRAINT foto_likes_pkey PRIMARY KEY (foto_id, lid_id);


--
-- Name: foto_reacties foto_reacties_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_reacties
    ADD CONSTRAINT foto_reacties_pkey PRIMARY KEY (id);


--
-- Name: fotos fotos_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.fotos
    ADD CONSTRAINT fotos_pkey PRIMARY KEY (id);


--
-- Name: leden leden_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.leden
    ADD CONSTRAINT leden_pkey PRIMARY KEY (id);


--
-- Name: mailinglijsten_abonnementen mailinglijsten_abonnementen_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_abonnementen
    ADD CONSTRAINT mailinglijsten_abonnementen_pkey PRIMARY KEY (abonnement_id);


--
-- Name: mailinglijsten mailinglijsten_adres_key; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten
    ADD CONSTRAINT mailinglijsten_adres_key UNIQUE (adres);


--
-- Name: mailinglijsten_berichten mailinglijsten_berichten_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_berichten
    ADD CONSTRAINT mailinglijsten_berichten_pkey PRIMARY KEY (id);


--
-- Name: mailinglijsten_opt_out mailinglijsten_opt_out_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_opt_out
    ADD CONSTRAINT mailinglijsten_opt_out_pkey PRIMARY KEY (id);


--
-- Name: mailinglijsten mailinglijsten_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten
    ADD CONSTRAINT mailinglijsten_pkey PRIMARY KEY (id);


--
-- Name: pages pages_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (key);


--
-- Name: passwords passwords_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.passwords
    ADD CONSTRAINT passwords_pkey PRIMARY KEY (lid_id);


--
-- Name: pollopties pollopties_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pollopties
    ADD CONSTRAINT pollopties_pkey PRIMARY KEY (id);


--
-- Name: profielen_privacy profielen_privacy_field_key; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.profielen_privacy
    ADD CONSTRAINT profielen_privacy_field_key UNIQUE (field);


--
-- Name: profielen_privacy profielen_privacy_id_key; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.profielen_privacy
    ADD CONSTRAINT profielen_privacy_id_key UNIQUE (id);


--
-- Name: registrations registrations_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.registrations
    ADD CONSTRAINT registrations_pkey PRIMARY KEY (confirmation_code);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (session_id);


--
-- Name: sign_up_entries sign_up_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entries
    ADD CONSTRAINT sign_up_entries_pkey PRIMARY KEY (id);


--
-- Name: sign_up_entry_values sign_up_entry_values_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entry_values
    ADD CONSTRAINT sign_up_entry_values_pkey PRIMARY KEY (entry_id, field_id);


--
-- Name: sign_up_fields sign_up_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_fields
    ADD CONSTRAINT sign_up_fields_pkey PRIMARY KEY (id);


--
-- Name: sign_up_forms sign_up_forms_pkey; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_forms
    ADD CONSTRAINT sign_up_forms_pkey PRIMARY KEY (id);


--
-- Name: stickers stickersmap_pk; Type: CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.stickers
    ADD CONSTRAINT stickersmap_pk PRIMARY KEY (id);


--
-- Name: committee_email_committee_id_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX committee_email_committee_id_idx ON public.committee_email USING btree (committee_id);


--
-- Name: forum_messages_thread_id_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX forum_messages_thread_id_idx ON public.forum_messages USING btree (thread_id);


--
-- Name: forum_threads_forum_id_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX forum_threads_forum_id_idx ON public.forum_threads USING btree (forum_id);


--
-- Name: foto_boeken_parent; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX foto_boeken_parent ON public.foto_boeken USING btree (parent_id);


--
-- Name: foto_boeken_visit_lid_id_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX foto_boeken_visit_lid_id_idx ON public.foto_boeken_visit USING btree (lid_id);


--
-- Name: foto_faces_lid_id_deleted_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX foto_faces_lid_id_deleted_idx ON public.foto_faces USING btree (lid_id, deleted);


--
-- Name: foto_reacties_foto_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX foto_reacties_foto_idx ON public.foto_reacties USING btree (foto);


--
-- Name: fotos_boek_hidden_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX fotos_boek_hidden_idx ON public.fotos USING btree (boek, hidden);


--
-- Name: pollvoters_lid_poll_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE UNIQUE INDEX pollvoters_lid_poll_idx ON public.pollvoters USING btree (lid, poll);


--
-- Name: sign_up_fields_form_id_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE INDEX sign_up_fields_form_id_idx ON public.sign_up_fields USING btree (form_id);


--
-- Name: sign_up_fields_form_id_name_idx; Type: INDEX; Schema: public; Owner: webcie
--

CREATE UNIQUE INDEX sign_up_fields_form_id_name_idx ON public.sign_up_fields USING btree (form_id, name);


--
-- Name: committee_members actieveleden_commissieid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.committee_members
    ADD CONSTRAINT actieveleden_commissieid_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: committee_members actieveleden_lidid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.committee_members
    ADD CONSTRAINT actieveleden_lidid_fkey FOREIGN KEY (member_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: agenda agenda_committee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.agenda
    ADD CONSTRAINT agenda_committee_id_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id);


--
-- Name: announcements announcements_committee_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_committee_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: besturen besturen_page_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.besturen
    ADD CONSTRAINT besturen_page_fkey FOREIGN KEY (page_id) REFERENCES public.pages(id);


--
-- Name: commissies commissies_page_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.commissies
    ADD CONSTRAINT commissies_page_fkey FOREIGN KEY (page_id) REFERENCES public.pages(id);


--
-- Name: committee_email committee_email_committee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.committee_email
    ADD CONSTRAINT committee_email_committee_id_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: email_confirmation_tokens email_confirmation_tokens_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.email_confirmation_tokens
    ADD CONSTRAINT email_confirmation_tokens_member_id_fkey FOREIGN KEY (member_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: forum_messages forum_messages_thread_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_messages
    ADD CONSTRAINT forum_messages_thread_fkey FOREIGN KEY (thread_id) REFERENCES public.forum_threads(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: forum_sessionreads forum_sessionreads_lid_key; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_sessionreads
    ADD CONSTRAINT forum_sessionreads_lid_key FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: forum_visits forum_visits_forum_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_visits
    ADD CONSTRAINT forum_visits_forum_id_fkey FOREIGN KEY (forum_id) REFERENCES public.forums(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: forum_visits forum_visits_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.forum_visits
    ADD CONSTRAINT forum_visits_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_boeken_custom_visit foto_boeken_custom_visit_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken_custom_visit
    ADD CONSTRAINT foto_boeken_custom_visit_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_boeken_visit foto_boeken_visit_boek_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken_visit
    ADD CONSTRAINT foto_boeken_visit_boek_id_fkey FOREIGN KEY (boek_id) REFERENCES public.foto_boeken(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_boeken_visit foto_boeken_visit_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_boeken_visit
    ADD CONSTRAINT foto_boeken_visit_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_faces foto_faces_foto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_faces
    ADD CONSTRAINT foto_faces_foto_id_fkey FOREIGN KEY (foto_id) REFERENCES public.fotos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_faces foto_faces_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_faces
    ADD CONSTRAINT foto_faces_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_faces foto_faces_tagged_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_faces
    ADD CONSTRAINT foto_faces_tagged_by_fkey FOREIGN KEY (tagged_by) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: foto_hidden foto_hidden_foto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_hidden
    ADD CONSTRAINT foto_hidden_foto_id_fkey FOREIGN KEY (foto_id) REFERENCES public.fotos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_hidden foto_hidden_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_hidden
    ADD CONSTRAINT foto_hidden_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_likes foto_likes_foto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_likes
    ADD CONSTRAINT foto_likes_foto_id_fkey FOREIGN KEY (foto_id) REFERENCES public.fotos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_likes foto_likes_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_likes
    ADD CONSTRAINT foto_likes_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_reacties foto_reacties_foto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_reacties
    ADD CONSTRAINT foto_reacties_foto_fkey FOREIGN KEY (foto) REFERENCES public.fotos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_reacties_likes foto_reacties_likes_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_reacties_likes
    ADD CONSTRAINT foto_reacties_likes_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_reacties_likes foto_reacties_likes_reactie_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.foto_reacties_likes
    ADD CONSTRAINT foto_reacties_likes_reactie_id_fkey FOREIGN KEY (reactie_id) REFERENCES public.foto_reacties(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fotos fotos_boek_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.fotos
    ADD CONSTRAINT fotos_boek_fkey FOREIGN KEY (boek) REFERENCES public.foto_boeken(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten_abonnementen mailinglijsten_abonnementen_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_abonnementen
    ADD CONSTRAINT mailinglijsten_abonnementen_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten_abonnementen mailinglijsten_abonnementen_mailinglijst_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_abonnementen
    ADD CONSTRAINT mailinglijsten_abonnementen_mailinglijst_id_fkey FOREIGN KEY (mailinglijst_id) REFERENCES public.mailinglijsten(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten_berichten mailinglijsten_berichten_commissie_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_berichten
    ADD CONSTRAINT mailinglijsten_berichten_commissie_fkey FOREIGN KEY (commissie) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten_berichten mailinglijsten_berichten_mailinglijst_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_berichten
    ADD CONSTRAINT mailinglijsten_berichten_mailinglijst_fkey FOREIGN KEY (mailinglijst) REFERENCES public.mailinglijsten(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten mailinglijsten_commissie_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten
    ADD CONSTRAINT mailinglijsten_commissie_fkey FOREIGN KEY (commissie) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: mailinglijsten_opt_out mailinglijsten_opt_out_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_opt_out
    ADD CONSTRAINT mailinglijsten_opt_out_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mailinglijsten_opt_out mailinglijsten_opt_out_mailinglijst_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.mailinglijsten_opt_out
    ADD CONSTRAINT mailinglijsten_opt_out_mailinglijst_id_fkey FOREIGN KEY (mailinglijst_id) REFERENCES public.mailinglijsten(id);


--
-- Name: pages pages_committee_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_committee_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id);


--
-- Name: password_reset_tokens password_reset_tokens_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_member_id_fkey FOREIGN KEY (member_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: passwords passwords_lid_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.passwords
    ADD CONSTRAINT passwords_lid_id_fkey FOREIGN KEY (lid_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: pollopties pollopties_pollid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pollopties
    ADD CONSTRAINT pollopties_pollid_fkey FOREIGN KEY (pollid) REFERENCES public.forum_threads(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: pollvoters pollvoters_lid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pollvoters
    ADD CONSTRAINT pollvoters_lid_fkey FOREIGN KEY (lid) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: pollvoters pollvoters_poll_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.pollvoters
    ADD CONSTRAINT pollvoters_poll_fkey FOREIGN KEY (poll) REFERENCES public.forum_threads(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sessions sessions_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_member_id_fkey FOREIGN KEY (member_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sessions sessions_override_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_override_member_id_fkey FOREIGN KEY (override_member_id) REFERENCES public.leden(id) ON UPDATE SET NULL ON DELETE SET NULL;


--
-- Name: sign_up_entries sign_up_entries_form_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entries
    ADD CONSTRAINT sign_up_entries_form_id_fkey FOREIGN KEY (form_id) REFERENCES public.sign_up_forms(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sign_up_entries sign_up_entries_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entries
    ADD CONSTRAINT sign_up_entries_member_id_fkey FOREIGN KEY (member_id) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sign_up_entry_values sign_up_entry_values_entry_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entry_values
    ADD CONSTRAINT sign_up_entry_values_entry_id_fkey FOREIGN KEY (entry_id) REFERENCES public.sign_up_entries(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sign_up_entry_values sign_up_entry_values_field_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_entry_values
    ADD CONSTRAINT sign_up_entry_values_field_id_fkey FOREIGN KEY (field_id) REFERENCES public.sign_up_fields(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sign_up_fields sign_up_fields_form_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_fields
    ADD CONSTRAINT sign_up_fields_form_id_fkey FOREIGN KEY (form_id) REFERENCES public.sign_up_forms(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sign_up_forms sign_up_forms_agenda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_forms
    ADD CONSTRAINT sign_up_forms_agenda_id_fkey FOREIGN KEY (agenda_id) REFERENCES public.agenda(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: sign_up_forms sign_up_forms_committee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.sign_up_forms
    ADD CONSTRAINT sign_up_forms_committee_id_fkey FOREIGN KEY (committee_id) REFERENCES public.commissies(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: stickers stickers_toegevoegd_door_fkey; Type: FK CONSTRAINT; Schema: public; Owner: webcie
--

ALTER TABLE ONLY public.stickers
    ADD CONSTRAINT stickers_toegevoegd_door_fkey FOREIGN KEY (toegevoegd_door) REFERENCES public.leden(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

