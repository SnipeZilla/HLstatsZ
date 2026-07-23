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
include (PAGE_PATH . '/voicecomm_serverlist.php');

require_once(PAGE_PATH . '/mumble_class.php');

$mbId = valid_request($_GET['mbId'] ?? '', true);
$db->query("SELECT name, addr, descr, UDPPort FROM hlstats_Servers_VoiceComm WHERE serverId=" . intval($mbId));
$s = $db->fetch_array();

if (!$s) {
	error("Mumble server not found", 1);
	return;
}

$host = $s['addr'];
$port = (int) $s['UDPPort'];
$link = $host . ':' . $port;

$mb  = new MumbleQuery($host, $port, 3);
$res = $mb->query(30);

if ($res['error']) {
	error('Could not query Mumble server: ' . htmlspecialchars($res['error']), 1);
}

printSectionTitle(t('title.mumble.overview'));
?>
<div class="hlstats-steam-group">
	<div class="hlstats-profile-head">
		<div class="hlstats-avatar">
			<a href="mumble://<?= htmlspecialchars($link) ?>/">
				<img src="<?= IMAGE_PATH ?>/mumble/mumble.svg" class="hlstats-avatar-img" alt="Mumble" />
			</a>
		</div>
		<div class="hlstats-identity">
			<div class="hlstats-pname">
				<a href="mumble://<?= htmlspecialchars($link) ?>/"><?= htmlspecialchars($s['name']) ?></a>
			</div>
			<div class="hlstats-steam-stats">
				<div class="hlstats-steam-stat">
					<span class="sc-stat-value green"><?= $res['error'] ? '&mdash;' : ((int)$res['users'] . ' / ' . (int)$res['maxusers']) ?></span>
					<span class="sc-stat-label"><?= t('mumble.users') ?></span>
				</div>
				<div class="hlstats-steam-stat">
					<span class="sc-stat-value"><?= $res['error'] ? '&mdash;' : htmlspecialchars($res['version']) ?></span>
					<span class="sc-stat-label"><?= t('mumble.version') ?></span>
				</div>
				<div class="hlstats-steam-stat">
					<span class="sc-stat-value"><?= $res['error'] ? '&mdash;' : (number_format((int)$res['bandwidth']) . ' bps') ?></span>
					<span class="sc-stat-label"><?= t('mumble.bandwidth') ?></span>
				</div>
			</div>
			<p class="hlstats-steam-descr"><a href="mumble://<?= htmlspecialchars($link) ?>/"><?= htmlspecialchars($link) ?></a></p>
			<?php if (!empty($s['descr'])): ?>
			<p class="hlstats-steam-descr"><?= htmlspecialchars($s['descr']) ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
