<?php
/**
 * Name:  voteredb_blocks.php     V4.0  12/6/17
 * @file
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_coordinator_block
 * 
 * Builds the content of the NLP navigation block.
 * 
 * @param type $cb_delta - block id.
 * @return - renderable content for the block.
 */
function voterdb_coordinator_block($cb_delta) {
  if ($cb_delta == 'nlp_coordinator_welcome') {
    $cb_uid = $GLOBALS['user']->uid;
    if($cb_uid == 0) {
      // The user is unauthenticated.
      $cb_body = '<p>If you are a <span style="color:#FF0000">Neighborhood Leader Coordinator </span>'
        . 'you have to use the login above to gain access to any of the admin pages for NLP.</p>';
    } else {
      // We have an authenticated user.
      $cb_usr = user_load($cb_uid);
      // Coordinator's first name.
      $cb_fna = field_get_items('user', $cb_usr, 'field_firstname' , NULL);
      $cb_fn = $cb_fna[0]['value'];
      $cb_coa = field_get_items('user', $cb_usr, 'field_county' , NULL);
      $cb_co = $cb_coa[0]['value'];
      $cb_body =  '<p>Welcome back, '.$cb_fn
          . '<br>Click the link below to navigate to the NLP admin page.</p>';
      $cb_body .=  '<p><a href="/nlpadmin?County='
          .$cb_co.'">'.$cb_co.' County admin page</a></p>';
      }
    $result = array(
      '#markup' => $cb_body,
    );
  }
  return $result;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_block_info
 * 
 * Declare the existence of the NLP navigation block.  
 * 
 * @return block info array.
 */
function voterdb_block_info() {
  $bi_blocks['nlp_coordinator_welcome'] = array(
    // info: The name of the block.
    'info' => t('NLP Coordinator'),
    'status' => TRUE,
    'region' => 'sidebar_first',
    'visibility' => BLOCK_VISIBILITY_LISTED,
    'pages' => '<front>',
    'weight' => -9999,
  );
  return $bi_blocks;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_block_view
 * 
 * Fill this instance of the navigation block with the necessary links.
 * 
 * @param type $bv_delta - block identifier.
 * @return type
 */
function voterdb_block_view($bv_delta = '') {
  // The $delta parameter tells us which block is being requested.
  switch ($bv_delta) {
    case 'nlp_coordinator_welcome':
      $block['subject'] = t("NLP Coordinator");
      $block['content'] = voterdb_coordinator_block($bv_delta);
      break;
  }
  return $block;
}