ALTER TABLE pages ADD COLUMN slug character varying(100) DEFAULT NULL;
ALTER TABLE pages ADD CONSTRAINT uk_slug UNIQUE(slug);
