<?php
/*
 * Name: voterdb_dataentry_func.php      V4.1   5/20/18
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_build_login, voterdb_build_turf_select, voterdb_build_others_list,
 */



/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_login
 * 
 * Build the login form for the NL to get their turf for data entry.  Login
 * is their first and last name plus a password for NLP.  The password is
 * the same for everyone.  The first name used here is the MyCampaign
 * Nickname.
 * 
 * @param type $form_state
 * @return array - form element.
 */
function voterdb_build_login($form_state) {
  // Date of the eelection.
  $bl_edate = variable_get('voterdb_edate', '2017-05-16');
  // Date ballots are dropped.
  $bl_bdate = variable_get('voterdb_bdate', '2017-04-26');
  $bl_etime = strtotime($bl_edate);
  $bl_btime = strtotime($bl_bdate);
  $bl_edisplay = date('F j, Y',$bl_etime);
  $bl_bdisplay = date('F j, Y',$bl_btime);
  $bl_note = '';
  if(!empty($bl_edate)) {
    $bl_note .= 'Election day is '.$bl_edisplay.'.&nbsp;&nbsp;';
  }
  if(!empty($bl_bdate)) {
    $bl_note .= 'Ballots will be sent out after '.$bl_bdisplay.
      '.<br/>Please be sure to attempt contact with our voters before they get their ballots.' ;
  }
  if(!empty($bl_note)) {
    $form_element['note-tbl'] = array (
      '#type' => 'markup',
      '#markup' => $bl_note,
    );
  }
  $bl_br_date = variable_get('voterdb_br_date', NULL);
  if(!empty($bl_br_date)) {
    $form_element['br_date'] = array (
    '#type' => 'markup',
    '#markup' => '<br/><b>The latest date a ballot has been recorded as received: '.$bl_br_date.'</b>',);
  }
  $form_element['log-in'] = array(
    '#title' => 'Login to display your turf',
    '#prefix' => " \n".'<div id="login-div" style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  // First name title.
  $form_element['log-in']['fname'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:300px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'First Name:',
  );
  // First name data entry field.
  $form_element['log-in']['nlfname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Last name title.
  $form_element['log-in']['lname'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Last Name:',
  );
  // Last name data entry field.
  $form_element['log-in']['nllname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Password
  $form_element['log-in']['nl-spam'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Password:',
  );
  $form_element['log-in']['nl-block'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 10,
    '#maxlength' => 10,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Submit button
  $form_element['log-in']['submit'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#value' => 'Login >>'
  );  
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_turf_select
 * 
 * This NL has more than one turf.  Build a list of their turfs so the NL
 * can select one of them for data entry.  This is the only place that the 
 * NL will see the turf name assigned to the turf at turf checkin.
 * 
 * @param type $form_state
 * @return boolean
 */
function voterdb_build_turf_select(&$form_state) {
  // Get the names of the turfs associated with this NL.
   $turfObj = $form_state['voterdb']['turfObj'];
   $turfArray = $form_state['voterdb']['turfArray'];
   $turfNames = $turfObj->createTurfNames($turfArray);
  // Give the list of turfs and let the use select one of these turfs.
  $form_element['turfselect'] = array(
    '#type' => 'radios',
    '#multiple' => FALSE,
    '#title' => t('Select a turf from the list'),
    '#options' => $turfNames,
    '#required' => TRUE,
  );
  $form_element['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Display the selected turf >>'
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_others_list
 * 
 * This function creates a list of all the NLs in the HD of this NL.  There 
 * is always one since this NL is in the HD.  The form elements are created
 * to display the list of turf for the user to select one.
 * 
 * @param type $form_state
 * 
 * @return $form_element - 
 */
function voterdb_build_others_list($form_state) {
  $bn_hd = $form_state['voterdb']['HD'];
  $bn_county = $form_state['voterdb']['county'];
  if ($bn_hd == '') {
    drupal_set_message('Your house district is not known.','status');
  } else {
    $bn_nldisplay_header_data = array (
        array('data'=>'Name','style'=>'text-align:left; width:140px;'),
        array('data'=>'Reported Results','style'=>'text-align:center; width:110px;'),
    );
    $form_element['others-display'] = array(
        '#tree' => TRUE,
        '#theme' => 'table',
        '#attributes' => array('style' => 'width:250px;'),
        '#header' => $bn_nldisplay_header_data,
        '#rows' => array(),
    );
    // Create a list of NLs that have signed up for this cycle, including 
    // their reporting status.
    db_set_active('nlp_voterdb');
    try {
      $bn_query = db_select(DB_NLSSTATUS_TBL, 'r');
      $bn_query->join(DB_NLS_TBL, 'n', 'r.'.NN_MCID.' = n.'.NH_MCID );
      $bn_query->addField('n', NH_NICKNAME);
      $bn_query->addField('n', NH_LNAME);
      $bn_query->addField('r', NN_RESULTSREPORTED);
      $bn_query->condition(NN_NLSIGNUP,'Y');
      $bn_query->condition(NH_HD,$bn_hd);
      $bn_query->condition('n.'.NN_COUNTY,$bn_county);
      $bn_query->orderBy(NH_LNAME);
      $bn_query->orderBy(NH_FNAME);
      $bn_result = $bn_query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    $bn_nl_list = $bn_result->fetchAll(PDO::FETCH_ASSOC);
    db_set_active('default');
    // Get the path to the gold star image.
    $bn_module_path = drupal_get_path('module','voterdb');
    $bn_star = '<img alt="" src="'.$bn_module_path.'/voterdb_star.png" />';
    // For each NL, get their record.
    foreach ($bn_nl_list as $bn_nl) {
      $bn_nlfname = $bn_nl[NH_NICKNAME];
      $bn_nllname = $bn_nl[NH_LNAME];  // Last name.
      //Give those reporting status a gold star.
      $bn_nlrpt = ($bn_nl[NN_RESULTSREPORTED]!='') ? $bn_star :'';
      $form_element['others-display']['#rows'][] = array (
        array('data'=>$bn_nllname.', '.$bn_nlfname),
        array('data'=>$bn_nlrpt,'style'=>'text-align:center;'),
      );
    }
  }
  $form_element['other-submit'] = array(
    '#type' => 'submit',
    '#value' => 'Next >>'
  );
  drupal_set_message('Please be sure to click the next button and not
    the browser back button','status');  
  return $form_element;
}

