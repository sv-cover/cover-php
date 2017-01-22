CREATE TABLE vacancies(
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    type integer,
    url TEXT NOT NULL,
    company TEXT NOT NULL,
    hours integer,
    experience TEXT NOT NULL,
    study_year integer,
    created timestamp without time zone NOT NULL DEFAULT ('now'::text)::timestamp(6) without time zone
);