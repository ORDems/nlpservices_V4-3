<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiVoter.
 */

namespace Drupal\voterdb;

//require_once "voterdb_constants_van_api_tbl.php";
require_once "voterdb_constants_voter_tbl.php";

class ApiVoter {
  
  function __construct() {
    $this->result = NULL;

  }
  

  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
   * parseAddress
   * 
   * 
   * @param type $address
   * @return type
   */
  private function parseAddress($address) {
    $addrFields = array();
    $streetParts = array();
    $prefix = array('E','N','NE','NW','S','SE','SW','W');
    $fields = explode(' ', $address);
    $part = VN_STREETNO;
    foreach ($fields as $field) {
      if($part==VN_STREETNO) {
        $part = VN_STREETPREFIX;
        if(is_numeric($field)) {
          $addrFields[VN_STREETNO] = $field;
          continue;
        } else {
          $addrFields[VN_STREETNO] = NULL;
        }
      }
      if($part==VN_STREETPREFIX) {
        $part = VN_STREETNAME;
        if(in_array($field, $prefix)) {
          $addrFields[VN_STREETPREFIX] = $field;
          continue;
        } else {
          $addrFields[VN_STREETPREFIX] = NULL;
        }
      }
      $streetParts[] = $field;
    }
    $streetName = implode(' ', $streetParts);
    $addrFields[VN_STREETNAME] = $streetName;
    return $addrFields;
  }


  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getApiVoter
 * 
 * 
 */
  public function getApiVoter($countyAuthenticationObj,$database,$vanid) {
    
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;

     $expandOptions = '?$expand=phones,emails,addresses,codes,districts,electionRecords';
    
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/people/'.$vanid.$expandOptions;
    //voterdb_debug_msg('voter URL', $url);

    $ch = curl_init($url);
      
    if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
      voterdb_debug_msg('setopt HEADER error', curl_error($ch));
    }
    //if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database)) {
    //  voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    //}

    if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
      voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    }


    $result = curl_exec($ch);

    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    //$info = curl_getinfo($ch);
    //voterdb_debug_msg('info', $info);
    //voterdb_debug_msg('result', $result);
    //voterdb_debug_msg('curl hdl', $ch);
    curl_close($ch);
    
    $resultObj = json_decode($result);
    //voterdb_debug_msg('result array', $resultObj);
    
    $voter[VN_VANID] = $resultObj->vanId;
    $voter[VN_FIRSTNAME] = $resultObj->firstName;
    $voter[VN_LASTNAME] = $resultObj->lastName;
    $voter[VN_NICKNAME] = $resultObj->nickname;
    $voter[VN_PARTY] = $resultObj->party;
    $voter[VN_SEX] = $resultObj->sex;
    
    
    $voter[VN_CITY] = $voter[VN_STREETNAME] = $voter[VN_STREETPREFIX] =  $voter[VN_STREETNO] = NULL;

    $voter[VN_MZIP] = $voter[VN_MCITY] = $voter[VN_MADDRESS] = NULL;

    
    if(!empty($resultObj->addresses)) {
      foreach ($resultObj->addresses as $addressObj) {
        //voterdb_debug_msg('address object', $addressObj);
        $type = $addressObj->type;
        switch ($type) {
          case 'Voting':
            $address = $addressObj->addressLine1;
            $addrFields = $this->parseAddress($address);
            foreach ($addrFields as $addrKey => $addrValue) {
              $voter[$addrKey] = $addrValue;
            }
            $voter[VN_CITY] = $addressObj->city;
          case 'Mailing':
            $voter[VN_MADDRESS] = $addressObj->addressLine1;
            $voter[VN_MCITY] = $addressObj->city;
            $voter[VN_MZIP] = $addressObj->zipOrPostalCode;
            break;
        }
      }
    }
    
    $voter[VN_HOMEPHONE] = $voter[VN_CELLPHONE] = NULL;
    if(!empty($resultObj->phones)) {
      foreach ($resultObj->phones as $phoneObj) {
        $phoneType = $phoneObj->phoneType;
        switch ($phoneType) {
          case 'Home':
            $voter[VN_HOMEPHONE] = $phoneObj->phoneNumber;
            break;
          case 'Cell':
            $voter[VN_CELLPHONE] = $phoneObj->phoneNumber;
            break;
        }
      }
    }
    
    $voter[VN_PCT] = $voter[VN_HD] = $voter[VN_COUNTY] = $voter[VN_CD] = NULL;
    if(!empty($resultObj->districts)) {
      foreach ($resultObj->districts as $districtObj) {
        $districtType = $districtObj->name;
        switch ($districtType) {
          case 'Congressional':
            $fieldObj = $districtObj->districtFieldValues[0];          
            $voter[VN_CD] = $fieldObj->name;
            break;
          case 'County':
            $fieldObj = $districtObj->districtFieldValues[0];          
            $voter[VN_COUNTY] = $fieldObj->name;
            break;
          case 'Precinct':
            $fieldObj = $districtObj->districtFieldValues[0];          
            $voter[VN_PCT] = $fieldObj->name;
            break;
          case 'State House':
            $fieldObj = $districtObj->districtFieldValues[0];          
            $voter[VN_HD] = $fieldObj->name;
            break;
        }
      }
    }
    
    $voter[VN_VOTING] = NULL;
    if(!empty($resultObj->electionRecords)) {
      $electionArrays = array();
      foreach ($resultObj->electionRecords as $electionObj) {
        //voterdb_debug_msg('election object', $electionObj);
        $electionType = $electionObj->electionRecordType;
        $electionTypeFields = explode('-', $electionType);
        $year = trim($electionTypeFields[0]);
        $electionTypeValue = trim($electionTypeFields[1]);
        
        if($electionTypeValue == "General" OR $electionTypeValue == "Primary") {
          $electionTypeCode = ($electionTypeValue == "General")?0:1;
          $electionIndex = $year.'-'.$electionTypeCode;
          $electionArrays[$electionIndex]['typeCode'] = $electionTypeValue;
          $electionArrays[$electionIndex]['year'] = $year;
          $electionArrays[$electionIndex]['participation'] = $electionObj->participation;
        }
      }
      ksort($electionArrays);
      //voterdb_debug_msg('sorted voting', $electionArrays);
      $voting = '';
      for ($index = 0; $index < 4; $index++) {
        $electionArray = array_pop($electionArrays);
        if($electionArray === FALSE) {break;}
        $type = substr($electionArray['typeCode'],0,1);
        $year = $electionArray['year'] % 100;
        $participation = (!empty($electionArray['participation']))?$electionArray['participation']:' ';
        if($participation == 'X') {
          $participation = 'Y';
        }
        $voting .= $type.$year.':'.$participation.' ';
      }
      $voter[VN_VOTING] = $voting;
    }
    
    
    
    
    
    return $voter;

  }
}

