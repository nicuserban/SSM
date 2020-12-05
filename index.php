<?php
/**
 * STEAM STATS MANIA
 */

require_once 'config.php';
require_once 'src/lib.php';
$dbParams = array();
if ($useDb) {
    $dbParams = array(
        'dbHost' => $dbHost,
        'dbName' => $dbName,
        'dbUser' => $dbUser,
        'dbPassword' => $dbPassword
    );
}

$api = new SteamStatsMania($apiKey, $dataFormat, $dbParams);

$api->getAllAchievementsForPlayer('restlesss');
