ALTER TABLE pages ADD COLUMN slug character varying(100) DEFAULT NULL;
ALTER TABLE pages CONSTRAINT uk_slug UNIQUE(slug);
