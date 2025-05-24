<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$folder  = '../../cache/awards';
$file = $folder.'/awards-'.$request['game'].'.json';

$query = !file_exists($file) || filemtime($file) < strtotime(CACHE_DAILY);


// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query  = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';
    //awards
    $sql="SELECT
			hlstats_awards.awardId,
			hlstats_awards.awardType,
			hlstats_awards.code,
			hlstats_awards.name,
			hlstats_awards.verb,
			hlstats_awards.d_winner_id,
			hlstats_awards.d_winner_count,
			hlstats_players.lastName AS d_winner_name,
			hlstats_players.flag AS flag,
			hlstats_players.country AS country
		FROM
			hlstats_awards
		LEFT JOIN hlstats_players ON
			hlstats_Players.playerId = hlstats_awards.d_winner_id
		WHERE
			hlstats_Awards.game= '".$request['game']."'
            AND hlstats_Awards.d_winner_id > 0
		ORDER BY
			hlstats_Awards.name";

    $data=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            $data['awards'][]=$row;

        }

    }

    //Daily winner
$sql = "SELECT
            UNIX_TIMESTAMP(hlstats_players_history.eventTime) AS timestamp,
            SUM(hlstats_players_history.kills) as kills,
            SUM(hlstats_players_history.connection_time) as connection_time,
            hlstats_players.playerId as playerId,
            hlstats_players.lastName AS lastName,
            hlstats_players.flag AS flag,
            hlstats_players.country AS country
        FROM
            hlstats_players_history
        LEFT JOIN
            hlstats_players
        ON
            hlstats_players.playerId = hlstats_players_history.playerId
        WHERE 
            hlstats_players_history.eventTime >= CURDATE() - INTERVAL 1 DAY
            AND hlstats_players_history.eventTime < CURDATE()
            AND hlstats_players_history.game = '".$request['game']."'
            AND hlstats_players.lastAddress <> ''
        GROUP BY 
            timestamp, playerId, lastName, flag, country
        ORDER BY
            kills DESC, connection_time DESC 
        LIMIT 30";



    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            $data['daily'][]=$row;

        }

    } else { $data['daily'] = array(); }


    //ranks info
    $sql="SELECT
			rankName,
			minKills,
			maxKills,
			image
		FROM
			hlstats_ranks
		WHERE
			game = '".$request['game']."'	
		ORDER BY
			minKills";

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            $data['ranks'][]=$row;

        }

    }


    //Top30 ranks
    $sql = "SELECT 
                playerId,
                lastName,
                flag,
                country,
                kills
            FROM
                hlstats_players
            WHERE
                hideranking <> 2 
                AND lastAddress <> ''
                AND game = '".$request['game']."'
            ORDER BY
                kills DESC
            LIMIT 30";

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_assoc()) {
            $data['rank30'][] = $row;
        }
    
    } else { $data['rank30'] = array(); }

}

$yesterday='<span style="font-weight:400;font-size:0.85em">'.date('l dS \o\f F Y',strtotime('yesterday')).'</span>';
$dawards='<h2>Daily Awards '.$yesterday.'</h2><div class="awards">';
if (isset($data['awards'])){

    for ( $i=0; $i<count($data['awards']); $i++) {
    
                $image = IMAGE_PATH.'/games/'.$request['game'].'/dawards/'.strtolower($data['awards'][$i]['awardType']).'_'.$data['awards'][$i]['code'].'.png';
                if ( !file_exists(DIR_NAME.'/'.$image) ) {
                    $image = 'styles/css/images/award.png';
                }       
                $dawards .= '<div class="award-item">'.
                               '<img src="'.$image.'" class="award-img" alt="'.$data['awards'][$i]['code'].'" title="'.$data['awards'][$i]['verb'].'">'.
                               '<div class="awards-text"><span style="font-weight:400;">'.$data['awards'][$i]['name'].'</span><br><span style="font-size:0.9em;font-weight:400;">'.$data['awards'][$i]['d_winner_count'].' '.$data['awards'][$i]['verb'].'</span></div>'.
                            '<div style="margin:0 auto"><a href="javascript:hlz.profile2('.$data['awards'][$i]['d_winner_id'].')"><img src="styles/css/images/flags/'.$data['awards'][$i]['flag'].'.svg" width="20px" style="margin-right:8px"'.
                                'title="'.$data['awards'][$i]['country'].'" alt="'.$data['awards'][$i]['flag'].'">'.
                                '<span data-player="'.$data['awards'][$i]['d_winner_id'].'">'.$data['awards'][$i]['d_winner_name'].'</span></a></div>'.
							
							'</div>';
    }

} else { $dawards .= '<span class="awards-text">No Winner Today.</span>'; }
$dawards .='</div>';




$daily='<h2>Today\'s Best Players</h2><div class="container"><div class="text-content"><div>';
if (!empty($data['daily'])){
    for ( $i=0; $i<count($data['daily']); $i++) {
        $daily .= '<div class="tablerow">'.
                    '<div>'.($i+1).'</div><div><a href="javascript:hlz.profile2('.$data['daily'][$i]['playerId'].')"><img src="styles/css/images/flags/'.$data['daily'][$i]['flag'].'.svg" width="20px" style="margin-right:8px" title="'.$data['daily'][$i]['country'].'" alt="'.$data['daily'][$i]['flag'].'"><span data-player="'.$data['daily'][$i]['playerId'].'">'.$data['daily'][$i]['lastName'].'</a></span></div>'.
                    '<div>'.$data['daily'][$i]['kills'].' kills</div>'.
                  '</div>';
    }
}
$daily .= '</div></div></div>';




$ranks='<h2>Ranks system</h2><div class="awards">';
for ( $i=0; $i<count($data['ranks']); $i++) {

$ranks .= '<div class="award-item">'.
               '<img src="'.IMAGE_PATH.'/ranks/'.$data['ranks'][$i]['image'].'.png" alt="'.$data['ranks'][$i]['rankName'].'" title="'.$data['ranks'][$i]['rankName'].' ['.$data['ranks'][$i]['minKills'].' kills]">'.
               '<div class="awards-text">'.
                   '<p>'.$data['ranks'][$i]['rankName'].'</p>'.
                   '<p><em style="font-weight:400;"> '.$data['ranks'][$i]['minKills'].' - '.$data['ranks'][$i]['maxKills'].'  Kills.</em></p>'.
               '</div>'.
           '</div>';


}
$ranks .='</div>';

$rank30='<h2>Top 30 By Rank</h2><div class="container"><div class="text-content"><div>';
for ( $i=0; $i<count($data['rank30']); $i++) {
    $rank30 .= '<div class="tablerow">'.
                '<div>'.($i+1).'</div><div><a href="javascript:hlz.profile2('.$data['rank30'][$i]['playerId'].')"><img src="styles/css/images/flags/'.$data['rank30'][$i]['flag'].'.svg" width="20px" style="margin-right:8px" title="'.$data['rank30'][$i]['country'].'" alt="'.$data['rank30'][$i]['flag'].'"><span data-player="'.$data['rank30'][$i]['playerId'].'">'.$data['rank30'][$i]['lastName'].'</span></a></div>'.
                '<div>'.$data['rank30'][$i]['kills'].' kills</div>'.
              '</div>';
}
$rank30 .= '</div></div></div>';


Send(array('dawards'=>$dawards, 'daily' => $daily, 'ranks' => $ranks, 'rank30' => $rank30));

ob_end_flush();

if ( $query ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($data,JSON_PRETTY_PRINT));

}
?>