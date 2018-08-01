<?php
/**
 * Name: voterdb_nlp_instructions.php    V4.3  7/30/18
 * 
*/

require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_paths.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_instructions_nlp.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpPaths;
use Drupal\voterdb\NlpInstructions;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlp_instructions_form
 *
 * Create a form for uploading a PDF for the instructions for a canvass and
 * for a postcard.
 * 
 * @param type $form
 * @param type $form_state
 * @return string - form.
 */
function voterdb_nlp_instructions_form($form,&$form_state) {
  //voterdb_debug_msg('form', '');
  $ni_button_obj = new NlpButton();
  $ni_button_obj->setStyle();
  // Verify we know the group.
  if (!isset($form_state['voterdb']['reenter'])) {
    //$form_state['voterdb']['pass'] = 'page one';
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  // Get the instruction file names for this county, canvass and postcard.
  $ni_county = $form_state['voterdb']['county'];
  //voterdb_debug_msg('county', $ni_county);
  
  $instructionsObj = new NlpInstructions();
  
  $ni_current = $instructionsObj->getInstructions($ni_county);
  
  $form_state['voterdb']['current'] = $ni_current;
  // Create the form to display of all the NLs
  $hg_banner = voterdb_build_banner ($ni_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $hg_banner
  ); 
  $form['legend'] = array (
    '#type' => 'markup',
    '#markup' => '<p><span style="font-weight: bold;">Current instruction file name: </span>'.$ni_current['canvass']['fileName'].
      '</br><span style="font-weight: bold;">Current postcard file name: </span>'.$ni_current['postcard']['fileName'].'</p>',
  );
  $form['iform'] = array(
    '#title' => '<span style="font-weight: bold;">Select the file to upload</span>',
    '#prefix' => " \n".'<div id="iform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(240,240,240); width: 600px;'), ),
  );
  // Name the PDF with the canvass instructions.
  $form['iform']['canvass'] = array(
      '#type' => 'file',
      '#title' => t('Canvass instructions'),
      '#size' => 75,
  );
  // Name of the PDF with the instrucitons for a postcard.
  $form['iform']['card'] = array(
      '#type' => 'file',
      '#title' => t('Postcard instructions'),
      '#size' => 75,
  );
  $form['absentee'] = array (
    '#type' => 'markup',
    '#markup' => '<p><span style="font-weight: bold;">Current title: </span>'.$ni_current['absentee']['title'].
      '</br><span style="font-weight: bold;">Current description: </span>'.$ni_current['absentee']['blurb'].
      '</br><span style="font-weight: bold;">Registration/absentee form: </span>'.$ni_current['absentee']['fileName'].'</p>',
  );
        
        
  $form['aform'] = array(
    '#title' => '<span style="font-weight: bold;">Enter the title, description and select the file to upload</span>',
    '#prefix' => " \n".'<div id="iform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(240,240,240); width: 600px;'), ),
  );
  // Description of instructions for registration or absentee ballot.
  $form['aform']['absentee_title'] = array (
    '#title' => t(' Title for the instructions for registration or absentee ballot.'),
    '#size' => 16,
    '#maxlength' => 16,
    '#type' => 'textfield',
  );
  // Description of instructions for registration or absentee ballot.
  $form['aform']['absentee_blurb'] = array (
    '#title' => t(' Description of instructions for registration or absentee ballot.'),
    '#size' => 75,
    '#rows' => 3,
    '#maxlength' => 250,
    '#type' => 'textarea',
  );
  // Name of the PDF with the instrucitons for registration or absentee ballot.
  $form['aform']['absentee'] = array(
      '#type' => 'file',
      '#title' => t('Registration or absentee ballot instructions'),
      '#size' => 75,
  );
  // A submit button to update the NL recruitment goals.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Upload Instructions',
      '#description' => t('Upload the instructions file(s)'),
      '#suffix' => '</section>',
  );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$ni_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlp_instructions_form_validate
 * 
 * Vslidate that each of the files are of PDF for,at and that the name is
 * not too long.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_nlp_instructions_form_validate($form, &$form_state) {
  $fv_type = $form_state['triggering_element']['#type'];
  if ($fv_type != 'submit') {return;}
  // The submit button was clicked.
  $fv_canvass = $_FILES['files']['name']['canvass'];
  $fv_canvasstmp = $_FILES['files']['tmp_name']['canvass'];
  if ($fv_canvass != '') {
    $fv_cname_txt = strtolower($fv_canvass);
    $fv_cname_txt_array = explode('.', $fv_cname_txt);
    $fv_ctype_txt = end($fv_cname_txt_array);
    if ($fv_ctype_txt != 'pdf') {
      form_set_error('canvass', 'The canvass instructions must be a PDF.');
      return;
    }
  $fv_strlen = strlen($fv_canvass);
  if ($fv_strlen > 160) {
    form_set_error('canvass', 'The canvass instructions file name is too long.');
      return;
    }
  }
  
  $form_state['voterdb']['canvass'] = array(
      'new'=>$fv_canvass,
      'tmp'=>$fv_canvasstmp,
      'title'=>NULL,
      'blurb'=>NULL
  );

  // Now check that we have the PDF for the turf.
  $fv_postcard = $_FILES['files']['name']['card'];
  $fv_postcard_tmp = $_FILES['files']['tmp_name']['card'];
  
  if ($fv_postcard != '') {
    $fv_cname_pdf = strtolower($fv_postcard);
    $fv_cname_pdf_array = explode('.', $fv_cname_pdf);
    $fv_ctype_pdf = end($fv_cname_pdf_array);
    if ($fv_ctype_pdf != 'pdf') {
      form_set_error('card', 'The post card instruction file must be a PDF.');
      return;
    }
  $fv_strlen = strlen($fv_postcard);
  if ($fv_strlen > 160) {
    form_set_error('card', 'The postcard instructions file name is too long.');
      return;
    }
  }
  
  $form_state['voterdb']['postcard'] = array(
      'new'=>$fv_postcard,
      'tmp'=>$fv_postcard_tmp,
      'title'=>NULL,
      'blurb'=>NULL
  );
  
  $fv_absentee = $_FILES['files']['name']['absentee'];
  $fv_absentee_tmp = $_FILES['files']['tmp_name']['absentee'];
  $fv_absentee_blurb = $form_state['values']['absentee_blurb'];
  $fv_absentee_title = $form_state['values']['absentee_title'];
  
  if (!empty($fv_absentee) OR !empty($fv_absentee_blurb) OR !empty($fv_absentee_title)) {
    if(empty($fv_absentee_blurb)) {
      form_set_error('absentee_blurb', 'You must also have a description of the file to be attached.');
      return;
    }  
    if(empty($fv_absentee_title)) {
      form_set_error('absentee_blurb', 'You must also have a title of the file to be attached.');
      return;
    }  
    if(empty($fv_absentee)) {
      form_set_error('absentee', 'You must specify a file name.');
      return;
    }  
    $fv_ename_pdf = strtolower($fv_absentee);
    $fv_ename_pdf_array = explode('.', $fv_ename_pdf);
    $fv_etype_pdf = end($fv_ename_pdf_array);
    if ($fv_etype_pdf != 'pdf') {
      form_set_error('absentee', 'The registration or absentee file must be a PDF.');
      return;
    }
  $fv_strlen = strlen($fv_absentee);
  if ($fv_strlen > 160) {
    form_set_error('absentee', 'The registration or absentee file name is too long.');
      return;
    }
  }
  
  $form_state['voterdb']['absentee'] = array(
      'new'=>$fv_absentee,
      'tmp'=>$fv_absentee_tmp,
      'title'=>$fv_absentee_title,
      'blurb'=>$fv_absentee_blurb
  );
  
  //voterdb_debug_msg('Validate', $form_state['voterdb'],__FILE__,__LINE__);
  
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_update_instructions_file
 * 
 * Move the file with the instructions in PDF format and remember the name.
 * Delete the previous file if it exists.
 * 
 * @param type $form_state
 * @param type $ui_type - either NE_CANVASS or NE_POSTCARD. 
 */
function voterdb_update_instructions_file($ui_county,$ui_type,$ui_new,$ui_current) {
  $ui_filename = $ui_new['new'];
  $ui_title = $ui_new['title'];
  $ui_blurb = $ui_new['blurb'];
  $pathsObj = new NlpPaths();
  // If we have a new instruction, delete the current one and save the new one.
  if ($ui_filename != '') {
    // If a file already exists, delete it.
    if(!empty($ui_current['fileName'])) {
      // Delete the current file.
      $ui_current_full = $pathsObj->getPath('INST',$ui_county).$ui_current['fileName'];
      drupal_unlink($ui_current_full);
    }
    // Move the temp to the permanent location.
    $ui_tmp_fname = $ui_new['tmp'];
    //$ui_full_name = voterdb_get_path('INST',$ui_county).$ui_filename;
    $ui_full_name = $pathsObj->getPath('INST',$ui_county).$ui_filename;
            
    drupal_move_uploaded_file($ui_tmp_fname, $ui_full_name);

    $req = array(
      'county' => $ui_county,
      'type' => $ui_type,
      'fileName' => $ui_filename,
      'title' => $ui_title,
      'blurb' => $ui_blurb
    );
    $instructionsObj = new NlpInstructions();
    $instructionsObj->createInstructions($req);
    

  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlp_instructions_form_submit.
 * 
 * Process the submit of either the canvass or the postcard instructions, or
 * both.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_nlp_instructions_form_submit($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  
  $county = $form_state['voterdb']['county'];

  $current = $form_state['voterdb']['current'];
  
  if (!empty($form_state['voterdb']['canvass']['new'])) {
    $canvass = $form_state['voterdb']['canvass'];
    voterdb_update_instructions_file($county,'canvass',$canvass,$current['canvass']);
  } 
  if (!empty($form_state['voterdb']['postcard']['new'])) {
    $postcard = $form_state['voterdb']['postcard'];
    voterdb_update_instructions_file($county,'postcard',$postcard,$current['postcard']);
  }
  if (!empty($form_state['voterdb']['absentee']['new'])) {
    $absentee = $form_state['voterdb']['absentee'];
    voterdb_update_instructions_file($county,'absentee',$absentee,$current['absentee']);
  }
}