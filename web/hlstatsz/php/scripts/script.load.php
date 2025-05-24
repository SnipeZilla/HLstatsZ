<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$sid      = $_GET['sid'];
$folder   = '../../cache/live';
$file     = $folder.'/load-'.$sid.'.json';
$query    = !file_exists($file) ||  strtotime(CACHE_PLAYERS) > filemtime($file);

//Cached file:
if ( !$query ) {

    $load  = json_decode(file_get_contents($file),true);
    $query = $load === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    $sql = "SELECT
                (TRUNCATE(timestamp/3600,0)*3600) as hour,
                MAX(act_players) as ap,
                MAX(max_players) as mp,
                MAX(uptime) as up,
                MAX(fps) as fps
            FROM
                hlstats_server_load
            WHERE
                server_id = '".$sid."' AND timestamp < (UNIX_TIMESTAMP(NOW()) - 86400)
            GROUP BY hour
            ORDER BY
                hour ASC
            LIMIT 8760";

    
    $time=array();
    $ap=array();
    $mp=array();
    $up=array();
    $fps=array();
    $map=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_row()) {
            $time[] = $row[0]*1000;
            $ap[]   = $row[1];
            $mp[]   = $row[2];
            $up[]   = $row[3];
            $fps[]  = $row[4];
            $map[]  = '';
        }
    
    }

    $sql = "SELECT
                timestamp, act_players, max_players, uptime, fps, map
            FROM
                hlstats_server_load
            WHERE
                server_id ='".$sid."' AND timestamp >= (UNIX_TIMESTAMP(NOW()) - 86400)
            ORDER BY
                timestamp ASC";

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_row()) {
            $time[] = $row[0]*1000;
            $ap[]   = $row[1];
            $mp[]   = $row[2];
            $up[]   = $row[3];
            $fps[]  = $row[4];
            $map[]  = $row[5];
        }
    
    }
    $load=array('time'       => $time,
                'act_player' => $ap,
                'max_player' => $mp,
                'uptime'     => $up,
                'fps'        => $fps,
                'map'        => $map);

}

Send(array( "load" => array($load) ));

ob_end_flush();

if ( $query ) {

    $mysqli -> close();
    // Save data:
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($load));

}
?>

    