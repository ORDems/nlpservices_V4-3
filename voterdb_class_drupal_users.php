<?php
/*
 * Name: voterdb_class_drupal_user.php   V4.3 8/21/18
 *
 */
namespace Drupal\voterdb;
require_once "voterdb_debug.php";

class NlpDrupalUser {
  
  const NLPROLE = 'neighborhood leader';
  
  public $nlpRoles = array(
    'nl' => NLP_LEADER_ROLE,
    'co' => NLP_COORDINATOR_ROLE,
    'admin' => NLP_ADMIN_ROLE,
    'authenticated' => 'authenticated user',
  );
  
  private $searchFields = array(
    'mcid'=>'field_mcid',
    'firstName'=>'field_firstname',
    'lastName'=>'field_lastname',
    'userName'=>'name',
    'email'=>'mail',
  );
  
  private function extractUserInfo($accountObj) {
    $uid = $accountObj->uid;
    $firstName = $accountObj->field_firstname;
    $lastName = $accountObj->field_lastname;
    $phone = $accountObj->field_phone;
    $mcid = $accountObj->field_mcid;
    $county = $accountObj->field_county;
    $sharedEmail = $accountObj->field_shared_email;
    $user['email'] = $accountObj->mail;
    $user['userName'] = $accountObj->name;
    $user['uid'] = $uid;
    $user['firstName'] = (empty($firstName))?NULL:$firstName['und'][0]['value'];
    $user['lastName'] = (empty($lastName))?NULL:$lastName['und'][0]['value'];
    $user['phone'] = (empty($phone))?NULL:$phone['und'][0]['value'];
    $user['mcid'] = (empty($mcid))?NULL:$mcid['und'][0]['value'];
    $user['county'] = (empty($county))?NULL:$county['und'][0]['value'];
    $user['sharedEmail'] = (empty($sharedEmail))?NULL:$sharedEmail['und'][0]['value'];
    return $user;
  }
  
  public function getCurrentUser() {
    $accountObj = user_uid_optional_load();
    $user = $this->extractUserInfo($accountObj);
    $user['roles'] = $accountObj->roles;
  return $user;
  }
  
  public function getUsers($queryObj,$county) {
    $queryObj->entityCondition('entity_type', 'user')
      ->fieldCondition('field_county', 'value', $county)
      ->addMetaData('account', user_load(1));       
    $result = $queryObj->execute();
    $userArray = array();
    if(!empty($result)) {
      foreach ($result['user'] as $countyUserObj) {
        $uid = $countyUserObj->uid;
        $accountObj = user_load($uid);
        $userArray[$uid] = $this->extractUserInfo($accountObj);
        $userArray[$uid]['roles'] = $accountObj->roles;
      }
    }
    return $userArray;
  }
  
  
  public function searchUsers($queryObj,$field,$value) {
    if(empty($this->searchFields[$field])) {return NULL;}
    $drupalField = $this->searchFields[$field];
    $queryObj->entityCondition('entity_type', 'user')
      ->fieldCondition($drupalField, 'value', $value,'CONTAINS')
      ->addMetaData('account', user_load(1));       
    $result = $queryObj->execute();
    $userArray = array();
    if(!empty($result)) {
      foreach ($result['user'] as $countyUserObj) {
        $uid = $countyUserObj->uid;
        $accountObj = user_load($uid);
        $userArray[$uid] = $this->extractUserInfo($accountObj);
        $userArray[$uid]['roles'] = $accountObj->roles;
      }
    }
    return $userArray;
  }
  
  public function getCounties($queryObj) {
    $queryObj->entityCondition('entity_type', 'user')
      ->addMetaData('account', user_load(1));       
    $result = $queryObj->execute();
    $counties = array();
    if(!empty($result)) {
      foreach ($result['user'] as $countyUserObj) {
        $uid = $countyUserObj->uid;
        $accountObj = user_load($uid);
        if(!empty($accountObj->field_county)) {
          $countyField = $accountObj->field_county; 
          $county = $countyField['und'][0]['value'];
          if(!empty($county)) {
            if(empty($counties['names'][$county])) {
              $counties['names'][$county] = $county;
              $counties['counts'][$county] = 1;
            } else {
              $counties['counts'][$county]++;
            }
          }
        }
      }
    }
    return $counties;
  }
  
  public function getUserByMcid($queryObj,$mcid) {
    $queryObj->entityCondition('entity_type', 'user')
      ->fieldCondition('field_mcid', 'value', $mcid)
      ->addMetaData('account', user_load(1));       
    $result = $queryObj->execute();
    if(empty($result)) {return NULL;}
    $countyUserObj = current($result['user']);
    $uid = $countyUserObj->uid;
    $accountObj = user_load($uid);
    $userArray = $this->extractUserInfo($accountObj);
    return $userArray;
  }
  
  public function getUserByName($userName) {
    $accountObj = user_load_by_name($userName);
    if(empty($accountObj)) {return NULL;}
    $user = $this->extractUserInfo($accountObj);
    return $user;
  }
  
  public function getUserByUid($uid) {
    $accountObj = user_load($uid);
    if(empty($accountObj)) {return NULL;}
    $user = $this->extractUserInfo($accountObj);
    return $user;
  }
  
  public function getUserByEmail($email) {
    $accountObj = user_load_by_mail($email);
    if(empty($accountObj)) {return NULL;}
    $user = $this->extractUserInfo($accountObj);
    return $user;
  }
  
  public function getUserObj($uid) {
    return user_load($uid);
  }
  
  
  public function createUser($account) {
    global $base_url;
    $notify = TRUE;
    $func = 'send';
    $email = $account['mail'];
    if(empty($email)) {
      $email = 'donotemail_'.$account['firstName'].'_'.$account['lastName'].'@nlpservices.org';
      $notify = FALSE;
    }
    $rawUserName = $account['firstName'].'.'.$account['lastName'];
    $lcUsrName = strtolower($rawUserName);
    $userName = preg_replace('/-|\s+|&#0*39;|\'/', '', $lcUsrName);
    $userByName = $this->getUserByName($userName);
    if(!empty($userByName)) {
      $userByName['status'] = 'exists';
      return $userByName;
    }
    $userByEmail = $this->getUserByEmail($email);
    $sharedEmail = NULL;
    if(!empty($userByEmail)) {
      $sharedEmail = $email;
      //$urlParts = parse_url($base_url);
      //$domain = preg_replace('/^www\./', '', $urlParts['host']);
      //$firstName = preg_replace('/-|\s+|&#0*39;|\'/', '',strtolower($account['firstName']));
      //$lastName = preg_replace('/-|\s+|&#0*39;|\'/', '',strtolower($account['lastName']));
      $email = 'shared_'.$email;
      $func = 'shared';
    }
    $rid = $this->getNlpRoleId();
    $edit = array(
      'name' => $userName, 
      'pass' => $account['magicWord'],
      'mail' => $email,
      'init' => $email, 
      'status' => 1, 
      'access' => REQUEST_TIME,
      'language' => 'en',
      'timezone' => 'America/Los_Angeles',
      'roles' => array(
        DRUPAL_AUTHENTICATED_RID => 'authenticated user',
        $rid => self::NLPROLE,
      ),
      'field_firstname' => array(LANGUAGE_NONE => array(array('value' => $account['firstName']))),
      'field_lastname' => array(LANGUAGE_NONE => array(array('value' => $account['lastName']))),
      'field_county' => array(LANGUAGE_NONE => array(array('value' => $account['county']))),
      'field_mcid' => array(LANGUAGE_NONE => array(array('value' => $account['mcid']))) , 
      'field_phone' => array(LANGUAGE_NONE => array(array('value' => $account['phone']))) , 
      'field_shared_email' => array(LANGUAGE_NONE => array(array('value' => $sharedEmail))) ,
      'func' => $func,
      'firstName' => $account['firstName'],
      'sharedEmail' => $sharedEmail,
    );
    $accountObj = user_save(NULL, $edit);
    if(empty($accountObj)) {
      return $user['status'] = 'error';
    }
    if($notify) {
      $params['func'] = 'account_notification';
      $params['subject'] = 'Neighborhood Leader account login: Voter contact reports';
      // Construct the message.
      $message = "<p>" . $account['firstName'] . ",</p>";
      $message .= '<p>Thanks for being a Neighborhood Leader in '.$account['county'].' County.&nbsp; ';
      $message .= 'An account has been created for you to report your contacts with the voters in your turf.</p>';
      'Please click this link and read the instructions if your are not already familiar with the program.  ' .
      'It is important that you return to this login to report the results of your attempts to contact the voters.&nbsp;   ' .
      $message .= '<p>Please click the link below to take you to login.&nbsp;  ';
      $message .= 'After login, you will see a link in the upper left corner. (<span style="color:red;">Get your walksheet:</span>).&nbsp;  ' .
              'Click that link and print the PDF file for your walksheet.&nbsp; ';
      $message .= 'Also, in the box for Instructions, there is a link for the current election.&nbsp;  '
              . 'Please click this link and read the instructions if your are not already familiar with the program.  </p>';
      $message .=  'In the upper right corner of the page you will see a link for "My account".&nbsp;  That link can be used to change your password if you like. ';
      $message .= '<p><a href="' . $base_url . '" target="_blank">Neighborhood Leader Login</a></p>';
      $message .= t('<p>' . 'Your login name is: @name ' . '</p>', array('@name' => $userName));
      $message .= t('<p>' . 'The password is: @pw ' . '</p>', array('@pw' => $account['magicWord']));
      $currentUser = $this->getCurrentUser();
      $df_thanks = '<p>Please contact me if you have any questions.<br>Thanks<br>@fname @lname<br>@phone<br>' .
              '<a href="mailto:@email?subject=NL%20Help%20Request">@email</a></p>';
      $currentUserEmail =  (!empty($currentUser['sharedEmail']))?$currentUser['sharedEmail']:$currentUser['email'];
      $message .= t($df_thanks, array(
          '@fname' => $currentUser['firstName'],
          '@lname' => $currentUser['lastName'],
          '@phone' => $currentUser['phone'],
          '@email' => $currentUserEmail,));
      $params['message'] = $message;
      $recipient = (empty($sharedEmail))?$email:$sharedEmail;
      $sender = 'NLP Admin<';
      $sender .= variable_get('voterdb_email', 'notifications@nlpservices.org');
      $sender .= '>';
      $language = language_default();
      $result = drupal_mail('voterdb', 'account_notify' , $recipient, $language, $params, $sender, TRUE);
      if ($result['result'] != TRUE) {
        $df_info = 'CO [' . $currentUser['firstName'] . ' ' . $currentUser['lastName'] . '] NL [' . $account['firstName'] .
          ' ' . $account['lastName'] . ' - ' . $account['email'] . ']';
        drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
        voterdb_login_tracking('turf', $account['county'], 'Account notification email failed', $df_info);
      }
    }
    $user = $this->extractUserInfo($accountObj); 
    $user['status'] = 'complete';
  return $user;
  }
  
  public function updateUser($editUpdate) {
    global $base_url;
    $uid = $editUpdate['uid'];
    $accountObj = user_load($uid);
    $sharedEmail = NULL;
    $edit = array();
    foreach ($editUpdate as $nlpKey => $nlpValue) {
      switch ($nlpKey) {
        case 'mcid':
          $edit['mcid'] = $nlpValue;
          break;
        case 'firstName':
          $edit['firstName'] = $nlpValue;
          break;
        case 'lastName':
          $edit['lastName'] = $nlpValue;
          break;
        case 'mail':
          $edit['mail'] = $nlpValue;
          $userByEmail = $this->getUserByEmail($nlpValue);
          if(!empty($userByEmail)) {
            $sharedEmail = $nlpValue;
            $edit['field_shared_email'] = array(LANGUAGE_NONE => array(array('value' => $nlpValue)));
          }
          break;
        case 'county':
          $edit['field_county'] = array(LANGUAGE_NONE => array(array('value' => $nlpValue)));
          break;
        case 'phone':
          $edit['field_phone'] = array(LANGUAGE_NONE => array(array('value' => $nlpValue)));
          break;
      }
    }
    if(!empty($sharedEmail)) {
      $urlParts = parse_url($base_url);
      $domain = preg_replace('/^www\./', '', $urlParts['host']);
      $firstName = (!empty($edit['firstName']))? preg_replace('/-|\s+|&#0*39;|\'/', '',strtolower($edit['firstName'])):'fn'.$uid;
      $lastName = (!empty($edit['lastName']))? preg_replace('/-|\s+|&#0*39;|\'/', '',strtolower($edit['lastName'])):'ln'.$uid;
      $edit['mail'] = 'shared_'.$firstName.'_'.$lastName.'@'.$domain;
    }
    $accountObj->uid = $editUpdate['uid'];
    $updatedUser = user_save($accountObj, $edit);
    return $updatedUser;
  }
  
  public function deleteUser($uid) {
    user_delete($uid);
  }
  
  public function getNlpRoleId() {
    $roles = user_roles();
    return array_search(self::NLPROLE, $roles);
  }
  
  public function getRoles() {
    $drupalRoles = user_roles();
    $roleIds = array_flip($drupalRoles);
    foreach ($this->nlpRoles as $drupalName) {
      if(!empty($roleIds[$drupalName])) {
        $roles[$roleIds[$drupalName]] = $drupalName;
      }
    }
    return $roles;
  }
  
  
  public function addUser($account) {
    if(empty($account['userName'])) {
      $userByName['status'] = 'no userName';
      return $userByName;
    }
    $userName = $account['userName'];
    $userByName = $this->getUserByName($userName);
    if(!empty($userByName)) {
      $userByName['status'] = 'exists';
      return $userByName;
    }
    $func = 'send';
    if(empty($account['email'])) {
      $userByName['status'] = 'no email';
      return $userByName;
    }
    
    $email = $account['email'];
    $sharedEmail = NULL;
    if(!empty($account['sharedEmail'])) {
      $sharedEmail = $account['sharedEmail'];
    }
    $userByEmail = $this->getUserByEmail($email);
    if(!empty($userByEmail)) {
      $sharedEmail = $email;
      $email = 'shared_'.$email;
      $func = 'shared';
    }
    $roles = $account['roles'];
    
    $edit = array(
      'name' => $userName, 
      'pass' => $account['magicWord'],
      'mail' => $email,
      'init' => $email, 
      'status' => 1, 
      'access' => REQUEST_TIME,
      'language' => 'en',
      'timezone' => 'America/Los_Angeles',
      'roles' => $roles,
      'field_firstname' => array(LANGUAGE_NONE => array(array('value' => $account['firstName']))),
      'field_lastname' => array(LANGUAGE_NONE => array(array('value' => $account['lastName']))),
      'field_county' => array(LANGUAGE_NONE => array(array('value' => $account['county']))),
      'field_mcid' => array(LANGUAGE_NONE => array(array('value' => $account['mcid']))) , 
      'field_phone' => array(LANGUAGE_NONE => array(array('value' => $account['phone']))) , 
      'field_shared_email' => array(LANGUAGE_NONE => array(array('value' => $sharedEmail))) ,
      'func' => $func,
      'firstName' => $account['firstName'],
      'sharedEmail' => $sharedEmail,
    );
    $accountObj = user_save(NULL, $edit);
    if(empty($accountObj)) {
      return $user['status'] = 'error';
    }
    $user = $this->extractUserInfo($accountObj); 
    $user['status'] = 'complete';
    return $user;
  }
  
  
}
