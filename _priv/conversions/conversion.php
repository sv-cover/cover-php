<?php
	ini_set('display_errors', true);
	error_reporting(E_ALL ^ E_NOTICE);

	include('../../include/init.php');
	require_once('editable.php');
	include('auth.php');
	
	header('Content-type: text/html; charset=ISO-8859-15');

	convert_privacy();
	convert_profielen_privacy();
	convert_beginjaar();
	convert_character_varying();
	convert_confirm();
	convert_agenda();
	convert_gastenboek();
	convert_boeken();
	convert_poll_voters();
	convert_configuratie();
	convert_links();
	convert_taken();
	convert_commissies();
	convert_pages();
	convert_actieve_leden();
	convert_leden();
	convert_fotoboek();
	convert_forum();
	convert_lid_fotos();
	convert_commissie_summary();
	
	function convert_commissie_summary() {
		$commissie_model = get_model('DataModelCommissie');
		$editable_model = get_model('DataModelEditable');
		$commissies = $commissie_model->get();
		
		foreach ($commissies as $commissie) {
			$page = $editable_model->get_iter($commissie->get('page'));

			$content = $page->get('content');
			$content = preg_replace('/^\s*(\[page\])?\s*\[h1\].*?\[\/h1\].*commissie_email.*$/mi', '$1', $content);
			
			$content = preg_replace('/^\s*(\[page\])?\s*\[h1\].*?\[\/h1\].*\n.*commissie_email.*$/mi', '$1', $content);
			
			$content = preg_replace('/^\s*\[h1\].*?\[\/h1\]\s*/ims', '', $content);

			$pages = editable_parse($content, $page->get('owner'));

			$notags = strip_tags($pages[0]);
			preg_match('/\b(.+?\b\W+){0,20}/', $notags, $matches);

			$summary = $matches[0] . ($matches[0] != $notags ? ('...') : '');
			
			$pages = editable_split_pages($page->get('content'));
			$pages[0] = '[samenvatting]' . $summary . "[/samenvatting]\n" . preg_replace('/\[samenvatting\].*\[samenvatting\]/ism', '', $pages[0]);
			$page->set('content', (count($pages) > 1 ? ('[page]' . implode('[/page][page]', $pages) . '[/page]') : $pages[0]));

			$editable_model->update($page);
		}
		
		echo "<b>Convert added commissie summaries conversion done...</b><br>\n";
	}
	
	function convert_profielen_privacy() {
		$db = get_db();
		$db->query('create table public.profielen_privacy ("id" integer not null unique, "field" text not null unique)');
		
		$db->query('insert into profielen_privacy ("id", "field") VALUES (0, \'naam\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (1, \'adres\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (2, \'postcode\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (3, \'woonplaats\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (4, \'geboortedatum\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (5, \'beginjaar\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (6, \'telefoonnummer_vast\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (7, \'telefoonnummer_mobiel\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (8, \'email\')');
		$db->query('insert into profielen_privacy ("id", "field") VALUES (9, \'foto\')');
		
		echo "<b>Convert profielen_privay table conversion done...</b><br>\n";
	}
	
	function convert_configuratie() {
		$db = get_db();
		$db->query('create table public.configuratie ("key" varchar(100) NOT NULL unique, "value" text not null)');
		$db->query('insert into configuratie ("key", "value") VALUES(\'boeken_bestellen\', \'1\')');
		
		echo "<b>Convert configuratie table conversion done...</b><br>\n";
	}
	
	function convert_lid_fotos() {
		$db = get_db();
		
		$db->query('create sequence public.lid_fotos_id_seq');
		$db->query('create table public.lid_fotos ("id" integer NOT NULL default nextval(\'lid_fotos_id_seq\'::regclass), "lid_id" integer, "foto" bytea)');
		
		echo "<b>Convert lid fotos table conversion done...</b><br>\n";
	}
	
	function convert_leden() {
		$db = get_db();
		
		$db->query('alter table profielen add "taal" character varying(10) default \'nl\'');
		
		echo "<b>Convert leden table conversion done...</b><br>\n";
	}
	
	function convert_actieve_leden() {
		$db = get_db();
		
		$db->query('alter table actieveleden add "sleutel" integer');
		$db->query('drop table sleutelbeheer');
		
		echo "<b>Convert actieve leden table conversion done...</b><br>\n";
	}
	
	function convert_pages() {
		$db = get_db();
		
		$db->query('alter table pages add "content_en" text');
		$db->query('alter table pages add "content_de" text');

		echo "<b>Convert pages table conversion done...</b><br>\n";	
	}

	function convert_commissies() {
		$db = get_db();
		
		$db->query('alter table commissies drop column beschrijving');
		
		$max = $db->query_value('select max(id) + 1 from commissies');
		$db->query('create sequence public.commissies_id_seq start with ' . $max);
		$db->query('alter table commissies alter column id set default nextval(\'commissies_id_seq\'::regclass)');

		echo "<b>Convert commissies table conversion done...</b><br>\n";
	}
	
	function convert_taken() {
		$db = get_db();
		
		$db->query('create sequence public.taken_id_seq');
		$db->query('create table public.taken ("id" integer NOT NULL default nextval(\'taken_id_seq\'::regclass), "taak" text NOT NULL, "beschrijving" text NOT NULL, "afgehandeld" date, "toegewezen" integer, "prioriteit" integer not null default 2)');
		$db->query('create table public.taken_subscribe ("lidid" integer NOT NULL, "taakid" integer NOT NULL)');
		
		echo "<b>Convert taken table conversion done...</b><br>\n";
	}
	
	function convert_links() {
		$db = get_db();
		$db->query('drop table links');

		$db->query('create sequence public.links_id_seq');
		$db->query('create table public.links ("id" integer NOT NULL default nextval(\'links_id_seq\'::regclass), "categorie" integer NOT NULL, "url" text NOT NULL, "titel" text NOT NULL, "beschrijving" text NOT NULL, "door" integer NOT NULL, "moderated" integer NOT NULL default 0)');
		
		$db->query('drop table links_sections');
		$db->query('drop sequence links_sections_id_seq');

		$db->query('create sequence public.links_categorie_id_seq');
		$db->query('create table public.links_categorie ("id" integer NOT NULL default nextval(\'links_categorie_id_seq\'::regclass), "titel" text NOT NULL, "order" integer NOT NULL)');
		
		echo "<b>Convert links table conversion done...</b><br>\n";
	}
	
	function convert_poll_voters() {
		$db = get_db();
		$db->query('create table public.pollvoters ("lid" integer NOT NULL, "poll" integer NOT NULL)');

		echo "<b>Convert poll voters table conversion done...</b><br>\n";
	}
	
	function convert_boeken() {
		$db = get_db();
		$db->query('create table public.boeken_categorie ("id" integer not null unique, "categorie" text not null unique)');

		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(1, \'Eerstejaars\')');
		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(2, \'Tweedejaars\')');
		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(3, \'Derdejaars\')');
		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(4, \'Ma-AI\')');
		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(5, \'Ma-MMC\')');
		$db->query('insert into boeken_categorie ("id", "categorie") VALUES(6, \'Keuzevakken Bachelor\')');
		
		echo "<b>Boeken categorie table conversion done...</b><br>\n";
	}

	function convert_gastenboek() {
		$db = get_db();

		$max = $db->query_value('SELECT MAX(id) + 1 FROM gastenboek');
		$db->query('create sequence public.gastenboek_id_seq start with ' . $max);
		$db->query('alter table gastenboek alter column ip type character varying(50)');

		$reps = array('gastenboek' => array('message'));
		remove_obsolete_escapes($reps);
		
		echo "<b>Gastenboek table conversion done...</b><br>\n";
	}

	function convert_agenda() {
		$db = get_db();

		$start = $db->query_value('SELECT MAX(id) + 1 FROM agenda');
		$db->query('CREATE sequence public.agenda_id_seq start with ' . $start);
		$db->query('ALTER TABLE agenda ALTER COLUMN id SET default nextval(\'agenda_id_seq\'::regclass)');

		$db->query('CREATE table public.agenda_moderate ("agendaid" integer not null, "overrideid" integer not null default 0)');

		echo "<b>Agenda table conversion done...</b><br>\n";
	}

	function convert_confirm() {
		$db = get_db();

		$db->query('ALTER TABLE confirm add "type" text not null default \'\';');

		echo "<b>Confirm table conversion done...</b><br>\n";
	}
	
	function convert_character_varying() {
		/* Converts all non varying character fields in leden and
		 * to varying so to remove the annoying whitespace
		 */
		$db = get_db();
		 
		/* Get all the values */
		$db->query('ALTER TABLE leden ALTER COLUMN postcode TYPE character varying(7)');
		$db->query('ALTER TABLE leden ALTER COLUMN telefoonnummer_vast TYPE character varying(11)');
		$db->query('ALTER TABLE leden ALTER COLUMN telefoonnummer_mobiel TYPE character varying(11)');
		
		echo "<b>Character varying conversion done...</b><br>\n";
	}
	
	function convert_beginjaar() {
		/* Converts the leden.beginjaar field from date to integer
		 * since it only contains a year 
		 */
		 
		$db = get_db();
		 
		/* Get all the values */
		$all = $db->query('SELECT id, beginjaar FROM leden');
		 
		/* Remove the column */
		$db->query("ALTER TABLE leden DROP COLUMN beginjaar");
		
		if ($db->get_last_error()) {
			echo $db->get_last_error() . '<br/>';
			return;
		}
		
		/* Add the column with the new type */
		$db->query('ALTER TABLE leden ADD COLUMN beginjaar INTEGER');

		if ($db->get_last_error()) {
			echo $db->get_last_error() . '<br/>';
			return;
		}
		
		/* Set the default value to current year */
		$db->query("ALTER TABLE leden ALTER COLUMN beginjaar SET DEFAULT DATE_PART('year', CURRENT_TIMESTAMP)");

		if ($db->get_last_error()) {
			echo $db->get_last_error() . '<br/>';
			return;
		}

		$result = true;

		foreach ($all as $lid) {
			$beginjaar = substr($lid['beginjaar'], 0, 4);
			
			$db->update('leden', array('beginjaar' => intval($beginjaar)), 'id = ' . $lid['id']);
		}
		
		echo "<b>Beginjaar conversion " . ($result ? 'ok' : 'error') . "...</b><br>\n";
	}

	function convert_privacy() {
		/* Convert privacy values to new system
		 * Previous system had 3 bits for every private option
		 * Options were:
		 *	- None set then not visible
		 *	- Visible to AI network  (bit 0 == 1)
		 *	- Visible to RUG network (bit 1 == 1)
		 *	- Visibile to Internet   (bit 2 == 1)
		 * New system is:
		 *	- None set then not visible
		 *	- Visible to members     (bit 0 == 1)
		 *	- Visible to all         (bit 0 == 1, bit 1 == 1, bit 2 == 1)
		 */

		$db = get_db();
		$all = $db->query('SELECT id, privacy FROM leden');
		$result = true;

		foreach ($all as $lid) {
			$privacy = intval($lid['privacy']);
			$newvalue = 0;

			for ($i = 0; $i < 28; $i += 3) {
				$pos = pow(2, $privacy);
				
				/* Check for internet */
				if ($privacy & pow(2, $i + NETWORK_OTHER)) {
					/* Visible to all, bitmask 111 = 7 */
					$value = 7;
				} elseif (($privacy & pow(2, $i + NETWORK_AI)) || ($privacy & pow(2, $i + NETWORK_RUG))) {
					/* Visible to members, bitmask 001 = 1*/
					$value = 1;
				} else {
					/* Not visible, bitmask 000 = 0*/
					$value = 0;
				}
				
				$newvalue = $newvalue + ($value << $i);
			}

			$db->update('leden', array('privacy' => $newvalue), 'id = ' . $lid['id']);
		}
		
		echo "<b>Privacy conversion " . ($result ? 'ok' : 'error') . "...</b><br>\n";
	}
	
	function convert_fotoboek() {
		$db = get_db();
		
		$result = true;

		$reps = array('foto_boeken' => array('titel'),
				'fotos' => array('beschrijving'),
				'foto_reacties' => array('reactie'));
		
		remove_obsolete_escapes($reps);

		/* Create table for book thumbnails */
		$db->query('create table public.foto_boeken_thumb ("boek" integer not null unique, "image" bytea, "theme" character varying(20), "generated" timestamp without time zone not null default (\'now\'::text)::timestamp(6) without time zone)');

		echo "<b>Fotoboek conversion " . ($result ? 'ok' : 'error') . "...</b><br>\n";
	}
	
	function remove_obsolete_escapes($reps) {
		$db = get_db();
		$result = true;

		foreach ($reps as $table => $columns) {
			foreach ($columns as $column) {
				$db->update($table, array($column => "replace(" . $column . ", '" . $db->escape_string("\\'") . "', '" . $db->escape_string("'") . "')"), '', array($column));
				$db->update($table, array($column => "replace(" . $column . ", '" . $db->escape_string('\"') . "', '" . $db->escape_string('"') . "')"), '', array($column));
			}
		}
		
		return $result;	
	}
	
	function convert_forum() {
		$db = get_db();	
		
		$result = true;
		
		
		/* Remove obsolete columns */
		$db->query('alter table forum_messages drop column lastreply');
		$db->query('alter table forums drop column posts');
		$db->query('alter table forums drop column threads');
		
		/* Create acl */
		$db->query('create sequence public.forum_acl_id_seq');
		$db->query('create table public.forum_acl ("id" integer NOT NULL default nextval(\'forum_acl_id_seq\'::regclass), "forumid" integer not null, "type" smallint, "uid" integer, "permissions" integer)');

		$db->query('create sequence public.forum_group_id_seq');
		$db->query('create table public.forum_group ("id" integer NOT NULL default nextval(\'forum_group_id_seq\'::regclass), "name" character varying(50))');

		$db->query('create sequence public.forum_group_member_id_seq');
		$db->query('create table public.forum_group_member ("id" integer NOT NULL default nextval(\'forum_group_member_id_seq\'::regclass), "guid" integer, "type" smallint, "uid" integer)');
		
		$db->query('alter table forums add "position" integer default 0');
		$db->query('alter table forum_messages add "author_type" smallint default 1');
		$db->query('alter table forum_replies add "author_type" smallint default 1');
		
		$db->query('create sequence public.forum_header_id_seq');
		$db->query('create table public.forum_header ("id" integer NOT NULL default nextval(\'forum_header_id_seq\'::regclass), "name" character varying(150), "position" integer)');
		
		$db->query('alter table forum_messages add "poll" integer not null default 0;');

		/* Create commissie forums */
 		$commissie_model = get_model('DataModelCommissie');
 		$commissies = $commissie_model->get();
 		$forum_model = get_model('DataModelForum');
		$i = 100;
		
		$iter = new DataIter($forum_model, -1, array(
				'name' => 'Commissies',
				'position' => 99));
		$forum_model->insert_header($iter);
		
		foreach ($commissies as $commissie) {
			$iter = new DataIter($forum_model, -1, array(
					'name' => 'Commissie: ' . $commissie->get('naam'),
					'description' => 'Privé forum voor ' . ($commissie->get('id') == 0 ? 'het' : 'de') . ' ' . $commissie->get('naam'),
					'position' => $i));

			$id = $forum_model->insert($iter, true);
			
			/* Set permissions */
			$iter = new DataIter($forum_model, -1, array(
					'type' => 2,
					'uid' => intval($commissie->get('id')),
					'forumid' => intval($id),
					'permissions' => ACL_READ | ACL_WRITE | ACL_REPLY | ACL_POLL));
			$forum_model->insert_acl($iter);
			$i++;
		}

		/* Create Weblog forum */
		$iter = new DataIter($forum_model, -1, array(
				'name' => 'Weblog',
				'description' => 'Weblog van het Bestuur',
				'position' => 1));
		$id = $forum_model->insert($iter, true);
		
		/* Set permissions */
		$iter = new DataIter($forum_model, -1, array(
				'type' => 2,
				'uid' => COMMISSIE_BESTUUR,
				'forumid' => intval($id),
				'permissions' => ACL_READ | ACL_WRITE | ACL_REPLY | ACL_POLL));
		$forum_model->insert_acl($iter);
		$iter = new DataIter($forum_model, -1, array(
				'type' => -1,
				'uid' => -1,
				'forumid' => intval($id),
				'permissions' => ACL_READ | ACL_REPLY));
		$forum_model->insert_acl($iter);
		
		/* Set in configuration */
		$config_model = get_model('DataModelConfiguratie');
		$iter = new DataIter($config_model, -1, array(
				'key' => 'weblog_forum',
				'value' => intval($id)));
		$config_model->insert($iter);

		/* Create Mededelingen forum */
		$iter = new DataIter($forum_model, -1, array(
				'name' => 'Mededelingen',
				'description' => 'Belangrijke mededelingen van het bestuur en de commissies.',
				'position' => 1));
		$id = $forum_model->insert($iter, true);
		
		/* Set permissions */
		$iter = new DataIter($forum_model, -1, array(
				'type' => 2,
				'uid' => -1,
				'forumid' => intval($id),
				'permissions' => ACL_READ | ACL_WRITE));
		$forum_model->insert_acl($iter);
		$iter = new DataIter($forum_model, -1, array(
				'type' => -1,
				'uid' => -1,
				'forumid' => intval($id),
				'permissions' => ACL_READ));
		$forum_model->insert_acl($iter);
		
		/* Move all news to this forum */
		$news = $db->query('SELECT * FROM news ORDER BY id');
		
		foreach ($news as $new) {
			$iter = new DataIter($forum_model, -1, array(
					'forum' => intval($id),
					'author' => intval($new['commissie']),
					'author_type' => 2,
					'subject' => $new['subject'],
					'message' => $new['message'],
					'date' => $new['date']));
			$forum_model->insert_thread($iter);
		}

		/* Set in configuration */
		$config_model = get_model('DataModelConfiguratie');
		$iter = new DataIter($config_model, -1, array(
				'key' => 'news_forum',
				'value' => intval($id)));
		$config_model->insert($iter);

		$db->query('DROP table news');

		/* Create poll forum */
		$forum_model = get_model('DataModelForum');
		$iter = new DataIter($forum_model, -1, array(
				'name' => 'Cover polls',
				'description' => 'Wil je weten wat andere mensen over een bepaald onderwerp is, plaats dan hier een leuke poll. Deze polls komen ook op de voorpagina te staan. In dit forum kun je elke 14 dagen een nieuwe poll plaatsen.',
				'position' => 1));
		$id = $forum_model->insert($iter, true);
		
		/* Set permissions */
		$iter = new DataIter($forum_model, -1, array(
				'type' => -1,
				'uid' => -1,
				'forumid' => intval($id),
				'permissions' => ACL_READ | ACL_REPLY | ACL_POLL));
		$forum_model->insert_acl($iter);
		$iter = new DataIter($forum_model, -1, array(
				'type' => 2,
				'uid' => -1,
				'forumid' => intval($id),
				'permissions' => ACL_READ | ACL_REPLY));
		$forum_model->insert_acl($iter);

		/* Set in configuration */
		$config_model = get_model('DataModelConfiguratie');
		$iter = new DataIter($config_model, -1, array(
				'key' => 'poll_forum',
				'value' => intval($id)));
		$config_model->insert($iter);
		
		/* Move polls */
		$polls = $db->query('SELECT * FROM polls ORDER BY id');
		
		foreach ($polls as $poll) {
			if (intval($poll['commissieid']) == COMMISSIE_EASY) {
				$author = intval($poll['door']);
				$author_type = 1;
			} else {
				$author = intval($poll['commissieid']);
				$author_type = 2;
			}

			$iter = new DataIter($forum_model, -1, array(
					'forum' => intval($id),
					'author' => $author,
					'author_type' => $author_type,
					'subject' => $poll['titel'],
					'message' => '',
					'date' => $poll['date'],
					'poll' => 1));
			$pollid = $forum_model->insert_thread($iter);

			$db->query('UPDATE pollopties SET pollid = ' . intval($pollid) . ' WHERE pollid = ' . $poll['id']);
			$db->query('UPDATE pollvoters SET poll = ' . intval($pollid) . ' WHERE poll = ' . $poll['id']);
		}
		
		$db->query('DROP table polls');
		
 		$reps = array('forum_messages' => array('subject', 'message'),
 				'forum_replies' => array('subject', 'message'));
 		
 		remove_obsolete_escapes($reps);

		$db->query('alter table forum_visits alter column lastvisit set default (\'now\'::text)::timestamp(6) with time zone');
		$db->query('DELETE FROM forum_visits');
		$db->query('DELETE FROM forum_sessionreads');

		echo "<b>Forum conversion " . ($result ? 'ok' : 'error') . "...</b><br>\n";
	}
?>

