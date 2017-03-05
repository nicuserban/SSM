<?php

/**
* Main class
*/
class SteamStatsMania
{

    const API_ENDPOINT = 'http://api.steampowered.com';
    const ALLOWED_FORMATS = array('json', 'xml', 'vdf');

    protected $apiKey;
    protected $format = 'json';

    public function __construct($apiKey, $format = null)
    {
        $this->apiKey = $apiKey;
        if (!empty($format) && in_array($format, self::ALLOWED_FORMATS)) {
            $this->format = $format;
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
            // Log wrong request/answer;
            return false;
        }
        
        $decodeMethod = 'steam' . strtoupper($this->format) . 'Decode';
        $data = $this->$decodeMethod($res);
        if (empty($data)) {
            // Log error answer;
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
    
    public function getOwnedGames($steamId)
    {
        if (empty($steamId)) {
            return false;
        }
        
        $res = $this->makeRequest('IPlayerService', 'GetOwnedGames', 'v0001', array('steamid' => $steamId));
        
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
}
