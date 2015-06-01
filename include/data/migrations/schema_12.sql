ALTER TABLE mailinglijsten
	ADD COLUMN tag varchar(100) NOT NULL DEFAULT 'Cover',
	ADD COLUMN on_subscribtion_subject TEXT DEFAULT NULL,
    ADD COLUMN on_subscribtion_message TEXT DEFAULT NULL,
    ADD COLUMN on_first_email_subject TEXT DEFAULT NULL,
    ADD COLUMN on_first_email_message TEXT DEFAULT NULL;


ALTER TABLE mailinglijsten_berichten
	ADD COLUMN sender TEXT DEFAULT NULL;