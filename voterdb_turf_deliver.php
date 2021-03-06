<?php

/*
 * Name:  voterdb_turf_deliver.php               V4.3 4/3/19
 */
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_track.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_paths.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_coordinators_nlp.php";
require_once "voterdb_class_instructions_nlp.php";
require_once "voterdb_class_magic_word.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpPaths;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCoordinators;
use Drupal\voterdb\NlpInstructions;
use Drupal\voterdb\NlpMagicWord;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_selected_callback
 * 
 * AJAX call back for the selection of the HD.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_hd_selected_callback($form, $form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['hd-change'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_selected_callback
 * 
 * AJAX callback for the selection of an NL to associate with a turf.
 *
 * @return array
 */
function voterdb_pct_selected_callback($form, $form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['hd-change']['turf-select'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_deliver_form
 *
 * Create the form for sending a turf packet email to an NL.
 *
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_turf_deliver_form($form, &$form_state) {
  $fv_button_obj = new NlpButton();
  $fv_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    if (!voterdb_get_group($form_state)) {
      return;
    }
    $form_state['voterdb']['hd-saved'] = $form_state['voterdb']['pct-saved'] = 0;
    $form_state['voterdb']['reenter'] = TRUE;
  }
  $fv_county = $form_state['voterdb']['county'];
  $fv_hd_saved = $form_state['voterdb']['hd-saved'];
  // Create the banner.
  $fv_banner = voterdb_build_banner($fv_county);
  $form['note'] = array(
      '#type' => 'markup',
      '#markup' => $fv_banner
  );
  // Request the user select either a HD or a Precinct.
  if (!isset($form_state['values']['HD'])) {
    $fv_selected_hd = $fv_previous_hd = $form_state['voterdb']['PreviousHD'] = $fv_hd_saved;
  } else {
    $fv_selected_hd = $form_state['values']['HD'];
    $fv_previous_hd = $form_state['voterdb']['PreviousHD'];
  }
  // If the user changed the HD, then reset the pct to zero.
  if ($fv_selected_hd != $fv_previous_hd) {
    $form_state['values']['pct'] = 0;
    $form_state['input']['pct'] = 0;
    $form_state['complete form']['hd-change']['pct']['#input'] = 0;
    $form_state['complete form']['hd-change']['pct']['#value'] = 0;
    $form_state['voterdb']['PreviousHD'] = $fv_selected_hd;
  }
  // Get the list of HDs with existing turfs.
  
  $turfsObj = new NlpTurfs();
  //$form_state['voterdb']['turfsObj'] = $turfsObj;
  $fv_hd_options = $turfsObj->getTurfHD($fv_county);
  
  
  if ($fv_hd_options) {
    // House Districts exists.
    $form_state['voterdb']['hd_options'] = $fv_hd_options;
    $form['HD'] = array(
        '#type' => 'select',
        '#title' => t('House District Number'),
        '#options' => $fv_hd_options,
        '#default_value' => $fv_selected_hd,
        '#ajax' => array(
            'callback' => 'voterdb_hd_selected_callback',
            'wrapper' => 'hd-change-wrapper',
        )
    );
  }
  // Put a container around both the pct and the NL selection, they both
  // reset and have to be redrawn with a change in the HD.
  $form['hd-change'] = array(
      '#prefix' => '<div id="hd-change-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
      '#attributes' => array('style' => array('background-image: none; border: 0px; width: 550px; padding:0px; margin:0px; background-color: rgb(255,255,255);'),),
  );
  $fv_selected_pct = (isset($form_state['values']['pct'])) ? $form_state['values']['pct'] : 0;
  $fv_selected_hd_name = $fv_hd_options[$fv_selected_hd];
  $fv_pct_options = $turfsObj->getTurfPct($fv_county,$fv_selected_hd_name);
  
  $form_state['voterdb']['pct_options'] = $fv_pct_options;
  if (!$fv_pct_options) {
    drupal_set_message("No turfs exist", "status");
  } else {
    // Precincts exis    $form_state['voterdb']['pct_options'] = $fv_pct_options;
    $form['hd-change']['pct'] = array(
        '#type' => 'select',
        '#title' => t('Precinct Number'),
        '#options' => $fv_pct_options,
        '#default_value' => $fv_selected_pct,
        '#ajax' => array(
            'callback' => 'voterdb_pct_selected_callback',
            'wrapper' => 'ajax-turf-replace',
            'effect' => 'fade',
        ),
    );
  }
  // The user selected a precinct, now create the list of turfs.
  $turfReq['county'] = $fv_county;
  $turfReq['pct'] = $fv_pct_options[$fv_selected_pct];
  $turfArray = $turfsObj->getTurfs($turfReq);
  
  
  $nlsObj = new NlpNls();
  
  // Display the turf choices.
  if (!empty($turfArray)) {
    
    $form_state['voterdb']['turfs'] = $turfArray;
    //$fv_turfs = $turfArray['turfs'];
    $turfDisplay = $turfsObj->createTurfDisplay($turfArray);
    
    $fv_turf_choices = array();
    foreach ($turfArray as $fv_turf_index=>$fv_turf) {
      // Get the information about the NL for this turf.
      $fv_mcid = $fv_turf['MCID'];

      $nl = $nlsObj->getNlById($fv_mcid);
      $fv_email = $nl['email'];

      // Create the display for this turf choice.
      $fv_turf_choices[$fv_turf_index] = '['.$fv_email.'] '.$turfDisplay[$fv_turf_index];
      $form_state['voterdb']['turfs'][$fv_turf_index]['email'] = $fv_email;
    }
    
    $form['hd-change']['turf-select'] = array(
        '#title' => t('Select the NL to to recieve the turf and instructions'),
        '#type' => 'radios',
        '#prefix' => '<div id="ajax-turf-replace">',
        '#suffix' => '</div>',
        '#options' => $fv_turf_choices,
    );
  } else {
    drupal_set_message('There are no turfs for this selection', 'status');
  }
  // Allow the sender to add a paragraph to the email.
  $form['hd-change']['note'] = array(
      '#title' => 'Additional note for the NL',
      '#type' => 'textarea',
      '#description' => 'This will add an additional paragraph to the email to be sent to the NL.'
  );
  // Add a submit button to send the email.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Send an email to the selected NL >>',
  );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fv_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_deliver_form_submit
 * 
 * Send an email to the selected NL with the turf, instructions and other
 * information.
 *
 * Process the file submitted.
 * @param type $form
 * @param type $form_state
 */
function voterdb_turf_deliver_form_submit($form, &$form_state) {
  //global $base_url;
  $serverName = $GLOBALS['_SERVER']['SERVER_NAME'];
  $serverUrl = 'https://'.$serverName;
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  $df_county = $form_state['voterdb']['county'];
  $df_turf_select = $form_state['input']['turf-select'];
  $tc_turf_array = $form_state['voterdb']['turfs'][$df_turf_select];
  $df_mcid = $tc_turf_array['MCID'];
  $df_account = user_uid_optional_load();
  // Name of the NLP Coordinator sending the message.
  $df_field_firstname = $df_account->field_firstname;
  $df_field_lastname = $df_account->field_lastname;
  $df_field_phone = $df_account->field_phone;
  $df_user_fname = $df_field_firstname['und'][0]['safe_value'];
  $df_user_lname = $df_field_lastname['und'][0]['safe_value'];
  $df_user_phone = $df_field_phone['und'][0]['safe_value'];
  $df_user_email = $df_account->mail;
  // Get the optional not ffrom the sender to the NL.
  $df_note = $form_state['input']['note'];
  $df_plain_note = check_plain($df_note);
  // Get the canvass instruction file name.
  $instructionsObj = new NlpInstructions();
  $df_instuctions = $instructionsObj->getInstructions($df_county);
  //$df_instuctions = voterdb_get_instructions($df_county);
  //voterdb_debug_msg('instructions', $df_instuctions);
  if (empty($df_instuctions['canvass']['fileName'] )) {
    drupal_set_message('Opps, you need to upload the canvass instructions first.', 'error');
    return;
  }
  // Check if this NL is signed up to send a postcard.
  //$df_contact_array = unserialize(CT_CONTACT_ARRAY);
  $nlsObj = new NlpNls();
  $df_nls_status = $nlsObj->getNlsStatus($df_mcid,$df_county);
  
  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  $user = $userObj->getUserByMcid($queryObj,$df_mcid);
  //voterdb_debug_msg('user', $user);
          

  $df_mail = ($df_nls_status['contact'] == 'mail');
  // Get the external URI for the instructions.
  $pathsObj = new NlpPaths();
  $df_path = $pathsObj->getPath('INST',$df_county);
  //voterdb_debug_msg('path', $df_path);
  
  $df_curl = file_create_url($df_path . $df_instuctions['canvass']['fileName']);
  $df_purl = file_create_url($df_path . $df_instuctions['postcard']['fileName']);
  // Get the info about the NL for the email.
  
  
  $df_nl = $nlsObj->getNlById($df_mcid);
  //voterdb_debug_msg('nlnew', $df_nl);
  /*
  db_set_active('nlp_voterdb');
  $df_tselect = "SELECT * FROM {" . DB_NLS_TBL . "} WHERE  " .
          NH_MCID . " = :mcid";
  $df_targs = array(
      ':mcid' => $df_mcid);
  $df_result = db_query($df_tselect, $df_targs);
  $df_nls = $df_result->fetchAll(PDO::FETCH_ASSOC);
  $df_nl = $df_nls[0];
  db_set_active('default');
  voterdb_debug_msg('nl', $df_nl);
   */
  
  
  // Set up the function call to send the email.
  //$df_params = $df_nl;
  $df_params['func'] = 'turf-deliver';
  $df_language = language_default();
  //$df_send = TRUE;
  //$df_module = 'voterdb';
  //$df_key = 'deliver turf';  // Let the hook know it is us.
  // Sender's info.
  $df_params['s-fn'] = $df_user_fname;
  $df_params['s-ln'] = $df_user_lname;
  $df_params['s-email'] = $df_user_email;
  $df_params['county'] = $df_county;
  // Recipient's info, ie the NL.
  $df_params['r-fn'] = $df_nl['nickname'];
  $df_params['r-ln'] = $df_nl['lastName'];
  $df_params['r-email'] = $df_nl['email'];
  // Add the note if one provided.
  if (!empty($df_plain_note)) {
    $df_params['note'] = $df_plain_note;
  }

  // Get the name of the coordinator for this NL.  Use the sender's name
  // if the name of the coordinator is not yet known.
  //$df_hd = $df_nl[NH_HD];
  //$df_pct = $df_nl[NH_PCT];
  $df_cofname = $df_user_fname;
  $df_colname = $df_user_lname;
  $df_semail = $df_user_email;
  $df_phone = $df_user_phone;
  
  $df_region = array(
    'hd'=>$df_nl['hd'],
    'pct'=>$df_nl['pct'],
    'county'=>$df_county,
  );
  $coordinatorsObj = new NlpCoordinators();
  $df_region['coordinators'] = $coordinatorsObj->getAllCoordinators();
  //voterdb_debug_msg('region', $df_region);
  
  $df_coordinator = $coordinatorsObj->getCoordinator($df_region);
  //voterdb_debug_msg('coordinator', $df_coordinator);

  if (!empty($df_coordinator)) {
    $df_cofname = $df_coordinator['firstName'];
    $df_colname = $df_coordinator['lastName'];
    $df_phone = $df_coordinator['phone'];
    $df_semail = $df_coordinator['email'];
  }
  
  $magicWordObj = new NlpMagicWord();
  $magicWord = $magicWordObj->getMagicWord($df_mcid);
  if(empty($magicWord)) {
    $magicWord = 'Your Password';
  }
  
  // Construct the message.
  $df_message = "<p>" . $df_nl['nickname'] . ",</p>";
  $df_message .= t("<p>" . 'Thanks for helping establish the Neighborhood Leader Program in @grp County.&nbsp; ', array('@grp' => $df_county));
  $df_message .= t('The first link will take you to the instructions for your canvass of your neighbors.&nbsp;  ' .
          'Please click this link and read the instructions if your are not already familiar with the program.  ' . '</p>');
  // Add the link to the instructions.
  $df_message .= t('<p><a href="@c-url" target="_blank">Neighborhood Leader Instructions - canvass</a></p>', array('@c-url' => $df_curl));
  // Also add instructions for the postcard if the NL signed up to send cards.
  if (!empty($df_instuctions['postcard']['fileName']) AND $df_mail) {  // Display the postcard option only for those that need it.
    $df_message .= t('The next link describes the process for sending a postcard.  This is optional and may only apply to rural neighborhoods.&nbsp;  ' . '</p>');
    $df_message .= t('<p><a href="@p-url" target="_blank">Neighborhood Leader Instructions - postcard</a></p>', array('@p-url' => $df_purl));
  }
  
  if (!empty($df_instuctions['absentee']['fileName'])) {  // Display the registration or absentee option.
    $df_aurl = file_create_url($df_path . $df_instuctions['absentee']['fileName']);
    $df_blurb = $df_instuctions['absentee']['blurb'];
    $df_title = $df_instuctions['absentee']['title'];
    $df_message .= t('<p>  ' . $df_blurb .'</p>');
    $df_message .= t('<p><a href="@a-url" target="_blank"> '.$df_title.' Form</a></p>', array('@a-url' => $df_aurl));
  }

  $df_message .= t('Please click the link below to take you to the login for your list of voters.&nbsp;  ' .
          'It is important that you return to this login to report the results of your attempts to contact the voters.&nbsp;   ' .
          'After login, you will see a link in the upper left corner.&nbsp;  That will take you to a printable copy of your list of voters.  ' .
          'Click that link and print the list.' . '</p>');
  $df_message .= t('<p><a href="' . $serverUrl . '" target="_blank">Neighborhood Leader Login</a></p>');
  $df_message .= t('<p>' . 'Your login name is: @name ' . '</p>', array('@name' => $user['userName']));
  $df_message .= t('<p>' . 'The password is: @pass' . '</p>', array('@pass' => $magicWord));
  // Add the optional note.
  if (!empty($df_plain_note)) {
    $df_message .= '<p>' . $df_plain_note . '</p>';
  }
  $df_thanks = '<p>Please contact me if you have any questions.<br>Thanks<br>@fname @lname<br>@phone<br>' .
          '<a href="mailto:@email?subject=NL%20Help%20Request">@email</a></p>';
  $df_message .= t($df_thanks, array(
      '@fname' => $df_cofname,
      '@lname' => $df_colname,
      '@phone' => $df_phone,
      '@email' => $df_semail,));
  $df_params['message'] = $df_message;
  //voterdb_debug_msg('Param', $df_params);
  // Specify 'to' and 'from' addresses.
  $df_to = $df_nl['email'];
  if (empty($df_to)) {
    drupal_set_message(t('The selected NL does not have and email address so nothing was sent.'), 'error');
    return 0;
  }
  $df_from = 'NLP Admin<';
  $df_from .= variable_get('voterdb_email', 'notifications@nlpservices.org');
  $df_from .= '>';
  //voterdb_debug_msg('params', $df_params);
  // Send the email.
  $result = drupal_mail('voterdb', 'deliver turf' , $df_to, $df_language, $df_params, $df_from, TRUE);
  //voterdb_debug_msg('Result', $result);
  // Report results, track the send, and update the "delivered" status.
  $df_info = 'CO [' . $df_cofname . ' ' . $df_colname . '] NL [' . $df_nl['nickname'] .
          ' ' . $df_nl['lastName'] . ' - ' . $df_nl['email'] . ']';
  if ($result['result'] == TRUE) {
    // Update the NLs status to indicate the turf was delivered.

    $df_nls_status = $nlsObj->getNlsStatus($df_mcid,$df_county);
    $df_nls_status['turfDelivered'] = 'Y';
    $nlsObj->setNlsStatus($df_nls_status);
    //voterdb_debug_msg('status', $df_nls_status);
    // Now update date the turf was delivered.
    $turfsObj = new NlpTurfs();
    $turfsObj->setTurfDelivered($tc_turf_array['TurfIndex']);
    
    $statusHistory = array(
      'mcid' => $df_mcid,
      'county' => $df_county,
      'status' => $nlsObj::HISTORYDELIVEREDTURF,
      'nlFirstName' => $df_nl['nickname'],
      'nlLastName' => $df_nl['lastName']
    );

    $nlsObj->setStatusHistory($statusHistory);
    
    //voterdb_nl_status_history($df_county,$df_mcid,NY_DELIVEREDTURF);
    drupal_set_message(t('Your message has been sent.'));
    voterdb_login_tracking('turf', $df_county, 'Email sent', $df_info);
  } else {
    drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    voterdb_login_tracking('turf', $df_county, 'Email failed', $df_info);
  }
  return;
}
