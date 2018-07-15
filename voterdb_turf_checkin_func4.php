<?php
/*
 * Name: voterdb_turf_checkin_func4.php   V4.2  6/18/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_mailing_list, voterdb_get_turf_index, voterdb_display_names, 
 * voterdb_set_moved, voterdb_moved_check, 
 */

use Drupal\voterdb\NlpPaths;

define('OT_ADDR',serialize(array(VN_STREETNO,VN_STREETPREFIX,VN_STREETNAME,
    VN_STREETTYPE,VN_APTTYPE,VN_APTNO,VN_CITY))); 

define('NAME','0');
define('MADDR','1'); 
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_mailing_list
 * 
 * Create a mailing address list for each household.   The names and ages of
 * every voter in the household will be listed to help address the postcard.
 * 
 * @param type $form_state
 * @return boolean|string - File name where mail list is saved.
 *                        - FALSE if database error.
 */
function voterdb_mailing_list($form_state) {
  $ml_county = $form_state['voterdb']['county'];
  $ml_turf_index = $form_state['voterdb']['turf_index'];
  $ml_mcid = $form_state['voterdb']['mcid'];
  // Get the list of voters for this turf from the voter grp table, order by voting address.
  db_set_active('nlp_voterdb');
  try {
    $ml_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $ml_query->join(DB_NLPVOTER_TBL, 'v', 'v.'.VN_VANID.' = g.'.NV_VANID );
    $ml_query->fields('v');
    $ml_query->condition(NV_NLTURFINDEX,$ml_turf_index);
    $ml_query->condition('g.'.NV_COUNTY,$ml_county);
    $ml_query->orderBy(VN_STREETNAME);
    $ml_query->orderBy(VN_STREETNO);
    $ml_query->orderBy(VN_APTNO);
    $ml_query->orderBy(VN_LASTNAME);
    $ml_query->orderBy(VN_FIRSTNAME);
    $ml_result = $ml_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return 0;
  }
  $ml_vtr_list = $ml_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  // Create a postcard addr file.
  $ml_mail_file = "MAIL-".$ml_mcid."-".$ml_turf_index.".txt";
  
  $pathsObj = new NlpPaths();
  $ml_call_path = $pathsObj->getPath('MAIL',$ml_county);
  //$ml_call_path = voterdb_get_path('MAIL',$ml_county);
  $ml_mail_file_name = $ml_call_path . $ml_mail_file;
  file_save_data('', $ml_mail_file_name, FILE_EXISTS_REPLACE);
  $ml_mail_fh = fopen($ml_mail_file_name,"w");
  if ($ml_mail_fh == FALSE) {
    drupal_set_message('Failed to open Mail file', 'error');
    return FALSE;
  }
  // Write a header record to the file.
  $ml_hdr_string = "Name(s)"."\t"."Mailing Address"."\n";
  fwrite($ml_mail_fh,$ml_hdr_string);
  // Create the display of voter's mailing address, grouped if more than one at the same address.
  foreach ($ml_vtr_list as $ml_vtr_info) {
    // Extracted the name, address and age info from the vtr record.
    $ml_vtr_sal = " [".$ml_vtr_info[VN_NICKNAME]."]";
    $ml_vtr_nm = $ml_vtr_info[VN_FIRSTNAME]." ".$ml_vtr_info[VN_LASTNAME];
    $ml_vtr_age = "- Age(".$ml_vtr_info[VN_AGE].")";
    $ml_vtr_name = $ml_vtr_nm.$ml_vtr_sal.$ml_vtr_age;
    $ml_vtr_maddr = $ml_vtr_info[VN_MADDRESS].'<br>'.$ml_vtr_info[VN_MCITY]. ', '.$ml_vtr_info[VN_MSTATE].' '.$ml_vtr_info[VN_MZIP];
    // If the first voter in household, remember name and address incase there are others.
    if (empty($ml_current[MADDR])) {
      $ml_current[MADDR] = $ml_vtr_maddr;
      $ml_current[NAME] = $ml_vtr_name;
      $ml_first_vtr = FALSE;
    } else {
      // If not the first voter in the household, then if another voter at the 
      // same address, then add the name to the list.
      if($ml_vtr_maddr == $ml_current[MADDR]) {
        $ml_current[NAME] .= "<br>".$ml_vtr_name;
      } else {
        // If this voter is registered at a different address, write the 
        // mailing address record, and start over with this voter.
        $ml_mail_string = $ml_current[NAME]."\t".$ml_current[MADDR]."\n";
        fwrite($ml_mail_fh,$ml_mail_string);
        $ml_current[MADDR] = $ml_vtr_maddr;
        $ml_current[NAME] = $ml_vtr_name;
      }
    }
  } 
  // Write the record for the last household.
  if (!empty($ml_current[MADDR])) {
    $ml_mail_string = $ml_current[NAME]."\t".$ml_current[MADDR]."\n";
    fwrite($ml_mail_fh,$ml_mail_string);
  }
  // close the file.
  fclose($ml_mail_fh);
  return $ml_mail_file;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_names
 * 
 * Display the list of names of voters.  Voters who moved are displayed
 * as a warning but overlapped voters will be an error.
 *  
 * @param type $dn_dup_vtrs - array of voter names.
 * @param type $dn_type - type of list: moved or overlapped.
 */
function voterdb_display_names($form_state,$dn_dup_vtrs,$dn_type) {
  $dn_new_vtrs = $form_state['voterdb']['voters'];
  // Display a title for the type of list.
  switch ($dn_type) {
      case 'moved':
        drupal_set_message('Voters marked as moved.','warning');
        break;
      case 'overlap':
        drupal_set_message('Voters in two or more turfs.','error');
        break;
    }    
  // For each voter, construct a name and address line for display.  
  // For voters who moved, both the old and new addresses are displayed.
  foreach ($dn_dup_vtrs as $dn_dup_vtr) {
    $dn_dup_vanid = $dn_dup_vtr[VN_VANID];
    $dn_dup_turfindex = $dn_dup_vtr[NV_NLTURFINDEX];
    
    $turfsObj = $form_state['voterdb']['turfsObj'];
    $dn_dup_turf_rec = $turfsObj->getTurf($dn_dup_turfindex);
    
    //$dn_dup_turf_rec = voterdb_get_turf($dn_dup_turfindex);  // func.
    $dn_dup_vtr_name = $dn_dup_vtr[VN_LASTNAME].','.$dn_dup_vtr[VN_FIRSTNAME]; 
    $dn_nl = ' - NL: '.$dn_dup_turf_rec['NLfname'].','.$dn_dup_turf_rec['NLlname'];
    $dn_name_line = 'VANID: '.$dn_dup_vanid.' '.$dn_dup_vtr_name.$dn_nl;
    $dn_date_reg = ' - DateReg: '.$dn_dup_vtr[VN_DATEREG];
    $dn_old_addr = '';
    $dn_addr_fields = unserialize(OT_ADDR);
    foreach ($dn_addr_fields as $dn_addr_field) {
      $dn_old_addr .= ' '.$dn_dup_vtr[$dn_addr_field];
    }
    $dn_new_addr = '';
    foreach ($dn_addr_fields as $dn_addr_field) {
      $dn_new_addr .= ' '.$dn_new_vtrs[$dn_dup_vanid][$dn_addr_field];
    }
    // Display the message we created.
    switch ($dn_type) {
      case 'moved':
        drupal_set_message($dn_name_line,'warning');
        drupal_set_message('  '.$dn_date_reg.' Old addr:'.$dn_old_addr,'warning');
        drupal_set_message('  '.$dn_date_reg.' New addr:'.$dn_new_addr,'warning');
        break;
      case 'overlap':
        drupal_set_message($dn_name_line.' '.$dn_date_reg.' - Addr:'.$dn_old_addr,'error');
        break;
    }    
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_set_moved
 * 
 * Set the status of the voters in the array to moved.  The status is contained
 * in the voter grp table because the voter is assigned to two turfs.  The
 * moved status keeps the voter from being considered as an overlap.
 * 
 * @param type $sm_vtr_array - array of voters who have moved.  Array of VANIDs.
 */
function voterdb_set_moved($sm_county,$sm_vtrs_moved) {
  // For each VANID in the array, set the status in the grp table to moved.
  foreach ($sm_vtrs_moved as $sm_vanid => $sm_vtr_moved) {
    $sm_mcid = $sm_vtr_moved['MCID'];
    $sm_vanid = $sm_vtr_moved['VANID'];
    // Set the status to "M".
    db_set_active('nlp_voterdb');
    db_update(DB_NLPVOTER_GRP_TBL)
      ->fields(array(
        NV_VOTERSTATUS => 'M',))
      ->condition(NV_VANID,$sm_vanid)
      ->condition(NV_MCID,$sm_mcid)
      ->condition(NV_COUNTY,$sm_county)
      ->execute();
    db_set_active('default');
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_moved_check
 * 
 * This function is given an array of voters that are already assigned to 
 * another turf.  Each voter is checked to see if the new information has a 
 * newer date of registration and the address has changed.  If so, the voter
 * is marked as "moved" in the else the voter is declared "overlapped".
 * 
 * @param type $form_state - array of voters already assigned to another turf.
 * @return - array of voters, grouped into moved and overlapped categories.
 */
function voterdb_moved_check($form_state) {
  $mc_duplicates = $form_state['voterdb']['duplicates'];
  $mc_voters = $form_state['voterdb']['voters'];
  $mc_catagory = array();
  $mc_addr_fields = unserialize(OT_ADDR);
  $mc_moved_cnt = $mc_overlap_cnt = 0;
  // For each voter in the array, check if they have moved.
  foreach ($mc_duplicates as $mc_dup_vanid => $mc_dup_voter) {
    // If the date of registration is missing, then ignore the test for moved.
    if(empty($mc_dup_voter['voter'][VN_DORCURRENT]) OR empty($mc_dup_voter['temp'][VN_DORCURRENT]) ) {
      $mc_catagory['overlap'][$mc_overlap_cnt++] = $mc_dup_voter;
    }
    // If the new voter record has an earlier registration date, then check addr.
    elseif($mc_dup_voter['voter'][VN_DORCURRENT] >= $mc_dup_voter['temp'][VN_DORCURRENT]) {
      $mc_catagory['overlap'][$mc_overlap_cnt++] = $mc_dup_voter;
    } else {
      // Check if the new registration is an address change.
      $mc_changed = FALSE;
      foreach ($mc_addr_fields as $mc_addr_field) {
        if($mc_dup_voter[$mc_addr_field] != $mc_voters[$mc_dup_vanid][$mc_addr_field]) {
          $mc_changed = TRUE;
        }
      }
      // The voter changed address if different.
      if ($mc_changed) {
        $mc_catagory['moved'][$mc_moved_cnt++] = $mc_dup_voter;
      } else {
        $mc_catagory['overlap'][$mc_overlap_cnt++] = $mc_dup_voter;
      }
    }
  }
  return $mc_catagory;
}