<?php
/**
 * Name: voterdb_legislative_fixes.php    V4.2    6/5/18
 * 
 */

//require_once "voterdb_constants_nls_tbl.php";
//require_once "voterdb_constants_ld_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_legislative_fixes.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpLegFix;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_fix_display
 *
 * Convert the array of legislative district fixes to a set of strings.
 * 
 * @param type 
 * @return boolean - array of fixes in text form for checkboxes.
 */
function voterdb_fix_display ($fd_fixes) {
  $fd_i = 0;
  foreach ($fd_fixes as $fd_fix) {
    $fd_fix_array[$fd_i++] = 'MCID ['.$fd_fix['mcid'].
            '] '.$fd_fix['firstName'].' '.$fd_fix['lastName'].
            ' HD ['.$fd_fix['hd'].'] PCT ['.$fd_fix['pct'].']';
  }
  return $fd_fix_array;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_legislative_fixes_form
 *
 * Create the form for managing the fixes for HD and Precinct.
 * @return $form
 */
function voterdb_legislative_fixes_form($form, &$form_state) {
  $hf_button_obj = new NlpButton();
  $hf_button_obj->setStyle();
  if(!voterdb_get_group($form_state)) {return;}
  $hf_county = $form_state['voterdb']['county'];
  // Create the form to display leg district fixes.
  $hf_banner = voterdb_build_banner ($hf_county);
  
  $legFixObj = new NlpLegFix();
  $form_state['voterdb']['legFixObj'] = $legFixObj;
  
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $hf_banner
  ); 
 // Description.
  $form['fxdeldesc'] = array(
      '#type' => 'item',
      '#title' => t('Substitute for missing HD/Pct'),
      '#prefix' => " \n".'<div style="width:500px;">'." \n",
      '#suffix' => " \n".'</div>'." \n",
      '#markup' => 'Either select one or more fixes to remove (if present) or '
      . 'add a new one.  The substitute values are used when a list of '
      . 'Prospective NLs is uploaded and the substitution is made only if the '
      . 'uploaded values for HD/pct are empty.   After setting the substitute '
      . 'values, upload the list of prospective NLs again.',
    );
  
  
  $hf_fixes = $legFixObj->getLegFixes($hf_county);
  //$hf_fixes = voterdb_get_leg_districts ($hf_county);
  // If fixes exist, display them incase one or more are to be deleted.
  if(!empty($hf_fixes)) {
    $form_state['voterdb']['fixes'] = $hf_fixes;
    $hf_options = voterdb_fix_display ($hf_fixes);
    $form['oldfix'] = array(
      '#title' => 'Delete existing substitute values for HD/Pct',
      '#prefix' => " \n".'<div id="remove-fix" style="width:400px;">'." \n",
      '#suffix' => " \n".'</div>'." \n",
      '#type' => 'fieldset',
      );
    // Add a file upload file.
    $form['oldfix']['fxdel'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Select one or more subsitutes to delete.'),
        '#options' => $hf_options,
      );
    // Add a submit button.
    $form['oldfix']['fxsubdel'] = array(
        '#type' => 'submit',
        '#id' => 'fxsubdel',
        '#value' => 'Delete selected fixes',
      );
    }
  // Enter info for a new fix to HD and Pct.
  $form['newfix'] = array(
    '#title' => 'Add substitute values for HD/Pct when missing',
    '#prefix' => " \n".'<div id="add-fix" style="width:400px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    );
  // Description of add
  $form['newfix']['fxadddesc'] = array(
      '#type' => 'item',
      '#markup' => 'Enter information to add substitute values for HD and Precinct.',
    );
  // MCID data entry field.
  $form['newfix']['fxmcid'] = array (
    '#title' => t('MCID'),
    '#size' => 11,
    '#type' => 'textfield',
    );
  // First name data entry field.
  $form['newfix']['fxfname'] = array (
    '#title' => t('First Name'),
    '#size' => 40,
    '#type' => 'textfield',
    );
  // Last name data entry field.
  $form['newfix']['fxlname'] = array (
    '#title' => t('Last Name'),
    '#size' => 40,
    '#type' => 'textfield',
    );
  // HD data entry field.
  $form['newfix']['fxhd'] = array (
    '#title' => t('HD'),
    '#size' => 5,
    '#type' => 'textfield',
    );
  // Pct data entry field.
  $form['newfix']['fxpct'] = array (
    '#title' => t('Pct'),
    '#size' => 20,
    '#type' => 'textfield',
    );
  // Add a submit button.
  $form['newfix']['fxsubadd'] = array(
      '#type' => 'submit',
      '#id' => 'fxsubadd',
      '#value' => 'Add this fix',
    );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$hf_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_legislative_fixes_form_validate
 *
 * Validate the fields entered.  Mostly, we check for the most obvious errors
 * but not for validity of HD/Pct combo.
 *
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_legislative_fixes_form_validate($form, &$form_state) {
  $fv_triggering_event = $form_state['triggering_element']['#id'];
  switch ($fv_triggering_event) {
    // A new fix is being added.
    case  'fxsubadd':
      // Get the values entered for this fix.
      $fs_fix['county'] = $form_state['voterdb']['county'];
      $fs_fix['mcid'] = $form_state['values']['fxmcid'];
      $fs_fix['firstName'] = $form_state['values']['fxfname'];
      $fs_fix['lastName'] = $form_state['values']['fxlname'];
      $fs_fix['hd'] = $form_state['values']['fxhd'];
      $fs_fix['pct'] = str_replace(' ', '', $form_state['values']['fxpct']); // Remove blanks.
      $form_state['voterdb']['fix'] = $fs_fix;
      // Now validate the fields.
      foreach ($fs_fix as $fs_key => $fs_value) {
        switch ($fs_key) {
          case 'mcid':
            if ($fs_value == '') {
              form_set_error('fxmcid','MCID cannot be blank.');
            } elseif (!is_numeric($fs_value)){
              form_set_error('fxmcid','MCID must be numeric');
            }
            break;
          case 'firstName':
            if ($fs_value == '') {
              form_set_error('fxfname','First name cannot be blank.');
            }
            break;
          case 'lastName':
            if ($fs_value == '') {
              form_set_error('fxlname','First name cannot be blank.');
            }
            break;
          case 'hd':
            if ($fs_value == '') {
              form_set_error('fxhd','HD name cannot be blank.');
            } elseif (!is_numeric($fs_value)){
              form_set_error('fxhd','HD ust be numeric');
            } elseif ($fs_value < 1 OR $fs_value > 60) {
              form_set_error('fxhd','HD must be in the range 1-60');
            }
            break;
          case 'pct':
            // Most precinct numbers will be numeric but Umatilla uses a letter!
            if ($fs_value == '') {
              form_set_error('fxpct','Pct cannot be blank.');
            } 
            break;
        }
      }
      break;
    // Delete an existing fix.
    case  'fxsubdel':
      $fs_selections = $form_state['values']['fxdel'];
      $fs_selected = FALSE;
      foreach ($fs_selections as $fs_sel) {
        if ($fs_sel != '') {
          $fs_selected = TRUE;
        }
      }
      if (!$fs_selected) {
        form_set_error('fxdel','You must select something.');
      }
      break;
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_legislative_fixes_form_submit
 *
 * Process the info submitted.   
 * 
 * @param type $form
 * @param type $form_state
 */
function voterdb_legislative_fixes_form_submit($form, &$form_state) {
  $fs_triggering_event = $form_state['triggering_element']['#id'];
  $legFixObj = $form_state['voterdb']['legFixObj'];
  switch ($fs_triggering_event) {
    // Add a new fix for HD and Pct.
    case  'fxsubadd':
      $fs_fix = $form_state['voterdb']['fix'];
      $legFixObj->createLegFix($fs_fix);
      break;
    //  One or more fixes are to be deleted.
    case  'fxsubdel':
      // At least one was selected.
      $fs_selections = $form_state['values']['fxdel'];
      $fs_fixes = $form_state['voterdb']['fixes'];
      foreach ($fs_selections as $fs_sel) {
        if ($fs_sel != '') {
          $fs_mcid = $fs_fixes[$fs_sel][LD_MCID];
          $fs_county = $fs_fixes[$fs_sel][LD_COUNTY];
          $legFixObj->deleteLegFix($fs_county,$fs_mcid);
        }
      }
      break;
  }
}
