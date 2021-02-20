CREATE TABLE partners(
    id serial PRIMARY KEY,
    name character varying(255) NOT NULL,
    type integer NOT NULL,
    url character varying(255) NOT NULL,
    logo_url character varying(255) NOT NULL,
    logo_dark_url character varying(255) DEFAULT NULL,
    page_id integer DEFAULT NULL REFERENCES pages (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
    hidden integer NOT NULL DEFAULT 0, -- Deactivated
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
