<?php
/**
* STEAM STATS MANIA
*/

require_once 'config.php';
require_once 'src/lib.php';
$api = new SteamStatsMania($apiKey);

$steamUserId = $api->getSteamUserIdByVanityUrl('restlesss');
$ownedGames = $api->getOwnedGames($steamUserId);

echo '<pre>';
var_dump($steamUserId);
var_dump($ownedGames);
die;
