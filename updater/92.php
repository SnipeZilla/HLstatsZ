<?php
if (!defined('IN_UPDATER')) {
    die('Do not access this file directly.');
}

// ---------------------------------------------
// Seed STEAM_ADMIN as a real superadmin row
// ---------------------------------------------
echo "<h3>Seed STEAM_ADMIN as a superadmin</h3>";

// Before updater/91.php, STEAM_ADMIN was an invisible env-var-only
// special case: Auth::__construct() granted acclevel 100 to whoever's
// SteamID64 matched it, with no corresponding hlstats_Users row. Sites
// upgrading to per-admin Steam login would silently lose that access
// (beyond the legacy-bootstrap fallback in Auth) unless STEAM_ADMIN is
// promoted to an actual, manageable admin row. This does that once,
// automatically, so the transition is seamless.
if (defined('STEAM_ADMIN') && STEAM_ADMIN !== '') {
    $db->query("SELECT username FROM hlstats_Users WHERE steamid64='" . $db->escape(STEAM_ADMIN) . "' LIMIT 1");

    if ($db->num_rows() > 0) {
        $db->free_result();
        echo "&rarr; STEAM_ADMIN already has an admin row — nothing to do.<br />";
    } else {
        $db->free_result();

        // "SteamAdmin" matches the legacy fallback's session username in
        // pages/admin.php. Guard against an unlikely pre-existing row
        // with that exact username (e.g. a manually created account).
        $username = 'SteamAdmin';
        $suffix = 0;
        do {
            $candidate = $suffix === 0 ? $username : $username . $suffix;
            $db->query("SELECT username FROM hlstats_Users WHERE username='" . $db->escape($candidate) . "' LIMIT 1");
            $taken = $db->num_rows() > 0;
            $db->free_result();
            $suffix++;
        } while ($taken);

        // password left empty on purpose: md5('') never matches a
        // stored empty string in Auth::checkPass(), so this row is
        // Steam-login-only until an admin sets a real password for it.
        $db->query("
            INSERT INTO hlstats_Users (username, password, acclevel, playerId, steamid64)
            VALUES ('" . $db->escape($candidate) . "', '', 100, 0, '" . $db->escape(STEAM_ADMIN) . "')
        ");
        echo "&rarr; Seeded superadmin row '" . htmlspecialchars($candidate, ENT_QUOTES, 'UTF-8') . "' for STEAM_ADMIN.<br />";
    }
} else {
    echo "&rarr; STEAM_ADMIN not set — nothing to seed.<br />";
}

$dbversion = 92;
echo "Updating database schema version.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");

?>
