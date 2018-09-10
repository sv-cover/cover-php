--
-- Sign up forms
--

CREATE TABLE sign_up_forms (
    id SERIAL PRIMARY KEY,
    committee_id INTEGER REFERENCES commissies (id) ON UPDATE CASCADE ON DELETE SET NULL,
    agenda_id INTEGER DEFAULT NULL REFERENCES agenda (id) ON UPDATE CASCADE ON DELETE SET NULL,
    created_on timestamp without time zone NOT NULL,
    open_on timestamp without time zone DEFAULT NULL,
    closed_on timestamp without time zone DEFAULT NULL
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

