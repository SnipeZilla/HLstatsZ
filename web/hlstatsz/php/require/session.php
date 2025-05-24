<?php
if (  empty($_SESSION['token']) || $_SESSION['token'] !== $_TOKEN ) die('private');
?>