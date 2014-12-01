<?php


/********************************************
 *   Utility class
 * *******************************************/

class TS_Golf_Utility
{
    public static $TimeoutDuration = 120;

    private static $UTFChars = array('0', '1','2', '3', '4', '5', '6', '7', '8',
                                     '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
                                     'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q',
                                     'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
                                     'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
                                     'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
                                     'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );


    public static function GenerateSignature($pubkey, $secret, $nonce, $timestamp)
    {
        $baseStr =  "public=".$pubkey.
                    "&nonce=".$nonce.
                    "&time".$timestamp;
        $urlStr = urlencode($baseStr);
        $hashStr = base64_encode(hash_hmac('sha1', $urlStr, base64_decode($secret), true));

        $hashStr = preg_replace("/\//", '-', $hashStr);
        $hashStr = preg_replace("/\+/", '_', $hashStr);

        return $hashStr;
    }

    // Capped at 64 chars
    public static function GenerateUTF62Token($length = 32)
    {
        $retStr = "";

        $strLen = max(0, min(64, $length));

        for($i = 0; $i < $strLen; $i++)
        {
            $retStr .= TS_Golf_Utility::$UTFChars[rand(0, 61)];
        }

        return $retStr;
    }
}

/********************************************
 *   Base functionality
 * *******************************************/

class TS_Golf_API
{
    public $RootAddr = "http://golfapi.tournamentseeker.com";

    public $ApiKey;
    public $SecretKey;
    public $IsDebug;

    private $Token;

    /* Constructors */
    function __construct($apiKey, $secretKey, $isDebug = true)
    {
        $this->ApiKey = $apiKey;
        $this->SecretKey = $secretKey;
        $this->IsDebug = $isDebug;
        $this->Token = "";
    }
    /* Auth Response */
    function GetAuthResponse()
    {
        $nonce = TS_Golf_Utility::GenerateUTF62Token(16);
        $time = time();
        $sig = TS_Golf_Utility::GenerateSignature($this->ApiKey, $this->SecretKey, $nonce, $time);

        $response = $this->MakeAuthResponseCall($nonce, $time, $sig);

        $this->Token = (isset($response['token'])) ? $response['token'] : "";
        return $response;
    }

    function MakeAuthResponseCall($nonce, $time, $sig)
    {
        $method = "auth/token";
        $url = implode('/', array($this->RootAddr, $method, $this->ApiKey, $nonce, $sig, $time));
        $resp = $this->CallAPI("GET", $url);
        // echo $resp;
        $respArr = json_decode($resp, true);
        return $respArr;
    }

    /********************************************
     *   Data Types
     * *******************************************/

    function GetFormats()
    {
        $response = $this->AttemptAPICall("formats", "GET");
        return $response;
    }

    function GetSkillTypes()
    {
        $response = $this->AttemptAPICall("skilltypes", "GET");
        return $response;
    }

    function GetStates()
    {
        $response = $this->AttemptAPICall("states", "GET");
        return $response;
    }


    /********************************************
     *   Support Functionality
     * *******************************************/

    function AttemptAPICall($requestUrl, $method, $data = false)
    {
        $baseUrl = implode('/', array($this->RootAddr, $requestUrl));
        $url = implode('/', array($baseUrl, $this->Token));
        // If we don't already have a token, fetch that first
        if (strlen($this->Token) < 64)
        {
            $this->GetAuthResponse();
            $url = implode('/', array($baseUrl, $this->Token));
        }

        $response = $this->CallAPI($method, $url, $data);
        $respArr = json_decode($response, true);

        // if ($respArr == null) echo $response;

        // If forbidden, fetch new credentials and try one more time
        if ($respArr['status'] == 403)
        {
            $this->GetAuthResponse();
            $url = implode('/', array($baseUrl, $this->Token));
            $response = $this->CallAPI($method, $url);

            $respArr = json_decode($response, true);
        }

        return $respArr;
    }

    private function CallAPI($method, $url, $data = false)
    {
        $curl = curl_init();

        switch ($method)
        {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        default:
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // Optional Authentication:
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: " . $this->Token));
        curl_setopt($curl, CURLOPT_VERBOSE, 1);

        $ret = curl_exec($curl);
        curl_close($curl);
        return $ret;
    }
}

class TS_Golf_Division
{
    public $divisionID = 0;             // int          - Division ID
    public $skillTypeID = 0;            // int          - SkillType ID
    public $skillTypeName = "";         // string       - SkillType Name
    public $formatID = 0;               // int          - Format ID
    public $formatName = "";            // string       - Format Name
    public $registrationFee = 0;        // int          - The cost of registering for the event, in $.01 USD
    public $feePerTeam = false;         // boolean      - Whether or not the indicated fee is for the entire team, rather than per-player
    public $prizeDetails = "";          // string       - Description of the prizes to be awarded for this division

    function __construct($pDivisionID = 0, $pSkillTypeID = 0, $pSkillTypeName = "", $pFormatID = 0, $pFormatName = "", $pRegistrationFee = 0, $pFeePerTeam = false, $pPrizeDetails = "")
    {
        $this->divisionID = $pDivisionID;
        $this->skillTypeID = $pSkillTypeID;
        $this->skillTypeName = $pSkillTypeName;
        $this->formatID = $pFormatID;
        $this->formatName = $pFormatName;
        $this->registrationFee = $pRegistrationFee;
        $this->feePerTeam = $pFeePerTeam;
        $this->prizeDetails = $pPrizeDetails;
    }
}


class TS_Golf_Event
{
    public $eventID = 0;                // int          - Event ID
    public $userID = 0;                 // int          - User ID
    public $eventName = "";             // string       - The name of the event
    public $eventDescription = "";      // string       - Event description
    public $travelAccomodations = "";   // string       - Travel accomodations for anyone travelling in for the event
    public $contactEmail = "";          // string       - The email of the primary contact person for the event
    // Times
    public $regStartTime = NULL;        // DateTime     - The start time/date of event registration
    public $regEndTime = NULL;          // DateTime     - The end time/date of event registration
    public $eventStartTime = NULL;      // DateTime     - The start time/date of the event
    public $eventEndTime = NULL;        // DateTime     - The end time/date of the event
    // Location
    public $venueName = "";             // string       - The name of the venue
    public $addressStreet = "";         // string       - The street address of the event venue (if event is in-person)
    public $addressCity = "";           // string       - The city of the event venue (if event is in-person)
    public $addressStateID = 0;         // int          - The state/region of the event (int ID for Locations table entry)
    public $addressZip = "";            // string       - The zip code of the event venue (if event is in-person)
    // Web Contact
    public $webAddress = "";            // string       - Web address for the event
    public $regAddress = "";            // string       - Registration web address for the event
    public $facebookLink = "";          // string       - Facebook event page for the event
    public $twitterHash = "";           // string       - Twitter hashtag for the event
    public $streamLink = "";            // string       - twitch.tv link for the event
    // Read-only details
    // (won't be saved if you change them)
    public $featureLevel = 0;           // int          - The name of the state in which the event takes place
    public $addressState_Name = "";     // string       - The name of the state in which the event takes place
    public $addressState_Abbr = "";     // string       - The abbreviation of the state in which the event takes place
    public $addressState_Country = "";  // string       - The name of the country in which the event takes place
    public $eventLogoThumb = "";        // string       - The path of thethumbnail of the event's logo
    public $eventLogoFull = "";         // string       - The path of the full image of the event's logo
    public $lastUpdateTime = NULL;      // DateTime     - The date of the most recent update to this event

    public $divisions = NULL;

    function __construct($pEventID = 0, $pUserID = 0, $pEventName = "", $pEventDescription = "", $pRegStartTime = NULL, $pRegEndTime = NULL, $pEventStartTime = NULL, $pEventEndTime = NULL, $pVenueName = "", $pAddressStreet = "", $pAddressCity = "", $pAddressStateID = 0, $pAddressZip = "", $pWebAddress = "", $pRegAddress = "", $pFacebookLink = "", $pTwitterHash = "", $pStreamLink = "", $pTravelAccomodations = "", $pContactEmail = "", $pAddressState_Name = "", $pAddressState_Abbr = "", $pAddressState_Country = "", $pEventLogo_Thumb = "", $pEventLogo_Full = "", $pFeatureLevel = 0, $pLastUpdateTime = NULL)
    {
        $this->eventID = $pEventID;
        $this->userID = $pUserID;
        $this->eventName = $pEventName;
        $this->eventDescription = $pEventDescription;
        $this->regStartTime = $pRegStartTime;
        $this->regEndTime = $pRegEndTime;
        $this->eventStartTime = $pEventStartTime;
        $this->eventEndTime = $pEventEndTime;
        $this->venueName = $pVenueName;
        $this->addressStreet = $pAddressStreet;
        $this->addressCity = $pAddressCity;
        $this->addressStateID = $pAddressStateID;
        $this->addressZip = $pAddressZip;
        $this->webAddress = $pWebAddress;
        $this->regAddress = $pRegAddress;
        $this->facebookLink = $pFacebookLink;
        $this->twitterHash = $pTwitterHash;
        $this->streamLink = $pStreamLink;
        $this->travelAccomodations = $pTravelAccomodations;
        $this->contactEmail = $pContactEmail;

        $this->addressState_Name = $pAddressState_Name;
        $this->addressState_Abbr = $pAddressState_Abbr;
        $this->addressState_Country = $pAddressState_Country;
        $this->eventLogoThumb = $pEventLogo_Thumb;
        $this->eventLogoFull = $pEventLogo_Full;
        $this->featureLevel = $pFeatureLevel;
        $this->lastUpdateTime = $pLastUpdateTime;

        $divisions = array();
    }

    /********************************************
     *   Events
     * *******************************************/

    static function GetEvent($tsapi, $eventId)
    {
        $requestUrl = implode('/', array("event", $eventId));
        $response = $tsapi->AttemptAPICall($requestUrl, "GET");

        $event = NULL;
        if($response['status'] == 200 && isset($response['data']))
        {
            foreach($response['data'] as $data) {
                $event = new TS_Golf_Event( $data['listing']['eventID'],
                                        $data['listing']['userID'],
                                        $data['listing']['eventName'],
                                        $data['listing']['eventDescription'],
                                        $data['listing']['regStartTime'],
                                        $data['listing']['regEndTime'],
                                        $data['listing']['eventStartTime'],
                                        $data['listing']['eventEndTime'],
                                        $data['listing']['venueName'],
                                        $data['listing']['addressStreet'],
                                        $data['listing']['addressCity'],
                                        $data['listing']['addressStateID'],
                                        $data['listing']['addressZip'],
                                        $data['listing']['webAddress'],
                                        $data['listing']['regAddress'],
                                        $data['listing']['facebookLink'],
                                        $data['listing']['twitterHash'],
                                        $data['listing']['streamLink'],
                                        $data['listing']['travelAccomodations'],
                                        $data['listing']['contactEmail'],
                                        $data['listing']['addressState_Name'],
                                        $data['listing']['addressState_Abbr'],
                                        $data['listing']['addressState_Country'],
                                        $data['listing']['eventLogo_Thumb'],
                                        $data['listing']['eventLogo_Full'],
                                        $data['listing']['featureLevel'],
                                        $data['listing']['lastUpdateTime']);

                foreach($data['divisions'] as $div)
                {
                    $division = new TS_Golf_Division($div['divisionID'], $div['skillTypeID'], $div['skillTypeName'], $div['formatID'], $div['formatName'], $div['registrationFee'], $div['feePerTeam'], $div['prizeDetails']);
                    $event->divisions[] = $division;
                }
                break; // Hacky, I know, but oh well. :)
            }
        }

        return $event;
    }

    static function AddEvent($tsapi, $event)
    {
        $data['event'] = json_encode($event);
        $response = $tsapi->AttemptAPICall("event", "POST", $data);
        return $response;
    }

    static function ArchiveEvent($tsapi, $eventId)
    {
        $requestUrl = implode('/', array("event", $eventId));
        $response = $tsapi->AttemptAPICall($requestUrl, "DELETE");
        return $response;
    }

    static function UpdateEvent($tsapi, $event)
    {
        $data['event'] = json_encode($event);

        $requestUrl = implode('/', array("event", $event->eventID));
        $response = $tsapi->AttemptAPICall($requestUrl, "POST", $data);
        return $response;
    }

    /********************************************
     *   Searching
     * *******************************************/

    static function SearchEventsBySkillType($tsapi, $skillTypeID) { return TS_Golf_Event::SearchEvents($tsapi, $gameID); }
    static function SearchEventsByFormat($tsapi, $formatID) { return TS_Golf_Event::SearchEvents($tsapi, 0, $formatID); }
    static function SearchEventsByState($tsapi, $stateID) { return TS_Golf_Event::SearchEvents($tsapi, 0, 0, $stateID); }
    static function SearchEventsByFeatureLevel($tsapi, $featureLevel) { return TS_Golf_Event::SearchEvents($tsapi, 0, 0, 0, $featureLevel); }
    static function SearchEventsByUserID($tsapi, $userID) { return TS_Golf_Event::SearchEvents($tsapi, 0, 0, 0, "", $userID); }
    static function SearchEventsBySearchTerm($tsapi, $searchTerm) { return TS_Golf_Event::SearchEvents($tsapi, 0, 0, 0, "", 0, $searchTerm); }

    static function SearchEvents($tsapi, $skillTypeID, $formatID, $stateID = 0, $featureLevel = "", $userID = 0, $searchTerm = "")
    {

        $data['skill_type'] = $skillTypeID;
        $data['format'] = $formatID;
        $data['state'] = $stateID;
        $data['event_name'] = $searchTerm;
        $data['feature_level'] = $featureLevel;
        $data['user_id'] = $userID;

        $response = $tsapi->AttemptAPICall("search", "POST", $data);
        $events = TS_Golf_Event::ParseEvents($response);
        return $events;
    }

    static function GetMyEvents($tsapi)
    {
        $response = $tsapi->AttemptAPICall("myevents", "GET");
        $events = TS_Golf_Event::ParseEvents($response);
        return $events;
    }

    static function GetMyFavorites($tsapi)
    {
        $response = $tsapi->AttemptAPICall("favorites", "GET");
        $events = TS_Golf_Event::ParseEvents($response);
        return $events;
    }

    static function ParseEvents($response)
    {
        $events = NULL;
        if($response['status'] == 200 && isset($response['data']) && isset($response['data']['resultsFound']))
        {
            $events = array();
            // echo "Results: " . $response['data']['resultsFound'];
            if($response['data']['resultsFound'] > 0)
            {
                foreach($response['data']['listings'] as $data)
                {
                    $isAtVenue = $data['listing']['isOnlineEvent'] != true;

                    $event = new TS_Golf_Event( $data['listing']['eventID'],
                        $data['listing']['userID'],
                        $data['listing']['eventName'],
                        $data['listing']['eventDescription'],
                        $data['listing']['regStartTime'],
                        $data['listing']['regEndTime'],
                        $data['listing']['eventStartTime'],
                        $data['listing']['eventEndTime'],
                        $data['listing']['venueName'],
                        $data['listing']['addressStreet'],
                        $data['listing']['addressCity'],
                        $data['listing']['addressStateID'],
                        $data['listing']['addressZip'],
                        $data['listing']['webAddress'],
                        $data['listing']['regAddress'],
                        $data['listing']['facebookLink'],
                        $data['listing']['twitterHash'],
                        $data['listing']['streamLink'],
                        $data['listing']['travelAccomodations'],
                        $data['listing']['contactEmail'],
                        $data['listing']['addressState_Name'],
                        $data['listing']['addressState_Abbr'],
                        $data['listing']['addressState_Country'],
                        $data['listing']['eventLogo_Thumb'],
                        $data['listing']['eventLogo_Full'],
                        $data['listing']['featureLevel'],
                        $data['listing']['lastUpdateTime']);

                    foreach($data['divisions'] as $div)
                    {
                        $division = new TS_Golf_Division($div['divisionID'], $div['skillTypeID'], $div['skillTypeName'], $div['formatID'], $div['formatName'], $div['registrationFee'], $div['feePerTeam'], $div['prizeDetails']);
                        $event->divisions[] = $division;
                    }

                    $events[] = $event;
                }
            }
        }

        return $events;
    }
}
?>