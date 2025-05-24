<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$serverId = !empty($_GET['serverId'])? $_GET['serverId'] : 0;
$draw     = !empty($_GET['draw'])? $_GET['draw'] : 0;
$teams    = json_decode($_GET['teams'],true);
$folder   = '../../cache/live';
$file     = $folder.'/live.json';
$query    = !file_exists($file) ||  strtotime(CACHE_LIVE) > filemtime($file);

//Cached file:
if ( !$query ) {

    $server  = json_decode(file_get_contents($file),true);
    $query = $server === false;

}

if ( $query ) {
    // MySQL
    require '../../php/require/mysqli.php';

    //Maps
    $sql = "SELECT
        serverId, act_map, map_started
    FROM
        hlstats_servers 
    GROUP BY
        serverId";

    $server = array();
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()) {

        $t= ($row['map_started']? round(time()-$row['map_started']) : 0);
        $server[$row['serverId']]['map']  = array($row['act_map'], sprintf('%02d:%02d:%02d', $t/3600, floor($t/60)%60, $t%60), $row['map_started']);
        $server[$row['serverId']]['team'] = array();
        $server[$row['serverId']]['chat'] = array();

    }

    // Live stats:
    $sql = "SELECT
                server_id, player_id, cli_address, cli_country, cli_flag, cli_state, cli_lat, cli_lng, steam_id,
                name, team, kills, deaths, headshots, ping, connected, skill_change, skill
            FROM
                hlstats_livestats
            ORDER BY
                server_id ASC, team ASC, kills DESC, connected DESC";

    $id     = 0;
    $lobby  = array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_assoc()) {

            if ( $id != $row['server_id'] ) {
 
                if ( !empty($lobby) ) {
				
                    $server[$id]['team']['lobby'] = $lobby;
                    $lobby  = array();
				    
                }
                $id = $row['server_id'];
                unset($row['server_id']);

            }

            if ( isset($teams[$row['team']]) ) { 
    
                $server[$id]['team'][$row['team']][] = $row;

            } else {

                $row['team']  = 'lobby';
                $lobby[] = $row; 

            }
    
        }
        if ( !empty($lobby) ) $server[$id]['team']['lobby'] = $lobby;
    
    }

   //live chat

		
        $from=strtotime("-1 hour");
        $sql = "
          SELECT 
            hlstats_events_chat.serverId,
            hlstats_events_chat.message,
            hlstats_players.lastName 
          FROM 
            hlstats_events_chat
          JOIN 
            hlstats_players
          ON 
            hlstats_events_chat.playerId = hlstats_players.playerId
          WHERE 
            UNIX_TIMESTAMP(hlstats_events_chat.eventTime) >= '".$from."'
          ORDER BY 
            hlstats_events_chat.eventTime DESC
          LIMIT 9999";
    
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_assoc()) {
 
            $server[$row['serverId']]['chat'][]=array($row['lastName'],$row['message']);

        }
		
    }

}

$player=array();
foreach ($server as $id => $u) {

    $total=0;
    $server[$id]['bots'] = 0;
    $server[$id]['players'] = 0;
    foreach ($server[$id]['team'] as $k => $v) {

        for ($i = 0; $i < count($v); $i++) {
	    
            if ( $serverId == $id ) {
	    
                if ( !isset($teams[$v[$i]['team']]) ) $v[$i]['team']='lobby';
                $t= ($v[$i]['connected']? round(time()-$v[$i]['connected']) : 0);
                if ( $v[$i]['skill_change'] > 0 ) { $icon='<span style="color:green;cursor:default;margin-left:8px">&#x25B2;</span>'; }
                elseif ( $v[$i]['skill_change'] < 0 ) { $icon='<span style="color:red;cursor:default;margin-left:8px">&#x25BC;</span>'; }
                else { $icon=''; }
                $bot=$v[$i]['steam_id']=='BOT';
                $player[$total]['flag']='<img src="styles/css/images/flags/'.($bot?'bot':$v[$i]['cli_flag']).'.svg" width="20px" style="margin-right:8px" title="'.($bot?"I'm a bot":$v[$i]['cli_country']).'" alt="'.($bot?'Bot':$v[$i]['cli_flag']).'">';
                $player[$total]['name']='<span data-player="'.$v[$i]['player_id'].'" title="'.$teams[$v[$i]['team']][1].'">'.$v[$i]['name'].'</span>';
                $player[$total]['played']=sprintf('%02d:%02d:%02d', $t/3600, floor($t/60)%60, $t%60);
                $player[$total]['kills']=$v[$i]['kills'];
                $player[$total]['headshots']=$v[$i]['headshots'];
                $player[$total]['hsk']=sprintf('%.2f',$v[$i]['kills']?$v[$i]['headshots']/$v[$i]['kills']:'-');
                $player[$total]['deaths']=$v[$i]['deaths'];
                $player[$total]['kd']=sprintf('%.2f',$v[$i]['deaths']?$v[$i]['kills']/$v[$i]['deaths']:'-');
                $player[$total]['skill']= $v[$i]['skill'].$icon;
                $player[$total]['change']= $v[$i]['skill_change'].$icon;
                $player[$total]['team']='team_'.$teams[$v[$i]['team']][0];
                $total++;
	    
            }
	    
            $server[$id]['bots']   += intval($v[$i]['steam_id']=='BOT');
            $server[$id]['players']+= intval($v[$i]['steam_id']!='BOT');;

	    }

    } 

}
//$server['firstEvent']=$firstEvent;
Send( array(
            "sid"             => $serverId,
            "config"          => array("img"  => IMAGE_PATH,
                                       "live" => strtotime("now") - strtotime(CACHE_LIVE)),
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "server"          => $server,
            "data"            => $player
));

ob_end_flush();
if ($query ) {
    $mysqli -> close();
    // Save data:
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($server));
}

?>

    