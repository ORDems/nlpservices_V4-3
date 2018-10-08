<?php
/*
 * Name: voterdb_dataentry_func2.php     V4.3  9/12/18
 *
 */

/** * * * * * functions supported * * * * * *
 * voterdb_addto_string, voterdb_build_call_list, 
 * voterdb_build_voter_tbl
 */


use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpSurveyResponse;
use Drupal\voterdb\NlpCandidates;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\NlpActivistCodes;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpPaths;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_addto_string
 * 
 * Simple function to reduce lines of code.  It constructs an HTML string
 * with styling.
 * 
 * @param type $as_value
 * @param type $as_date
 * @param type $as_string
 * @param type $as_style
 */
function voterdb_addto_string($as_value,$as_date,&$as_string, $as_style) {
  if($as_value == '') {return;}  
  $as_string .= ($as_string != '')?'<br/>':'';
  $as_new = $as_date.': '.$as_value;
  if ($as_style != '') {
    $as_new = '<span style="'.$as_style.'">'.$as_new.'</span>';
  }
  $as_string .= $as_new;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_call_list
 * 
 * Build a file with the names and phone numbers for the voters.   The voters
 * with ballots turned in are not marked with the "call" status.  
 * 
 * @param type $form_state - array of voters.
 * @return string - URI of file with the call list.
 */
function voterdb_build_call_list(&$form_state) {
  $cl_voters = $form_state['voterdb']['voters'];
  $cl_county = $form_state['voterdb']['county'];
  $cl_mcid = $form_state['voterdb']['mcid'];
  $cl_turfindex = $form_state['voterdb']['turfIndex'];       
  // Open a call sheet file.
  
  //$pathsObj = $form_state['voterdb']['pathsObj'];
  $pathsObj = new NlpPaths();
  $cl_call_path = $pathsObj->getPath('CALL',$cl_county);
  
  
  $cl_fname = 'CALL-'.$cl_mcid.'-'.$cl_turfindex;
  $cl_call_uri = $cl_call_path . $cl_fname;
  file_save_data('', $cl_call_uri, FILE_EXISTS_REPLACE);
  $cl_call_file_fh = fopen($cl_call_uri,"w");
  //$cl_types = unserialize(DE_TYPE_ARRAY);
  foreach ($cl_voters as $cl_voter_info) {
    // Write a record to the call sheet file.
    $cl_call_record[0] = $cl_voter_info[VN_FIRSTNAME]." ". $cl_voter_info[VN_LASTNAME];
    $cl_call_record[1] = "H:".$cl_voter_info[VN_HOMEPHONE];
    $cl_call_record[2] = "C:".$cl_voter_info[VN_CELLPHONE];
    $cl_call_record[3] = $cl_voter_info['current']['Contact'];
    $cl_call_record[4] = ($cl_voter_info['status']['voted']=='')?'call':'';
    $cl_call_string = implode("\t", $cl_call_record);
    $cl_call_string .= "\n";
    fwrite($cl_call_file_fh,$cl_call_string);
  }
  fclose($cl_call_file_fh);
  // Save the turf call list file name.
  
  $turfObj = new NlpTurfs();
  $turfObj->updateTurfFiles('call',$cl_fname,$cl_turfindex);

  return $cl_call_uri;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_voter_tbl
 *
 * Display the list of voters assigned to this NL and show previously
 * reported results.  An HTML table is built to display the voters in grid 
 * form for data entry.  THe changes are processed in the validate function.
 *
 * @param type $form_state
 * @return form element for display of voters or FALSE if error.
 */
define('CW_VOTER', '180');
define('CW_PHONE', '85');
define('CW_CONTACT', '150');
define('CW_SURVEY', '150');
define('CW_CANDIDATE', '150');
define('CW_COMMENT', '280');
define('CW_VOTED', '90');
define('CW_TABLE', CW_VOTER+CW_PHONE+CW_CONTACT+CW_SURVEY+CW_CANDIDATE+ CW_COMMENT+CW_VOTED+7);

function voterdb_build_voter_tbl(&$form_state) {
  // Create the DIV for the AJAX redraw of the table.
  $bv_voters = $form_state['voterdb']['voters'];
  $bv_history = $form_state['voterdb']['history'];
  //voterdb_debug_msg('history', $bv_history);
  $surveyResponseObj = new NlpSurveyResponse();
  $surveyQuestionObj = new NlpSurveyQuestion($surveyResponseObj);
  $questionArray = $surveyQuestionObj->getSurveyQuestion();
  if(!empty($questionArray)) {
    $bv_title = $questionArray['questionName'];
    $qid = $questionArray['qid'];
    $form_state['voterdb']['surveyQid'] = $qid;
    $surveyResponseList = $surveyResponseObj->getSurveyResponseList($qid);
  } else {
    $surveyResponseList = NULL;
    $bv_title = NULL;
  }
  $form_state['voterdb']['surveyResponseList'] = $surveyResponseList;
  $form_state['voterdb']['surveyTitle'] = $bv_title;
  $canvassResponsesObj = new NlpResponseCodes();
  $canvassResponseList = $canvassResponsesObj->getNlpResponseCodesList();
  $form_state['voterdb']['canvassResponseList'] = $canvassResponseList;
  $nlpActivistCodesObj = new NlpActivistCodes();
  $notADemAC = $nlpActivistCodesObj->getActivistCode('NotADem');
  $form_state['voterdb']['notADemAC'] = $notADemAC;
  //voterdb_debug_msg('notadem ', $notADemAC['current']);
  
  $form_element['vform'] = array(
    '#prefix' => " \n".'<div id="vform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(255,255,255);'), ),
  );
  // Start the table.
  $cw_table = CW_VOTER+CW_PHONE+CW_CONTACT+CW_SURVEY+CW_CANDIDATE+ CW_COMMENT+CW_VOTED+7;
  $form_element['vform']['table_start'] = array(
    '#prefix' => " \n".'<style type="text/css"> textarea { resize: none;} </style>',
    '#markup' => " \n".'<!-- Data Entry Table -->'." \n".'<table border="1" style="font-size:x-small; padding:0px; '
    . 'border-color:#d3e7f4; border-width:1px; width:'.$cw_table.'px;" class="noborder">',
  );
  // Create the header.
  $bv_header_row = " \n ".'<th style="width:'.CW_VOTER.'px; font-size:small;"><p>Voter</p></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_PHONE.'px; font-size:small;"><p>Phone #s</p></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_CONTACT.'px; color: blue; font-size:small;">'
      . '<div id="hintth"><p>Contact Attempt'
      . '<span style="position: absolute; width: 450px; left: 10px; top: -70px; ">'
          . 'Use this column to report an attempt to contact the voter where you did not have a conversation.  '
          . 'The NLP Hostile check box indicates the voter should not be in the NLP program.</span>'
      . '</p></div></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_SURVEY.'px; color: blue; font-size:small;">'
      . '<div id="hintth"><p>Personal Contact'
      . '<span style="position: absolute; width: 300px; left: 10px; top: -50px; ">'
          . 'Use this column to report personal contact and record their pledge to vote.</span>'
      . '</p></div></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_CANDIDATE.'px; color: blue; font-size:small;">'
      . '<div id="hintth"><p>Contact Detail'
      . '<span style="position: absolute; width: 300px; left: 10px; top: -70px; ">'
          . 'Optional report for additional information about a voter contact.</span>'
      . '</p></div></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_COMMENT.'px; font-size:small;"><p>Comment</p></th>';
  $bv_header_row .= " \n ".'<th style="width:'.CW_VOTED.'px; font-size:small;"><p>Voted</p></th>';
  $form_element['vform']['header_row'] = array(
    '#markup' => " \n".'<thead>'.
    " \n".'<tr>'.$bv_header_row." \n".'</tr>'." \n".'</thead>',
  );
  // Start the body.
  $form_element['vform']['body-start'] = array(
    '#markup' => " \n".'<tbody>',
  );
  
  // Loop through the voters in the turf and create a row with contact
  // information and the data entry form elements.
  
  
  $bv_vcnt = 0;
  foreach ($bv_voters as $bv_voter_info) {
    $bv_vanid = $bv_voter_info[VN_VANID];
    $bv_moved = $bv_voter_info['status'][VM_MOVED]; 
    $bv_deceased = $bv_voter_info['status'][VM_DECEASED]; 
    $bv_party = $bv_voter_info[VN_PARTY];
    //$bv_types = unserialize(DE_TYPE_ARRAY);
    // Save the VAN id for processing any entered data.
    $form_state['voterdb']['voter'][$bv_vcnt] = $bv_vanid;
    $bv_name_string = $bv_voter_info[VN_LASTNAME].", ".$bv_voter_info[VN_FIRSTNAME];
    if(!empty($bv_party)) {
      $bv_name_string .= ' ('.$bv_party.')';
    }
    // Use the Drupal class for odd/even table rows and start the row.
    $form_element['vform']["row-start-$bv_vcnt"] = array(
      '#markup' => " \n".'<tr class="odd">'.'<!-- '.$bv_name_string.' row -->',);
    // Construct the info for the voter contact cell.
    $bv_name_string .= "<br/>";
    $bv_addr_string = $bv_voter_info[VN_STREETNO]." "
            .$bv_voter_info[VN_STREETPREFIX]." ".$bv_voter_info[VN_STREETNAME]
            ." ".$bv_voter_info[VN_STREETTYPE]." ".$bv_voter_info[VN_APTTYPE]
            ." ".$bv_voter_info[VN_APTNO]."<br/>".$bv_voter_info[VN_CITY]
            ." - [".$bv_vanid."]";
    // If the voter has moved, display the info in light grey.
    $bv_grey = 'style='.DE_GREY;
    $bv_voter_col = (!$bv_moved AND !$bv_deceased)? 
            "<strong>$bv_name_string</strong> $bv_addr_string" :
            "<span ".$bv_grey.">".$bv_name_string.$bv_addr_string."</span>";
    $form_element['vform']["cell-$bv_vcnt-0"] = array(
        '#markup' => " \n ".'<td class="td-de">'.$bv_voter_col.'</td>',
        );
    // Create the phone number cell.
    $bv_phones = "H:".$bv_voter_info[VN_HOMEPHONE]."<br/>C:".$bv_voter_info[VN_CELLPHONE];
    $form_element['vform']["cell-$bv_vcnt-1"] = array(
        '#markup' => " \n ".'<td class="td-de">'.$bv_phones.'</td>',
        );
    
    // Create the cell for the voter contact result.
    
    $form_element['vform']["cell-$bv_vcnt-2"]['start'] = array(
        '#markup' => " \n ".'<td class="td-de">',
        );
    
    $form_element['vform']["cell-$bv_vcnt-2"]["CO-".$bv_vanid] = array(
      '#type' => 'select',
      '#options' => $canvassResponseList,
      //'#prefix' => " \n ".'<td class="td-de">',
      //'#suffix' => '</div>',
      //'#suffix' => '</td>',
      '#ajax' => array(
        'callback' => 'voterdb_vselect_callback',
        'wrapper' => 'vform-div',
        '#event' => 'change',
        ),
      '#default_value' => 0,
    );
    
    if(!empty($notADemAC)) {
      $bv_default = FALSE;
      //$bv_color = 'black';
      //voterdb_debug_msg('activist', $bv_voter_info['activist']);
      if(!empty($bv_voter_info['activist']['NotADem']['Value'])) {
        $bv_default = $bv_voter_info['activist']['NotADem']['Value'];
        //if($bv_default) {
        //  $bv_color = 'red';
        //}
      }
      //voterdb_debug_msg('default', $bv_default);
      $form_element['vform']["cell-$bv_vcnt-2"]["ND-".$bv_vanid] = array(
        '#type' => 'checkbox',
        '#default_value' => $bv_default,
        '#title' => $notADemAC['name'],
        //'#id' => 'cell-$bv_vcnt-2',
        //'#prefix' => " \n ".'<div><span style="color:'.$bv_color.';">',
        //'#suffix' => '</span></div></td>',
        //'#suffix' => '</td>',
        '#ajax' => array(
          'callback' => 'voterdb_checkbox_callback',
          'wrapper' => 'vform-div',
          '#event' => 'change',
          ),
      );
    } 
    $form_element['vform']["cell-$bv_vcnt-2"]['end'] = array(
        '#markup' => " \n ".'</td>',
      );
    
    
    // Create the cell for the survey question.
    if(empty($bv_title)) {
      $form_element['vform']['SQ-'.$bv_vanid] = array(
        '#markup' => " \n ".'<td class="td-de">N/A</td>',
      );
    } else {
      $form_element['vform']["SQ-".$bv_vanid] = array(
        '#type' => 'select',
        '#title' => t($bv_title),
        '#options' => $surveyResponseList,
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#ajax' => array(
          'callback' => 'voterdb_vselect_callback',
          'wrapper' => 'vform-div',
          '#event' => 'change',
          ),
        '#default_value' => 0,
      );
    }
    
    
    // Create the cell for reporting candidate ID.
    // Candidate name is first.
    $bv_qid = $bv_voter_info['qid'];
    $bv_color = ($bv_qid == 0)? DE_BLACK: DE_RED;
    $bv_candidates = $form_state['voterdb']['candidates']; 
    if(empty($bv_candidates)) {
      $form_element['vform']['CA-'.$bv_vanid] = array(
        '#markup' => " \n ".'<td class="td-de">N/A</td>',
      );
    } else {
      $bv_candidates[0] = 'Select Survey Question';
      // There are candidates to ID, so show the option.
      $form_element['vform']["CN-".$bv_vanid] = array(
        '#type' => 'select',
        '#options' => $bv_candidates,
        //'#title' => 'Candidate',
        '#attributes' => array('style' => array($bv_color)),
        '#prefix' => " \n ".'<td class="td-de">',
        '#ajax' => array(
          'callback' => 'voterdb_vselect_callback',
          'wrapper' => 'vform-div',
          '#event' => 'change',
          ),
        );
      
      // Show the ID selection if a candiate was chosen.
      if ($bv_qid != 0) {
        // Then the ID select.
        //voterdb_debug_msg('qid '.$bv_qid, '');
        $candidateObj = new NlpCandidates();
        //$questionObj = $form_state['voterdb']['questionObj'];
        //voterdb_debug_msg('question obj ', $questionObj);
        $responsesList = $candidateObj->getResponsesList($bv_qid);
        $form_state['voterdb']['ID'][$bv_qid] = $responsesList;

        $bv_id = $bv_voter_info['ID'];
        $bv_icolor = ($bv_id == 0)? DE_BLACK: DE_RED;
        $form_element['vform']["ID-".$bv_vanid] = array(
          '#type' => 'select',
          '#options' => $responsesList,
          //'#title' => 'ID',
          //'#title_display' => 'after',
          '#suffix' => '</td>',
          '#ajax' => array(
            'callback' => 'voterdb_vselect_callback',
            'wrapper' => 'vform-div',
            '#event' => 'change',
            ),
          '#default_value' => $bv_id,
          '#attributes' => array('style' => array($bv_icolor)),
          );
      }
    }
    // Create the cell for entering a comment.
    $form_element['vform']["CM-".$bv_vanid] = array(
        '#type' => 'textarea',
        '#rows' => 2,
        '#cols' => 13,
        '#resizable' => FALSE,
        '#prefix' => " \n ".'<td class="td-de">',
        '#suffix' => '</td>',
        '#ajax' => array(
            'keypress' => TRUE,
            'callback' => 'voterdb_vtextbox_callback',
            'wrapper' => 'vform-div',
          ),
         );
    // The last cell has the information on the status of the voters ballot.
    if($bv_deceased) {
      $bv_mbdate = "**DECEASED**";
    } elseif ($bv_moved) {
      $bv_mbdate = "**MOVED**";
    } else {
      $bv_mbdate = $bv_voter_info['status']['voted'];
    }
    $bv_edisplay = '';  // No voting record to display.
    if ($bv_history) {  // User requested history.
      // Get the text for display the prior four major elections.
      $bv_edisplay = $bv_voter_info[VN_VOTING];
    }
    $form_element['vform']["cell-$bv_vcnt-5"] = array(
        '#markup' => " \n ".'<td class="td-de">'.$bv_mbdate.'</td>',
        );
    // End the row.
    $form_element['vform']["row-end-$bv_vcnt"] = array(
      '#markup' => " \n".'</tr>',
      );
    //voterdb_debug_msg('voterinforcurrent ', $bv_voter_info['current']);
    foreach ($bv_voter_info['current'] as $bv_type => $bv_val) {
      if($bv_type=='Activist') {continue;}
      $bv_rpt[$bv_type] = $bv_val;
      if ($bv_history) {  // If hi//storical contacts requested, then add.
        if($bv_val != '') {  // New line if report for current cycle.
        }
        $bv_rpt[$bv_type] .= $bv_voter_info['historic'][$bv_type];
      }
    }
    // Start the row for display of previous results.
    $bv_results_row = " \n".'<tr class="even">'
      ." \n ".'<td class="td-de"></td>'
      ." \n ". '<td class="td-de"></td>'
      ." \n ". '<td class="td-de">'.$bv_rpt['Contact'].'</td>'
      ." \n ". '<td class="td-de">'.$bv_rpt['Survey'].'</td>'
      ." \n ". '<td class="td-de">'.$bv_rpt['ID'].'</td>'
      ." \n ". '<td class="td-de">'.$bv_rpt['Comment'].'</td>'
      ." \n ". '<td class="td-de">'.$bv_edisplay.'</td>'
      ." \n ".'</tr>';
    $form_element['vform']["row-results-$bv_vcnt"] = array(
        '#markup' => $bv_results_row,
        );
    $bv_vcnt++;
  }
    // End of the table.
  $form_element['vform']['table_end'] = array(
    '#markup' => " \n".'</tbody>'." \n".'</table>'." \n".'<!-- End of Data Entry Table -->'." \n",
    );
    return $form_element;
}
