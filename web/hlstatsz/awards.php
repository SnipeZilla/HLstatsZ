<?php
require 'php/require/session.start.php';
$_SESSION['page']='awards';
require 'config.php';
include 'php/header.php';
?>
<div id="hlz-topstart" class="hlz-topstart"></div>
<div id="hlz-profile2" style="display:none"><h2 class="hlz-profile2">Profile<span class="close"></span></h2>
<div id="profile-award"></div>
</div>
<div class="hlz-profile hlz-awards">
    <div class="hlz-item-1">
        <div class="hlz-card"></div>
    </div>
    <div class="hlz-item-2">
        <div class="hlz-card"></div>
    </div>
    <div class="hlz-item-1">
        <div class="hlz-card"></div>
    </div>
    <div class="hlz-item-2">
        <div class="hlz-card"></div>
    </div>
</div>

<?php
include 'php/footer.php';
?>