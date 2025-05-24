<?php
if ( empty($_TOKEN) || empty($_GET['token']) || $_GET['token'] !== $_TOKEN ) {
    
    echo json_encode(array("error" => 'invalid session'));
    exit();

}
?>