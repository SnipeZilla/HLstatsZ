<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$folder = '../../cache';
$file = $folder.'/global.json';
$query = !file_exists($file) ||  strtotime(CACHE_GLOBAL) > filemtime($file);
$config = array("img"  => IMAGE_PATH,
                "live" => strtotime("now") - strtotime(CACHE_LIVE),
                "profile" => strtotime("now") - strtotime(CACHE_PLAYERS));

//Cached file:
if ( !$query ) {

    $data  = json_decode(file_get_contents($file),true);

    if ( $data !== false ) {

        Send(array(
            "config"  => $config,
            "trend"   => $data['trend'],
            "stats"   => $data['stats']
        ));
        ob_end_flush();
        exit(); 

    }

}

// MySQL
require '../../php/require/mysqli.php';

$data=array();

// Past 24h trends:
$sql = "SELECT
            game, COUNT(playerId) AS players, SUM(kills) as kills
        FROM
            hlstats_players
        WHERE
            createdate >= (UNIX_TIMESTAMP(NOW()) - 86400)
        GROUP BY
            game";

$trend = array();
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    // Fetch data and store in array
    while($row = $result->fetch_assoc()) {
        $trend[] = $row;
    }
}
$data["trend"]=$trend;

// Globals stats:
$sql = "SELECT 
            COUNT(playerId) AS players, SUM(kills) as kills, SUM(headshots) as headshots, SUM(deaths) as deaths
        FROM
            hlstats_players
        WHERE
            lastAddress <> ''";

$stats = array();
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {

    while($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
}
$data["stats"]=$stats;

//Send data
Send(array("config" => $config,
           "trend"  => $trend,
           "stats"  => $stats));
ob_end_flush();
$mysqli -> close();
// Save data:
if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
file_put_contents($file, json_encode($data));
?>

    