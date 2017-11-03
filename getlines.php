<?php
    require_once("autoload.php");
    $sptrans = new SptransApi\sptrans();
    $auth = $sptrans->auth();
    print_r($auth);
    die;
    $lines = $sptrans->getLines();
