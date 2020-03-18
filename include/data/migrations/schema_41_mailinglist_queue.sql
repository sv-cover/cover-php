CREATE TABLE mailinglijsten_berichten (
    id SERIAL PRIMARY KEY,
    destination varchar NOT NULL,
    destination_type varchar NOT NULL DEFAULT 'mailinglist',
    mailinglist_id integer DEFAULT NULL REFERENCES mailinglijsten (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE RESTRICT,
    message TEXT NOT NULL,
    status varchar NOT NULL DEFAULT 'waiting',
    queued_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone,
    processing_on timestamp without time zone
);
