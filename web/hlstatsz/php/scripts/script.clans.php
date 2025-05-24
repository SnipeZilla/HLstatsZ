<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$folder  = '../../cache/clans';

//order by:
$order='clan_skill';
$sort='DESC';
if ( isset($request['order']) ) {

    $order = $request['order'][0]['column']>0?$request['columns'][$request['order'][0]['column']]['data']:'clan_skill';
    $sort  = strtoupper($request['order'][0]['dir']);

}

//Search
$search=trim($request['search']['value']);
if ( empty($search) && $search !== $request['search']['value'] ) {

   Send( array(
        "search"          => $request['search']['value'],
        "draw"            => $request['draw'],
        "recordsTotal"    => $request['total'],
        "recordsFiltered" => 0,
        "data"            => null
    ));

   ob_end_flush();
   exit();

 }

$file = $folder.'/clans-'.$request['game'].'-'.$request["start"].'-'.$order.'-'.$sort.'.json';

$query = $order != 'clan_skill' || $search != '' || !file_exists($file) || strtotime(CACHE_PLAYERS) > filemtime($file);

// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';
    $sql = "SELECT COUNT(DISTINCT(clan))
            FROM 
                hlstats_players 
            WHERE
                hideranking <> 2 
                AND lastAddress <> ''
                AND game = '".$request['game']."'
                AND clan <> 0";

    $data = array();
    $result = $mysqli->query($sql);
    $data['total'] = $result->fetch_row()[0];
    $recordsFiltered = $data['total'] ;


    if ( $search ) {
    // Searching:
    $search = $mysqli->real_escape_string($search);
    $sql = "
            WITH RankedClans AS (
                SELECT 
                ROW_NUMBER() OVER (ORDER BY AVG(hlstats_players.skill) DESC, SUM(hlstats_players.kills) DESC) AS rank_position,
                hlstats_players.clan as clan,
                hlstats_clans.tag,
                hlstats_clans.name as name,
                SUM(hlstats_players.kills) AS clan_kills,
                SUM(hlstats_players.deaths) AS clan_deaths,
                SUM(hlstats_players.suicides) AS clan_suicides,
                FLOOR(AVG(hlstats_players.skill)) AS clan_skill,
                SUM(hlstats_players.shots) AS clan_shots,
                SUM(hlstats_players.hits) AS clan_hits,
                SUM(hlstats_players.headshots) AS clan_headshots,
                COUNT(hlstats_Players.playerId) AS members,
                ROUND(SUM(IF(hlstats_players.deaths=0, 0, hlstats_players.kills/hlstats_players.deaths)), 2) AS kd,
                ROUND(SUM(IF(hlstats_players.kills=0, 0, hlstats_players.headshots/hlstats_players.kills)), 2) AS hsk
            FROM
                hlstats_players
            		LEFT JOIN
            			hlstats_clans
            		ON
            			hlstats_players.clan = hlstats_clans.clanId
            WHERE
                hlstats_players.hideranking <> 2 
                AND hlstats_players.lastAddress <> ''
                AND hlstats_players.game = '".$request['game']."'
                AND clan <> 0
            GROUP BY
                hlstats_players.clan
            )
            SELECT *
            FROM
                RankedClans
            WHERE
                ".(filter_var($search, FILTER_VALIDATE_INT)? "clan = '".$search."'" : "name LIKE '%".$search."%'")."
            ORDER BY
                rank_position,
                name DESC
            LIMIT
                ".$request['length']." OFFSET ".$request['start'];
            
    } else {
    $sql = "SELECT 
                ROW_NUMBER() OVER (ORDER BY AVG(hlstats_players.skill) DESC, SUM(hlstats_players.kills) DESC) AS rank_position,
                hlstats_players.clan,
                hlstats_clans.tag,
                hlstats_clans.name,
                SUM(hlstats_players.kills) AS clan_kills,
                SUM(hlstats_players.deaths) AS clan_deaths,
                SUM(hlstats_players.suicides) AS clan_suicides,
                FLOOR(AVG(hlstats_players.skill)) AS clan_skill,
                SUM(hlstats_players.shots) AS clan_shots,
                SUM(hlstats_players.hits) AS clan_hits,
                SUM(hlstats_players.headshots) AS clan_headshots,
                COUNT(hlstats_Players.playerId) AS members,
                ROUND(SUM(IF(hlstats_players.deaths=0, 0, hlstats_players.kills/hlstats_players.deaths)), 2) AS kd,
                ROUND(SUM(IF(hlstats_players.kills=0, 0, hlstats_players.headshots/hlstats_players.kills)), 2) AS hsk
            FROM
                hlstats_players
            		LEFT JOIN
            			hlstats_clans
            		ON
            			hlstats_players.clan = hlstats_clans.clanId
            WHERE
                hlstats_players.hideranking <> 2 
                AND hlstats_players.lastAddress <> ''
                AND hlstats_players.game = '".$request['game']."'
                AND clan <> 0
            GROUP BY
                hlstats_players.clan
            ORDER BY
                ".$order." ".$sort."
            LIMIT 
                ".$request['length']." OFFSET ".$request['start'];

}
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        if ( $search ) { $recordsFiltered = $result->num_rows; }
    
        while($row = $result->fetch_assoc()) {
            $data['clan'][] = $row;
        }
    
    } else { $recordsFiltered = 0; }

} else { $recordsFiltered = $data['total']; }


$clan=array();
if ( !empty($data['clan']) ) {

    for ($i = 0; $i < count($data['clan']); $i++) {
    
        $clan[$i]['id']=$data['clan'][$i]['clan'];
        $clan[$i]['rank_position']='<em class="hlz-rank">'.$data['clan'][$i]['rank_position'].'</em>';
        $clan[$i]['tag']='<span class=clan-tag>'.$data['clan'][$i]['tag'].'</span>';
        $clan[$i]['name']='<span class=clan-name>'.$data['clan'][$i]['name'].'</span>';
        $clan[$i]['members']=$data['clan'][$i]['members'];
        $clan[$i]['clan_skill']=$data['clan'][$i]['clan_skill'];
        $clan[$i]['clan_kills']=$data['clan'][$i]['clan_kills'];
        $clan[$i]['clan_headshots']=$data['clan'][$i]['clan_headshots'];
        $clan[$i]['clan_deaths']=$data['clan'][$i]['clan_deaths'];
        $clan[$i]['kd']=sprintf('%.2f',$data['clan'][$i]['kd']);
        $clan[$i]['hsk']=sprintf('%.2f',$data['clan'][$i]['hsk']);
    
    }

}
Send( array(
            "search"          => $search,
            "draw"            => $request['draw'],
            "recordsTotal"    => $data['total'],
            "recordsFiltered" => $recordsFiltered,
            "data"            => $clan
    ) ); 

ob_end_flush();

if ( $query && $search == '' && $order == 'clan_skill' ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($data,JSON_PRETTY_PRINT));

}
?>