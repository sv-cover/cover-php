#!/usr/bin/php
<?php
define('IN_SITE', true);

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/..'));
include('src/framework/config.php');
include('src/framework/data/data.php');

if (count($_SERVER['argv']) < 2)
{
        echo "Looks like you forgot to provide the photo names as arguments!\n";
        exit(1);
}

$folder = $_SERVER['argv'][1];
$db = get_db();

function find_member_id($name)
{
        global $db;

        $result = $db->query("SELECT id FROM leden WHERE (voornaam || CASE  WHEN char_length(tussenvoegsel) > 0 THEN ' ' || tussenvoegsel ELSE '' END || ' ' || achternaam) = '" . pg_escape_string($name) . "'");

        if (count($result) > 1) {
                echo "WARNING: multiple members named '$name' found.\n";
                return null;
        }
        else if (count($result) == 0) {
                echo "WARNING: no members named '$name' found.\n";
                return null;
        }
        else
                return intval($result[0]['id']);
}

function insert_photo($member_id, $name, $data)
{
        global $db;

        $result = $db->query("SELECT * FROM \"lid_fotos\" WHERE \"lid_id\" = " . $member_id);

        if (count($result) > 0) {
                echo "WARNING: '$name' already has a profile picture.\n";
                return;
        }

        $db->query("DELETE FROM \"lid_fotos\" WHERE \"lid_id\" = " . $member_id);

        $db->query("INSERT INTO \"lid_fotos\" (\"id\", \"lid_id\", \"foto\") VALUES (nextval('lid_fotos_id_seq'::regclass), " . $member_id . ", '" . pg_escape_bytea($data) . "')");

        // echo($db->get_last_error(). "\n");
}

function upload_photo($file)
{
        $name = basename($file, '.jpg');

        $name = str_replace('_', ' ', $name);

        if (ctype_digit($name))
                $member_id = intval($name);
        else
                $member_id = find_member_id($name);

        if ($member_id === null)
                return;

        $photo = file_get_contents($file);

        if ($photo === false) {
                echo "ERROR: can't open file $photo.\n";
                return;
        }

        echo "UPDATE $file > $member_id\n";
        insert_photo($member_id, $name, $photo);

        unlink($file);
}

for ($i = 1; $i < $argc; ++$i)
        upload_photo($argv[$i]);
