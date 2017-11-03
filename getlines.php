<?php
    require_once("autoload.php");
    $sptrans = new Sptrans\sptrans();
    $auth = $sptrans->auth();

    $lines = $sptrans->getLines();
print_r($lines);
die;
