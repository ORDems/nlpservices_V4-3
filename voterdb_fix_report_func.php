<?php
/*
 * Name: voterdb_fix_report_func.php      V4.1   6/1/18
 *
 */

use Drupal\voterdb\NlpTurfs;

/** * * * * * functions supported * * * * * *
 * voterdb_build_admin_login, voterdb_build_nl_select,
 * voterdb_set_active_status
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_user
 * 
 * Get the name of the user that is marking records.  This activity will
 * be tracked in case something goes wrong.
 * 
 * @return array - associate array with the name of the Drupal user.
 */
function voterdb_get_user() {
  $gu_account = user_uid_optional_load();
  $gu_field_firstname = $gu_account->field_firstname;
  $gu_field_lastname = $gu_account->field_lastname;
  $gu_user['fname'] = $gu_field_firstname['und'][0]['safe_value'];
  $gu_user['lname'] = $gu_field_lastname['und'][0]['safe_value'];
  return $gu_user;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_turf_list
 * 
 * Create three arrays.  One is an array of unique NLs with a turf.  The second
 * is an array of turfs with the NL identified.  The first helps us select
 * and NL with a turf with faulty reports.  The second helps if we have the
 * case of an NL with multiple turfs.  The third uses the mcid as the key for
 * the turf name.  This is used for display to the user.
 * 
 * @param type $gt_county
 * @return array - associate array of turfs and NLs.
 */
function voterdb_get_turf_list($gt_county) {
  
  $turfsObj = new NlpTurfs();
  $turfReq['county'] = $gt_county;
  $gt_turf_recs = $turfsObj->getTurfs($turfReq);
  
  // Create and array of distinct MCIDs.
  $gt_nls = $gt_turfs= $gt_multi = array();
  foreach ($gt_turf_recs as $gt_turf_rec) {
    $gt_mcid = $gt_turf_rec['MCID'];
    $gt_text = $gt_turf_rec[NH_NICKNAME].' '.$gt_turf_rec[NH_LNAME].' MCID['.$gt_mcid.']';
    $gt_nls[$gt_mcid][$gt_turf_rec['TurfIndex']] = 
            array(NH_HD=>$gt_turf_rec[NH_HD],NH_PCT=>$gt_turf_rec[NH_PCT],'text'=>$gt_text);
    $gt_turfs[$gt_mcid][$gt_turf_rec['TurfIndex']] = $gt_turf_rec['TurfName'];
    $gt_turf_names[$gt_mcid] = $gt_turf_rec['TurfName'];
  }
  foreach ($gt_turfs as $gt_tmcid => $gt_turf) {
    if(count($gt_turf) > 1) {
      foreach ($gt_turf as $gt_tindex => $gt_tname) {
        $gt_multi[$gt_tmcid][$gt_tindex] = $gt_tname;
        $gt_nls[$gt_tmcid][$gt_tindex]['text'] .= ' *';
      }
    }
  }
  $gt_lists['nl'] = $gt_nls;
  $gt_lists['multi'] = $gt_multi;
  $gt_lists['tnames'] = $gt_turf_names;
  return $gt_lists;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_hd_list
 * 
 * Build a sorted list of HD numbers from the list of NLs with turfs.  This
 * list uses the HD of the NL and not the turf (in case an NL is working
 * in a different turf than where they live.)  The list is used for a select.
 * 
 * @param type $gh_lists - list of turfs created by voterdb_get_turf_list.
 * @return type - associated array of HD numbers.
 */
function voterdb_get_hd_list($gh_lists) {
  $gh_nls = $gh_lists['nl'];
  foreach ($gh_nls as $gh_nl) {
    foreach ($gh_nl as $gh_turf) {
      $gh_hd = $gh_turf[NH_HD];
      $gh_hds[$gh_hd] = $gh_hd;
    }
  }
  ksort($gh_hds);
  return $gh_hds;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_pct_list
 * 
 * Create a list of precinct numbers from the list of turfs that are in
 * the currently selected HD.  The list is used in a select.
 * 
 * 
 * @param type $gp_lists - list of turfs created by voterdb_get_turf_list.
 * @param type $gp_hd - the curent selection for the HD.
 * @return type - a sorted list of unique precinct numbers in the HD with turfs.
 */
function voterdb_get_pct_list($gp_lists,$gp_hd) {
  $gp_nls = $gp_lists['nl'];
  foreach ($gp_nls as $gp_nl) {
    foreach ($gp_nl as $gp_turf) {
      if($gp_turf[NH_HD] == $gp_hd) {
        $gp_pct = $gp_turf[NH_PCT];
        $gp_pcts[$gp_pct] = $gp_pct;
      }  
    }
  }
  ksort($gp_pcts);
  return $gp_pcts;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_nl_list
 * 
 * Create two list of NLs with turfs in the selected precinct.  The first list
 * is the MCIDs to find the database record and the second is text for 
 * the radio button display.
 * 
 * @param type $gn_lists
 * @param type $gn_pct
 * @return type
 */
function voterdb_get_nl_list($gn_lists,$gn_pct) {
  $gn_nls = $gn_lists['nl'];
  foreach ($gn_nls as $gn_mcid => $gn_nl) {
    foreach ($gn_nl as $gn_turf) {
      if($gn_turf[NH_PCT] == $gn_pct) {
        $gn_nl_lists[$gn_mcid] = $gn_turf['text'];
      }
    }
  }
  ksort($gn_nl_lists);
  return $gn_nl_lists;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_voter_list
 * 
 * Create a list of all the voters in all the turfs assigned to this NL.
 * 
 * @param type $gv_lists
 * @param type $gv_nls_list
 * @return type
 */
function voterdb_get_voter_list($gv_lists,$gv_selected_mcid) {
  // Get the turf indexes of all the turfs associated with this NL.
  $gv_nls = $gv_lists['nl'];
  foreach ($gv_nls as $gv_mcid => $gv_nl) {
    if($gv_mcid == $gv_selected_mcid) {
      foreach ($gv_nl as $gv_index => $gv_turf) {
        $gv_turf_indexes[] = $gv_index;
      }
    }
  }
  db_set_active('nlp_voterdb');
  try {
    $gv_query = db_select(DB_NLPVOTER_GRP_TBL, 'g');
    $gv_query->join(DB_NLPVOTER_TBL, 'v', 'v.'.VN_VANID.' = g.'.NV_VANID );
    $gv_query->addField('v', VN_VANID);
    $gv_query->addField('g', NV_NLTURFINDEX);
    $gv_query->condition(NV_NLTURFINDEX, $gv_turf_indexes, 'IN');
    $gv_result = $gv_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  db_set_active('default');
  $gv_voters = $gv_result->fetchAll(PDO::FETCH_ASSOC);
  // Build the assocative array of VANIDs and the voter records.
  foreach ($gv_voters as $gv_voter) {
    $gv_vanid = $gv_voter[VN_VANID];
    $gv_vanids[$gv_vanid] = $gv_vanid;
  }
  return $gv_vanids;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_reports
 * 
 * Given an array of VANIDs, get all the reports associated with these voters.
 * The list can be from a single turf or multiple turfs.
 * 
 * @param type $gr_vanids - list if VANIDs for the voters in turfs.
 * @return array - associative array of reports for a list of voters.
 */
function voterdb_get_reports($gr_vanids) {
  db_set_active('nlp_voterdb');
  try {
    $gr_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $gr_query->join(DB_NLPVOTER_TBL, 'v', 'v.'.VN_VANID.' = r.'.NC_VANID );
    $gr_query->fields('r');
    $gr_query->addField('v', VN_FIRSTNAME);
    $gr_query->addField('v', VN_LASTNAME);
    $gr_query->orderBy('r.'.NC_CYCLE);
    $gr_query->orderBy('r.'.NC_VANID);
    $gr_query->condition('r.'.NC_VANID, $gr_vanids, 'IN');
    $gr_result = $gr_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e[errorInfo] );
    return array();
  }
  db_set_active('default');
  $gr_reports = $gr_result->fetchAll(PDO::FETCH_ASSOC);
  return $gr_reports;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_cycle
 * 
 * Given an array of reports, return an array of the unique cycles present
 * in that list.  The list is sorted in date order.
 * 
 * @param type $gc_reports
 * @return type
 */
function voterdb_get_cycle($gc_reports) {
  $gc_cycles = array();
  foreach ($gc_reports as $gc_report) {
    $gc_cycle = $gc_report[NC_CYCLE];
    $gc_parts = explode('-',$gc_cycle);
    
    $gc_parts[1] = str_pad($gc_parts[1], 2, '0', STR_PAD_LEFT);
    
    $gc_ecycle = implode('-', $gc_parts);

    $gc_cycles[$gc_ecycle] = $gc_cycle;
  }
  ksort($gc_cycles);
  return $gc_cycles;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_reset_element
 * 
 * This function will reset various values in the forms API fields in 
 * the forms state array,  This is the recommended way to ensure that a 
 * change in the default_value will be displayed.
 * 
 * @param type $form_state
 * @param type $re_element - The form element to be reset
 * @param type $re_selected - the new default value.
 */
function voterdb_reset_element(&$form_state,$re_element,$re_selected) {
  $form_state['values'][$re_element] = $re_selected;
  $form_state['input'][$re_element] = $re_selected;
  $form_state['complete form']['hd-change'][$re_element]['#input'] = 1;
  $form_state['complete form']['hd-change'][$re_element]['#value'] = $re_selected;
  $form_state['complete form']['hd-change'][$re_element]['#default_value'] = $re_selected;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_nl_select
 * 
 * Build the login form for the admin to get access to things that are
 * dangerous.  The password is different from that used by the NL.
 * 
 * @param type $form_state
 * @return array - form element.
 */
function voterdb_build_nl_select(&$form_state) {
  $form_element = array();
  $fv_county = $form_state['voterdb']['county'];
  $fv_saved = $form_state['voterdb']['saved'];
  $fv_selected = $form_state['voterdb']['saved'];
  // Create the form to display of all the NLs.
  // Get the values selected for HD, Pct, NL and cycle.  The first time they don't yet exist
  // so the saved value is used (actually the saved were initialized to 0).
  $fv_iarray = array('hd'=>'HD','pct'=>'pct','nl'=>'nls-select','cycle'=>'cycle');
  foreach ($fv_iarray as $fv_key => $fv_val) {
     $fv_selected[$fv_key] = isset($form_state['values'][$fv_val])? $form_state['values'][$fv_val]:$fv_saved[$fv_key];
  }
  // If the HD was changed, the pct, nl and cycle are reset to 0.  They have to be reselected.
  if ($fv_selected['hd'] != $fv_saved['hd'] ) {
    $fv_selected['pct'] = $fv_selected['nl'] = $fv_selected['cycle'] = 0;
    // If the precinct changed, we have to reselect the nl and cycle.
  } elseif ($fv_selected['pct'] != $fv_saved['pct'] ) {
    $fv_selected['nl'] = $fv_selected['cycle'] = 0;
    // If the nl was changed, then the choices of cycle may change as well.
  } elseif ($fv_selected['nl'] != $fv_saved['nl'] ) {
    $fv_selected['cycle'] = 0;
  }
  // Get the list of NLs with turfs and the list of those turfs.
  $fv_lists = voterdb_get_turf_list($fv_county);
  if(empty($fv_lists)) {return '';}
  // Save these lists so we can use them later.
  $form_state['voterdb']['turf-select'] = $fv_lists['multi'];
  $form_state['voterdb']['tnames'] = $fv_lists['tnames'];
  $form_state['voterdb']['saved'] = $fv_selected; 
  // Get the list of unique HD numbers in the existing turfs.
  $fv_hds = voterdb_get_hd_list($fv_lists);
  if($fv_selected['hd'] == 0) {
    reset($fv_hds);
    $fv_selected['hd'] = key($fv_hds);
    if(isset($form_state['complete form'])) {
      voterdb_reset_element($form_state,'HD',$fv_selected['hd']);
    }
  }
  // Now get the list of precincts associate with this HD.
  $fv_pcts = voterdb_get_pct_list($fv_lists,$fv_selected['hd']);
  if($fv_selected['pct'] == 0) {
    reset($fv_pcts);
    $fv_selected['pct'] = key($fv_pcts);
    if(isset($form_state['complete form'])) {
      voterdb_reset_element($form_state,'pct',$fv_selected['pct']);
    }
  }
  $fv_nls_text = voterdb_get_nl_list($fv_lists,$fv_selected['pct']);
  if($fv_selected['nl'] == 0) {
    reset($fv_nls_text);
    $fv_selected['nl'] = key($fv_nls_text);
    if(isset($form_state['complete form'])) {
      voterdb_reset_element($form_state,'nls-select',$fv_selected['nl']);
    }
  }
  $fv_voter_lists = voterdb_get_voter_list($fv_lists,$fv_selected['nl']);
  $fv_reports = voterdb_get_reports($fv_voter_lists);
  $form_state['voterdb']['reports'] = $fv_reports;
  $fv_cycles = voterdb_get_cycle($fv_reports);
  if($fv_selected['cycle'] == 0) {
    reset($fv_cycles);
    $fv_selected['cycle'] = key($fv_cycles);
    if(isset($form_state['complete form'])) {
      voterdb_reset_element($form_state,'cycle',$fv_selected['cycle']);
    }
  }
  $form_state['voterdb']['cycles'] = $fv_cycles;
  $form_state['voterdb']['saved'] = $fv_selected;
  $form_state['voterdb']['selected'] = $fv_selected;
  // Put a container around all the selections.
  $form_element['hd-change'] = array(
    '#title' => 'Select the HD, Pct, NL and Cycle to get list of reports',
    '#prefix' => " \n".'<div id="hd-change-wrapper"  style="width:350px;" >'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
   );
  // Display a list of House Districts with prospective NLs.
  // The default is the value last set in case the form is reused.
  // Set the AJAX configuration to rebuild the following lists if an HD is
  // selected.
  $form_state['voterdb']['hd-options'] = $fv_hds;
  $form_element['hd-change']['HD'] = array(
      '#type' => 'select',
      '#title' => t('Select a House District'),
      '#options' => $fv_hds,
      '#default_value' => $fv_selected['hd'],
      '#ajax' => array (
          'callback' => 'voterdb_hd_selected_callback',
          'wrapper' => 'hd-change-wrapper',
      )
  );
  // Show the list of precincts for the selected HD. 
  $form_state['voterdb']['pct-options'] = $fv_pcts;
  $form_element['hd-change']['pct'] = array(
      '#type' => 'select',
      '#title' => t('Select a Precinct Number for HD'). $fv_hds[$fv_selected['hd']],
      '#options' => $fv_pcts,
      '#default_value' => $fv_selected['pct'],
      '#ajax' => array(
        'callback' => 'voterdb_hd_selected_callback',
        'wrapper' => 'hd-change-wrapper',
        'effect' => 'fade',
      ),
  );
  // Create the list of known NLs in this precinct for the options list.
  $form_state['voterdb']['nls-choices'] = $fv_nls_text;
  // Offer a set of radio buttons for selection of an NL. 
  $form_element['hd-change']['nls-select'] = array(
      '#title' => t('Select the NL for the list of reports.'),
      '#type' => 'radios',
      '#options' => $fv_nls_text,
      '#default_value' => $fv_selected['nl'],
      '#ajax' => array(
        'callback' => 'voterdb_hd_selected_callback',
        'wrapper' => 'hd-change-wrapper',
        'effect' => 'fade',
    )
  );
  // Get the desired cycle.
  $form_state['voterdb']['cycle-options'] = $fv_cycles;
  $form_element['hd-change']['cycle'] = array(
      '#type' => 'select',
      '#title' => t('Select an election cycle.'),
      '#options' => $fv_cycles,
      '#default_value' => $fv_selected['cycle'],
  );
  if(!empty($fv_lists['multi'])) {
     $form_element['footnote'] = array (
      '#type' => 'markup',
      '#markup' => '* multiple turfs exist for this NL.<br>',
    ); 
  }
  // And, a submit button.
  $form_element['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Display reports for this NL. >>',
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_report_list
 * 
 * Get the list of all the reports entered by an NL for a specific cycle
 * and build an array for the select boxes.
 * 
 * @param type $rl_reports
 * @param type $rl_cycle
 * @return int - array of precinct numbers in numerical order or FALSE.
 */
function voterdb_report_list($rl_reports,$rl_cycle) {
  // Build the options array for the form check boxes.
  $rl_rpts = array();
  $rl_j = 0;
  foreach ($rl_reports as $rl_rpt) {  
    if($rl_rpt[NC_CYCLE] == $rl_cycle) {
      //$rl_mcid = $rl_rpt[NC_MCID];
      $rl_fn = $rl_rpt[VN_FIRSTNAME];
      $rl_ln = $rl_rpt[VN_LASTNAME];
      $rl_display = "[".$rl_rpt[NC_VANID]."] ".$rl_fn." ".$rl_ln.": ";
      $rl_display .= $rl_rpt[NC_CDATE].": ";
      $rl_display .= $rl_rpt[NC_TYPE].": ".$rl_rpt[NC_VALUE].": ".$rl_rpt[NC_TEXT];
      if($rl_rpt[NC_ACTIVE]) {
        $rl_rpts['defaults'][$rl_j++] = $rl_rpt[NC_RINDEX];
      }
      $rl_rpts['active'][$rl_rpt[NC_RINDEX]] = $rl_rpt[NC_ACTIVE];
      $rl_rpts['display'][$rl_rpt[NC_RINDEX]] = $rl_display;
    }
  }
  return $rl_rpts;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_report_list
 * 
 * Build a form with a list of reports made by this NL for a specific cycle.
 * A list of check boxes are offered to either activate or deactivate a 
 * specific report.
 * 
 * @param type $form
 * @param type $form_state
 * @return form element
 */
function voterdb_build_report_list($form,&$form_state) {
  $rl_cyclei = $form_state['values']['cycle'];
  $fv_cycles = $form_state['voterdb']['cycles'];
  $rl_cycle = $fv_cycles[$rl_cyclei];
  $rl_reports = $form_state['voterdb']['reports'];
  // Get the reports for this NL.
  $rl_rpts = voterdb_report_list($rl_reports,$rl_cycle);
  //voterdb_debug_msg('reports', $rl_rpts);
  $form_state['voterdb']['nl-reports'] = $rl_rpts;
  $rl_mcid = $form_state['voterdb']['selected']['nl'];
  $rl_turf_name = $form_state['voterdb']['tnames'][$rl_mcid];
  // Description.
  $form_element['fixrpt-desc'] = array(
      '#type' => 'item',
      '#title' => t('List of reports for turf: '.$rl_turf_name.', Cycle: '.$rl_cycle),
      '#markup' => 'Set or clear the checkbox selection to enable/disable report.',
  );
  // Add a check box for each report.
  $form_element['fix-rpt'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Report enable/disable.'),
      '#options' => $rl_rpts['display'],
      '#default_value' => $rl_rpts['defaults']
  );
  // And, a submit button.
  $form_element['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit changes. >>',
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_set_active_status
 * 
 * A report was either activated or deactivated.  Change the status in the 
 * database to reflect the user's change.
 * 
 * @param type $form_state
 */
function voterdb_set_active_status($form_state) {
  foreach ($form_state['input']['fix-rpt'] as $sa_rindex => $sa_input) {
    $sa_ison = ($sa_input == '')?FALSE:TRUE;
    $sa_wason = $form_state['voterdb']['nl-reports']['active'][$sa_rindex];
    if ($sa_ison != $sa_wason) {
      $sa_act = ($sa_ison)?"1":"0";
      $sa_stat = ($sa_ison)?"Set Active":"Set Inactive";
      // Something Changed.
      $sa_changed = $form_state['voterdb']['nl-reports']['display'][$sa_rindex];
      $sa_cmsg = "Changed Report: ".$sa_changed.' : '.$sa_stat;
      drupal_set_message($sa_cmsg,'status');
      try {
        db_set_active('nlp_voterdb');
        db_merge(DB_NLPRESULTS_TBL)
          ->key(array(NC_RINDEX => $sa_rindex))
          ->fields(array(NC_ACTIVE => $sa_act,))
          ->execute();
        db_set_active('default');
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return FALSE;
      }
    }
  }
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
  // Rebuild the form to list the NLs in the precinct after the hd is
  // selected.
  return $form['hd-change'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_selected_callback
 * 
 * AJAX callback for the selection of an NL to associate with a turf.
 *
 * @return array
 */
function voterdb_pct_selected_callback ($form,$form_state) {
  // Rebuild the form to list the NLs in the precinct after the precinct is
  // selected.
  return $form['hd-change'];
}
