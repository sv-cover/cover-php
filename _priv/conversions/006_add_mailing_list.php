<?php

require_once 'init.php';

title('Add mailing list management conversion');

$db = get_db();

message("Adding a primary key to the commissies table");
result($db->query("ALTER TABLE commissies ADD PRIMARY KEY (id)"));

message("Adding mailinglist table");
result($db->query("CREATE TABLE
	mailinglijsten
	(
	  id integer NOT NULL,
	  naam character varying(255) NOT NULL,
	  omschrijving text,
	  publiek smallint NOT NULL DEFAULT 0,
	  CONSTRAINT mailinglijsten_pkey PRIMARY KEY (id)
	)"));

message("Adding mailinglist subscriptions table");
result($db->query("CREATE TABLE
	mailinglijsten_abonnementen
	(
	  abonnement_id character(40) NOT NULL,
	  mailinglijst_id integer NOT NULL,
	  lid_id integer NOT NULL,
	  ingeschreven_op date NOT NULL DEFAULT ('now'::text)::timestamp(6) with time zone,
	  opgezegd_op date,
	  CONSTRAINT mailinglijsten_abonnementen_pkey PRIMARY KEY (abonnement_id),
	  CONSTRAINT mailinglijsten_abonnementen_lid_id_fkey FOREIGN KEY (lid_id)
	      REFERENCES leden (id) MATCH SIMPLE
	      ON UPDATE NO ACTION ON DELETE NO ACTION,
	  CONSTRAINT mailinglijsten_abonnementen_mailinglijst_id_fkey FOREIGN KEY (mailinglijst_id)
	      REFERENCES mailinglijsten (id) MATCH SIMPLE
	      ON UPDATE NO ACTION ON DELETE NO ACTION
	)"));