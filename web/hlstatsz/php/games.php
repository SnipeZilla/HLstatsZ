<?php
require_once 'php/require/session.php';
$dir = 'cache';
$file = $dir.'/games.json';
$file2= $dir.'/settings.json';
$query = !file_exists($file) || strtotime(CACHE_GLOBAL) > filemtime($file);
$games = array();

if ( !$query ) {

    $games  = json_decode(file_get_contents($file),true);
    $query = $games === false;

}

if ( $query ) {

    require_once "require/mysqli.php";

    $sql = "SELECT
                a.game, a.serverId, a.address, a.port, a.publicaddress, a.act_map, a.map_started, a.max_players, a.kills, a.headshots, a.name as fname, a.lat, a.lng, a.city, a.country, b.name
            FROM
                hlstats_servers a 
            INNER JOIN
                 hlstats_games b
            ON
                a.game = b.code
            ORDER BY
                a.kills DESC, a.game DESC";

    $game=array();
    $games['server'] = array();
    $games['stats']  = array();
    $games['stats']['server']=0;
    $result = $mysqli->query($sql);
    if ( $result->num_rows > 0 ) {

        while($row = $result->fetch_assoc()) {

            if ( !isset($games['stats'][$row['game']]) ) {

                $game[]=$row['game'];
                $games['stats'][$row['game']] = array('kills'     => intval($row['kills']),
                                                      'headshots' => intval($row['headshots']));
                
            } else {

                $games['stats'][$row['game']]['kills']    += intval($row['kills']);
                $games['stats'][$row['game']]['headshots']+= intval($row['headshots']);

            }

            $games['stats']['SIDs'][$row['serverId']] = $row['game'];
            $games['stats']['server']    += 1;
            $games['server'][$row['game']][] = $row;

        }

    }


    for ( $i=0; $i<count($game); $i++ ) {

        $sql = "SELECT 
                    COUNT(DISTINCT(playerId))
                FROM 
                    hlstats_players 
                WHERE
                    hideranking <> 2 
                    AND lastAddress <> ''
                    AND game = '".$game[$i]."'";
      
        $result = $mysqli->query($sql);
        if ( $result->num_rows > 0 ) {

            $total = $result->fetch_row()[0];

        } else { $total=-1; }

        $games['stats'][$game[$i]]['players']=intval($total);

    }

    file_put_contents($file, json_encode($games));

}

$set = !file_exists($file2);

if ( !$set ) {

    $settings  = json_decode(file_get_contents($file2),true);
    $set = $settings === false;

}

if ( $set ) {
    require_once "require/mysqli.php";

    //Options
    $options=array();
    $options['version']=$mysqli->query("SELECT value FROM hlstats_options WHERE keyname = 'version'")->fetch_row()[0];
    $options['sitename']=$mysqli->query("SELECT value FROM hlstats_options WHERE keyname = 'sitename'")->fetch_row()[0];
    $options['siteurl']=$mysqli->query("SELECT value FROM hlstats_options WHERE keyname = 'siteurl'")->fetch_row()[0];
    $options['forum_address']=$mysqli->query("SELECT value FROM hlstats_options WHERE keyname = 'forum_address'")->fetch_row()[0];
    $options['minActivity']=$mysqli->query("SELECT value FROM hlstats_options WHERE keyname = 'MinActivity'")->fetch_row()[0];
    
    //team
    $game='';
    foreach( $games['server'] as $k => $v ) {
    
        $game .= (empty($game) ? "game='".$k."'" : " OR game='".$k."'");
    
    }
    
    
    $sql = "SELECT
                code, playerlist_index, name
            FROM
                hlstats_teams
            WHERE
                ".$game;
    
    $teams=array();
    $teams['lobby'] = array(0,'Lobby');
    $teams['Spectator'] = array(0,'Spectator');
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_assoc()) {
    
            $teams[$row['code']] = [$row['playerlist_index'],$row['name']];
    
        }
    
    }

    $settings=array("options" => $options, "teams" => $teams);
    file_put_contents($file2, json_encode($settings));

}

if ( !empty($mysqli) ) {
    
    $mysqli -> close();

}
?>