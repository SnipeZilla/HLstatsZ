<?php
if (!defined('IN_UPDATER')) {
    die('Do not access this file directly.');
}

// ---------------------------------------------
// Per-admin Steam login
// ---------------------------------------------
echo "<h3>Per-admin Steam login</h3>";

echo "<b>Adding steamid64 column to hlstats_Users...</b><br />";
$db->query("ALTER TABLE hlstats_Users ADD COLUMN steamid64 VARCHAR(20) NULL DEFAULT NULL AFTER playerId");
echo "&rarr; hlstats_Users.steamid64 added. Set each admin's SteamID64 from Admin &rarr; Admin Users to enable per-admin Steam login.<br />";

// No UNIQUE index here on purpose: the admin-management UI's EditList
// writes SQL '' (not NULL) when a text field is cleared - completely
// normal for any admin without Steam login configured - and a UNIQUE
// constraint would make every admin's cleared field collide with every
// other admin's cleared field (#duplicate-entry-for-key-steamid64).

$dbversion = 91;
echo "Updating database schema version.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");

?>
