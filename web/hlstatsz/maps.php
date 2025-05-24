<?php
require 'php/require/session.start.php';
$_SESSION['page']='maps';
include 'config.php';
include 'php/header.php';
?>
<table id="maps" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Rank</th>
            <th>Map</th>
            <th>Kills</th>
            <th>Headshots</th>
            <th>HS:K</th>
            <th>Popularity</th>
            <th>Download</th>
        </tr>
    </thead>
</table>
<?php
include 'php/footer.php';
?>