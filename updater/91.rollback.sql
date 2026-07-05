-- Manual rollback for updater/91.php (per-admin Steam login).
--
-- Not part of the automatic updater (which is forward-only, driven by
-- pages/admintasks/updater.php looping updater/<dbversion+1>.php). Run
-- this by hand against the panel's database only if migration 91 causes
-- problems and you need to return to the dbversion 90 schema.
--
-- Assumes 91.php ran to completion (column + index both exist). No
-- IF EXISTS guards here (portability across MySQL/MariaDB is a wash for
-- a script you run once, by hand, when you already know the state).

ALTER TABLE hlstats_Users DROP INDEX steamid64;
ALTER TABLE hlstats_Users DROP COLUMN steamid64;
UPDATE hlstats_Options SET `value` = '90' WHERE `keyname` = 'dbversion';
