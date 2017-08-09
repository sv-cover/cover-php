<?php
if (!defined('IN_SITE'))
	return;

$config = [
	'theme' => 'default',
	'report_errors' => true,
	'show_exceptions' => true,
	'show_queries' => false,
	'report_to' => 'webcie@ai.rug.nl',

	'environment' => 'docker',

	'twig_cache' => '/var/www/tmp/twig',

	'sticker_cache' => '/var/www/tmp/stickers',
	
	/* Bestuur */
	'email_bestuur' => 'bestuur@svcover.nl',

	/* Support */
	'email_support' => 'acdcee@svcover.nl',
	
	'default_language' => 'en',

	'facebook_app_id' => '',
	'facebook_app_secret' => '',
	'enable_facebook' => false,
	'enable_facebook_rsvp' => false,

	'enable_photos_read_status' => true,

	'elms_id' => $_ENV['ELMS_ID'],
	'elms_secret' => $_ENV['ELMS_SECRET'],

	// Absolute path to the root folder of all photos. The paths
	// in the database are appended to this path to form the complete
	// absolute path of each photo. You can use something like sshfs
	// to mount the Coverserver file system and specify the mount plus
	// path here.
	'path_to_photos' => '/opt/photos',

	// Optionally you can configure a different url for the photos, e.g.
	// https://www.svcover.nl/fotoboek.php?view=scaled for when you don't
	// have access to the photos locally.
	'url_to_scaled_photo' => 'fotoboek.php?view=scaled',

	'path_to_scaled_photo' => '/var/www/tmp/photos/%d/%dx%d.jpg',

	'path_to_scaled_profile_picture' => '/var/www/tmp/profiles/%d/%dx%d-%s.%s',

	'precomputed_photo_scales' => array(
		[1600, 1600], // Full size photo
		[null, 400] // size used for the thumbnails below photo and in photo books
	),

	// Enable Committee battle
	'committee_battle' => true,

	// Used for forms & XSS prevention. Generate with `openssl rand -base64 64`.
	'nonce_salt' => file_get_contents('/var/www/tmp/nonce'), 

	// Lifetime in seconds.
	'nonce_lifetime' => 3600 * 12, 

	// For the Website calling the API of Secretary to propose new members
	'secretary_root' => $_ENV['SECRETARY_ROOT'],
	'secretary_user' => $_ENV['SECRETARY_USER'],
	'secretary_password' => $_ENV['SECRETARY_PASSWORD'],

	// The shared secret that is used in the checksum when Secretary wants to
	// update the info of a member.
	'secretary_shared_secret' => $_ENV['SECRETARY_SHARED_SECRET'],

	// For the website calling the KAST API to read the current credit balance
	'kast_root' => $_ENV['KAST_ROOT'],
	'kast_app' => $_ENV['KAST_APP'],
	'kast_secret' => $_ENV['KAST_SECRET'],

	// For the website calling Incassomatic to fetch the current balance and contract status.
	'incassomatic_root' => $_ENV['INCASSO_ROOT'],
	'incassomatic_app' => $_ENV['INCASSO_APP'],
	'incassomatic_secret' => $_ENV['INCASSO_SECRET'],

	// Sentry exception reporting
	'sentry_url' => $_ENV['SENTRY_URL'],
	'sentry_public_url' => $_ENV['SENTRY_PUBLIC_URL'],

	// Wiki search RPC
	'wiki_url' => $_ENV['WIKI_URL'],
	'wiki_public_url' => $_ENV['WIKI_PUBLIC_URL'],

	// url for merging internal ical with google ical
	'url_to_external_ical' => $_ENV['CALENDAR_URL']
];
