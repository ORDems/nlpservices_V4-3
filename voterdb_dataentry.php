<?php
/*
 * Name: voterdb_dataentry.php      V4.2  6/18/18
 */
//require_once "voterdb_constants_goals_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
//require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_log_tbl.php"; 
//require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_constants_date_tbl.php";
//require_once "voterdb_constants_candidates_tbl.php";
require_once "voterdb_constants_nlp_instructions_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_debug.php";
//require_once "voterdb_nls_validate.php";
require_once "voterdb_banner.php";
require_once "voterdb_track.php";
require_once "voterdb_path.php";
require_once "voterdb_dates.php";
require_once "voterdb_get_browser.php";
require_once "voterdb_instructions_get.php"; 
require_once "voterdb_coordinators_get.php"; 
require_once "voterdb_dataentry_func.php";
require_once "voterdb_dataentry_func2.php";
require_once "voterdb_dataentry_func3.php";
require_once "voterdb_dataentry_func4.php";
require_once "voterdb_dataentry_func5.php";

require_once "voterdb_class_magic_words.php";
require_once "voterdb_class_candidates_nlp.php";
require_once "voterdb_class_survey_question_nlp.php";
//require_once "voterdb_class_canvass_response_nlp.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_activist_codes_nlp.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_paths.php";
require_once "voterdb_class_nls.php";


use Drupal\voterdb\NlpMagicWords;
use Drupal\voterdb\NlpCandidates;
use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpPaths;
use Drupal\voterdb\NlpNls;


define('DE_BLUE','color:blue;');
define('DE_GREY','color:grey;');
define('DE_RED','color:red;');
define('DE_BLACK','color:black;');
define('DE_TBL_STYLE', '
        .noborder {border-collapse: collapse; border-style: hidden; table-layout:fixed;}
        .nowhite {margin:0px; padding:0px; line-height:100%;}
        .form-item {margin-top:0px; margin-top:0px;}
        .td-de {margin-left:2px; margin-bottom:2px; line-height:100%;}
        .form-type-textfield {margin: 2px 2px;}
        .aside-goals {margin:5px; width:390px; float:left;line-height:100%;}
        .goal-tx {text-align:center;width:65px;line-height:100%;}
        .goal-hdr {text-align:center;width:65px;line-height:100%; font-weight:bold;}
        textarea {width: 270px; min-width:270px; max-width:270px; height: 38px; min-height:38px; max-height:38px; resize:none;}
        .button {
          background-color: #4CAF50; /* Green */
          border: none;
          color: white;
          padding: 15px 32px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 16px;
        }
        div.rt {padding:0px; margin:0px; line-height:100%;} 
        section.rt {padding:0px; margin:0px; line-height:100%;}
        input[type="submit"] {
          background-color: #dddddd;
          border: 1px solid #000000;
          border-radius: 5px;
          color: black;
          padding: 3px 6px;
          text-align: center;
          text-decoration: none;
          }
        input[type="submit"]:hover {
          background-color: #3090cc;
          border: 1px solid #3090cc;
          border-radius: 5px;
          color: white;
          padding: 3px 6px;
          text-align: center;
          text-decoration: none;
          }
        ');
define('DC_HINTS',
 '#hintth { position: relative; }
  #hintth p { margins: 0; padding: 0; }
  #hintth p span { display: none; color: #0033ff; }
  #hintth p:hover span { display: block; position: absolute; width: 300px;
    font-weight: normal;
    left: -100px; top: -60px; color: #000000; text-align: left; 
    padding: 3px; border: 2px solid #0000ff; border-radius:5px;
    background: rgba(255, 255, 255, 1.0) } '
  );

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_dataentry_form
 *
 * Using multipass forms, verify the NL knows the password, select a turf
 * (when more than one turf exists for this NL), collect data from the NL
 * on voter contact, and record the information in a MySQL table.
 *
 * @param type $form_id
 * @param type $form_state
 * @return array - $form.
 */
function voterdb_dataentry_form($form_id, &$form_state) {
  //voterdb_debug_msg('form', '');
  // Verify we know the group
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['page'] = 'log-in';
    $form_state['voterdb']['reenter'] = TRUE;
    $form_state['voterdb']['history'] = FALSE;
  }
  // Styles used to build the data entry table.
  $dv_tbl_style = DE_TBL_STYLE.DC_HINTS;
  drupal_add_css($dv_tbl_style, array('type' => 'inline'));
  // Create the form to display of all the NLs.
  $dv_county = $form_state['voterdb']['county'];
  
  if(empty($form_state['voterdb']['turfObj'])) {
    $turfObj = new NlpTurfs();
    $form_state['voterdb']['turfObj'] = $turfObj;
    $form_state['voterdb']['pathsObj'] = new NlpPaths();
  }
  
  $dv_banner = voterdb_build_banner ($dv_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $dv_banner
  ); 
  $page = $form_state['voterdb']['page'];
  switch ($page) {
/* * * * * * * * * * * * *
 * Ask the NL for name and password
 */
    case 'log-in':
      $form['login'] = voterdb_build_login($form_state);  // func.
      break;
/* * * * * * * * * * * * *
 * This NL has more than one turf so one has to be chosen for dataentry.
 */
    case 'turf-select':
      $form['turfselect'] = voterdb_build_turf_select($form_state); // func.
      break;
/* * * * * * * * * * * * *
 * We have a turf selected so display the list of voters in this turf for
 * data entry. 
 */
    case 'data-entry':
      //voterdb_debug_msg('dataentry', '');
      // Build the table of voters if we haven't already done it.
      if(!isset($form_state['voterdb']['voters'])) {
        //voterdb_debug_msg('fetchvoters', '');
        $form_state['voterdb']['voters'] = voterdb_fetch_voters($form_state);  // func5.
        //voterdb_debug_msg('voters', $form_state['voterdb']['voters']);
        $form_state['voterdb']['call-file'] = voterdb_build_call_list($form_state);  // func2.
        //voterdb_debug_msg('callfile', $form_state['voterdb']['call-file']);
        
        $candiatesObj = new NlpCandidates();
        //voterdb_debug_msg('candidatesobj', $candiatesObj);
        $district['hd'] = $form_state['voterdb']['turf-hd'];
        $district['pct'] = $form_state['voterdb']['turf-pct'];
        $district['cd'] = $form_state['voterdb']['turf-cd'];
        $district['county'] = $form_state['voterdb']['county'];
        $candidates = $candiatesObj->getCandidateList($district);
        $form_state['voterdb']['candidates'] = $candidates; 
        
        // Record the date of this access to the turf by the NL.
        $turfIndex = $form_state['voterdb']['turfIndex'];
        $turfObj = $form_state['voterdb']['turfObj'];
        //voterdb_debug_msg('turfobj', $turfObj);
        $turfObj->setLastTurfAccess($turfIndex,NULL);
        
      }
      //voterdb_debug_msg('voters', $form_state['voterdb']['voters']);
      $form['info-box-c0'] = array(
        '#markup' => " \n".'<div>'.
        " \n".'<!-- Info box table -->'." \n".'<table class="noborder" style="width:840px; margin:0px 0px 6px 0px; padding:0px">'.
        " \n".'<tbody style="border-collapse: collapse; border-style: hidden;">'.
        " \n".'<tr>',
      );
      $form['info-box-c1'] = array('#markup'=>" \n ".
        '<td style="width:400px; margin:0px; padding:0px">'.'<!-- lists cell -->',);
      // Show a link for a list of other NLs and the link to the Turf PDF.
      $form['lists'] = voterdb_lists($form_state); //func3.
      // Display a box with links to instructions.
      $form['info-box-c2'] = array('#markup'=>" \n ".'</td>'.
        " \n ".'<td style="width:130px; margin:0px; padding:0px">'.'<!-- instructions cell -->',);
      $form['instruct'] =  voterdb_instruct_disp($form_state); //func3.
      // Show the click box for the option to show historical voter contacts.
      $form['info-box-c3'] = array('#markup'=>" \n ".'</td>'.
        " \n ".'<td style="width:100px; margin:0px; padding:0px">'.'<!-- history checkbox cell -->',);
      $form['center'] = voterdb_history_option($form_state);  // func3.
      // Show a box with the contact information for the coordinator.
      $form['info-box-c4'] = array('#markup'=>" \n ".'</td>'.
        " \n ".'<td style="width:200px; margin:0px; padding:0px">'.'<!-- coordinator cell -->',);
      $form['coordinator'] =  voterdb_coordinator_disp($form_state); //func3.
      $form['info-box-c5'] = array('#markup'=>" \n ".'</td>'.
        " \n ".'<td style="width:220px; margin:0px; padding:0px">'.'<!-- reminder cell -->',);
      
      $turfIndex = $form_state['voterdb']['turfIndex'];
      $turf = $form_state['voterdb']['turfArray']['turfs'][$turfIndex];
      
      $form['reminder'] =  array(
          '#markup'=>" \n ".
        'Your turf was made available to you on '.$turf['CommitDate'].
          ' for the '.$turf['ElectionName'],);
      
      
      
      $form['info-box-c6'] = array('#markup' => " \n ".'</td>'.
        " \n".'</tr>'.
        " \n".'</tbody>'.
        " \n".'</table>'." \n".'<!-- End of Info box table -->'.
        " \n".'</div>');
      // Input for the date of the canvass.
      $form['canvass_date'] = voterdb_canvass_date($form_state);  //func3.
      //  When there is more than one turf for the NL, display the option to change turfs.
      if($form_state['voterdb']['turf-select']) {
        $form['next-turf'] = voterdb_turf_select($form_state);  //func3.
      }
      // Build the huge display of voters with options to enter canvass results.
      $form['voters'] =  voterdb_build_voter_tbl($form_state);  //func2.
      break;
/* * * * * * * * * * * * *
 * Display the list of other NLs in this NLs HD
 */
    case 'other_list':
      $form['others'] = voterdb_build_others_list($form_state);  // func.
      break;
  }
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_dataentry_form_validate
 *
 * Validate the various forms we use in this multipass form process.   For 
 * the login, verify we know the name of the NL and they know the magic word.
 * The magic word is intended to keep out the Drupal aware bots.   And, two 
 * words are allowed, the current one and the previous one. Then we check if 
 * there is a turf and if there are more than one, the next display will select 
 * one.
 * 
 * The data entry is all done using ajax.  But the data entry page does allow
 * the user to display a list of NLs in the precinct and to manage access to
 * multiple turfs.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_dataentry_form_validate($form, &$form_state) {
  //voterdb_debug_msg('validate', $form_state['voterdb']);
  //voterdb_debug_msg('validate', '');
  $page = $form_state['voterdb']['page'];
  switch ($page) {
/* * * * * * * * * * * * *
 * Pass Login Validate.
 * The first time through we are validating the user login.
 */
    case 'log-in':
      $dv_county = $form_state['voterdb']['county'];
      $dv_fname = $form_state['values']['nlfname'];
      $dv_ln = $form_state['values']['nllname'];
      $dv_lname = str_replace("'", "&#039;", $dv_ln); // fix the apostrophies.
      $dv_browser = getBrowser();
      // Verify we know this NL.
      // Note: resolve the case where there are two NLs with the same name.
      $nlsObj = new NlpNls();
      //voterdb_debug_msg('nlsobj', $nlsObj);
      $dv_nls_info = $nlsObj->getNl($dv_fname, $dv_lname, $dv_county);
      //voterdb_debug_msg('nlsinfo', $dv_nls_info);
      //$dv_nls_info = voterdb_nls_validate($dv_fname, $dv_lname,$dv_county);  // nls_validate.
      // Stop if we don't have this person in the database.
      if (!$dv_nls_info) {
        $dv_info = $dv_fname.' '.$dv_lname.' : '.$dv_browser['platform'].' '.$dv_browser['browser'];
        voterdb_login_tracking('login',$dv_county, 'Invalid User Name',$dv_info);  // func
        form_set_error('nllname','Your name is not on our list of active Neighborhood Leaders.  Please contact your coordinator.');
        form_set_error('nlfname','');
        return FALSE;
      }
      $dv_mcid = $dv_nls_info['mcid'];
      // Check that the password is reasonably close.  This just stops the Russian bots.

      $dv_passwordObj = new NlpMagicWords();
      $dv_password_array = $dv_passwordObj->getMagicWords($dv_county);
      $dv_password = strtolower($dv_password_array['password']);
      $dv_altpassword = strtolower($dv_password_array['passwordAlt']);
      $dv_pw_try = strtolower($form_state['values']['nl-block']);

      if ($dv_password != $dv_pw_try AND $dv_altpassword != $dv_pw_try) {
        $dv_info = $dv_fname.' '.$dv_lname.' ['.$dv_pw_try.']'.' : '.$dv_browser['platform'].' '.$dv_browser['browser'];
        voterdb_login_tracking('login',$dv_county, 'Invalid Password',$dv_info);  // func.
        form_set_error('nl-block','Incorrect Password');
        return;
      }
      $form_state['voterdb']['fname'] = $dv_fname;
      $form_state['voterdb']['lname'] = $dv_lname;
      $form_state['voterdb']['mcid'] = $dv_mcid;
      $form_state['voterdb']['HD'] = $dv_nls_info['hd'];
      $form_state['voterdb']['pct'] = $dv_nls_info['pct'];
      // Check if this NL has one or more turfs
      
      
      $turfObj = $form_state['voterdb']['turfObj'];
      //voterdb_debug_msg('turfobj', $turfObj);
      $turfArray = $turfObj->turfExists($dv_mcid,$dv_county);
      //voterdb_debug_msg('turf', $turfArray);
      $form_state['voterdb']['turfArray'] = $turfArray;
      
      if (empty($turfArray)) {
        drupal_set_message("You do not have a turf assigned",'error');
        form_set_error('lname', 'Contact your NLP coordinator');
        $dv_info = $dv_fname.' '.$dv_lname;
        voterdb_login_tracking('login',$dv_county,'No turf',$dv_info);  // func.
        return FALSE;
      }
      $form_state['voterdb']['turfIndex'] = $turfArray['turfIndex'];
      $dv_info = $dv_fname.' '.$dv_lname.' : '.$dv_browser['platform'].' '.$dv_browser['browser'];
      voterdb_login_tracking('login',$dv_county,'Successful Login',$dv_info);  // func
      
      break;
/* * * * * * * * * * * * *
 * Process the triggering event during data entry.
 */
    case 'data-entry':
      $form_state['voterdb']['reenter'] = TRUE;
      // Check if NL wants to see the list of other NLs.
      if(($form_state['triggering_element']['#id'] == 'show-list') OR 
        ($form_state['triggering_element']['#id'] == 'show-turfs')) {
        return;  // Let the submit call back process this request.
      }
      // Process the ajax data entry.
      voterdb_process_voter_info($form_state);  // func5.
      break;      
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_dataentry_form_submit
 *
 * Process the form submitted.  For a login, record that the NL has looked at
 * their turf to stop the inactivity email, and proceed to either select a
 * turf or to display the only turf.
 * 
 * If a turf was selected, proceeed to display the selected one.
 * 
 * For the data entry page. the submit is either to look at the list of other
 * PCPs in the precinct or to select a different turf (only when the NL has
 * more than one turf assigned, which is rare).
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_dataentry_form_submit($form, &$form_state) {
  //voterdb_debug_msg('submit', $form_state['voterdb']);
  //voterdb_debug_msg('submit', '');
  $page = $form_state['voterdb']['page'];
  switch ($page) {
/* * * * * * * * * * * * *
 * The NL has successfully logged in.  Now we either ask the NL for the turf
 * when there are more than one turfs for this NL or we jump to the voter
 * data entry form.   Also, the form_state variable is preserved for the
 * next pass.
 */
    case 'log-in':
      $form_state['voterdb']['reenter'] = TRUE;
      $form_state['rebuild'] = TRUE;  // form_state will persist.
      // Record the date of the last login.
      $dv_mcid = $form_state['voterdb']['mcid'];
      $dv_county = $form_state['voterdb']['county'];
      $dv_date = date('Y-m-d');
      db_set_active('nlp_voterdb');
      db_merge(DB_NLSSTATUS_TBL)
        ->key(array(
          NN_COUNTY => $dv_county,
          NN_MCID => $dv_mcid))
        ->fields(array(
          NN_LOGINDATE => $dv_date))
        ->execute();
      db_set_active('default');
      // If more than one turf, ask the NL which one is wanted.
      $dv_turf_cnt = $form_state['voterdb']['turfArray']['turfCnt'];
      $form_state['voterdb']['page'] = ($dv_turf_cnt==1)?'data-entry':'turf-select';
      $form_state['voterdb']['turf-select'] = FALSE;
      break;
/* * * * * * * * * * * * *
 * The NL selected one of their turfs for data entry.
 */
    case 'turf-select':
      $form_state['voterdb']['reenter'] = TRUE;
      $form_state['voterdb']['page'] = 'data-entry';
      $form_state['rebuild'] = TRUE;  // form_state will persist.
      //$dv_turf = $form_state['values']['turfselect'];  // Index to names.
      // The selected turf. Get the name and the index.
      //$form_state['voterdb']['turfArray']['TurfName'] = 
      //        $form_state['voterdb']['turf-array'][$dv_turf]['TurfName'];
      $form_state['voterdb']['TurfIndex'] = $form_state['values']['turfselect'];
              //$form_state['voterdb']['turf-array'][$dv_turf]['TurfIndex'];
      $form_state['voterdb']['turf-select'] = TRUE;  // There is more than one turf.
      // get a new list of voters.
      unset($form_state['voterdb']['voters']);
      break;
    
/* * * * * * * * * * * * *
 * 
 * The NL selected something in the data entry page.
 */
    case 'data-entry':   
      $form_state['voterdb']['reenter'] = TRUE;
      $form_state['rebuild'] = TRUE;  // form_state will persist
      // Check if the NL wants the list of other NLs.
      if($form_state['triggering_element']['#id'] == 'show-list') {
        $form_state['voterdb']['page'] = 'other_list';
        return;  // reenter the form to create the display of NLs.
      }
      // Check if the NL wants to select a different turf.
      if($form_state['triggering_element']['#id'] == 'show-turfs') {
        $form_state['voterdb']['page'] = 'turf-select';
        return;  
      }
      break;
/* * * * * * * * * * * * *
 * 
 * The NL wants to return to the data entry.
 */
    case 'other_list':
      $form_state['voterdb']['reenter'] = TRUE;
      $form_state['voterdb']['page'] = 'data-entry';
      $form_state['rebuild'] = TRUE;  // form_state will persist.
      break;
  }
}
