<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpButton.
 */
/*
 * Name:  voterdb_class_button.php               V4.1 5/28/18
 */

namespace Drupal\voterdb;

class NlpButton {
  const BUTTONSTYLE = '
    input[type="submit"] {
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
    .button {
      border: 1px solid #000000;
      display: block;
      width: 175px;
      height: 20px;
      padding: 0px;
      background-color: #dddddd;
      text-align: center;
      border-radius: 5px;
      color: black;
      }
    p.narrow {margin:6px 0px; padding:0px; font-size:medium;} 
    #hint1 { position: relative; }
    #hint1 a span { display: none; color: #0033ff; }
    #hint1 a:hover span { display: block; position: absolute; width: 300px;
      background-color: #ffffff; left: 200px; top: -50px; color: #0033ff; 
      padding: 5px; border: 2px solid #0033ff; border-radius:5px;}
    #hint2 span {display: none; color: #0033ff;}
    ';
  
    public function setStyle() {
      $en_button_style = self::BUTTONSTYLE;
      drupal_add_css($en_button_style, array('type' => 'inline'));
    return;
  }
  
  
}