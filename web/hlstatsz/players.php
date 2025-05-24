<?php
require 'php/require/session.start.php';
$_SESSION['page']='players';
include 'config.php';
include 'php/header.php';
?>
<table id="players" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Rank</th>
            <th>Player</th>
            <th>Points</th>
            <th>Activity</th>
            <th>Connection</th>
            <th>Kills</th>
            <th>Deaths</th>
            <th>Headshots</th>
            <th>K:D</th>
            <th>HS:K</th>
        </tr>
    </thead>
</table>
<?php
include 'php/footer.php';
?>