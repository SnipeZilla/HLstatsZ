<?php
// Deny access
require_once 'php/require/session.php';
define("DIR_NAME",dirname(__FILE__));

// DB_ADDR - The address of the database server, in host:port format.
//           (You might also try setting this to e.g. ":/tmp/mysql.sock" to
//           use a Unix domain socket, if your mysqld is on the same box as
//           your web server.)
define("DB_ADDR", '');

// DB_USER - The username to connect to the database as
define("DB_USER", '');

// DB_PASS - The password for DB_USER
define("DB_PASS", '');

// DB_NAME - The name of the database
define("DB_NAME", '');

// IMAGE_PAT
define("IMAGE_PATH", './hlstatsimg');

// DL_URL Download  Path 'https://xxxxx.xxx/'
define("DL_URL", '');

// GOOGLE Analytics (G-xxxxxx)
define ("GOOGLE", '');

//UMAMI Analytics (Website ID)
define ("UMAMI", '');

// Cache/Refresh all requests
define("CACHE_GLOBAL", "-12 hours");       // Server Stats (totals players, servers stats, maps...)
define("CACHE_PLAYERS", "-15 minutes");    // players, clans, bans, ping
define("CACHE_LIVE", "-60 seconds");       // Live view page
define("CACHE_DAILY", "today 00:05:00");   // Awards: only once a day
?>
