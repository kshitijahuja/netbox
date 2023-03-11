<?php

/**
* Description: Perform basic API operations with Netbox API
* Author: Kshitij Ahuja
* Version: 1.0.0
* Author URI: http://academicdatasolutions.com
* Email: help@academicdatasolutions.com
**/

//todo: add getAccessLevelNames


class Netbox {

    /**
     * Set API Base URI
     */
    //todo: add auto-removal of trailing slash
    public function setApiBaseUri($uri){
        $this->apiBaseUri = $uri;
    }

    /**
     * Get API Base URI
     */
    public function getApiBaseUri(){
        return $this->apiBaseUri;
    }

    /**
     * Set Session Id 
     */
    public function setSessionId($sessionId){
        $this->sessionId = $sessionId;
    }

    /**
     * Unset Session Id 
     */
    public function unsetSessionId(){
       unset($this->sessionId);
    }

    /**
     * Generic Http POST
     */    
    private function httpPost($payload)
    {
        $url = $this->apiBaseUri.'/goforms/nbapi'; //todo: refactor this
        $ch = curl_init();  
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER, false); 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);    
        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
        "content-type: text/xml"
        ));
        $output=curl_exec($ch); 
        curl_close($ch);
        return $output; 
    }
    
    /**
     * Log in - Creates a Netbox session
     */
    public function login($user, $pass) {
        $payload = "<NETBOX-API>
        <COMMAND name='Login' num='1'>
        <PARAMS>
            <USERNAME>$user</USERNAME>    
            <PASSWORD>$pass</PASSWORD>    
        </PARAMS>
        </COMMAND>
        </NETBOX-API>";
        $response = $this->httpPost($payload);
        $records = simplexml_load_string($response);
        $newArr = json_decode(json_encode($records), true);          

        // validate data => recreate response array to return with err code
        $responseArr = [];
        $responseArr['data'] = $newArr;
        if(array_key_exists("CODE", $newArr['RESPONSE']) && $newArr['RESPONSE']['CODE'] == "SUCCESS") {
            $sessionId = $newArr['@attributes']['sessionid'];
            $this->setSessionId($sessionId);
            $responseArr['httpCode'] = 200;
        }elseif(array_key_exists("APIERROR", $newArr['RESPONSE'])) {
            $responseArr['data'] = $newArr;
            $responseArr['httpCode'] = 400;
        }    

        return $responseArr;    
    }  

    /**
     * Log out - destroys the Netbox session
     */
    public function logout() {
        $sessionId = $this->sessionId;
        $payload = "<NETBOX-API sessionid='$sessionId'>
        <COMMAND name='Logout' num='1' dateformat='tzoffset'/>
        </NETBOX-API>";
        $response = $this->httpPost($payload);
        $records = simplexml_load_string($response);
        $newArr = json_decode(json_encode($records), true);          

        // validate data => recreate response array to return with err code
        $responseArr = [];
        $responseArr['data'] = $newArr;
        if(array_key_exists("CODE", $newArr['RESPONSE']) && $newArr['RESPONSE']['CODE'] == "SUCCESS") {
            $this->unsetSessionId(); // unsets the sessionId property
            $responseArr['httpCode'] = 200;
        }else {
            $responseArr['httpCode'] = 400;
        }    

        return $responseArr;    
    }    

    /**
     * Add a Person
     */
    public function addPerson($data){        
        $payload = "<NETBOX-API sessionid='$this->sessionId'>
        <COMMAND name='AddPerson' num='1' dateformat='tzoffset'>
        <PARAMS>
            <PERSONID>".$data['person_id']."</PERSONID>
            <LASTNAME>".$data['last_name']."</LASTNAME>
            <FIRSTNAME>".$data['first_name']."</FIRSTNAME>
            </PARAMS>
        </COMMAND>
        </NETBOX-API>";    
        $response = $this->httpPost($payload);
        $records = simplexml_load_string($response);
        $newArr = json_decode(json_encode($records), true);          

        // validate data => recreate response array to return with err code
        $responseArr = [];
        $responseArr['data'] = $newArr;
        if(array_key_exists("CODE", $newArr['RESPONSE']) && $newArr['RESPONSE']['CODE'] == "SUCCESS") {
            unset($this->sessionId); // unsets the session property
            $responseArr['httpCode'] = 200;
        }else {
            $responseArr['httpCode'] = 400;
        }    

        return $responseArr;
    }

    /**
     * Get a Person
     */
    public function getPerson($data){        
        $payload = "<NETBOX-API sessionid='$this->sessionId'>
        <COMMAND name='GetPerson' num='1' dateformat='tzoffset'>
            <PARAMS>
            <PERSONID>".$data['person_id']."</PERSONID>
            <ALLPARTITIONS></ALLPARTITIONS>
            <ACCESSLEVELDETAILS>0</ACCESSLEVELDETAILS>
            <WANTCREDENTIALID>1</WANTCREDENTIALID>          
            </PARAMS>
        </COMMAND>
        </NETBOX-API>";    
        $response = $this->httpPost($payload);
        $records = simplexml_load_string($response);
        $newArr = json_decode(json_encode($records), true);          

        // validate data => recreate response array to return with err code
        $responseArr = [];
        $responseArr['data'] = $newArr;
        if(array_key_exists("CODE", $newArr['RESPONSE']) && $newArr['RESPONSE']['CODE'] == "SUCCESS") {
            unset($this->sessionId); // unsets the session property
            $responseArr['httpCode'] = 200;
        }else {
            $responseArr['httpCode'] = 400;
        }    

        return $responseArr;
    }

    /**
     * Generate MAC
     */
    public function generateMac() {
        # MAC = [(Rand - Rand - Sequence)(SHA1 of (Rand - Rand - Sequence))] 
        $mac = rand(10000, 99999).rand(10000, 99999).round(microtime(true) * 1000);
        $mac = $mac.''.sha1($mac);
        return $mac;
    }
    
    /**
     * Get API Version
     */
    public function getAPIVersion(){
        $payload = "<NETBOX-API>
        <COMMAND name='GetAPIVersion' num='1'>
        </COMMAND>
        </NETBOX-API>";
        $response = $this->httpPost($payload);
        return $response;    
    }

    /**
     * Get Access History (Using MAC)
     * @param: date in YYYY-MM-DD format     
     */
    public function getAccessHistoryMac($date,$nextlogid) {

        $callParams = '';
        if(empty($date)) { $date = date("Y-m-d"); }
        $callParams = $callParams."
        <OLDESTDTTM>".$date."</OLDESTDTTM>";

        if(!empty($nextlogid) && $nextlogid != '-1') { 
            $callParams = $callParams."
            <STARTLOGID>".$nextlogid."</STARTLOGID>";
        }
        $payload = "<NETBOX-API>
        <COMMAND name='GetAccessHistory' num='1' dateformat='tzoffset'>
            <PARAMS>        
                $callParams
            </PARAMS>
        </COMMAND>
        <MAC>".$this->generateMac()."</MAC>
        </NETBOX-API>";

        $response = $this->httpPost($payload);
        $records = simplexml_load_string($response);
        $newArr = json_decode(json_encode($records), true);          

        // validate data => recreate response array to return with err code
        $responseArr = [];
        $responseArr['data'] = $newArr;
        if(array_key_exists("CODE", $newArr['RESPONSE']) && $newArr['RESPONSE']['CODE'] == "SUCCESS") {
            unset($this->sessionId); // unsets the session property
            $responseArr['httpCode'] = 200;
        }else {
            $responseArr['httpCode'] = 400;
        }    

        return $responseArr;
    }

    /**
     * Get Access History (Using Login)
     * @param: date in YYYY-MM-DD format     
     */
    public function getAccessHistoryLogin($date) {

        $callParams = '';
        if(empty($date)) { $date = date("Y-m-d"); } //todo: fix this deafault value to be tz specific date
        $callParams = $callParams."
        <OLDESTDTTM>".$date."</OLDESTDTTM>";
    
        if(!empty($nextlogid) && $nextlogid != '-1') { 
            $callParams = $callParams."
            <STARTLOGID>".$nextlogid."</STARTLOGID>";
        }
        $payload = "<NETBOX-API sessionid='$this->sessionId'>
        <COMMAND name='GetAccessHistory' num='1' dateformat='tzoffset'>
            <PARAMS>        
                $callParams
            </PARAMS>
        </COMMAND>
        </NETBOX-API>";

        $allLogs = array();
        $thisCallLogs = array();
        $responseArr = [];
        $nextlogid = '0'; # set to 0 by default; loop to stop when value turns -1
        while($nextlogid != '-1'){        
            // echo "NLId: ".$nextlogid;
            $responseXML = $this->httpPost($payload);
            $recordsXML = simplexml_load_string($responseXML);
            $recordsArr = json_decode(json_encode($recordsXML), true);          

            if(array_key_exists("CODE", $recordsArr['RESPONSE']) && $recordsArr['RESPONSE']['CODE'] == "SUCCESS") {
                $responseArr['httpCode'] = 200;
                $thisCallLogs = $recordsArr['RESPONSE']['DETAILS']['ACCESSES']['ACCESS'];
                $allLogs = array_merge($allLogs, $thisCallLogs); 

                # next loop with depend on this value
                $nextlogid = $recordsArr['RESPONSE']['DETAILS']['NEXTLOGID'];
    
            } else {
                $responseArr['httpCode'] = 400; # one failed loop is enough to discard entire call as 400

                # break the loop
                $nextlogid = -1;
            }    
        }                

        // validate data => recreate response array to return with err code
        $responseArr['data'] = $allLogs;

        return $responseArr;
    }    

}

?>