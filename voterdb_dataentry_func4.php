<?php
/*
 * Name: voterdb_dataentry_func4.php    V4.2  7/11/18
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_display_voting, voterdb_get_goals, voterdb_get_nlscount,
 * voterdb_set_voter_status,voterdb_get_mbdates,voterdb_voted, 
 * voterdb_results_reported
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_voting
 * 
 * Display a line with the total vote return for the county, the return for 
 * the Rs and Ds, the total NLP return, and the return for this NL.
 *
 * @param type $di_link - database link.
 * @param type $di_county - This group name.
 * @param type $di_mcid - The MCID of the NL.

 * @return $di_output - text string of voting results for display.
 */
function voterdb_display_voting($di_link,$di_county,$di_mcid) {
  // Get the counts of voting from the upload of the matchbacks.
  db_set_active('nlp_voterdb');
  $di_select = "SELECT * FROM {".DB_BALLOTCOUNT_TBL."} WHERE  ".
    BC_COUNTY. " = :county ";
  $di_args = array(
    ':county' => $di_county);
  $di_result = db_query($di_select,$di_args);
  $di_tccnt_array = $di_result->fetchAssoc();
  // Percentage of the registered voters that have voted.
  $di_reg=$di_tccnt_array[1];
  $di_reg_br=$di_tccnt_array[2];
  $di_tvpercent = ($di_reg > 0)?round($di_reg_br/$di_reg*100,1).'%':'0%';
  $di_output ="<br>County voting: $di_tvpercent, ";
  // Percent for Democrats voting.
  $di_dem=$di_tccnt_array[3];
  $di_dem_br=$di_tccnt_array[4];
  $di_dpercent = ($di_dem > 0)?round($di_dem_br/$di_dem*100,1).'%':'0%';
  $di_output .="Dem: $di_dpercent, ";
  // Percent of Republicans voting.
  $di_rep=$di_tccnt_array[5];
  $di_rep_br=$di_tccnt_array[6];
  $di_rpercent = ($di_rep > 0)?round($di_rep_br/$di_rep*100,1).'%':'0%';
  $di_output .="Rep: $di_rpercent, ";
  // Count the number of voters assigned to NLs for this group.
  db_set_active('nlp_voterdb');
  try {
  $di_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
  $di_query->fields('g');
  $di_query->condition(NK_COUNTY,$di_county);
  $di_nvtr = $di_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return '';
  }
  // Count the number of voters who returned ballots.
  try {
    $bn_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $bn_query->join(DB_MATCHBACK_TBL, 'm', 'm.'.MT_VANID.' = g.'.VN_VANID );
    $bn_query->condition(NK_COUNTY,$di_county);
    $bn_query->condition(MT_DATE,'','<>');
    $di_rbvtd = $bn_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return '';
  }
  $di_npercent = ($di_nvtr > 0)?round($di_rbvtd/$di_nvtr*100,1).'%':'0%';
  $di_output .="NLP: $di_npercent, ";
  // Count the number of voters assigned to this NLs.
  try {
    $bn_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $bn_query->condition(NK_COUNTY,$di_county);
    $bn_query->condition(NK_MCID,$di_mcid);
    $di_vtr = $bn_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return '';
  }
  // Count the number of this NLs voters who returned ballots.
  try {
    $bn_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $bn_query->join(DB_MATCHBACK_TBL, 'm', 'm.'.MT_VANID.' = g.'.VN_VANID );
    $bn_query->condition(NK_COUNTY,$di_county);
    $bn_query->condition(VN_MCID,$di_mcid);
    $bn_query->condition(MT_DATE,'','<>');
    $di_vtd = $bn_query->countQuery()->execute()->fetchField();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return '';
  }
  $di_vpercent = ($di_vtr > 0)?round($di_vtd/$di_vtr*100,1).'%':'0%';
  $di_output .="Your voters: $di_vpercent ";
  db_set_active('default');
  return $di_output;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_goals
 *
 * Get the NL recruitment goal for a house district.  If the HD is set to ALL
 * the goal for the entire county is retrieved.  The county goal is set
 * separately and is not necessarily the sum of the HD goals.
 *
 * @param type $gg_county - name of the county.
 * @param type $gg_hd : HD number or ALL.
 * @return int : the numeric goal for HD or county.
 */
function voterdb_get_goals($gg_county,$gg_hd) {
  db_set_active('nlp_voterdb');
  $gg_tselect = "SELECT * FROM {".DB_NLPGOALS_TBL."} WHERE  ".
    NM_COUNTY. " = :county AND ".NM_HD." = :hd";
  $gg_targs = array(
    ':county' => $gg_county,
    ':hd' => $gg_hd);
  $gg_result = db_query($gg_tselect,$gg_targs);
  $gg_goal = $gg_result->fetchAssoc();
  db_set_active('default');  
  if(empty($gg_goal)) {return '';}
  // Return the requested goal, or zero if goals is not yet set.
  return $gg_goal[NM_NLPGOAL];
}

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
 * voterdb_voted
 * 
 * Check if this voter has turned in a ballot.
 * 
 * @param type $vd_link - database link.
 * @param type $vd_vanid - VANID of this voter.
 * @return string - Image of a star and the date the ballot was recieved or 
 *                  a null string.
 */
function voterdb_voted($vd_vanid) {
  db_set_active('nlp_voterdb');
  $vd_tselect = "SELECT * FROM {".DB_MATCHBACK_TBL."} WHERE  ".
    MT_VANID." = :vanid";
  $vd_targs = array(':vanid' => $vd_vanid);
  $vd_result = db_query($vd_tselect,$vd_targs);
  $vd_date = $vd_result->fetchAssoc();
  db_set_active('default');
  // If the voter has sent in a ballot, display the date it was received.
  if(empty($vd_date)){return FALSE;}
  $vd_mbdate_index = $vd_date[MT_DATE_INDEX];
  return $vd_mbdate_index;
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
