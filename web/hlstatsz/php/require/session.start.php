<?php
if ( empty($_SESSION)) session_start();
if ( empty($_SESSION['token']) ) $_SESSION['token']=bin2hex(random_bytes(32));
$_TOKEN=$_SESSION['token'];

function Url() {

    $http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $port= ( $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ) ? ":".$_SERVER['SERVER_PORT'] : "";
    return $http.$_SERVER['SERVER_NAME'].$port;

}

function getIcon($game){

    $path1='./styles/css/images/games/'.$game.'.png';
    $path2=IMAGE_PATH.'/games/'.$game.'/game.png';
    return file_exists($path1) ? $path1 : $path2;

}

function Send($data) {

    echo json_encode($data);
    flush();
    ob_flush();
    ob_clean();
}
?>