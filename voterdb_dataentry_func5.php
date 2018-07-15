<?php
/*
 * Name: voterdb_dataentry_func5.php     V4.2  7/11/18
 *
 */

//use Drupal\voterdb\NlpCanvassResponse;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpActivistCodes;

//define('RD_CC','0');  // Current election cyle index.
//define('RD_HC','1');  // Index for historical election cycles.

/** * * * * * functions supported * * * * * *
 * voterdb_fetch_voters, 
 * voterdb_vtextbox_callback, voterdb_vselect_callback,
 * voterdb_dselect_callback, voterdb_checkbox_callback, 
 * voterdb_process_voter_info, voterdb_report_track
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_fetch_voters
 * 
 * Build the array of voter information for the voters in this NL's turf.  
 * Also, get the precinct, HD and CD numbers for this turf.  These numbers
 * should all be the same but with shifting registration, one or more might
 * change.  So, look for at least three precincts that are the same and assume 
 * these are the numbers.
 * 
 * @param type $form_state
 * @return array of voter information for this turf or FALSE if error.
 */
function voterdb_fetch_voters(&$form_state) {
  $fv_mcid = $form_state['voterdb']['mcid'];
  $fv_turf_index = $form_state['voterdb']['turfIndex'];
  //$fv_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  $fv_county = $form_state['voterdb']['county'];
  // Get the voter info for all voters in this turf, use turf walksheet order.
  //voterdb_debug_msg('fetch voters', '');
  $nlpReportsObj = new NlpReports();
  //voterdb_debug_msg('report obj', $nlpReportsObj);
  
  db_set_active('nlp_voterdb');
  try {
    $fv_query = db_select(DB_NLPVOTER_TBL, 'v');
    $fv_query->join(DB_NLPVOTER_GRP_TBL, 'g', 'g.'.NV_VANID.' = v.'.VN_VANID );
    $fv_query->fields('v');
    $fv_query->condition(NV_MCID,$fv_mcid);
    $fv_query->condition(NV_NLTURFINDEX,$fv_turf_index);
    $fv_query->condition('g.'.NV_COUNTY,$fv_county);
    $fv_query->orderBy(VN_STREETNAME);
    $fv_query->orderBy(VN_STREETTYPE);
    $fv_query->orderBy(VN_STREETNO);
    $fv_query->orderBy(VN_APTTYPE);
    $fv_query->orderBy(VN_APTNO);
    $fv_query->orderBy(VN_LASTNAME);
    $fv_query->orderBy(VN_FIRSTNAME);
    $fv_result = $fv_query->execute();
    $fv_tvoters = $fv_result->fetchAll(PDO::FETCH_ASSOC);
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  db_set_active('default');
  $fv_brdates = voterdb_get_brdates();  // matchback dates.
  //voterdb_debug_msg('brdates', $fv_brdates);
  // Now build the array from the records.
  $fv_voters = array();
  $fv_vcnt = $fv_pcnt = 0;
  $fv_hd = $fv_pct = $fv_cd = NULL;
  foreach ($fv_tvoters as $fv_voter_info) {
    $fv_vanid = $fv_voter_info[VN_VANID];
    $fv_voter_status = voterdb_get_voter_status($fv_vanid, $fv_voter_info[VN_DORCURRENT]);  // func4.
    //voterdb_debug_msg('voterstatus', $fv_voter_status);
    // Check if the NL reported that this voter has moved, is deceased or is hostile.
    $fv_voter_info['status'] = $fv_voter_status; 
    $fv_date = ''; // Hasn't voted yet.
    $fv_mbindex = voterdb_voted($fv_vanid);  // func 4.
    if ($fv_mbindex) {  // Voted!
      // Display a gold star and the ballot retured date.
      $fv_module_path = drupal_get_path('module','voterdb');
      $fv_star = '<img alt="" src="'.$fv_module_path.'/voterdb_star.png" /></br>';
      $fv_date = $fv_star.$fv_brdates[$fv_mbindex];
    }
    $fv_voter_info['status']['voted'] = $fv_date; 
    //voterdb_debug_msg('date', $fv_date);
    //Search for existing reports on this voter.
    $fv_rresult = $nlpReportsObj->getNlpReports($fv_vanid);
    //voterdb_debug_msg('results', $fv_rresult);
    $fv_acresult = $nlpReportsObj->getNlpAcReport($fv_vanid);
    //voterdb_debug_msg('ac report '.$fv_vanid, $fv_acresult);
    
    $fv_display = $nlpReportsObj->displayNlReports($fv_rresult);
    //voterdb_debug_msg('report display', $fv_display);
    // Remember each report string for display later.
    $fv_voter_info['current']= $fv_display['current'];
    $fv_voter_info['historic'] = $fv_display['historic'];
    $fv_voter_info['activist'] = $fv_acresult;
    
    // Set the default candidate name for the ID report.
    $fv_voter_info['qid'] = 0;
    $fv_voter_info['ID'] = 0;
    

    // Save the first precinct and HD unless it is different from the second
    // and we have seen at least three that are the same.
    if($fv_pct==NULL) {
      $fv_hdc = $fv_voter_info[VN_HD];
      $fv_pctc = $fv_voter_info[VN_PCT];
      $fv_cdc = $fv_voter_info[VN_CD];
      if($fv_pcnt == 0) {
        $fv_hd = $fv_hdc;
        $fv_pct = $fv_pctc;
        $fv_cd = $fv_cdc;
      } elseif ($fv_pcnt<3 AND $fv_pct!=$fv_pctc) {
        $fv_hd = $fv_hdc;
        $fv_pct = $fv_pctc;
        $fv_cd = $fv_cdc;
        $fv_pcnt = 0;
      }
    }
    $fv_pcnt++;
    $fv_voters[$fv_vanid] = $fv_voter_info;
    $fv_vcnt++;
  } 
  // Save the turf HD and Precinct for the list of candidates to display.
  $form_state['voterdb']['turf-hd'] = $fv_hd;
  $form_state['voterdb']['turf-pct'] = $fv_pct;
  $form_state['voterdb']['turf-cd'] = $fv_cd;
  return $fv_voters;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_vtextbox_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_vtextbox_callback($form, &$form_state) {
  return $form['voters']['vform'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_vselect_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_vselect_callback($form, &$form_state) {
  return $form['voters']['vform'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_dselect_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_dselect_callback($form, &$form_state) {
  return $form['canvass_date'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_checkbox_callback
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_checkbox_callback($form, &$form_state) {
  //voterdb_debug_msg('form - voters - vform', array_keys($form['voters']['vform']['cell-0-2']['ND-73920']));
  //voterdb_debug_msg('form - voters - vform - checked', array_keys($form['voters']['vform']['cell-0-2']['ND-73920']['#checked']));
  //voterdb_debug_msg('form state - complete form', $form_state['complete form']['voters']['vform']['cell-0-2']['ND-73920']['#checked']);
  //voterdb_debug_msg('form state - complete form', array_keys($form_state['complete form']['voters']['vform']['cell-0-2']['ND-73920']));
  //voterdb_debug_msg('form state triggering element', $form_state['triggering_element']);
  $pv_cell = $form_state['triggering_element']['#array_parents'][2];
  //voterdb_debug_msg('cell: '.$pv_cell,'');
  $pv_element_clicked = $form_state['triggering_element']['#name'];
  //voterdb_debug_msg('clicked: '.$pv_element_clicked,'');
  
  $pv_value = strip_tags(filter_var($form_state['triggering_element']['#value'], FILTER_SANITIZE_STRING));
  //voterdb_debug_msg('value: '.$pv_value,'');
  
  $form_state['complete form']['voters']['vform'][$pv_cell][$pv_element_clicked]['#checked'] = $pv_value;
  $form['voters']['vform'][$pv_cell][$pv_element_clicked]['#checked'] = $pv_value;
  return $form['voters']['vform'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_process_voter_info
 *
 * The NL has submitted some canvas results.   Pick up the status and notes
 * and record it.
 *
 * @param type $form_state
 * @return boolean
 */
function voterdb_process_voter_info(&$form_state) {
  //voterdb_debug_msg('input', $form_state['input']);
  //voterdb_debug_msg('values', $form_state['values']);
  //voterdb_debug_msg('trigger', $form_state['triggering_element']);
  
  //$nlpReportsObj = new NlpCanvassResponse();
  $nlpReportsObj = new NlpReports();
  
  $pv_result['county'] = $form_state['voterdb']['county'];
  $pv_result['mcid'] = $form_state['voterdb']['mcid'];
  $pv_result['firstName'] = $form_state['voterdb']['fname'];
  $pv_result['lastName'] = $form_state['voterdb']['lname'];
  $pv_result['cycle'] = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  $pv_result['qid'] = NULL;
  $pv_result['rid'] = NULL;

  $pv_voters = $form_state['voterdb']['voters'];
  $pv_rday = $form_state['voterdb']['date-day']+1;
  $pv_day = str_pad($pv_rday, 2, "0", STR_PAD_LEFT);
  $pv_rmonth = $form_state['voterdb']['date-month']+1;
  $pv_month = str_pad($pv_rmonth, 2, "0", STR_PAD_LEFT);
  $pv_year = format_date(time(), 'custom', 'Y');
  $pv_date = $pv_year.'-'.$pv_month.'-'.$pv_day;
  $pv_result['date'] = $pv_date;
  // Determine which element triggered the AJAX event.
  $pv_element_clicked = $form_state['triggering_element']['#name'];
  $pv_value = strip_tags(filter_var($form_state['triggering_element']['#value'], FILTER_SANITIZE_STRING));
  //voterdb_debug_msg('elementclicked', $pv_element_clicked);
  
  //$pv_types = unserialize(DE_TYPE_ARRAY); // Names of fields.
  $pv_id_array = explode('-', $pv_element_clicked);
  $pv_vanid = $pv_id_array[1];  // VANID of affected NL (or zero).
  $pv_result['vanid'] = $pv_vanid;
  // Which element triggered the event.
  switch ($pv_id_array[0]) {
    // Process a comment.
    case 'CM':   
      if ($pv_value != '') {  // a comment was entered.
        if (strlen($pv_value) > NC_COMMENT_MAX) {
          drupal_set_message('Comment is limited to '.NC_COMMENT_MAX.' characters', 'warning');
          $pv_tvalue = substr($pv_value,0,NC_COMMENT_MAX);  // Truncate the comment.
        } else {
          $pv_tvalue = $pv_value;
        }
        // Replace comma's to permit export by phpMyAdmin.
        $pString = str_replace(',', ';', $pv_tvalue);
        // Remove control characters.
        for($control = 0; $control < 32; $control++) {
          $pString = str_replace(chr($control), "", $pString);
        }
        $pv_text_string = str_replace(chr(127), "", $pString);
        $pv_result['type'] = 'Comment';
        $pv_result['value'] = '';
        $pv_result['text'] = $pv_text_string;
        $nlpReportsObj->setNlReport($pv_result);
        
        voterdb_report_track($pv_result);
        
        $form_state['voterdb']['voters'][$pv_vanid]['current']['Comment'] =
                $nlpReportsObj->displayNewNlReport($form_state['voterdb']['voters'][$pv_vanid]['current']['Comment'],
                $pv_tvalue,$pv_date);
        
        //voterdb_addto_string($pv_tvalue,$pv_date,$form_state['voterdb']['voters'][$pv_vanid]['current'] ['Comment'],DE_RED);
        $form_state['values'][$pv_element_clicked] = '';  // Clear entry.
        $form_state['input'][$pv_element_clicked] = '';
      }
      break;
    // Candidate Name.  
    case 'CN':   
      if (!empty($pv_value)) {  // a candidate name was entered.
        // Remember the name to link with ID value later.
        $form_state['voterdb']['voters'][$pv_vanid]['qid']=$pv_value;
        $form_state['voterdb']['voters'][$pv_vanid]['elementclicked'] = $pv_element_clicked;
      }
      break;
    // An ID report.
    case 'ID':   
      if(!empty($pv_value)) { // The NL changed an ID cell.
        $pv_qid = $pv_voters[$pv_vanid]['qid'];

        $pv_candidates = $form_state['voterdb']['candidates'];
        $pv_candidate_name = $pv_candidates[$pv_qid];
        
        $responseList = $form_state['voterdb']['ID'][$pv_qid];
        $pv_rid = $pv_value;
        
        
        $pv_result['type'] = 'ID';
        $pv_result['value'] = $responseList[$pv_rid];
        $pv_result['text'] = $pv_candidate_name;
        $pv_result['qid'] = $pv_qid;
        $pv_result['rid'] = $pv_rid;
        
        $nlpReportsObj->setNlReport($pv_result);
        
        voterdb_report_track($pv_result);
        $pv_id = $pv_candidate_name.'[' .$responseList[$pv_rid].']';
        
        $form_state['voterdb']['voters'][$pv_vanid]['current']['ID'] =
                $nlpReportsObj->displayNewNlReport($form_state['voterdb']['voters'][$pv_vanid]['current']['ID'],
                $pv_id,$pv_date);
        //voterdb_addto_string($pv_id,$pv_date,$form_state['voterdb']['voters'][$pv_vanid]['current']['ID'],DE_RED);
        $form_state['voterdb']['voters'][$pv_vanid]['ID'] = 0;
        $form_state['voterdb']['voters'][$pv_vanid]['candidate'] = 0;
        $pv_saved_click = $form_state['voterdb']['voters'][$pv_vanid]['elementclicked'];
        $form_state['values'][$pv_element_clicked] = 0;
        $form_state['input'][$pv_element_clicked] = 0;
        $form_state['values'][$pv_saved_click] = 0;
        $form_state['input'][$pv_saved_click] = 0;
        $form_state['voterdb']['voters'][$pv_vanid]['qid'] = 0;
      }
      break;
      
      
      
    // Contact report.
    case 'CO':  
      $pv_sticky = array('Moved','Deceased','Hostile');
      if(!empty($pv_value)) {  // A contact selection.
        // Get the text associated with the new contact selection.
        
        $canvassResponseList = $form_state['voterdb']['canvassResponseList'];
        $rid = $pv_value;
        $pv_result['rid'] = $rid;
        
        //voterdb_debug_msg('response list '.$rid, $canvassResponseList);
        //voterdb_debug_msg('rid '.$rid,'');
        
        $pv_contact_name = $canvassResponseList[$rid];
        $pv_result['type'] = 'Contact';
        $pv_result['value'] = $pv_contact_name;
        $pv_result['text'] = '';
        if(in_array($pv_contact_name,$pv_sticky)) {
        //if(isset($pv_sticky[$pv_value])) {
          $pv_result['text'] = VN_DORCURRENT.':'.$pv_voters[$pv_vanid][VN_DORCURRENT].';';
          voterdb_set_voter_status($pv_vanid, $pv_voters[$pv_vanid][VN_DORCURRENT],$pv_contact_name);// func4.
        }
        
        $nlpReportsObj->setNlReport($pv_result);
        
        voterdb_report_track($pv_result);
        
        $form_state['voterdb']['voters'][$pv_vanid]['current']['Contact'] =
                $nlpReportsObj->displayNewNlReport($form_state['voterdb']['voters'][$pv_vanid]['current']['Contact'],
                $pv_contact_name,$pv_date);
        
        //voterdb_addto_string($pv_contact_name,$pv_date,$form_state['voterdb']['voters'][$pv_vanid]['current']['Contact'],DE_RED);
        $form_state['values'][$pv_element_clicked] = '';  // Clear entry.
        $form_state['input'][$pv_element_clicked] = '';
      }
      break;
      
      
      // Not a Dem.
    case 'ND':  
      //voterdb_debug_msg('input', $form_state['input']);
      //voterdb_debug_msg('values', $form_state['values']);
      // Get the text associated with the new contact selection.
      //voterdb_debug_msg('form state', $form_state);
      
      //voterdb_debug_msg('activist',$pv_voters[$pv_vanid]['activist']);
      $pv_result['rindex'] = 0; 
      if(!empty($pv_voters[$pv_vanid]['activist']['NotADem']['Rindex'])) {
        $pv_result['rindex'] = $pv_voters[$pv_vanid]['activist']['NotADem']['Rindex'];
      }
      
      $notADemAC = $form_state['voterdb']['notADemAC'];
      $pv_result['rid'] = $notADemAC['activistCodeId'];
      
      $pv_result['type'] = 'Activist';
      $pv_result['value'] = $pv_value;
      $pv_result['text'] = 'NotADem';
      $nlpReportsObj->setNlAcReport($pv_result);

      $form_state['voterdb']['voters'][$pv_vanid]['activist']['NotADem']['value'] = $pv_value;
      //voterdb_debug_msg('voter',$form_state['voterdb']['voters'][$pv_vanid]);
      voterdb_report_track($pv_result);

      //$form_state['values'][$pv_element_clicked] = '';  // Clear entry.
      //$form_state['input'][$pv_element_clicked] = '';
      //voterdb_debug_msg('input', $form_state['input']);
      
      //$form_state['complete form']['voters']['vform']['cell-0-2']['ND-73920']['#checked'] = 0;
      //$form['voters']['vform']['cell-0-2']['ND-73920']['#checked'] = 0;
      
      $pv_cell = $form_state['triggering_element']['#array_parents'][2];
      $form_state['complete form']['voters']['vform'][$pv_cell][$pv_element_clicked]['#checked'] = $pv_value;
      //$form['voters']['vform']['cell-0-2'][$pv_element_clicked]['#checked'] = 0;
      //voterdb_debug_msg('cell',$pv_cell);
      
      //voterdb_debug_msg('form checkbox', $form[$pv_element_clicked]);
      //$form['checkboxes_fieldset']['checkboxes'][$option]['#checked'] = FALSE;

      break;
      
      // Survey Question.
      case 'SQ':  
      if(!empty($pv_value)) {  // A survey selection.
        // Get the text associated with the survey question response.
        $rid = $pv_value;
        $pv_types_txt = $form_state['voterdb']['surveyResponseList'][$rid];
        $pv_text = $form_state['voterdb']['surveyTitle'];
        $pv_result['type'] = 'Survey';
        $pv_result['value'] = $pv_types_txt;
        $pv_result['text'] = $pv_text;
        $pv_display_txt = $pv_text.': '.$pv_types_txt;
        
        $pv_result['qid'] = $form_state['voterdb']['surveyQid'];
        $pv_result['rid'] = $rid;
        
        $nlpReportsObj->setNlReport($pv_result);
        
        voterdb_report_track($pv_result);
        
        
        $form_state['voterdb']['voters'][$pv_vanid]['current']['Survey'] =
                $nlpReportsObj->displayNewNlReport($form_state['voterdb']['voters'][$pv_vanid]['current']['Survey'],
                $pv_display_txt,$pv_date);
        //voterdb_addto_string($pv_display_txt,$pv_date,$form_state['voterdb']['voters'][$pv_vanid]['current']['Survey'],DE_RED);
        // Check if the NL reported the voter has moved.
        $form_state['values'][$pv_element_clicked] = '';  // Clear entry.
        $form_state['input'][$pv_element_clicked] = '';
      }
      //voterdb_debug_msg('input', $form_state['input']);
      break;   
    // The Month was changed.
    case 'MO': 
      if ($form_state['voterdb']['date-month'] != $pv_value) {
        $form_state['voterdb']['date-month'] = $pv_value;
      }
      break;
    // The day was changed.
    case 'DY': 
      if ($form_state['voterdb']['date-day'] != $pv_value) {
        $form_state['voterdb']['date-day'] = $pv_value;
      }
      break;
    // History option changed.
    case 'HI': 
      $form_state['voterdb']['history'] = $pv_value;
      unset($form_state['voterdb']['voters']);
      break; 
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_report_track
 * 
 * Write an NL report of a voter to the report database.
 * 
 * @param type $ir_result
 */
function voterdb_report_track($ir_result) {
  voterdb_results_reported($ir_result['mcid'],$ir_result['county']); // func4.
  $ir_info = $ir_result['firstName'].' '.$ir_result['lastName'].
          '['.$ir_result['mcid'].']-VANID['.$ir_result['vanid'].'] '.$ir_result['type'];
  voterdb_login_tracking('results',$ir_result['county'],'reported',$ir_info);
  return TRUE;
}