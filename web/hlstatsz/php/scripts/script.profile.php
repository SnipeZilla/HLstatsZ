<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$player = json_decode($_GET['player'],true);
$game=$_GET['game'];
$fname=isset($_GET['fname'])?json_decode(rawurldecode($_GET['fname']),true):'+++';
$folder = '../../cache/profile/'.$player['playerId'];
$file=$folder.'/profile-'.$game.'.json';
$file2=$folder.'/stats-'.$game.'.json';
$card=array();
$stats=array("killer"=>array(),
             "victim"=>array(),
             "weapon"=>array(),
             "mm"    =>array());

$query = !file_exists($file) || !file_exists($file2) || $player['last_event'] > filemtime($file);

if ( !$query ){

    $player = json_decode(file_get_contents($file),true);
    $stats  = json_decode(file_get_contents($file2),true);
    $query = $player === false || $stats === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    //coming from live index page
    if ( !isset($player['rank_position']) ) {

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
                        AND game = '".$game."'
                )
                SELECT *
                FROM
                    RankedPlayers
                WHERE
                    playerId = '".$player['playerId']."'";

            $data=array();
            $result = $mysqli->query($sql);
            if ($result->num_rows > 0) {
            
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            
            } else { exit(); }
            $player=$data[0];
    }

//-Card 1

    $sql="
        SELECT
            ROUND(ROUND(SUM(ping) / COUNT(ping), 0) / 2, 0) AS av_latency
        FROM
            hlstats_Events_Latency
        WHERE 
            playerId = '".$player['playerId']."'";
    $result = $mysqli->query($sql);
    list($player['av_latency']) = $result->fetch_row();

    // Favorite server
    $sql="
        SELECT
            hlstats_Events_Entries.serverId,
            hlstats_Servers.name,
            COUNT(hlstats_Events_Entries.serverId) AS cnt
        FROM
            hlstats_Events_Entries
        INNER JOIN
            hlstats_Servers
        ON
            hlstats_Servers.serverId = hlstats_Events_Entries.serverId
        WHERE 
            hlstats_Events_Entries.playerId = '".$player['playerId']."'
        GROUP BY
            hlstats_Events_Entries.serverId
        ORDER BY
            cnt DESC
        LIMIT
            1";

    $result = $mysqli->query($sql);
    list($player['favServerId'], $player['favServername']) = $result->fetch_row();

    //Favorite map
    $sql="
        SELECT
            map,
            COUNT(map) AS cnt
        FROM
            hlstats_Events_Entries
        WHERE
            playerId = '".$player['playerId']."'
        GROUP BY
            map
        ORDER BY
            cnt DESC
        LIMIT
            1";
    $result = $mysqli->query($sql);
    list($player['favMap']) = $result->fetch_row();

}


if ( !$player['favServername'] ) $player['favServername']=$fname;
$ping = ($player['av_latency']? $player['av_latency'].'ms' : '-');
    if ( $player['rank_position'] == 1 )  { $rc='first'; }
elseif ( $player['rank_position'] == 2 )  { $rc='second'; }
elseif ( $player['rank_position'] == 3 )  { $rc='third'; }
elseif ( $player['rank_position'] <= 30 ) { $rc='top30'; }
elseif ( $player['rank_position'] <= 100 ) { $rc='top100'; }
else   { $rc=''; }

$card[1] = array("card"  => 1,
                 "data" => '<img src="styles/css/images/games/'.$game.'-header.jpg" alt="tf">
                            <div><h2 title="Favorite server">'.stripslashes($player['favServername']).'</h2></div>
                            <div class="container">
                                <div class="text-content">
                                    <div>
                                        <div class="tablerow">
                                            <div>Rank:</div><div class="rank '.$rc.'">'.$player['rank_position'].'</div>
                                        </div>
                                        <div class="tablerow skill">
                                            <div>Point:</div><div class="z">'.$player['skill'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>First Seen:</div><div class="z"><em>'.date('l, F j, Y @g:i A', $player['createdate']).'</em></div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Last Seen:</div><div class="z"><em>'.date('l, F j, Y @g:i A', $player['last_event']).'</em></div>
                                        </div>
                                        <div class="tablerow">
                                           <div>Favorite Map:</div><div class="z">'.($player['favMap']?$player['favMap']:"-").'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Latency:</div><div class="z">'.$ping.'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Kills:</div><div class="z">'.$player['kills'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Kill Streak:</div><div class="z">'.$player['kill_streak'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Headshots:</div><div class="z">'.$player['headshots'].' ('.floor(($player['headshots']/($player['kills']?$player['kills']:1))*100).'%)</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Deaths:</div><div class="z">'.$player['deaths'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Death Streak:</div><div class="z">'.$player['death_streak'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Suicides:</div><div class="z">'.$player['suicides'].'</div>
                                        </div>
                                        <div class="tablerow">
                                            <div>Kills per min:</div><div class="z">'.sprintf('%.4f',$player['kills']/(($player['connection_time']+1)/60)).'</div>
                                        </div>
                                         <div class="tablerow">
                                            <div>Deaths per min:</div><div class="z">'.sprintf('%.4f',$player['deaths']/(($player['connection_time']+1)/60)).'</div>
                                        </div>
                                         <div class="tablerow">
                                            <div>Kills/deaths:</div><div class="z">'.$player['kd'].'</div>
                                        </div>
                                         <div class="tablerow">
                                            <div>Headshots/kills:</div><div class="z">'.$player['hsk'].'</div>
                                        </div>
                                   </div>
                                </div>
                            </div>'
                );

//-Card 2 Trend
if ( $query ) {

    $sql="
        SELECT
            UNIX_TIMESTAMP(eventTime) AS timestamp,
            SUM(connection_time) as total_time,
            SUM(kills) as total_kills,
            SUM(deaths) as total_deaths,
            SUM(suicides) as total_suicides,
            SUM(skill) as total_skill,
            SUM(shots) as total_shots,
            SUM(hits) as total_hits,
            SUM(headshots) as total_hs,
            SUM(kill_streak) as total_kill_streak,
            SUM(death_streak) as total_death_streak
        FROM
            hlstats_Players_History
        WHERE playerId = '".$player['playerId']."' 
              AND connection_time > 0
              AND game = '".$game."'
        GROUP BY
            timestamp
        ORDER BY
            timestamp DESC";

    $trend=array('timestamp'=>array(),
                 'total_time'=>array(),
                 'total_kills'=>array(),
                 'total_deaths'=>array(),
                 'total_suicides'=>array(),
                 'total_skill'=>array(),
                 'total_shots'=>array(),
                 'total_hs'=>array(),
                 'total_kill_streak'=>array(),
                 'total_death_streak'=>array()
            );
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            $trend['timestamp'][]          = intval($row['timestamp'])*1000;
            $trend['total_time'][]         = intval($row['total_time']);
            $trend['total_kills'][]        = intval($row['total_kills']);
            $trend['total_deaths'][]       = -intval($row['total_deaths']);
            $trend['total_suicides'][]     = intval($row['total_suicides']);
            $trend['total_skill'][]        = intval($row['total_skill']);
            $trend['total_shots'][]        = intval($row['total_shots']);
            $trend['total_hs'][]           = intval($row['total_hs']);
            $trend['total_kill_streak'][]  = intval($row['total_kill_streak']);
            $trend['total_death_streak'][] = -intval($row['total_death_streak']);

        }

    }
    $stats["trend"]=$trend;

}

$chart='<h2>Statistics</h2><div class="charts"><div class="chart"></div><div class="chart"></div></div>';
$card[2] =  array("card"  => 2,
                   "data"  => $chart,
                   "trend" => $stats["trend"]);


//-Card 3-4 Rank
if ( $query ) {

    $sql="
        SELECT
            rankName,
            image,
            minKills
        FROM
            hlstats_Ranks
        WHERE
            game = '".$game."'
        ORDER BY
            minKills ASC ";

    $player['rank']=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            $player['rank'][]=array("rankName" => $row['rankName'],
                                    "image"    => $row['image'],
                                    "minKills" => $row['minKills']);
            if ( $row['minKills'] > $player['kills'] ) break;

        }

    }

}

$Rank=array();
$actRank='';
for ( $i=0; $i<count($player['rank']); $i++) {


    if ( $i+1 == count($player['rank']) || $player['rank'][$i]['minKills'] > $player['kills'] ) {

        if ( count($Rank) < 1 ) {

            $actRank = '<div class="award"><img src="'.IMAGE_PATH.'/ranks/'.$player['rank'][$i]['image'].'.png" alt="'.$player['rank'][$i]['rankName'].'" title="'.$player['rank'][$i]['rankName'].'"></div>';

        } else {

           $actRank = $Rank[$i-1];
           unset($Rank[count($Rank)-1]);

        }

        if ( $i < count($player['rank']) && $i > 0 ) {

            $nextKills = $player['rank'][$i]['minKills']-$player['kills'];
            $nextRank  = $player['rank'][$i]['minKills']-$player['rank'][$i-1]['minKills'];
            if ( $nextRank) { $percentKills  = (1-($nextKills/$nextRank))*100; }
            else { $percentKills=0; }

        } else { $nextKills=1;  $nextRank=1; $percentKills=0; } 

    } else {

        $Rank[] = '<div class="award"><img src="'.IMAGE_PATH.'/ranks/'.$player['rank'][$i]['image'].'.png" alt="'.$player['rank'][$i]['rankName'].'" title="'.$player['rank'][$i]['rankName'].' ['.$player['rank'][$i]['minKills'].' kills]"></div>';
    }
    
}
$rh = '<h2>Rank History</h2><div class="awards">'.join('',$Rank).'</div>';
$r  = '<h2 class="daily">Rank</h2><div class="awards gg">'.$actRank.'</div>';

if ( $actRank ) {

    $r .= '<div style="position:relative;text-align:center"><meter min="0" max="100" low="25" high="50" optimum="75" value="'.$percentKills.'"></meter><div>Next Rank: '.$nextKills.($nextKills>1?' kills':' kill').' needed.</div></div>';

}

$card[3] = array("card"  => 3,
                 "data"  => $rh);
$card[4] =  array("card"  => 4,
                 "data"  => $r);


//-Card 5 Ribbons
if ( $query ) {

    $sql="
        SELECT
            a.awardCode, a.ribbonName, a.special, a.image, a.awardCount
        FROM
            hlstats_Ribbons a
        LEFT JOIN
            hlstats_Players_Ribbons b
        ON
            a.ribbonId = b.ribbonId AND a.game = b.game
        WHERE
            b.playerId = '".$player['playerId']."'
           AND b.game = '".$game."' ";
    
    $player['awards']=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        while($row = $result->fetch_assoc()) {
            if ( !isset($player['awards'][$row['awardCode']]) ) {
    
                $player['awards'][$row['awardCode']]=array("ribbonName" => $row['ribbonName'],
                                                           "special"    => $row['special'],
                                                           "image"      => $row['image'],
                                                           "awardCount" => $row['awardCount']); 
            } else {
                $player['awards'][$row['awardCode']]['awardCount']+=$row['awardCount'];
            }
    
        }
    
    }

}

$ribbons='<h2>Ribbons</h2><div class="awards">';
foreach ( $player['awards'] as $award ) {

    $image = IMAGE_PATH.'/games/'.$game.'/ribbons/'.$award['image'];
    if ( !file_exists(DIR_NAME.'/'.$image) ) {
        $image = 'styles/css/images/award.png';
     }  
      
    $ribbons .= '<div class="award"><img src="'.$image.'" alt="'.$award['ribbonName'].'" title="'.$award["ribbonName"].' ['.$award['awardCount'].'x]"></div>';
		
}
$ribbons .='</div>';

$card[5] = array("card"  => 5,
                 "data"  => $ribbons);

//-Card 6 Awards
if ( $query ) {

    $sql="
        SELECT
            awardType, code, name, d_winner_count, g_winner_count, d_winner_id, g_winner_id
        FROM
            hlstats_Awards
        WHERE
            game = '".$game."'
            AND ( g_winner_id = '".$player['playerId']."' OR d_winner_id = '".$player['playerId']."')";

    $player['g_awards']=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {

            if ( $row['d_winner_id'] !== $player['playerId'] ) {
                $row['d_winner_count']=0;
            }
            if ( $row['g_winner_id'] !== $player['playerId'] ) {
                $row['g_winner_count']=0;
            }
            $player['g_awards'][] = array("image" => strtolower($row['awardType']).'_'.strtolower($row['code']).'.png',"name"=>$row['name'], "daily" => $row['d_winner_count'], "global" => $row['g_winner_count'] ); 

        }

    }

}

$global='';
$daily='';
for ( $i=0; $i<count($player['g_awards']); $i++) {

    if ( $player['g_awards'][$i]['global'] ) {

        $image = IMAGE_PATH.'/games/'.$game.'/gawards/'.$player['g_awards'][$i]['image'];
        if ( !file_exists(DIR_NAME.'/'.$image) ) {
            $image = 'styles/css/images/award.png';
        }    

        $global .='<div class="award"><img src="'.$image.'" alt="'.$player['g_awards'][$i]['name'].'" title="'.$player['g_awards'][$i]['name'].'"></div>';

    }
    if ( $player['g_awards'][$i]['daily'] ) {

        $image = IMAGE_PATH.'/games/'.$game.'/dawards/'.$player['g_awards'][$i]['image'];
        if ( !file_exists(DIR_NAME.'/'.$image) ) {
            $image = 'styles/css/images/award.png';
        }  
        $daily .='<div class="award"><img src="'.$image.'" alt="'.$player['g_awards'][$i]['name'].'" title="'.$player['g_awards'][$i]['name'].'"></div>';

    }

}


$ribbons='<h2>Global Awards</h2><div class="awards gg">'.$global.'</div>
          <h2 class="daily">Daily Awards</h2><div class="awards">'.$daily.'</div>';

$card[6] = array("card"  => 6,
                 "data"  => $ribbons);


// Card-7-8
if ( $query ) {

    if ( $player['favMap'] ) {

        //heat map
        $sql="
            SELECT
                pos_x,
                pos_y,
                pos_victim_x,
                pos_victim_y,
                victimId,
                weapon
            FROM
                hlstats_Events_frags
            WHERE
                ( killerId = '".$player['playerId']."' OR victimId = '".$player['playerId']."' )
                AND map = '".$player['favMap']."'
            LIMIT
                30";

        $killer = array( 'pos'=>array(),
                           'pos_victim'=>array() );
        $victim = array( 'pos'=>array(),
                           'pos_victim'=>array() );
        $weapon = array();
        $minmax = array(9999999999,-9999999999);
        $result = $mysqli->query($sql);
        while($row = $result->fetch_assoc()) {

            if ( $row['victimId'] == $player['playerId'] ) {
                $victim['pos'][]=array($row['pos_x'],$row['pos_y']);
                $victim['pos_victim'][]=array($row['pos_victim_x'],$row['pos_victim_y']);
            } else {
                $killer['pos'][]=array($row['pos_x'],$row['pos_y']);
                $killer['pos_victim'][]=array($row['pos_victim_x'],$row['pos_victim_y']);
                $weapon[]=$row['weapon'];
            }
            $minmax=array(min($row['pos_x'],$row['pos_victim_x'],$minmax[0]),max($row['pos_x'],$row['pos_victim_x'],$minmax[1]));

        }
        $values = array_count_values($weapon);
        arsort($values);
        $weapon=array_slice(array_keys($values), 0, 1, true);
        $stats["killer"]=$killer;
        $stats["victim"]=$victim;
        $stats["weapon"]=$weapon;
        $stats["mm"]=$minmax;

    }

}

$map = '';
$image = IMAGE_PATH.'/games/'.$game.'/maps/'.$player['favMap'].'.png';
if ( !file_exists(DIR_NAME.'/'.$image) ) {
    $image = IMAGE_PATH.'/games/'.$game.'/maps/'.$player['favMap'].'.jpg';
    if ( !file_exists(DIR_NAME.'/'.$image) ) {
        $image = IMAGE_PATH.'/no-map.png';
    }
}

$map = '<div class="map"><img src="'.$image.'" alt="'.$player['favMap'].'" title="'.$player['favMap'].'"><div class="maptitle">'.($player['favMap']?$player['favMap']:'-').'</div></div>';

$weapon='<div class="awards gw">';
for ( $i=0; $i<count($stats["weapon"]); $i++) {

        $image = IMAGE_PATH.'/games/'.$game.'/weapons/'.$stats["weapon"][$i].'.png';
        if ( !file_exists(DIR_NAME.'/'.$image) ) {
            $weapon .='<div class="weapon">< '.$stats["weapon"][$i].' ></div>';
        } else {
            $weapon .='<div class="weapon"><img src="'.$image.'" alt="'.$stats["weapon"][$i].'" title="'.$stats["weapon"][$i].'"></div>';
        }  

}
$weapon .='</div>';
$card[7] = array("card"  => 7,
                 "data"  => '<h2>Favorite Map</h2><div class="charts"><div class="chart">'.$map.'</div><div class="chart"></div></div>',
                 "stats" => $stats);
$card[8] = array("card"  => 8,
                 "data"  => '<h2>Favorite Weapons</h2>'.$weapon);

if ( $query ) {

    //Send(array("id" =>$player['playerId'], "card" => array($card[1],$card[2],$card[3],$card[4],$card[5],$card[6],$card[7],$card[8])));

    //-Card 0 STEAM profile
    $sql="
        SELECT
            CAST(LEFT(uniqueId,1) AS unsigned) + CAST('76561197960265728' AS unsigned) + CAST(MID(uniqueId, 3,10)*2 AS unsigned) AS steam64,
            uniqueId        
        FROM
            hlstats_PlayerUniqueIds
        WHERE
            playerId = '".$player['playerId']."'";
    
    $result = $mysqli->query($sql);
    list($player['steam64'],$player['steam']) = $result->fetch_row();
    
    if ( empty($player['steam64']) || $player['steam64'] == '76561197960265728' || preg_match('/^BOT/i',$player['steam64']) ) {
       Send(array("error"=>'error: '.$player['steam64']));
       exit();
    }
    
    $url = "https://steamcommunity.com/profiles/".$player['steam64']."?xml=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    $response = curl_exec($curl);
    curl_close($curl);
    
    $xml = simplexml_load_string($response);

    $groups=$xml->groups?$xml->groups->group[0]:'';
 

    $player['steamID']       = $xml->steamID?str_replace(array("//<![CDATA[","//]]>"),"",$xml->steamID):'Steam error: reload page';
    $player['avatarFull']    = $xml->avatarFull?str_replace(array("//<![CDATA[","//]]>"),"",$xml->avatarFull):'';
    $player['stateMessage']  = $xml->stateMessage?str_replace(array("//<![CDATA[","//]]>"),"",$xml->stateMessage):'';
    $player['vacBanned']     = $xml->vacBanned?intval($xml->vacBanned):'';
    $player['tradeBanState'] = $xml->tradeBanState?str_replace(array("//<![CDATA[","//]]>"),"",$xml->tradeBanState):'';

    if ( $groups ) {

        $player['groupURL']      = str_replace(array("//<![CDATA[","//]]>"),"",$groups->groupURL);
        $player['groupAvatar']   = str_replace(array("//<![CDATA[","//]]>"),"",$groups->avatarFull);
        $player['groupName']     = str_replace(array("//<![CDATA[","//]]>"),"",$groups->groupName);
        $player['headLine']      = str_replace(array("//<![CDATA[","//]]>"),"",$groups->headline);
        $player['memberCount']   = str_replace(array("//<![CDATA[","//]]>"),"",$groups->memberCount);
        $player['membersInGame'] = str_replace(array("//<![CDATA[","//]]>"),"",$groups->membersInGame);
        $player['membersOnline'] = str_replace(array("//<![CDATA[","//]]>"),"",$groups->membersOnline);

    }

}

if ( isset($player['groupURL']) ) {
    $group='<h2>Favorite Group</h2>
            <a href="https://steamcommunity.com/groups/'.$player['groupURL'].'" target="_blank">
            <div class="container">
                <img style="height:96px" src="'.$player['groupAvatar'].'" alt="group img">
                <div class="text-content">
                    <h3>'.$player['groupName'].'</h3>
                    <em>'.$player['headLine'].'</em>
                    <div class="favoritegroup">
                        <div>
                            <div class="value">'.$player['memberCount'].'</div>
                            <div class="label">Members</div>
                        </div>
                        <div>
                            <div class="value">'.$player['membersInGame'].'</div>
                            <div class="label">In-Game</div>
                        </div>
                        <div>
                            <div class="value">'.$player['membersOnline'].'</div>
                            <div class="label">Online</div>
                        </div>
                    </div>
                </div>
           </div></a>';
} else {
   $group=isset($xml->privacyState) && $xml->privacyState=='public' ? '' : '<p style="text-align:center">This profile is private.</p>'; 
}

if ( $player['clan'] && !isset($player['clanName']) && $query ) {

    $sql="
        SELECT
            tag as clanTag,
            name as clanName        
        FROM
            hlstats_clans
        WHERE
            clanId = '".$player['clan']."'";
    
    $result = $mysqli->query($sql);
    list($player['clanTag'],$player['clanName']) = $result->fetch_row();

}

$clan='';
if ( isset($player['clanName']) ) {

    $clan='<h2>Clan Member</h2><div class="container" title="'.$player['clanTag'].'"><span>'.$player['clanName'].'</span></div>';

}

$image=IMAGE_PATH."/unknown.jpg";
$card[0] = array("card"  => 0,
                 "data" =>  '<div class="container">
                                <img src="'.($player['avatarFull']?$player['avatarFull']:$image).'">
                                <div class="text-content">
                                    <h1>'.$player['steamID'].'<span class="online">'.$player['stateMessage'].'</span></h1>
                                    <p>
                                        <img src="styles/css/images/flags/'.$player['flag'].'.svg" width="20px" style="margin-right:8px" alt="'.$player['flag'].'">'.$player['country'].'
                                        <div>
                                            <div class="tablerow">
                                                <div>Steam:</div><div><a href="https://steamcommunity.com/profiles/'.$player['steam64'].'" target="_blank">STEAM_0:'.$player['steam'].'</a></div>
                                            </div>
                                            <div class="tablerow">
                                                <div>Steam64:</div><div><a href="https://steamcommunity.com/profiles/'.$player['steam64'].'?xml=1" target="_blank">'.$player['steam64'].'</a></div>
                                            </div>
                                            <div class="tablerow">
                                                <div>Player ID:</div><div class="z">'.$player['playerId'].'</div>
                                            </div>
                                            <div class="tablerow">
                                                <div>VAC Status:</div><div>'.($player['vacBanned']?'<span class="red">Banned</span>':'<span class="green">In good standing</span>').'</div>
                                            </div>
                                            <div class="tablerow">
                                                <div>VAC Trade Ban:</div><div class="z">'.$player['tradeBanState'].'</div>
                                            </div>
                                        </div>
                                        
                                    </p>
                                </div>
                            </div>'
                            .$group.$clan
                );

if ( $query ) {

    Send(array("id" =>$player['playerId'], "card" => $card));

    ob_end_flush();

    $mysqli -> close();

    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    
    file_put_contents($file, json_encode($player));
    file_put_contents($file2, json_encode($stats));

} else {

    Send(array("id" =>$player['playerId'], "card" => $card));
    exit();

}
?>