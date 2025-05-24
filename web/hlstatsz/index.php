<?php
require 'php/require/session.start.php';
$_SESSION['page']='live';
require 'config.php';
include 'php/header.php';
?>
<div id="hlz-topstart" class="hlz-topstart"></div>
<div id="openmap"></div>
<table id="servers" class="display" style="width:100%;display:none">
    <thead>
        <tr>
            <th data-priority="1"></th>
            <th data-priority="2">Server Name</th>
            <th>Address</th>
            <th>Map</th>
            <th>Played</th>
            <th>Players</th>
            <th>Bots</th>
            <th>Kills</th>
            <th>Headshots</th>
            <th data-priority="1">Online</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($games['server'] as $k => $v) {
        for ($i=0 ;$i<count($v); $i++) {
            $t= ($v[$i]['map_started']? round(time()-$v[$i]['map_started']) : 0);
            echo '<tr class="'.$k.'" data-server="'.$k.'-'.$v[$i]['serverId'].'">
                    <td class="dt-control dt-icon"><img src="'.getIcon($k).'" alt="'.$k.'" title="'.$v[$i]['name'].'"></td>
                    <td>'.str_replace('\\', '', $v[$i]['fname']).'</td>
                    <td><a href="steam://connect/'.$v[$i]['address'].':'.$v[$i]['port'].'" title="Click to join!">'.$v[$i]['publicaddress'].'</a></td>
                    <td>'.$v[$i]['act_map'].'</td>
                    <td>'.sprintf('%02d:%02d:%02d', $t/3600, floor($t/60)%60, $t%60).'</td>
                    <td><span class="players">0</span> /'.$v[$i]['max_players'].'</td>
                    <td>0</td>
                    <td>'.number_format($v[$i]['kills']).'</td>
                    <td>'.number_format($v[$i]['headshots']).'</td>
                    <td><div title="Querying server" class="status ping"></div></td>
                  </tr>';
        }
    }
?>
    <tbody>
</table>
<?php
include 'php/footer.php';
?>