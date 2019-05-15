<?php
/*
 * Name: voterdb_test.php     
 *
 */

require_once "voterdb_class_custom_fields_api.php";
require_once "voterdb_class_api_authentication.php";


use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\ApiCustomFields;

function voterdb_test() {

  $stateCommittee = variable_get('voterdb_state_committee', '');
  $apiAuthenticationObj = new ApiAuthentication();
  $stateAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  //voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
  
  $apiCustomFieldsObj = new ApiCustomFields($stateAuthenticationObj);
  $customFields = $apiCustomFieldsObj->getCustomFields(1);
  voterdb_debug_msg('custom fields', $customFields,__FILE__,__LINE__);
  
  
      
  $output = "test complete";
  return array('#markup' => $output);   

}