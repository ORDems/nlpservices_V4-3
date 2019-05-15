<?php
/*
 * Name: voterdb_test_form_users.php   V4.3 8/15/18
 * 
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_magic_word.php";


use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpMagicWord;


function voterdb_display_users($county) {
  
  $county = "Washington";
  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  
  $users = $userObj->getUsers($queryObj,$county);
  
  voterdb_debug_msg('Users', $users);
  
}


function voterdb_test_form() {

  
  $form['users'] = voterdb_display_users($county);
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}

function voterdb_test_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['email'])) {
    form_set_error('email', t('That e-mail address is not valid.'));
  }
}

function voterdb_test_form_submit($form, &$form_state) {
  email_example_mail_send($form_state['values']);
}