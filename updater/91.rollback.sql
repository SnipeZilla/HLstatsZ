-- Manual rollback for updater/91.php (per-admin Steam login).
--
-- Not part of the automatic updater (which is forward-only, driven by
-- pages/admintasks/updater.php looping updater/<dbversion+1>.php). Run
-- this by hand against the panel's database only if migration 91 causes
-- problems and you need to return to the dbversion 90 schema.
--
-- Assumes 91.php ran to completion (column exists, no unique index -
-- see 91.php for why there isn't one). No IF EXISTS guard (portability
-- across MySQL/MariaDB is a wash for a script you run once, by hand,
-- when you already know the state).
--
-- If your DB still has the old unique index from an earlier draft of
-- 91.php (i.e. you applied it before this fix), drop that first:
--   ALTER TABLE hlstats_Users DROP INDEX steamid64;

ALTER TABLE hlstats_Users DROP COLUMN steamid64;
UPDATE hlstats_Options SET `value` = '90' WHERE `keyname` = 'dbversion';
