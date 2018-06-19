<?php
/*
 * Name:  voterdb_candidates_func.php               V4.1 4/23/18
 */

/** * * * * * functions supported * * * * * *
 * voterdb_build_hd_entry, voterdb_build_cd_entry, voterdb_pct_entry, 
 * voterdb_cscope_callback, voterdb_build_county_entry, voterdb_build_cscope,
 * voterdb_get_candidates, voterdb_display_candidates, 
 * voterdb_build_candidate_list
 */

define('CU_EDIT', '50');
define('CU_DELETE', '50');
define('CU_WEIGHT', '50');
define('CU_NAME', '150');
define('CU_SCOPE', '50');
define('CU_CD', '20');
define('CU_COUNTY', '50');
define('CU_HD', '20');
define('CU_PCTS', '150');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_list
 * 
 * Construct the form entry for the HD.
 * 
 * @param type $form
 * @param type $form_state
 * @return 
 */
function voterdb_build_hd_entry(&$form,$form_state) {
  $he_hd_array = $form_state['voterdb']['hd_array'];
  $form['scope']['hd'] = array(
    '#type' => 'select',
    '#title' => t('Select the HD of the candidate.'),
    '#options' => array($he_hd_array),
    '#required' => TRUE,
    );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cd_list
 * 
 * Construct the form entry for the CD.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */

function voterdb_build_cd_entry(&$form,$form_state) {
  $he_cd_array = array('','1','2','3','4','5');
  $form['scope']['cd'] = array(
    '#type' => 'select',
    '#title' => t('Select the CD of the candidate.'),
    '#options' => array($he_cd_array),
    '#required' => TRUE,
    );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_entry
 * 
 * Construct the form entry for the list of precincts.
 * 
 * @param type $form_state
 * @return 
 */
function voterdb_pct_entry(&$form) {
  $form['scope']['pct'] = array (
    '#title' => 'Enter a list of precincts, separated by commas',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  return ;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cscope_callback
 * 
 * AJAX call back for the selection of the HD
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_cscope_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is
  // selected.
  return $form['scope'];
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_county_entry
 * 
 * Construct the form entry for the county name.  The entry is a selection of
 * the known counties.   Also, the list of county names is saved for recovery
 * when the selection is made.
 * 
 * @param type $form
 * @return type
 */
function voterdb_build_county_entry(&$form,&$form_state) {
  $ce_county_array = voterdb_get_county_names();
  foreach ($ce_county_array as $ce_county) {
    //$ce_hd_array = unserialize($ce_hd_string);
    $ce_counties[] = $ce_county;
  }
  $form_state['voterdb']['county-names'] = $ce_counties;
  $form['scope']['county'] = array(
    '#type' => 'select',
    '#title' => t('Select the County'),
    '#options' => array($ce_counties),
    '#required' => TRUE,
    );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_cscope
 * 
 * Construct the selection of the scope for the candidates campaign.  The
 * allowable scope is restricted to county, HD or precinct.  If the Option=ALL
 * modifier is used for the call, then the state and CD scope are permitted.
 * When the scope is selected, then the other fields are displayed.  The 
 * specific fields are dependent on the scope and the ALL option.
 * 
 * @param type $form
 * @param array $form_state
 * @return type
 */
function voterdb_build_cscope(&$form,&$form_state) {
  $fv_scope = $form_state['voterdb']['scope'];
  $fv_options = array('Select scope','Group of Precincts','House District','County');
  // Expand the allowed scope if the ALL option was specified.
  if ($form_state['voterdb']['ALL']) {
    $fv_options[4] = 'Congressional District';
    $fv_options[5] = 'State';
  }
  $form_state['voterdb']['scope-options'] = $fv_options;
  $form['scope-select'] = array(
    '#type' => 'select',
    '#title' => t('Select the scope.'),
    '#options' => $fv_options,
    '#ajax' => array (
        'callback' => 'voterdb_cscope_callback',
        'wrapper' => 'scope-wrapper',
        )
    );
  //Build a wrapper around the part that will change with input.
  $form['scope'] = array(
    '#prefix' => '<div id="scope-wrapper">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image: none; border: 0px; width: 550px; padding:0px; margin:0px; background-color: rgb(255,255,255);'), ),
   );
  
  // If the scope has been entered, ask for the candidate name.
  if($fv_scope=='unknown') {return;}
  $form['scope']['weight'] = array (
    '#title' => 'Enter the weight for ordering the display of names',
    '#size' => 2,
    '#maxlength' => 2,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  
  $availableCandidates = $form_state['voterdb']['availableCandidates'];
  if(!$availableCandidates) {
    
     // Submit button
    $form['scope']['submit-none'] = array(
      '#type' => 'submit',
      '#value' => 'There are no available candidates to add >>'
    );
    return;
  }
  
  foreach ($availableCandidates as $qid=>$availableCandidate) {
    $candidateList[$qid] = $availableCandidate['name'];
  }
  
  
  
  $form['scope']['qid'] = array(
    '#type' => 'radios',
    '#title' => 'Enter the name of the candidate',
    '#options' => $candidateList,
    '#description' => t('Select one of the candidate survey questions known to the API.'),
  );

  //voterdb_debug_msg('scope '.$fv_scope, '');
  // And, ask for the district.
  switch ($fv_scope) {
    case 'Group of Precincts':
      if ($form_state['voterdb']['ALL']) {
         voterdb_build_county_entry($form,$form_state);
      }
      voterdb_pct_entry($form);
      break;
    case 'House District':
      voterdb_build_hd_entry($form,$form_state);
      if ($form_state['voterdb']['ALL']) {
         voterdb_build_county_entry($form,$form_state);
      }
      break;
    case 'County':
      if ($form_state['voterdb']['ALL']) {
         voterdb_build_county_entry($form,$form_state);
      }
      break;
    case 'Congressional District':
      if ($form_state['voterdb']['ALL']) {
         voterdb_build_cd_entry($form);
      }
      break;   
  }
   // Submit button
  $form['scope']['submit-co'] = array(
    '#type' => 'submit',
    '#value' => 'Add this Candidate >>'
  );
  return;
} 

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_candidates
 * 
 * Get the candidates to be displayed for a specific scope.  And, only show 
 * those that are relevant to a specific county unless the ALL option is
 * specified.
 * 
 * @param type $gc_all
 * @param type $gc_scope
 * @return boolean
 */
function voterdb_get_candidates($gc_scope,$gc_all,$gc_county) {
  db_set_active('nlp_voterdb');
  $gc_query = db_select(DB_CANDIDATES_TBL, 'c');
  $gc_query->fields('c');
 
  switch ($gc_scope) {
    case 'State':
      $gc_query->condition(CD_SCOPE,'State');
      break;
    case 'CD':
      $gc_query->condition(CD_SCOPE,'CD');
      $gc_query->orderBy(CD_CD);
      break;
    case 'County':
      $gc_query->condition(CD_SCOPE,'County');
      if(!$gc_all) {
        $gc_query->condition(CD_COUNTY,$gc_county);
      } else {
        $gc_query->orderBy(CD_COUNTY);
      }
      break;
    case 'HD':
      $gc_query->condition(CD_SCOPE,'HD');
      if(!$gc_all) {
        $gc_query->condition(CD_COUNTY,$gc_county);
      } else {
        $gc_query->orderBy(CD_COUNTY);
      }
      $gc_query->orderBy(CD_HD);
      break;
    case 'Pcts':
      $gc_query->condition(CD_SCOPE,'Pcts');
      if(!$gc_all) {
        $gc_query->condition(CD_COUNTY,$gc_county);
      } else {
        $gc_query->orderBy(CD_COUNTY);
      }
      break;
  }
  $gc_query->orderBy(CD_WEIGHT);
  try {
  $gc_result = $gc_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return FALSE;
  }
  db_set_active('default');
  $gc_candidates = $gc_result->fetchAll(PDO::FETCH_ASSOC);
  return $gc_candidates;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_display_candidates
 * 
 * @param type $cat
 * @param type $candidateList
 * @param type $all
 * @return type form elements
 */
function voterdb_display_candidates($cat,$candidateList,$all) {

  $form_element[$cat] = array(
    '#title' => $cat.' candidates',
    '#prefix' => " \n".'<!-- '.$cat.' Candidate List Table -->'." \n".'<div id="'.$cat.'-div">'." \n",
    '#suffix' => " \n".'</div>'." \n".'<!-- End of Candidate List Table -->'." \n",
    '#type' => 'fieldset',
  );
  // Check if we have any candidates to display.
  if(empty($candidateList)) {
    $form_element[$cat]['note_'.$cat] = array (
    '#type' => 'markup',
    '#markup' => " \n "."There are no ".$cat." candidates assigned as yet.",
    );
    return;
  }
  // Start the table.
  $tableLen = CU_EDIT+CU_DELETE+CU_WEIGHT+CU_NAME+CU_SCOPE+CU_CD+CU_COUNTY+ CU_HD+CU_PCTS+17;

  $form_element[$cat]['table_start'] = array(
    '#prefix' => " \n".'<style type="text/css"> textarea { resize: none;} </style>',
    '#markup' => " \n"." \n".'<table border="1" style="font-size:x-small; padding:0px; '
    . 'border-color:#d3e7f4; border-width:1px; width:'.$tableLen.'px;">',
  );  
  // Create the header.
  $headerRow = " \n ".'<th style="width:'.CU_EDIT.'px;"></th>';
  $headerRow .= " \n ".'<th style="width:'.CU_DELETE.'px;"></th>';
  $headerRow .= " \n ".'<th style="width:'.CU_WEIGHT.'px;">Weight</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_NAME.'px;">Name</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_SCOPE.'px;">Scope</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_CD.'px;">CD</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_COUNTY.'px;">County</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_HD.'px;">HD</th>';
  $headerRow .= " \n ".'<th style="width:'.CU_PCTS.'px;">Precincts</th>';
  $form_element[$cat]['header_row'] = array(
    '#markup' => " \n".'<thead>'.
    " \n".'<tr>'.$headerRow." \n".'</tr>'." \n".'</thead>',
  );
  // Start the body.
  $form_element[$cat]['body-start'] = array(
    '#markup' => " \n".'<tbody>',
  );
  $odd = TRUE;
  foreach ($candidateList as $candidate) {
    $qid = $candidate['Qid'];
    $weight = $candidate['Weight'];
    $name = $candidate['Name'];
    $scope = $candidate['Scope'];
    $county = $candidate['County'];
    $cd = $candidate['CD'];
    $hd = $candidate['HD'];
    $pctList = $candidate['Pcts'];
    $class = ($odd)?'<tr class="odd">':'<tr class="even">';
    $odd = !$odd;
    // Use the Drupal class for odd/even table rows and start the row.
    $form_element[$cat]["row-start-$qid"] = array(
      '#markup' => " \n".$class.'<!-- '.$name.' row -->',
      );
    $dc_editable = TRUE;
    if($cat=='State' OR $cat=='Congressional') {
      if (!$all) {$dc_editable = FALSE;}
    }
    if($dc_editable) {
      // First cell is the edit link.
      $form_element[$cat]["submit-$qid-0"] = array(
        '#name' => "submit-$qid-0",
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#type' => 'submit',
        '#value' => 'edit',
      );

      // Second cell is the delete link.
      $form_element[$cat]["submit-$qid-1"] = array(
        '#name' => "submit-$qid-1",
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#type' => 'submit',
        '#value' => 'delete'
      );
    } else {
      $form_element[$cat]["submit-$qid-0"] = array(
        '#name' => "ed-$qid-0",
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#markup' => 'N/A',
        '#value' => 'edit',
      );

      // Second cell is the delete link.
      $form_element[$cat]["submit-$qid-1"] = array(
        '#name' => "de-$qid-1",
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#markup' => 'N/A',
        '#value' => 'delete'
      );
    }
    // Third cell is the weight.
    $form_element[$cat]["cell-$qid-2"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$weight.'</td>',
      );
    // Fourth cell is the candidates's name.
    $form_element[$cat]["cell-$qid-3"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$name.'</td>',
      );
    // Fifth cell is the scope.
    $form_element[$cat]["cell-$qid-4"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$scope.'</td>',
      );
    // Sixth cell is the CD.
    $form_element[$cat]["cell-$qid-5"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cd.'</td>',
      );
    // Seventh cell is the county.
    $form_element[$cat]["cell-$qid-6"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$county.'</td>',
      );
    // Eighth cell is the house district.
    $form_element[$cat]["cell-$qid-7"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$hd.'</td>',
      );
    // Nineth cell is the precinct list.
    $form_element[$cat]["cell-$qid-8"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$pctList.'</td>',
      );
    // End the row.
    $form_element[$cat]["row-end-$qid"] = array(
      '#markup' => " \n".'</tr>',
      );
  }
  // End of the table.
  $form_element[$cat]['table_end'] = array(
    '#markup' => " \n".'</tbody>'." \n".'</table>'." \n",
    );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_candidate_list
 * 
 * Build an ordered list of all the defined candidates.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_build_candidate_list($candidatesArray) { 
  $scopeArray['State'] = $scopeArray['CD'] = $scopeArray['County'] = 
       $scopeArray['HD'] = $scopeArray['Precinct'] = NULL;
  if(empty($candidatesArray)) {return $scopeArray;}
  foreach ($candidatesArray as $qid=>$candidateArray) {
    $scope = $candidateArray['Scope'];
    $scopeArray[$scope][$qid] = $candidateArray;
  }
  return $scopeArray;
}