-- Manual rollback for updater/92.php (seed STEAM_ADMIN as superadmin).
--
-- Not part of the automatic updater. Run by hand only if migration 92
-- causes problems. Deletes the row it created (matched by steamid64,
-- so it won't touch a row you've since edited to a different SteamID)
-- and returns dbversion to 91.
--
-- Replace <STEAM_ADMIN_VALUE> with the actual STEAM_ADMIN env var value
-- before running.

DELETE FROM hlstats_Users WHERE steamid64 = '<STEAM_ADMIN_VALUE>';
UPDATE hlstats_Options SET `value` = '91' WHERE `keyname` = 'dbversion';
