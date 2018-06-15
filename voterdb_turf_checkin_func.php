<?php
/*
 * Name: voterdb_turf_checkin_func.php     V4.1  5/29/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_nls_list, 
 * voterdb_get_base, voterdb_insert_turf
 * voterdb_hd_selected_callback, voterdb_pct_selected_callback
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nls_list
 * 
 * Get a list of NLs associated with the specified precinct.
 * 
 * @param type $nl_county - group name.
 * @param type $nl_pct - target precinct.
 * @param type $nl_mcid_array - array of MCIDs for the NL names.
 * @return string|boolean - Array of NL names or FALSE if there was a problem.
 */
function voterdb_nls_list($nl_county,$nl_pct,&$nl_mcid_array) {
  // Get a list of the NLs in the selected precinct, order by name.
  db_set_active('nlp_voterdb');
  try {
    $nl_query = db_select(DB_NLS_GRP_TBL, 'g');
    $nl_query->join(DB_NLS_TBL, 'n', 'g.'.NG_MCID.' = n.'.NH_MCID );
    $nl_query->addField('n', NH_NICKNAME);
    $nl_query->addField('n', NH_LNAME);
    $nl_query->addField('n', NH_EMAIL);
    $nl_query->addField('n', NH_MCID);
    $nl_query->condition(NH_PCT,$nl_pct);
    $nl_query->condition('g.'.NG_COUNTY,$nl_county);
    $nl_query->orderBy(NH_LNAME);
    $nl_query->orderBy(NH_NICKNAME);
    $nl_result = $nl_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  //voterdb_debug_msg('query', $nl_query, __FILE__, __LINE__);
  
  $nl_nls_list = $nl_result->fetchAll(PDO::FETCH_ASSOC);
  //voterdb_debug_msg('list', $nl_nls_list, __FILE__, __LINE__);
  db_set_active('default');
  if(empty($nl_nls_list)) {return FALSE;} // This should never happen.
 // There should always be at least one NL in any Pct choice.
  $nl_i = 0;
  foreach ($nl_nls_list as $nl_nls) {
    // Build the choices array for the radio button form options.
    // Display the name, email and MCID of the NL
    $nl_nls_choices[$nl_i] = $nl_nls[NH_NICKNAME].' '.$nl_nls[NH_LNAME].
        ': '.$nl_nls[NH_EMAIL].', MCID['.$nl_nls[NH_MCID].']';
    $nl_mcid_array[$nl_i] = $nl_nls;
    $nl_i++;
  } 
return $nl_nls_choices;
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_list
 * 
 * Construct an array of the House District numbers for which NLs exist.
 * 
 * @param type $hl_county  -  name of the group
 * @return array - array of HD numbers, in numerical order or FALSE
 */
function voterdb_hd_list($hl_county) {
  // Get the list of distinct HD numbers for this group, order numerically.
  db_set_active('nlp_voterdb');
  try {
    $hl_query = db_select(DB_NLS_GRP_TBL, 'g');
    $hl_query->join(DB_NLS_TBL, 'n', 'g.'.NG_MCID.' = n.'.NH_MCID );
    $hl_query->addField('n', NH_HD);
    $hl_query->distinct();
    $hl_query->condition('g.'.NG_COUNTY,$hl_county);
    $hl_query->orderBy(NH_HD);
    $hl_result = $hl_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  $hl_hd_list = $hl_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  if(empty($hl_hd_list)) {return FALSE;}
  // Build the options array for the select form options. 
  $hl_hdi = 0;
  foreach ($hl_hd_list as $hl_hd) {
    $hl_hd_options[$hl_hdi++] = $hl_hd[NH_HD];
  }
  return $hl_hd_options;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_list
 * 
 * Construct and array of the Precinct numbers in a specified HD for which 
 * NLs exist.
 * 
 * @param type $pl_county - name of the group.
 * @param type $pl_hd - number of the target HD.
 * @return int - array of precinct numbers in numerical order or FALSE.
 */
function voterdb_pct_list($pl_county,$pl_hd) {
  // Get the list of precinct numbers with at least one prospective NL in 
  // this HD, order numberically by precinct number.
  db_set_active('nlp_voterdb');
  try {
    $pl_query = db_select(DB_NLS_GRP_TBL, 'g');
    $pl_query->join(DB_NLS_TBL, 'n', 'g.'.NG_MCID.' = n.'.NH_MCID );
    $pl_query->addField('n', NH_PCT);
    $pl_query->distinct();
    $pl_query->condition('g.'.NG_COUNTY,$pl_county);
    $pl_query->condition(NH_HD,$pl_hd);
    $pl_query->orderBy(NH_PCT);
    $pl_result = $pl_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  $pl_pct_list = $pl_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  if(empty($pl_pct_list)) {return FALSE;}
  // Build the options array for the precinct select form
  $pl_pct_options = array();
  $pl_pcti = 0;
  foreach ($pl_pct_list as $pl_pct) {
    $pl_pct_options[$pl_pcti++] = $pl_pct[NH_PCT];
  } 
  return $pl_pct_options;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_base
 * 
 * Given a file name of a PDF file exported from the VoteBuilder, strip off
 * the suffix, ie the .pdf.  Then remove the two random strings at the end
 * which are separated by underscore.  I assume the underscore is not used
 * as part of the base file name.
 * 
 * @param type $gb_filename - name of a pdf file exported from VoteBuilder
 * return string - base file name
 */
function voterdb_get_base($gb_filename) {
  $gb_turfpdf_e = explode('.', $gb_filename);
  $gb_turfpdf_b = explode('_', $gb_turfpdf_e[0]);
  $gb_turf_base = $gb_turfpdf_b[0];
  return $gb_turf_base;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_insert_turf
 *
 * Enter the turf into the MySQL table for voters.  And, save the PDF if
 * submitted so the NL can get it on the website.

 * @param type $form_state
 * 
 * return - False if error.
 */
function voterdb_insert_turf(&$form_state) {
  $it_mcid = $form_state['voterdb']['mcid'];
  $it_county = $form_state['voterdb']['county'];
  $it_voters = $form_state['voterdb']['voters'];
  // replace voters.
  db_set_active('nlp_voterdb');
  foreach ($it_voters as $it_vanid => $it_voter) {
    try {
      db_merge(DB_NLPVOTER_TBL)
        ->key(array(VN_VANID => $it_vanid))
        ->fields($it_voter)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e , __FILE__, __LINE__);
      return FALSE;
    }
  } 
  // Create a turf table for this new turf. 
  $turf['county'] = $it_county;
  $turf['mcid'] = $it_mcid;
  $turf['firstName'] = $form_state['voterdb']['fname'];
  $turf['lastName'] = $form_state['voterdb']['lname'];
  $turf['turfName'] = $form_state['voterdb']['tname'];
  $turf['pdf'] = $form_state['voterdb']['pdf_file'];
  $turf['hd'] = $form_state['voterdb']['turf_hd'];
  $turf['pct'] = $form_state['voterdb']['turf_pct'];
  $turfsObj = $form_state['voterdb']['turfsObj'];
  $it_turf_index = $turfsObj->createTurf($turf);
  $form_state['voterdb']['turf_index'] = $it_turf_index;
  // Now insert a grp entry.
  $it_vanids = array_keys($it_voters);
  db_set_active('nlp_voterdb');
  foreach ($it_vanids as $it_vanid) {
    try {
      db_insert(DB_NLPVOTER_GRP_TBL)
        ->fields(array(
          NV_COUNTY => $it_county,
          NV_MCID => $it_mcid,
          NV_VANID => $it_vanid,
          NV_NLTURFINDEX => $it_turf_index,
          NV_VOTERSTATUS => 'A',
        ))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
  }
  db_set_active('default');
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_selected_callback
 * 
 * AJAX call back for the selection of the HD
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_hd_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['nl-select']['hd-change'];
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_selected_callback
 * 
 * AJAX callback for the selection of an NL to associate with a turf.
 *
 * @return array
 */
function voterdb_pct_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['nl-select']['hd-change']['nls-select'];
}