<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$folder  = '../../cache/clans';

//order by:
$order='skill';
$sort='DESC';
if ( isset($request['order']) ) {

    $order = $request['order'][0]['column']>0?$request['columns'][$request['order'][0]['column']]['data']:'skill';
    $sort  = strtoupper($request['order'][0]['dir']);

}

$file = $folder.'/clanplayers-'.$request['game'].'-'.$request["clan"].'-'.$request["start"].'-'.$order.'-'.$sort.'.json';
$recordsFiltered = $request['total'];

$query = $order != 'skill' || !file_exists($file) || strtotime(CACHE_PLAYERS) > filemtime($file);

// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    // Members:
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
                    hideranking <> 2 
                    AND lastAddress <> ''
                    AND game = '".$request['game']."'
            )
            SELECT *
            FROM
                RankedPlayers
            WHERE
                clan = '".$request['clan']."'
            ORDER BY
                ".$order." ".$sort."
            LIMIT 
                ".$request['length']." OFFSET ".$request['start'];

    
    $data = array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
    } else { $recordsFiltered = 0; }

}


$player=array();
if ( !empty($data) ) {
    for ($i = 0; $i < count($data); $i++) {
    
        $hours=floor($data[$i]['connection_time']/3600);
        $minutes=floor(($data[$i]['connection_time'] % 3600)/60);
        if ( $data[$i]['last_skill_change'] > 0 ) { $icon='<span style="color:green;cursor:default;margin-left:8px" title="+'.$data[$i]['last_skill_change'].'">&#x25B2;</span>'; }
        elseif ( $data[$i]['last_skill_change'] < 0 ) { $icon='<span style="color:red;cursor:default;margin-left:8px" title="'.$data[$i]['last_skill_change'].'">&#x25BC;</span>'; }
        else { $icon=''; }
        $player[$i]['rank']='<em class="hlz-rank">'.$data[$i]['rank_position'].'</em>';
        $player[$i]['lastName']='<img src="styles/css/images/flags/'.$data[$i]['flag'].'.svg" width="20px" style="margin-right:8px" title="'.$data[$i]['country'].'" alt="'.$data[$i]['flag'].'">'.
                                '<span data-player="'.$data[$i]['playerId'].'">'.$data[$i]['lastName'].'</span>';
        $player[$i]['skill']= $data[$i]['skill'].$icon;
        $player[$i]['activity']='<meter min="0" max="100" low="25" high="50" optimum="75" value="'.$data[$i]['activity'].'" title="'.date('l, F j, Y @g:i A', $data[$i]['last_event']).'"></meter>';
        $player[$i]['connection_time']=($hours>1?'<b>'.$hours.'</b> hours and ':'<b>0</b> hour and ').($minutes>1?'<b>'.$minutes.'</b> minutes':'<b>1</b></> minute');
        $player[$i]['kills']=$data[$i]['kills'];
        $player[$i]['deaths']=$data[$i]['deaths'];
        $player[$i]['headshots']=$data[$i]['headshots'];
        $player[$i]['kd']=sprintf('%.2f',$data[$i]['kd']);
        $player[$i]['hsk']=sprintf('%.2f',$data[$i]['hsk']);
    
    }
}
Send( array(
            "draw"            => $request['draw'],
            "recordsTotal"    => $request['total'],
            "recordsFiltered" => $recordsFiltered,
            "player"          => $data,
            "data"            => $player
    ) ); 

ob_end_flush();

if ( $query && $order == 'skill' ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($data));

}
?>