<?php
/*
 * Name: voterdb_fix_report_func2.php      V4.0   12/4/17
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_build_turf_select, voterdb_turf_selected_callback
 * 
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_turf_select
 * 
 * The selected NL has more than one turf.  Select one to get the list of 
 * reports to be disables or enabled.
 * 
 * @param type $form_state
 * @return string
 */
function voterdb_build_turf_select(&$form_state) {
  $fv_multi = $form_state['voterdb']['turf-select'];
  $fv_saved = $form_state['voterdb']['saved'];
  $fv_selected = $form_state['voterdb']['saved'];
  $fv_selected_mcid = $fv_selected['nl'];
  $fv_selected['turf'] = isset($form_state['values']['turf-select'])? $form_state['values']['turf-select']:$fv_saved['turf'];
  $fv_selected['cycle'] = isset($form_state['values']['cycle'])? $form_state['values']['cycle']:$fv_saved['cycle'];
  $form_state['voterdb']['selected'] = $fv_selected;
  $fv_turfs = $fv_multi[$fv_selected_mcid];
  $fv_tindexes = array();
  $fv_text = array(); 
  foreach ($fv_turfs as $fv_ti => $fv_txt) {
    $fv_tindexes[$fv_ti] = $fv_ti;
    $fv_text[$fv_ti] = $fv_txt;
  }
  $form_state['voterdb']['turfs']['indexes'] = $fv_tindexes;
  $form_state['voterdb']['turfs']['text'] = $fv_text;
  if($fv_selected['turf'] == 0) {
    reset($fv_tindexes);
    $fv_selected['turf'] = key($fv_tindexes);
    if(isset($form_state['complete form']['turf-change'])) {
      $form_state['values']['turf'] = $fv_selected['turf'];
      $form_state['input']['turf'] = $fv_selected['turf'];
      $form_state['complete form']['turf-change']['turf']['#input'] = 1;
      $form_state['complete form']['turf-change']['turf']['#value'] = $fv_selected['turf'];
      $form_state['complete form']['turf-change']['turf']['#default_value'] = $fv_selected['turf'];
    }
  }
  $fv_turf_region[NH_HD] = $fv_selected['hd'];
  $fv_turf_region[NH_PCT] = $fv_selected['pct'];
  $fv_selected_tindex =   $fv_selected['turf'];
  //$fv_turf_region['text'] = $fv_text[$fv_selected_tindex];
  $fv_one_turf = array();
  $fv_one_turf['nl'][$fv_selected_mcid][$fv_selected_tindex] = $fv_turf_region;
  $form_state['voterdb']['tnames'][$fv_selected_mcid] = $fv_text[$fv_selected_tindex];
  $fv_voter_lists = voterdb_get_voter_list($fv_one_turf,$fv_selected_mcid);
  $fv_reports = voterdb_get_reports($fv_voter_lists);
  $form_state['voterdb']['reports'] = $fv_reports;
  $fv_cycles = voterdb_get_cycle($fv_reports);
  $form_state['voterdb']['cycles'] = $fv_cycles;
  $form_element['turf-change'] = array(
    '#title' => 'Select the turf for the list of reports',
    '#prefix' => " \n".'<div id="turf-change-wrapper"  style="width:400px;" >'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
   );
  $form_element['turf-change']['turf-select'] = array(
      '#type' => 'radios',
      '#title' => t('Select a House District'),
      '#options' => $fv_text,
      '#default_value' => $fv_selected['turf'],
      '#ajax' => array (
          'callback' => 'voterdb_turf_selected_callback',
          'wrapper' => 'turf-change-wrapper',
      )
  );
  // Get the desired cycle.
  $form_element['turf-change']['cycle'] = array(
      '#type' => 'select',
      '#title' => t('Select an election cycle.'),
      '#options' => $fv_cycles,
      '#default_value' => $fv_selected['cycle'],
      '#suffix' => '</div>',
  );
  // And, a submit button.
  $form_element['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Display reports for the selected turf. >>',
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_selected_callback
 * 
 * AJAX callback for the selection of an a turf.
 *
 * @return array
 */
function voterdb_turf_selected_callback ($form,$form_state) {
  // Rebuild the form to list the NLs in the precinct after the precinct is
  // selected.
  return $form['turf-change'];
}
