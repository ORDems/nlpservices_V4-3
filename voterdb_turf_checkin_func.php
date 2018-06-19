<?php
/*
 * Name: voterdb_turf_checkin_func.php     V4.2  6/18/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_get_base, voterdb_insert_turf
 * voterdb_hd_selected_callback, voterdb_pct_selected_callback
 */


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
      voterdb_debug_msg('e', $e->getMessage() );
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
      voterdb_debug_msg('e', $e->getMessage() );
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