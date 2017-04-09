<?php

/**
* Main class
*/
class SteamStatsMania
{

    public $steamUserVanityName = null;
    public $steamUserId = null;

    const API_ENDPOINT = 'http://api.steampowered.com';
    const ALLOWED_FORMATS = array('json', 'xml', 'vdf');

    protected $apiKey;
    protected $format = 'json';
    protected $db = null;

    public function __construct($apiKey, $format = null, $dbParams = array())
    {
        $this->apiKey = $apiKey;
        if (!empty($format) && in_array($format, self::ALLOWED_FORMATS)) {
            $this->format = $format;
        }

        if (!empty($dbParams)) {
            $dsn = "mysql:dbname={$dbParams['dbName']};host={$dbParams['dbHost']}";
            if (!empty($dbParams['dbPort'])) {
                $dsn .= ";port={$dbParams['dbPort']}";
            }

            try {
                $this->db = new PDO($dsn, $dbParams['dbUser'], $dbParams['dbPassword']);
            } catch (PDOException $e) {
                echo 'Database connection failed: ' . $e->getMessage();
                die;
            }
        }

    }

    private function buildRequestUrl($interface, $method, $version, $params = array())
    {
        $requestUrl = self::API_ENDPOINT . '/' . $interface . '/' . $method . '/' . $version;
        $requestUrl .= '?key=' . $this->apiKey;
        $requestUrl .= '&format=' . $this->format;
        if (!empty($params)) {
            foreach ($params as $paramName => $paramVal) {
                $requestUrl .= '&' . $paramName . '=' . $paramVal;
            }
        }
        return $requestUrl;
    }
    
    private function makeRequest($interface, $method, $version, $params)
    {
        $url = $this->buildRequestUrl($interface, $method, $version, $params);
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curl);
        curl_close($curl);
        
        if (empty($res)) {
            // TO DO: Log wrong request/answer;
            return false;
        }
        
        $decodeMethod = 'steam' . strtoupper($this->format) . 'Decode';
        $data = $this->$decodeMethod($res);
        if (empty($data)) {
            // TO DO: Log error answer;
            return false;
        }
        
        return $data;
    }
    
    private function steamJSONDecode($data)
    {
        return json_decode($data);
    }
    
    private function steamXMLDecode($data)
    {
        // TO DO
    }
    
    private function steamVDFDecode($data)
    {
        // TO DO
    }
    
    public function getSteamUserIdByVanityUrl($userId)
    {
        if (empty($userId)) {
            return false;
        }
        
        $res = $this->makeRequest('ISteamUser', 'ResolveVanityURL', 'v0001', array('vanityurl' => $userId));
        if (empty($res) || empty($res->response) || empty($res->response->steamid)) {
            return false;
        }
        
        return $res->response->steamid;
    }
    
    public function getOwnedGames($steamId, $includePlayedFreeGames = false, $includeAppinfo = false)
    {
        if (empty($steamId)) {
            return false;
        }
        
        $res = $this->makeRequest(
            'IPlayerService',
            'GetOwnedGames',
            'v0001',
            array(
                'steamid' => $steamId,
                'include_appinfo' => $includeAppinfo,
                'include_played_free_games' => $includePlayedFreeGames
            )
        );
        
        if (empty($res) || empty($res->response)) {
            return false;
        }
        
        return $res->response;
    }
    
    public function getUserStatsForGame($steamId, $appId)
    {
        if (empty($steamId) || empty($appId)) {
            return false;
        }
        
        $res = $this->makeRequest('ISteamUserStats', 'GetUserStatsForGame', 'v0002', array('steamid' => $steamId, 'appid' => $appId));
        
        if (empty($res)) {
            return false;
        }
        
        return $res;
    }

    public function getPlayerAchievements($steamId, $appId)
    {
        if (empty($steamId) || empty($appId) || empty($this->db)) {
            return false;
        }

        $res = $this->makeRequest('ISteamUserStats', 'GetPlayerAchievements', 'v0001',array('steamid' => $steamId, 'appid' => $appId));
        if (empty($res->playerstats)) {
            return false;
        }

        return $res->playerstats;
    }

    // Either vanity name or user id can be used
    public function getAllAchievmentsForPlayer($steamUserVanityName = null, $steamUserId = null)
    {
        ini_set('max_execution_time', 1000);
        if (empty($steamUserVanityName) && empty($steamUserId)) {
            return false;
        } elseif (!empty($steamUserVanityName)) {
            $this->steamUserVanityName = $steamUserVanityName;
            if (!empty($steamUserId)) {
                $this->steamUserId = $steamUserId;
            } else {
                $this->steamUserId = $this->getSteamUserIdByVanityUrl($this->steamUserVanityName);
            }
        }

        if (empty($this->steamUserId)) {
            return false;
        }
        if (empty($this->steamUserVanityName)) {
            $this->steamUserVanityName = $this->steamUserId;
        }

        $ownedGames = $this->getOwnedGames($this->steamUserId, true);

        // This section is heavy on number of requests, if the user has many games.
        if (!empty($ownedGames->games)) {
            foreach ($ownedGames->games as $game) {
                $achievements = $this->getPlayerAchievements($this->steamUserId, $game->appid);
                if ($achievements->success && !empty($achievements->achievements)) {
                   $aNr = count($achievements->achievements);
                   $aPNr = 0;
                   foreach ($achievements->achievements as $achievement) {
                       if ($achievement->achieved) {
                           $aPNr++;
                       }
                   }
                   if ($aPNr) {
                       $query = "INSERT INTO `player_achievments`(player_vanity_name, game_name, achievements_nr, achieved_by_player)
                                  VALUES(:steamUserVanityName, :game_name, :a_nr, :a_p_nr)";
                       $stmt = $this->db->prepare($query);
                       try {
                           $stmt->execute(array(
                               ':steamUserVanityName' => $this->steamUserVanityName,
                               ':game_name' => $achievements->gameName,
                               ':a_nr' => $aNr,
                               ':a_p_nr' => $aPNr
                           ));
                       } catch (PDOException $e) {
                           // @TO DO - log sql query errors
                       }
                   }
                }
            }

            echo 'Finished';

        }
    }
}
