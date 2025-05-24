<?php
if ( empty($mysqli) ) {

    if ( empty($_TOKEN) ) die("Private");

    $try=0;

    while ( $try < 3 ) {

        $mysqli = new mysqli(DB_ADDR, DB_USER, DB_PASS, DB_NAME);

        if ( $mysqli->connect_error ) {

            $try++;
            sleep(1);

        } else { break; }

    }

    if ( $mysqli->connect_error ) die("Connection failed: " . $mysqli->connect_error);

}
?>