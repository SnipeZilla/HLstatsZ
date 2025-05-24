<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
require '../../php/require/mysqli.php';
// hit counter
$mysqli->query("UPDATE hlstats_Options SET value=value+1 WHERE keyname='counter_hits';"); 
  
// visit counter
if ( !isset($_COOKIE['ELstatsNEO_Visit']) || ( isset($_COOKIE['ELstatsNEO_Visit']) && $_COOKIE['ELstatsNEO_Visit'] == 0 ) ) {
    $mysqli->query("UPDATE hlstats_Options SET value=value+1 WHERE keyname='counter_visits';");
    $mysqli -> close();
    setcookie('ELstatsNEO_Visit', '1', time() + (5 * 60), '/');   
}

?>