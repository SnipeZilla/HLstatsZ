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
if (!defined('IN_HLSTATS')) { die('Do not access this file directly'); }

class MumbleQuery
{
    private $host;
    private $port;
    private $timeout;

    public $error = null;

    public function __construct($host, $port = 64738, $timeout = 3)
    {
        $this->host    = $host;
        $this->port    = (int) $port;
        $this->timeout = (int) $timeout;
    }

    // Mumble's connectionless UDP ping: a 12-byte request (4 zero bytes + an
    // 8-byte identifier) gets a 24-byte reply with version/user/bandwidth info.
    // It does not expose channel or username data - that requires the full
    // TCP+TLS control protocol, which is out of scope here.
    public function query($cacheTTL = 0)
    {
        if ($cacheTTL > 0) {
            if (!is_dir('./cache')) mkdir('./cache', 0755, true);
            $cacheFile = './cache/hlstatsz_mumble_' . md5($this->host . ':' . $this->port) . '.json';
            if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
                $data = json_decode(file_get_contents($cacheFile), true);
                if (is_array($data)) return $data;
            }
        }

        $result = [
            'version'   => null,
            'users'     => 0,
            'maxusers'  => 0,
            'bandwidth' => 0,
            'error'     => null,
        ];

        $errno  = 0;
        $errstr = '';
        $sock = @stream_socket_client("udp://{$this->host}:{$this->port}", $errno, $errstr, $this->timeout);
        if (!$sock) {
            $result['error'] = "Socket error: {$errstr} [{$errno}]";
            $this->error = $result['error'];
            return $result;
        }
        stream_set_timeout($sock, $this->timeout);

        fwrite($sock, "\x00\x00\x00\x00" . random_bytes(8));
        $response = fread($sock, 24);
        $meta = stream_get_meta_data($sock);
        fclose($sock);

        if ($response === false || strlen($response) < 24) {
            $result['error'] = !empty($meta['timed_out'])
                ? 'Timeout waiting for Mumble ping response'
                : 'Invalid or empty Mumble ping response';
            $this->error = $result['error'];
            return $result;
        }

        $data    = unpack('Nversion/Nident1/Nident2/Nusers/Nmaxusers/Nbandwidth', $response);
        $version = $data['version'];

        $result['version']   = sprintf('%d.%d.%d', ($version >> 16) & 0xFF, ($version >> 8) & 0xFF, $version & 0xFF);
        $result['users']     = $data['users'];
        $result['maxusers']  = $data['maxusers'];
        $result['bandwidth'] = $data['bandwidth'];

        if ($cacheTTL > 0) {
            @file_put_contents($cacheFile, json_encode($result), LOCK_EX);
        }

        return $result;
    }
}
?>
