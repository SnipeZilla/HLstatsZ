<?php
if (!defined('IN_UPDATER')) {
    die('Do not access this file directly.');
}

// ---------------------------------------------
// Per-admin Steam login
// ---------------------------------------------
echo "<h3>Per-admin Steam login</h3>";

// Guarded the same way every other schema-changing migration in this
// project is (see 63.php's addColumnIfMissing(), 85.php's inline
// INFORMATION_SCHEMA check) - Force Update replays every migration
// from a very old baseline, so an unguarded ALTER TABLE here would be
// the one file in the whole history that isn't safe to replay.
$col_exists = $db->query("
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hlstats_Users'
      AND COLUMN_NAME = 'steamid64'
");
list($exists) = $col_exists->fetch_row();

if (!$exists) {
    echo "<b>Adding steamid64 column to hlstats_Users...</b><br />";
    $db->query("ALTER TABLE hlstats_Users ADD COLUMN steamid64 VARCHAR(20) NULL DEFAULT NULL AFTER playerId");
    echo "&rarr; hlstats_Users.steamid64 added. Set each admin's SteamID64 from Admin &rarr; Admin Users to enable per-admin Steam login.<br />";
} else {
    echo "&rarr; hlstats_Users.steamid64 already exists - skipping.<br />";
}

// No UNIQUE index here on purpose: the admin-management UI's EditList
// writes SQL '' (not NULL) when a text field is cleared - completely
// normal for any admin without Steam login configured - and a UNIQUE
// constraint would make every admin's cleared field collide with every
// other admin's cleared field (#duplicate-entry-for-key-steamid64).

$dbversion = 91;
echo "Updating database schema version.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");

?>
