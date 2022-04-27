-- Add extra conditions for opt-out lists

ALTER TABLE mailinglijsten
  ADD COLUMN has_members boolean NOT NULL DEFAULT TRUE,
  ADD COLUMN has_contributors boolean NOT NULL DEFAULT FALSE,
  ADD COLUMN has_starting_year integer DEFAULT NULL;
