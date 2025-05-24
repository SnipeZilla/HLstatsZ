<?php
require 'php/require/session.start.php';
$_SESSION['page']='clans';
include 'config.php';
include 'php/header.php';
?>
<table id="clans" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Rank</th>
            <th>Clan Tag</th>
            <th>Clan Name</th>
            <th>Members</th>
            <th>Points</th>
            <th>Kills</th>
            <th>Headshots</th>
            <th>Deaths</th>
            <th>K:D</th>
            <th>HS:K</th>
        </tr>
    </thead>
</table>
<?php
include 'php/footer.php';
?>