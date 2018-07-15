<?php
/*
 * Name: voterdb_dataentry_func.php      V4.2   7/11/18
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_build_announcement, voterdb_build_turf_select
 */



/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_announcement
 *
 * Build the announcement for an election cycle.
 * 
 * @return array - form element.
 */
function voterdb_build_announcement() {
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
   $turfObj = new NlpTurfs();
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
