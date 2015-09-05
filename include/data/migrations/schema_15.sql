CREATE TABLE registrations (
    confirmation_code VARCHAR(255) NOT NULL PRIMARY KEY,
    data TEXT NOT NULL,
    registerd_on timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);