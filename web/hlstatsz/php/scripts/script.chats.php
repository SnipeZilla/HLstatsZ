<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$request['servers']=json_decode(rawurldecode($request['servers']),true);
$folder  = '../../cache/chats';

//order by:
$order='time';
$sort='DESC';
if ( isset($request['order']) ) {

    $order = $request['order'][0]['column']>0?$request['columns'][$request['order'][0]['column']]['data']:'time';
    $sort  = strtoupper($request['order'][0]['dir']);

}

$where=array();
$fname=array();
for ( $i=0; $i<count($request['servers']); $i++ ) {
    $where[]=$request['servers'][$i]['serverId'];
    $fname[$request['servers'][$i]['serverId']]=$request['servers'][$i]['fname'];
}
$where=str_replace(['[', ']'], ['(', ')'],json_encode($where));

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

$file = $folder.'/chats-'.$request['game'].'-'.$request["start"].'.json';

$query = $order != 'time' || $search != '' || !file_exists($file) || strtotime(CACHE_PLAYERS) > filemtime($file);


// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    $sql="  SELECT
                COUNT(*) AS total_entries
            FROM
                hlstats_events_chat
            INNER JOIN
                hlstats_players
            ON
                hlstats_players.playerId = hlstats_events_chat.playerId
            WHERE
               hlstats_events_chat.serverId IN ".$where;

    $data = array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        $data['total']=intval($result->fetch_row()[0]);
        $recordsFiltered=$data['total'];

    } else {

        $data['total']=0;
        $recordsFiltered=0;

    }

    if ( $search ) {
    // Searching:
    $search = $mysqli->real_escape_string($search);
    $sql = "
            SELECT 
                hlstats_events_chat.eventTime as time,
                hlstats_events_chat.serverId as server,
                hlstats_events_chat.map as map,
                hlstats_events_chat.playerId,
                hlstats_events_chat.message as message,
                hlstats_players.lastName as lastName,
                hlstats_players.country,
                hlstats_players.flag
            FROM
                hlstats_events_chat
			INNER JOIN
				hlstats_players
			ON
				hlstats_players.playerId = hlstats_events_chat.playerId
            WHERE
                hlstats_events_chat.message LIKE '%".$search."%'
            ORDER BY
                time DESC
            LIMIT
                ".$request['length']." OFFSET ".$request['start'];
            
    } else {
    // Globals stats:
    $sql = "SELECT 
                hlstats_events_chat.eventTime as time,
                hlstats_events_chat.serverId as server,
                hlstats_events_chat.map as map,
                hlstats_events_chat.playerId,
                hlstats_events_chat.message as message,
                hlstats_players.lastName as lastName,
                hlstats_players.country,
                hlstats_players.flag
            FROM
                hlstats_events_chat
			INNER JOIN
				hlstats_players
			ON
				hlstats_players.playerId = hlstats_events_chat.playerId
            WHERE
                hlstats_events_chat.serverId IN ".$where."
            ORDER BY
                ".$order." ".$sort."
            LIMIT 
                ".$request['length']." OFFSET ".$request['start'];
    
    }
    
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
    
        if ( $search ) { $recordsFiltered = $result->num_rows; }
    
        while($row = $result->fetch_assoc()) {
            $data['chat'][] = array("serverId" => $row['server'],
                            "playerId" => $row['playerId'],
                            "time"     => strtotime($row['time']),
                            "lastName" => $row['lastName'],
                            "country"  => $row['country'],
                            "flag"     => $row['flag'],
                            "message"  => $row['message'],
                            "map"      => $row['map']);
        }
    
    } else { $recordsFiltered = 0; }

} else { $recordsFiltered=$data['total']; }


$chat=array();
if (!empty($data['chat'])){

    for ($i = 0; $i < count($data['chat']); $i++) {
    
        $chat[$i]['time']='<em class="hlz-rank">'.$data['chat'][$i]['time'].'</em>';
        $chat[$i]['lastName']='<img src="styles/css/images/flags/'.$data['chat'][$i]['flag'].'.svg" width="20px" style="margin-right:8px" title="'.$data['chat'][$i]['country'].'" alt="'.$data['chat'][$i]['flag'].'">'.
                                '<span data-player="'.$data['chat'][$i]['playerId'].'">'.$data['chat'][$i]['lastName'].'</span>';
        $chat[$i]['message']= '<div class="hlz-message">'.$data['chat'][$i]['message'].'</div>';
        $chat[$i]['server']=str_replace('\\', '',$fname[$data['chat'][$i]['serverId']]);
        $chat[$i]['map']=$data['chat'][$i]['map'];
    
    }

}
Send( array(
            "search"          => $search,
            "draw"            => $request['draw'],
            "recordsTotal"    => $data['total'],
            "recordsFiltered" => $recordsFiltered,
            "chat"            => $data,
            "data"            => $chat
    ) ); 

ob_end_flush();


if ( $query && $search == '' ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($data));

}
?>