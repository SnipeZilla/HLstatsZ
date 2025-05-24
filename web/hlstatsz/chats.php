<?php
require 'php/require/session.start.php';
$_SESSION['page']='chats';
include 'config.php';
include 'php/header.php';
?>
<table id="players" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Date</th>
            <th>Player</th>
            <th>Message</th>
            <th>Server</th>
            <th>Map</th>
        </tr>
    </thead>
</table>
<?php
include 'php/footer.php';
?>