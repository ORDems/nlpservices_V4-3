<?php
/*
 * Name: voterdb_dataentry_func4.php    V4.2  7/11/18
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_get_goals, voterdb_get_nlscount,
 * voterdb_set_voter_status,voterdb_get_mbdates,voterdb_voted, 
 * voterdb_results_reported
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_nlscount
 *
 * Counts NLs that either have signed up or who have reported results.  Type
 * selects which type to count.  The count is either for a single HD or for
 * the entire county.   The HD parameter selects which kind of count.
 *
 * The type parameter is the field name to count.  It is either the
 * NN_RESULTSREPORTED or the NN_NLSIGNUP column in the DB_NLSSTATUS_TBL.
 * These fields are either Y for yes or null for no.  We count the number
 * with Y set.
 *
 * @param type $gc_county - name of the group.
 * @param type $gc_hd - HD number or set to ALL to for a county wide count.
 * @param type $gc_type - Column name to count.
 * @return type - goal count or zero if error.
 */
function voterdb_get_nlscount($gc_county,$gc_hd,$gc_type) {
  db_set_active('nlp_voterdb');
  try {
    $gc_query = db_select(DB_NLSSTATUS_TBL, 's');
    $gc_query->join(DB_NLS_TBL, 'n', 'n.'.NG_MCID.' = s.'.NG_MCID);
    $gc_query->fields('s');
    $gc_query->condition('n.'.NN_COUNTY,$gc_county);
    $gc_query->condition($gc_type,'Y');
    if ($gc_hd != 0) {
      $gc_query->condition(NH_HD,$gc_hd);
    }
    $gc_cnt = $gc_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return '';
  }
  db_set_active('default');
  return $gc_cnt;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_set_voter_status
 * 
 * Set the voter status to moved.
 * 
 * @param type $mv_vanid - VANID of this voter.
 * @param type $mv_dorc - Date of current registration.
 * @param type $mv_field - Status field to set true. 
 * @return boolean - TRUE if moved.
 */
function voterdb_set_voter_status($mv_vanid, $mv_dorc, $mv_field) {
  db_set_active('nlp_voterdb');
  try {
    db_merge(DB_NLPVOTER_STATUS_TBL)
      ->key(array(
        VM_VANID => $mv_vanid,
        VM_DORCURRENT => $mv_dorc))
      ->fields(array(
        $mv_field => TRUE))
      ->execute();
    db_set_active('default');
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
  }
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_voter_status
 * 
 * Get the status of this voter.
 * 
 * @param type $mv_vanid - VANID of this voter.
 * @param type $mv_dorc - current date of registration.
 * @return array - associative array of status, or all status as FALSE.
 */
function voterdb_get_voter_status($mv_vanid, $mv_dorc) {
  $mv_null = array(VM_MOVED=>FALSE,VM_DECEASED=>FALSE,VM_HOSTILE=>FALSE);
  db_set_active('nlp_voterdb');
  try {
    $mv_squery = db_select(DB_NLPVOTER_STATUS_TBL, 's');
    $mv_squery->addField('s',VM_MOVED);
    $mv_squery->addField('s',VM_DECEASED);
    $mv_squery->addField('s',VM_HOSTILE);
    $mv_squery->condition(VM_VANID,$mv_vanid);
    $mv_squery->condition(VM_DORCURRENT,$mv_dorc);
    $mv_sresult = $mv_squery->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return $mv_null;
  }
  db_set_active('default');
  $mv_dorcurrent = $mv_sresult->fetchAssoc();
  $mv_status = (empty($mv_dorcurrent))?$mv_null:$mv_dorcurrent;
  return $mv_status;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_mbdates
 * 
 * Get the array of date indexes and the matching date string for display.
 * 
 * @return array - date indexes and date strings.
 */
function voterdb_get_mbdates() {
  db_set_active('nlp_voterdb');
  $vd_tselect = "SELECT * FROM {".DB_DATE_TBL."} WHERE  1";
  $vd_result = db_query($vd_tselect);
  $vd_dates = $vd_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');  
  foreach ($vd_dates as $vd_date) {
    $vd_date_array[$vd_date[DA_INDEX]] = $vd_date[DA_DATE];
  }
  return $vd_date_array;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_results_reported
 *
 * Record that this NL has reported canvas results.
 *
 * @param type $rr_mcid
 */
function voterdb_results_reported($rr_mcid,$rr_county) {
  db_set_active('nlp_voterdb');
  db_update(DB_NLSSTATUS_TBL)
    ->fields(array(
      NN_RESULTSREPORTED => 'Y',))
    ->condition(NN_MCID,$rr_mcid)
    ->condition(NN_COUNTY,$rr_county)
    ->execute();
  db_set_active('default');  
return TRUE;
}
