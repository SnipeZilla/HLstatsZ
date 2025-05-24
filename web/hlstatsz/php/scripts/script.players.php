<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$total   = $request['total'];
$folder  = '../../cache/players';

//order by:
$order='skill';
$sort='DESC';
if ( isset($request['order']) ) {

    $order = $request['order'][0]['column']>0?$request['columns'][$request['order'][0]['column']]['data']:'skill';
    $sort  = strtoupper($request['order'][0]['dir']);

}

//Search
$search=trim($request['search']['value']);
if ( empty($search) && $search !== $request['search']['value'] ) {

   Send( array(
        "search"          => $request['search']['value'],
        "draw"            => $request['draw'],
        "recordsTotal"    => 0,
        "recordsFiltered" => 0,
        "data"            => null
    ));

   ob_end_flush();
   exit();

 }

if ( isset($request['bans']) ) {

    $banned='banned-';
    $ranking='hideranking = 2';

} else {

    $banned='';
    $ranking='hideranking <> 2';

}

$file = $folder.'/players-'.$banned.$request['game'].'-'.$request["start"].'-'.$order.'-'.$sort.'.json';
$recordsFiltered = $total;

$query = $order != 'skill' || $search != '' || !file_exists($file) || strtotime(CACHE_PLAYERS) > filemtime($file);

// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    if ( $banned ) {

		$sql = "SELECT
			        COUNT(playerId)
		        FROM
			        hlstats_Players
		        WHERE
			        game='".$request['game']."'
                    AND hideranking = 2 ";

        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
            $total = $result->fetch_row()[0];
        } else { $total=0; }
    }

    if ( $search ) {
        // Searching:
        $search = $mysqli->real_escape_string($search);
        $sql = "
                WITH RankedPlayers AS (
                    SELECT 
                        ROW_NUMBER() OVER (ORDER BY skill DESC, kills DESC) AS rank_position,
                        playerId,
                        last_event,
                        connection_time,
                        lastName,
                        flag,
                        country,
                        clan,
                        kills,
                        deaths,
                        suicides,
                        skill,
                        shots,
                        hits,
                        headshots,
                        last_skill_change,
                        kill_streak,
                        death_streak,
                        activity,
                        createdate,
                        ROUND(IF(deaths=0, 0, kills/deaths), 2) AS kd,
                        ROUND(IF(kills=0, 0, headshots/kills), 2) AS hsk
                    FROM
                        hlstats_players
                    WHERE
                        ".$ranking."
                        AND lastAddress <> ''
                        AND game = '".$request['game']."'
                )
                SELECT *
                FROM
                    RankedPlayers
                WHERE
                    ".(filter_var($search, FILTER_VALIDATE_INT)? "playerId = '".$search."'" : "lastName LIKE '%".$search."%'")."
                ORDER BY
                    rank_position,
                    lastName DESC 
                LIMIT
                    ".$request['length']." OFFSET ".$request['start'];
            
    } else {
        // Globals stats:
        $sql = "
                SELECT 
                    ROW_NUMBER() OVER (ORDER BY skill DESC, kills DESC) AS rank_position,
                    playerId,
                    last_event,
                    connection_time,
                    lastName,
                    flag,
                    country,
                    clan,
                    kills,
                    deaths,
                    suicides,
                    skill,
                    shots,
                    hits,
                    headshots,
                    last_skill_change,
                    kill_streak,
                    death_streak,
                    activity,
                    createdate,
                    ROUND(IF(deaths=0, 0, kills/deaths), 2) AS kd,
                    ROUND(IF(kills=0, 0, headshots/kills), 2) AS hsk
                FROM
                    hlstats_players
                WHERE
                    ".$ranking."
                    AND lastAddress <> ''
                    AND game = '".$request['game']."'
                GROUP BY
                    playerId
                ORDER BY
                    ".$order." ".$sort."
                LIMIT 
                    ".$request['length']." OFFSET ".$request['start'];
    
    }
    
    $data = array();
    $data['total']=$total;
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        if ( $search ) { $recordsFiltered = $result->num_rows; }
    
        while($row = $result->fetch_assoc()) {
            $data['player'][] = $row;
        }
    
    } else { $recordsFiltered = 0; }

}


$player=array();
if ( isset($data['player']) ){
    for ($i = 0; $i < count($data['player']); $i++) {
        if ($banned) $data['player'][$i]['rank_position']='BAN';
        $hours=floor($data['player'][$i]['connection_time']/3600);
        $minutes=floor(($data['player'][$i]['connection_time'] % 3600)/60);
        if ( $data['player'][$i]['last_skill_change'] > 0 ) { $icon='<span style="color:green;cursor:default;margin-left:8px" title="+'.$data['player'][$i]['last_skill_change'].'">&#x25B2;</span>'; }
        elseif ( $data['player'][$i]['last_skill_change'] < 0 ) { $icon='<span style="color:red;cursor:default;margin-left:8px" title="'.$data['player'][$i]['last_skill_change'].'">&#x25BC;</span>'; }
        else { $icon=''; }
        $player[$i]['rank']='<em class="hlz-rank">'.$data['player'][$i]['rank_position'].'</em>';
        $player[$i]['lastName']='<div class="lastName"><img src="styles/css/images/flags/'.$data['player'][$i]['flag'].'.svg" width="20px" style="margin-right:8px" title="'.$data['player'][$i]['country'].'" alt="'.$data['player'][$i]['flag'].'">'.
        '<span data-player="'.$data['player'][$i]['playerId'].'">'.$data['player'][$i]['lastName'].'</span></div>';
        $player[$i]['skill']= $data['player'][$i]['skill'].$icon;
        $player[$i]['activity']='<meter min="0" max="100" low="25" high="50" optimum="75" value="'.$data['player'][$i]['activity'].'" title="'.date('l, F j, Y @g:i A', $data['player'][$i]['last_event']).'"></meter>';
        $player[$i]['connection_time']='<div class="connection_time">'.($hours>1?'<b>'.$hours.'</b> hours and ':'<b>0</b> hour and ').($minutes>1?'<b>'.$minutes.'</b> minutes':'<b>1</b></> minute').'</div>';
        $player[$i]['kills']=$data['player'][$i]['kills'];
        $player[$i]['deaths']=$data['player'][$i]['deaths'];
        $player[$i]['headshots']=$data['player'][$i]['headshots'];
        $player[$i]['kd']=sprintf('%.2f',$data['player'][$i]['kd']);
        $player[$i]['hsk']=sprintf('%.2f',$data['player'][$i]['hsk']);
    
    }
} else { $data['player']=null; }

Send( array(
            "search"          => $search,
            "draw"            => $request['draw'],
            "recordsTotal"    => $data['total'],
            "recordsFiltered" => $banned && !$search?$data['total']:$recordsFiltered,
            "player"          => $data['player'],
            "data"            => $player
    ) ); 

ob_end_flush();

if ( $query && $search == '' && $order == 'skill' ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode(array('player' => $data['player'], 'total' => $total)));

}
?>