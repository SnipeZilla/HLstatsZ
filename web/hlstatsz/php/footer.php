<?php
require_once 'php/require/session.php';
?>
<div>
    <div id="hlz-global"></div>
</div>
<div style="padding:40px"></div>
<script>
window.addEventListener('load', function() {
    hlz.$.settings=<?= json_encode($settings) ?>;    
    hlz.$.games=<?= json_encode($games) ?>;
    hlz.$.page=<?= json_encode($_SESSION['page']) ?>;
    hlz.$.now=<?= time() ?>; 
    <?php
        if ( $_SESSION['page'] == 'players' || $_SESSION['page'] == 'bans' ) {

            echo "hlz.table = new DataTable('#players', hlz.players());";

        } 
        if ( $_SESSION['page'] == 'clans' ) {

            echo "table = new DataTable('#clans', hlz.clans());";

        } 
        if ( $_SESSION['page'] == 'maps' ) {

            echo "table = new DataTable('#maps', hlz.maps());";

        } 
        if ( $_SESSION['page'] == 'chats' ) {

            echo "hlz.table = new DataTable('#players', hlz.chats());";

        } 

        if ( $_SESSION['page'] == 'live' ) {

            echo "  table = new DataTable('#servers', hlz.servers());
                    hlz.map = L.map('openmap',{zoomControl:false}).setView([47.45, -12.00], 3);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        minZoom: 2,
                        maxZoom: 19,
                        attribution: '&copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
                    }).addTo(hlz.map);
                    var bounds = L.latLngBounds(L.latLng(-89.98155760646617, -180), L.latLng(89.99346179538875, 180));
                    hlz.map.setMaxBounds(bounds);
                    hlz.map.on('drag', function() {
                        hlz.map.panInsideBounds(bounds, { animate: false });
                    });
                    
                    hlz.ajax(hlz.url.counter+'?token='+hlz.token);";
        } 
    ?>
     hlz.ajax(hlz.url.global);
})
</script>
<div class="copyright">
    <div>
        <a href="https://snipezilla.com">Snipe<span class="snipezilla">Zilla</span></a> - <a href="https://github.com/snipezilla" target="_blank">HLstats<span class="snipezilla">Z</span> <code><?= $version ?></code> </a>
    </div>
    <div>
        <a href="https://forums.alliedmods.net/forumdisplay.php?f=156" target="_blank">HLstatsX Community Edition <code><?= $settings['options']['version'] ?></code></a>
    </div>
</div>
</body>
</html>