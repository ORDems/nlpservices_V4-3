<?php
/**
 * Name:  voteredb_mail.php     V4.2  7/17/18
 * @file
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_mail_alter
 * 
 * Implements hook mail alter.
 * 
 * @param type $message
 */
function voterdb_mail_alter(&$message) {
  global $base_url;
  drupal_set_message('alter '.'<pre>'.print_r($message, true).'</pre>','status');
  if($message['module'] == 'voterdb') {
    //drupal_set_message('<pre>'.print_r($message, true).'</pre>','status');
    $options = array(
      'langcode' => $message['language']->language,
    );
    $ma_from = variable_get('voterdb_email', 'notifications@nlpservices.org');
    $signature = t('<br>If you recieved this email in error, please forward it to '
      . $ma_from . " and we will remove you from future emails.", array(), $options);
    if (is_array($message['body'])) {
      $message['body'][] = $signature;
    }
    else {
      // Some modules use the body as a string, erroneously.
      $message['body'] .= $signature;
    }
  if (isset($message['params']['func'])) { 
      if($message['params']['func'] == 'turf-deliver')  {
        $params = $message['params'];
        $ma_notify = array();
        $ma_notify['sender']['county'] = $params['county'];
        $ma_notify['sender']['s-fn'] = $params['s-fn'];
        $ma_notify['sender']['s-ln'] = $params['s-ln'];
        $ma_notify['sender']['s-email'] = $params['s-email'];

        $ma_notify['recipient']['r-fn'] = $params['r-fn'];
        $ma_notify['recipient']['r-ln'] = $params['r-ln'];
        $ma_notify['recipient']['r-email'] = $params['r-email'];

        $ma_notify_str = json_encode($ma_notify).'<eor>';

        $message['headers']['x-voterdb-notify'] = $ma_notify_str;
      } 
    }
  } elseif($message['module'] == 'user') {
    $accountObj = $message['params']['account'];
    if($message['key']=='register_admin_created' AND isset($accountObj->func)) {
      //$from = variable_get('voterdb_email', 'notifications@nlpservices.org');
      //$from = $message['from'];
      //$message['from'] = 'NLP Admin <'.$from.'>';
      
      $firstName = $accountObj->firstName;
      $func = $accountObj->func;
      if($func=='shared') {
        $message['to'] = $accountObj->sharedEmail;
      }
      $message['subject'] = 'Neighborhood Leader account login: access to your turf';
      //$body = $message['body'][0];
      //$content = strstr($body, ':');
      //$message['body'][0] = $message['params']['account']['field_firstname']['und'][0]['value'].',';
      $message['body'][0] = $firstName.',';
      $message['body'][1] = 'Thanks for being a Neighborhood Leader in '.$accountObj->County.' County.';
      $message['body'][2] = 'The administrator at [site:name] has created an account for you. 
        You may now log in to get your turf by clicking this link or copying and pasting it to your browser: ';
      $message['body'][3] = 'http//:'.$base_url;
      $message['body'][4] = 'Username: '.$accountObj->Name;
      $message['body'][5] = 'Password: '.$accountObj->Pass;
    }
    
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_mail
 * 
 * @param type $key
 * @param type $message
 * @param type $params
 */
function voterdb_mail($key, &$message, $params) {

  //drupal_set_message('hookmail key '.$key,'status');
  //drupal_set_message('<pre>'.print_r($message, true).'</pre>','status');
  //drupal_set_message('<pre>'.print_r($params, true).'</pre>','status');

  $options = array(
    'langcode' => $message['language']->language,
  );

  switch ($key) {

    case 'deliver turf':
      $df_firstname = $params['s-fn'];
      $df_lastname = $params['s-ln'];
      $df_semail = $params['s-email'];
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
      $message['subject'] = t('Neighborhood Leader Materials - @grp County', 
        array('@grp' => $params['county']), $options);
      $message['body'][] = $params['message'];
      $message['body'][] = t('<em>@fname @lname [@semail] sent you this message from NLP services.</em>', 
        array(
          '@fname' => $df_firstname,
          '@lname' => $df_lastname,
          '@semail' => $df_semail,),$options);
      break;
    
    case 'account_notify':
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
      $message['subject'] = $params['subject'];
      $message['body'][] = $params['message'];
      break;
    
    case 'no login':
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
      $message['subject'] = t('Neighborhood Leader Notification - @grp County', 
        array('@grp' => $params['county']), $options);
      $message['body'][] = $params['message'];
      $message['body'][] = t('<em>The NLP services admin sent you this message.</em>');
      break;
    
    case 'no report':
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
      $message['subject'] = t('Neighborhood Leader Notification - @grp County', 
        array('@grp' => $params['county']), $options);
      $message['body'][] = $params['message'];
      $message['body'][] = t('<em>The NLP services admin sent you this message.</em>');
      break;
    
    case 'notify bounce':
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
      $message['subject'] = t('Neighborhood Leader Notification - NL email bounce', 
        $options);
      $message['body'][] = $params['message'];
      $message['body'][] = t('<br><em>The NLP services admin sent you this message.</em>');
      break;
    
  }
}