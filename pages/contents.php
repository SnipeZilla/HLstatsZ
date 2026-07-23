<?php
/*
HLstatsZ - Real-time player and clan rankings and statistics
Originally HLstatsX Community Edition by Nicholas Hastings (2008–20XX)
Based on ELstatsNEO by Malte Bayer, HLstatsX by Tobias Oetzel, and HLstats by Simon Garner

HLstats > HLstatsX > HLstatsX:CE > HLStatsZ
HLstatsZ continues a long lineage of open-source server stats tools for Half-Life and Source games.
This version is released under the GNU General Public License v2 or later.

For current support and updates:
   https://snipezilla.com
   https://github.com/SnipeZilla
   https://forums.alliedmods.net/forumdisplay.php?f=156
*/
if ( !defined('IN_HLSTATS') ) { die('Do not access this file directly'); }

    if (!empty($_GET['ajax']) && $_GET['ajax'] === 'voicecomm') {
        global $db;
        $resultVoices = $db->query("
            SELECT serverId, name, addr, password, descr, queryPort, UDPPort, serverType
            FROM hlstats_Servers_VoiceComm
        ");
        include(PAGE_PATH . '/voicecomm_serverlist.php');
        exit;
    }

    if (($num_games == 1 && $num_voices == 0) || !empty($_GET['game'])) {
        include(PAGE_PATH . '/game.php');

    } else {
      if ($num_games) {
        unset($_SESSION['game']);

        printSectionTitle(t('title.games'));
    
if ($g_options['show_google_map'] == 1) {
?>
    <table class="hlstats-map">
        <tr>
            <td><div id="map"></div></td>
        </tr>  
</table>
<?php
}
?>
            <table>
                <tr>
                    <th class="hlstats-main-description left responsive"><?= t('game') ?></th>
                    <th class="hide-2"></th>
                    <th class="hide-2"></th>
                    <th><?= t('players') ?></th>
                    <th class="hide"><?= t('top.player') ?></th>
                    <th class="hide-2"><?= t('top.clan') ?></th>
                </tr>
<?php
        $nonhiddengamestring = "(";
        while ($gamedata = $db->fetch_row($resultGames))
        {
            $nonhiddengamestring .= "'$gamedata[0]',";
            $result = $db->query("
                SELECT
                    playerId,
                    lastName,
                    activity
                FROM
                    hlstats_Players
                WHERE
                    game='$gamedata[0]'
                    AND hideranking=0
                    AND lastAddress <> ''
                ORDER BY
                    ".$g_options['rankingtype']." DESC,
                    (kills/IF(deaths=0,1,deaths)) DESC
                LIMIT 1
            ");
        
            if ($db->num_rows($result) == 1)
            {
                $topplayer = $db->fetch_row($result);
            }
            else
            {
                $topplayer = false;
            }

            $result = $db->query("
            SELECT
                c.clanId,
                c.name,
                AVG(p.skill) AS skill,
                AVG(p.kills) AS kills,
                COUNT(p.playerId) AS numplayers
            FROM
                hlstats_Clans AS c
            INNER JOIN
                hlstats_Players AS p
                    ON p.clan = c.clanId
                    AND p.game = c.game
                    AND p.hideranking = 0
            WHERE
                c.game = '".$gamedata[0]."'
                AND c.hidden = 0
            GROUP BY
                c.clanId
            HAVING
                numplayers >= 2
            ORDER BY
                ".$g_options['rankingtype']." DESC
            LIMIT 1;
            ");

            if ($db->num_rows($result) == 1)
            {
                $topclan = $db->fetch_row($result);
            }
            else
            {
                $topclan = false;
            }

            $result= $db->query("
                SELECT
                    SUM(act_players) AS `act_players`,
                    SUM(max_players) AS `max_players`
                FROM
                    hlstats_Servers
                WHERE
                    hlstats_Servers.game='$gamedata[0]'
            ");
                            
            $numplayers = $db->fetch_array($result);
        if ($numplayers['act_players'] == 0 and $numplayers['max_players'] == 0) {
                $numplayers = false;
        } else {
                $player_string = $numplayers['act_players'].'/'.$numplayers['max_players'];
        }
?>
                <tr>
                    <td class="hlstats-main-column left">
                <a href="<?= $g_options['scripturl'] . "?game=$gamedata[0]" ?>">
                
            <?php $image = getImage("/games/$gamedata[0]/game"); 
                  if (!$image) $image = getImage("/games/$gamedata[2]/game"); ?>

                <span class="hlstats-icon"><img src="<?php echo ($image ? $image['url'] : IMAGE_PATH . '/game.png' ); ?>" alt="Game" /></span>
                <span class="hlstats-name"><?= $gamedata[1] ?><span></a>
                </td>
                        <td class="hide-2">
                <a href="<?= $g_options['scripturl'] . "?mode=players&amp;game=$gamedata[0]" ?>">🎮 <?= t('players') ?></a>
                        </td>
                        <td class="hide-2">
                <a href="<?= $g_options['scripturl'] . "?mode=clans&amp;game=$gamedata[0]" ?>">⚔️ <?= t('clans') ?></a>
                        </td>
                <td><?php echo ($numplayers ? $player_string : '-'); ?></td>
                <td class="hide">
            <?php if ($topplayer) { ?>
                    <a href="<?= $g_options['scripturl'] . "?mode=playerinfo&amp;player=" . $topplayer[0] . "&amp;game=" . $gamedata[0] ?>"><?= htmlspecialchars(html_entity_decode($topplayer[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_COMPAT) ?></a>
            <?php } else { echo '-'; } ?>
                </td>
                <td class="hide-2">
            <?php if ($topclan) { ?>
                    <a href="<?= $g_options['scripturl'] . "?mode=claninfo&amp;clan=" . $topclan[0] . "&amp;game=" . $gamedata[0] ?>"><?= htmlspecialchars($topclan[1]) ?></a>
            <?php } else { echo '-'; } ?>
                </td>
            </tr>
<?php
        }
?>
            </table>

<?php
}
        if ($num_voices) {
            $voicecomm_url = htmlspecialchars($_SERVER['PHP_SELF'] . '?mode=contents&ajax=voicecomm');
            echo '<div id="voicecomm-container" data-fetch-url="' . $voicecomm_url . '"></div>';
?>
            <script>
            (function () {
                var url = <?= json_encode($_SERVER['PHP_SELF'] . '?mode=contents&ajax=voicecomm') ?>;
                var el  = document.getElementById('voicecomm-container');
                el.addEventListener('fetch:loaded', function (e) {
                    if (e.detail.html.indexOf('data-voicecomm-stale') !== -1) {
                        fetch(url + '&refresh=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.text(); })
                            .then(function (html) { el.innerHTML = html; });
                    }
                });
                Fetch.run(url, el, false);
            })();
            </script>
<?php
        }

        if (!empty($nonhiddengamestring)) {
        printSectionTitle(t('title.stats'));
        
        $nonhiddengamestring = preg_replace('/,$/', ')', $nonhiddengamestring);
        
        $result = $db->query("SELECT COUNT(playerId) FROM hlstats_Players WHERE game IN $nonhiddengamestring");
        list($num_players) = $db->fetch_row($result);
        $num_players = nf($num_players);

        $result = $db->query("SELECT COUNT(clanId) FROM hlstats_Clans WHERE game IN $nonhiddengamestring");
        list($num_clans) = $db->fetch_row($result);
        $num_clans = nf($num_clans );

        $result = $db->query("SELECT COUNT(serverId) FROM hlstats_Servers WHERE game IN $nonhiddengamestring");
        list($num_servers) = $db->fetch_row($result);
        $num_servers = nf($num_servers);
        
        $result = $db->query("SELECT SUM(kills) FROM hlstats_Servers WHERE game IN $nonhiddengamestring");
        list($num_kills) = $db->fetch_row($result);
        $num_kills = nf($num_kills);

        $result = $db->query("
            SELECT 
                eventTime
            FROM
                hlstats_Events_Frags
            ORDER BY
                id DESC
            LIMIT 1
        ");
        list($lastevent) = $db->fetch_row($result);
?>
        <div>
            <ul>
                <li><?= t('contents.stats', [
                    '{players}' => '<strong>'.$num_players.'</strong>',
                    '{clans}'   => '<strong>'.$num_clans.'</strong>',
                    '{games}'   => '<strong>'.$num_games.'</strong>',
                    '{servers}' => '<strong>'.$num_servers.'</strong>',
                    '{kills}'   => '<strong>'.$num_kills.'</strong>',
                ]) ?></li>
                <?php if ($lastevent) { ?>
                <li><?= t('contents.last_kill', ['{date}' => '<strong>'.formatDate(strtotime($lastevent)).'</strong>']) ?></li>
                <?php } ?>
                <li><?= t('contents.realtime') ?>
                   <?php if ($g_options['DeleteDays']) { ?>
                   <?= t('contents.history', ['{days}' => '<strong>'.$g_options['DeleteDays'].'</strong>']) ?>
                   <?php } ?>
                </li>
            </ul>
        </div>
<?php
      }
    }
?>
