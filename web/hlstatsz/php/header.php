<?php
require_once 'php/require/session.php';
include 'php/games.php';
$version="0.2.3";
$dirname=dirname($_SERVER['PHP_SELF']).'/';
$url=Url().$dirname;
?>
<!DOCTYPE html>
<!--  SnipeZilla.com  -->
 <html class="dark" data-theme="dark">
	<head>
		<title><?= ucfirst($_SESSION['page']) ?> | HLStatsZ</title>
        <meta name="token" content="<?= $_SESSION['token'] ?>">
        <meta name="viewport" content="width=device-width">
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
		<link rel="shortcut icon" type="image/png" href="styles/css/images/Z.png">
		<link rel="stylesheet" type="text/css" href="styles/css/datatables.min.css">
		<link rel="stylesheet" type="text/css" href="styles/css/leaflet.css<?='?'.$version?>">
		<link rel="stylesheet" type="text/css" href="styles/css/hlstatsz.min.css">
		<script type="text/javascript" src="styles/js/datatables.min.js"></script>
		<script type="text/javascript" src="styles/js/apexcharts.min.js"></script>
		<script type="text/javascript" src="styles/js/leaflet.js"></script>
		<script type="text/javascript" src="styles/js/hlstatsz.min.js<?='?'.$version?>"></script>
        <?php
        if (!empty(GOOGLE)) {
            echo '
 	    <script data-template-name="public:google_analytics" async src="https://www.googletagmanager.com/gtag/js?id='.GOOGLE.'"></script>
        <script>
	        window.dataLayer = window.dataLayer || [];
	        function gtag(){dataLayer.push(arguments);}
	        gtag("js", new Date());
	        gtag("config", "'.GOOGLE.'", {});
	    </script>';
        }
        if (!empty(UMAMI)) {
            echo '<script defer src="https://cloud.umami.is/script.js" data-website-id="1f28524d-242e-4158-b36c-771c5a4a35ce"></script>';
        }
        ?>
	</head>
	<body>
	<div class="hlz-title">
        <ul class="hlz-menu" id="hlz-games">
		    <li<?= $_SESSION['page']=='live'?' class="hlz-active"':'';?>><a href="<?= $url ?>" id="hlz-link" title="hlstats live view">
			        <img src="<?= $url ?>styles/css/images/hlstatsz.png" alt="HLstatsZ" class="hlz-logo">
		        </a>
            </li>
        <?php
            foreach ($games['server'] as $game => $g) {
                echo '<li><img src="'.getIcon($game).'" alt="'.$game.'" title="'.$g[0]['name'].'"  onclick="hlz.game(this.alt)"></li>';
            }
        echo '<li id="hlz-submenu"><div class="hlz-menuicon" onclick="hlz.toggle()"><div></div><div></div><div></div></div>
              <ul class="hlz-menu txt">
              <li '.($_SESSION['page']=='players'?'class="hlz-active"':'').'><a href="'.$url.'players.php" title="Rank Player">Players</a></li>
              <li '.($_SESSION['page']=='clans'?'class="hlz-active"':'').'><a href="'.$url.'clans.php" title="Rank Clan">Clans</a></li>
              <li '.($_SESSION['page']=='awards'?'class="hlz-active"':'').'><a href="'.$url.'awards.php" title="Daily Awards">Awards</a></li>
              <li '.($_SESSION['page']=='chats'?'class="hlz-active"':'').'><a href="'.$url.'chats.php" title="What they said">Chats</a></li>'.
              (!empty(DL_URL)?'<li '.($_SESSION['page']=='maps'?'class="hlz-active"':'').' title="Played Maps."><a href="'.$url.'maps.php">Maps</a></li>':'').
              '<li '.($_SESSION['page']=='bans'?'class="hlz-active"':'').' title="Banned players, forever."><a href="'.$url.'bans.php">Bans</a></li>'. 
              (!empty($settings['options']['forum_address'])?'<li><a href="'.$settings['options']['forum_address'].'" title="'.$settings['options']['forum_address'].'">Forum</a></li>':'').'</ul>';
        ?>
        </li></ul>
	</div>