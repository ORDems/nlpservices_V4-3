<?php




require_once "voterdb_debug.php";




function voterdb_api_requests($form, &$form_state) {
  
  $turl = 'https://www.howsmyssl.com/a/check';
  
  $ch = curl_init($turl);
      


  if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
    voterdb_debug_msg('setopt USERPWD error', curl_error($ch),__FILE__, __LINE__);
  }


  $result = curl_exec($ch);

  if($result === FALSE) {
    voterdb_debug_msg('setopt exec error', curl_error($ch),__FILE__, __LINE__);
  }
  $info = curl_getinfo($ch);
  voterdb_debug_msg('info', $info, __FILE__, __LINE__);
  voterdb_debug_msg('result', $result, __FILE__, __LINE__);
  voterdb_debug_msg('curl hdl', $ch, __FILE__, __LINE__);
  
  $noteCatagory = json_decode($result, true);
  voterdb_debug_msg('notes/categories', $noteCatagory, __FILE__, __LINE__);
  
  curl_close($ch);

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#id' => 'Submit',
  );
 
  return $form;
}

// ========================================================================

function voterdb_api_requests_validate($form, &$form_state) {
  $form_state['voterdb']['reenter'] = true;
  //voterdb_debug_msg('verify: values', $form_state['values'],__FILE__,__LINE__);
  //voterdb_debug_msg('verify: voters', $form_state['voterdb'],__FILE__,__LINE__);

}

// ========================================================================

function voterdb_api_requests_submit($form, &$form_state) {
  
  //voterdb_debug_msg('verify: submit voters', $form_state['voterdb'],__FILE__,__LINE__);
  //voterdb_debug_msg('verify: submit values', $form_state['values'],__FILE__,__LINE__);

  $form_state['voterdb']['reenter'] = true;
  $form_state['rebuild'] = true;
 
  
return;
}


/**
 * This converts a string to hex for debugging
 * @param type $string
 * @return type
 */
function strToHex2($string) {
  $hex = '';
  for ($i = 0; $i < strlen($string); $i++) {
    $hex .= dechex(ord($string[$i]));
  }
  return $hex;
}


function voterdb_test() {
  $form = drupal_get_form('voterdb_api_requests');
  return $form;
}

