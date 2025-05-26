<?php
require '../../php/require/session.start.php';
require '../../php/require/session.script.php';
require '../../config.php';
$IPs      = json_decode($_GET['IPs'],true);
$SIDs      = json_decode($_GET['SIDs'],true);
$folder   = '../../cache/live';
$file     = $folder.'/ping.json';
$query    = isset($_GET['query']) || !file_exists($file) ||  strtotime(CACHE_PLAYERS) > filemtime($file);

//Cached file:
if ( !$query ) {

    $ping  = json_decode(file_get_contents($file),true);
    $query = $ping === false;

}

if ( $query ) {

    $ping=array();
    for ( $i=0; $i<count($SIDs); $i++ ) {

        $ap   = explode(":", $IPs[$i]);
        $ip   = $ap[0];
        $port = $ap[1]; 
        //request format
        $a_Header = 0x54;
        $r_Header = 0x49;
        $Payload = "Source Engine Query\0";
        $r_challenge = 0x41;
        //Command
        $req       = pack( 'ccccc', 0xFF, 0xFF, 0xFF, 0xFF, $a_Header).$Payload;
        $resp      = pack( 'c', $r_Header);
        $challenge = pack( 'c', $r_challenge);
        $ln        = strlen( $req );

        //Data
        $data = '';
        
        //opensocket
        $fp = fsockopen('udp://'.$ip, $port, $errno, $errstr, null);//default time from php.ini
        
        //connected
        if ( !empty($fp) ) {

            //Request AS_2INFO
            @fwrite($fp, $req, $ln);
            //Response
            stream_set_timeout($fp, 2);
            $data = @fread ($fp, 1400) ;
            $response   = substr($data, 4, 1);
            fclose($fp);

            if ( $response == $challenge || $response == $resp ) {

                $ping[$SIDs[$i]]='online'; // Online

            } else {

                $fp = fsockopen($ip, $port, $errno, $errstr, 1);

                if ( !empty($fp) ) {
                    
                    fclose($fp);
                    $ping[$SIDs[$i]]='warning'; // Online but no response

                } else { $ping[$SIDs[$i]]='offline'; } // Offline

            }

        } else { $ping[$SIDs[$i]]='offline'; } // Offline

    }

}

Send(array( "ping" => $ping ));

ob_end_flush();

if ( $query ) {

    // Save data:
    if ( !is_dir($folder) ) { mkdir($folder, 0777, true); }
    file_put_contents($file, json_encode($ping));

}
?>

    