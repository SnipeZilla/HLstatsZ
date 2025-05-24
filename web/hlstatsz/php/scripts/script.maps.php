<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$request = $_GET;
$folder  = '../../cache/maps';

//order by:
$order='kills';
$sort='DESC';
if ( isset($request['order']) ) {

    $order = $request['order'][0]['column']>0?$request['columns'][$request['order'][0]['column']]['data']:'kills';
    $sort  = strtoupper($request['order'][0]['dir']);

}

//download
if ( isset($request['download']) && !empty($request['download']) ) {

    $file=$request['download'].'.bsp.bz2';
    $exist=stripos( get_headers(DL_URL.$file)[0], "200 OK" )?true:false;
    if ( !$exist ) {
        $file=$request['download'].'.bsp';
        $exist=stripos( get_headers(DL_URL.$file)[0], "200 OK" )?true:false;
    }

    if ( $exist ) {

        Send(array("dl" => DL_URL.$file, "file" => $file ));
        ob_end_flush();
        exit();

    } else {

        Send(array("alert" => $request['download']." not found."));
        ob_end_flush();
        exit();

    }

}

//Search
$search=trim($request['search']['value']);
if ( empty($search) && $search !== $request['search']['value'] ) {

   Send( array(
        "search"          => $request['search']['value'],
        "draw"            => $request['draw'],
        "recordsTotal"    => 0,
        "recordsFiltered" => 0,
        "maps"            => 'map',
        "data"            => null
    ));

   ob_end_flush();
   exit();

 }

$file = $folder.'/maps-'.$request['game'].'-'.$request["start"].'-'.$order.'-'.$sort.'.json';

$query = $order != 'kills' || $search != '' || !file_exists($file) || strtotime(CACHE_GLOBAL) > filemtime($file);


// cache file?
if ( !$query ) {

    $data = json_decode(file_get_contents($file),true);
    $query = $data === false;

}

if ( $query ) {

    // MySQL
    require '../../php/require/mysqli.php';

    $sql="  SELECT
               headshots
            FROM
                hlstats_maps_counts
            WHERE
                game = '".$request['game']."'
                AND kills > 0
            ORDER BY
                headshots DESC
            LIMIT 1";

    $data = array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        $data['hs']=intval($result->fetch_row()[0]);

    } else {

        $data['hs']=1000000;

    }

    $sql="  SELECT
                COUNT(*) AS total
            FROM
                hlstats_maps_counts
            WHERE
                game = '".$request['game']."'
                AND kills > 0";


    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        $data['total']=intval($result->fetch_row()[0]);
        $recordsFiltered=$data['total'];

    } else {

        $data['total']=0;
        $recordsFiltered=0;

    }

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
        $total = $result->fetch_row()[0];
    } else { $total=0; }


    if ( $search ) {
        // Searching:
        $search = $mysqli->real_escape_string($search);
        $sql = "WITH RankedMaps AS (
                SELECT 
                    ROW_NUMBER() OVER (ORDER BY kills DESC) AS rank_position,
			        map,
			        kills,
			        headshots,
                    IF(kills=0, 0, headshots/kills) AS hsk,
                    IF(headshots=0, 0, (headshots/".$data['hs'].")*100) AS trend
                FROM
                    hlstats_maps_counts
                WHERE
			        game = '".$request['game']."'
                    AND kills > 0
            )
            SELECT *
		        FROM
			        RankedMaps
                WHERE
                    map LIKE '%".$search."%'
                    AND kills > 0 AND map <> ''
                ORDER BY
                    map DESC
                LIMIT
                    ".$request['length']." OFFSET ".$request['start'];
            
    } else {
        // Globals stats:
        $sql = "SELECT
                    ROW_NUMBER() OVER (ORDER BY kills DESC) AS rank_position,
			        map,
			        kills,
			        headshots,
                    IF(kills=0, 0, headshots/kills) AS hsk,
                    IF(headshots=0, 0, (headshots/".$data['hs'].")*100) AS trend
		        FROM
			        hlstats_maps_counts
		        WHERE
			        game = '".$request['game']."'
                    AND kills > 0 AND map <> ''
                ORDER BY
                    ".$order." ".$sort."
                LIMIT 
                    ".$request['length']." OFFSET ".$request['start'];
    }

    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
    
        if ( $search ) { $recordsFiltered = $result->num_rows; }
    
        while($row = $result->fetch_assoc()) {
            $data['map'][] = $row;
        }
    
    } else { $recordsFiltered = 0; }
}


$map=array();
if ( isset($data['map']) ){
    for ($i = 0; $i < count($data['map']); $i++) {

        $popularity=sprintf('%.2f',$data['map'][$i]['trend']);
        $map[$i]['rank_position']='<em class="hlz-rank">'.$data['map'][$i]['rank_position'].'</em>';
        $map[$i]['map']='<span class="map">'.$data['map'][$i]['map'].'</span>';
        $map[$i]['kills']= $data['map'][$i]['kills'];
        $map[$i]['headshots']= $data['map'][$i]['headshots'];
        $map[$i]['hsk']= $data['map'][$i]['kills']>0?sprintf('%.2f',($data['map'][$i]['headshots']/$data['map'][$i]['kills'])):0;
        $map[$i]['trend']='<meter min="0" max="100" low="25" high="50" optimum="75" value="'.$popularity.'" title="'.$popularity.'%"></meter>';
        $map[$i]['dl']='<div class="download"><img src="styles/css/images/dl.png" width="20px" alt="dl" data-map="'.$data['map'][$i]['map'].'"></div>';
    
    }
} else { $data['map']=null; }

Send( array(
            "search"          => $search,
            "draw"            => $request['draw'],
            "recordsTotal"    => $data['total'],
            "recordsFiltered" => !$search?$data['total']:$recordsFiltered,
            "maps"            => 'map',
            "data"            => $map
    ) ); 

ob_end_flush();

if ( $query && $search == '' && $order == 'kills' ) {

    $mysqli -> close();
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode(array('map' => $data['map'], 'total' => $total)));

}
?>