<?php
/*
 * Name: voterdb_nlptest4.php   V3.0 1/16/17
 * 
 */


function email_example_mail_send($form_values) {
  // All system mails need to specify the module and template key (mirrored from
  // hook_mail()) that the message they want to send comes from.
  $module = 'voterdb';
  $key = 'contact_message';

  // Specify 'to' and 'from' addresses.
  $to = $form_values['email'];
  $from = variable_get('site_mail', 'admin@example.com');

  // "params" loads in additional context for email content completion in
  // hook_mail(). In this case, we want to pass in the values the user entered
  // into the form, which include the message body in $form_values['message'].
  $params = $form_values;
  
  drupal_set_message('<pre>'.print_r($params, true).'</pre>','status');

  // The language of the e-mail. This will one of three values:
  // - user_preferred_language(): Used for sending mail to a particular website
  //   user, so that the mail appears in their preferred language.
  // - global $language: Used when sending a mail back to the user currently
  //   viewing the site. This will send it in the language they're currently
  //   using.
  // - language_default(): Used when sending mail to a pre-existing, 'neutral'
  //   address, such as the system e-mail address, or when you're unsure of the
  //   language preferences of the intended recipient.
  //
  // Since in our case, we are sending a message to a random e-mail address that
  // is not necessarily tied to a user account, we will use the site's default
  // language.
  $language = language_default();

  // Whether or not to automatically send the mail when drupal_mail() is
  // called. This defaults to TRUE, and is normally what you want unless you
  // need to do additional processing before drupal_mail_send() is called.
  $send = TRUE;
  // Send the mail, and check for success. Note that this does not guarantee
  // message delivery; only that there were no PHP-related issues encountered
  // while sending.
  $result = drupal_mail($module, $key, $to, $language, $params, $from, $send);
  if ($result['result'] == TRUE) {
    drupal_set_message(t('Your message has been sent.'));
  }
  else {
    drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
  }

}



function voterdb_test_form() {
  $form['intro'] = array(
    '#markup' => t('Use this form to send a message to an e-mail address. No spamming!'),
  );
  $form['email'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail address'),
    '#required' => TRUE,
  );
  $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message'),
    '#required' => TRUE,
  );
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