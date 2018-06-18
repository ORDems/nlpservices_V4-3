<?php
/*
 * Name: voterdb_nls_status.php   V4.1  6/4/18
 * Manage the NLS status.
 */
require_once "voterdb_constants_nls_tbl.php";   // Constants for NLS records

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nls_status
 * 
 * Get or update the status of the NL.  The status is reported in the display 
 * of the prospective NLs.
 *
 * @param type $su_func  - GET or PUT
 * @param type $su_mcid  - The ID of this NL
 * @param type $su_county - The name of the group
 * @param type $su_status - array of the status fields
 * 
 * @return boolean - array of status fields or FALSE if error
 */
function voterdb_nls_status($su_func,$su_mcid,$su_county,$su_status) {
  switch ($su_func) { 
    // Fetch the current status record.
    case 'GET':
      // Get the existing status of this NL.
      db_set_active('nlp_voterdb');
      try {
      $su_sselect = "SELECT * FROM {".DB_NLSSTATUS_TBL."} WHERE  ".
        NN_COUNTY. " = :county AND ".NN_MCID." = :mcid";
      $su_sargs = array(
        ':county' => $su_county,
        ':mcid' => $su_mcid);
      $su_nls_status = db_query($su_sselect,$su_sargs);
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e , __FILE__, __LINE__);
        return FALSE;
      }
      $su_nl_status = $su_nls_status->fetchAssoc();
      if(!$su_nl_status) {
        // Status does not yet exist, fill an array with null values
        $su_nls_col_list = unserialize(NN_NLSSTATUS_LIST);
        $su_nl_status = array();
        foreach ($su_nls_col_list as $su_col_name) {
          $su_nl_status[$su_col_name] = NULL;
        }
        $su_nl_status[NN_MCID] = $su_mcid;
        $su_nl_status[NN_COUNTY] = $su_county;
        $su_types = unserialize(CT_CONTACT_ARRAY);
        $su_nl_status[NN_CONTACT] = $su_types[CT_CANVASS];
      }
      db_set_active('default');
      break;
    // Merge the new information into the status for this NL.
    case 'PUT':
      db_set_active('nlp_voterdb');
      try {
        db_merge(DB_NLSSTATUS_TBL)
          ->fields($su_status)
          ->key(array(
            NN_MCID => $su_mcid,
            NN_COUNTY => $su_county))
          ->execute();
        db_set_active('default');
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
        return FALSE;
      }
      $su_nl_status = $su_status;
      db_set_active('default');
      break;
  }
  return $su_nl_status;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nl_status_history
 * 
 * Keep track of the changes to the status of the NL.
 * 
 * @param type $nh_county
 * @param type $nh_mcid
 * @param type $nh_status
 * @return boolean
 */
function voterdb_nl_status_history($nh_county,$nh_mcid,$nh_status) {
  // Get the name of this NL.
  db_set_active('nlp_voterdb');
  try {
    $nh_query = db_select(DB_NLS_TBL, 'n');
    $nh_query->addField('n', NH_NICKNAME);
    $nh_query->addField('n', NH_LNAME);
    $nh_query->condition(NH_MCID,$nh_mcid);
    $nh_result = $nh_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  $nh_nlr = $nh_result->fetchAll(PDO::FETCH_ASSOC);
  $nh_nl = $nh_nlr[0];
  // Create a history record of the status change of the NL.
  $nh_date = date('Y-m-d G:i:s'); 
  try {
    db_insert(DB_NLSSTATUS_HISTORY_TBL)
      ->fields(array(
        NY_DATE => $nh_date,
        NY_MCID => $nh_mcid,
        NY_COUNTY => $nh_county,
        NY_CYCLE => variable_get('voterdb_ecycle', 'yyyy-mm-t'),
        NY_STATUS => $nh_status,
        NY_NLFNAME => $nh_nl[NH_NICKNAME],
        NY_NLLNAME => $nh_nl[NH_LNAME],
        ))
      ->execute();
    db_set_active('default');
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  return;
}