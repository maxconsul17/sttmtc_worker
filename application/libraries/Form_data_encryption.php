<?php  
/**
* @author justin (with e)
* @copyright 2018
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Form_data_encryption
{
  
  function __construct()
  {
    
  }


  function encryptionSetup(){
    return array(
      "f_start_idx"       => 2,
      "isCodeInUpperCase" => true
    );
  }

  function encryptedCode(){
    extract($this->encryptionSetup());
    $encrypted_code_arr = array();
    $f_end_idx = $f_start_idx + 5;

    if($f_end_idx > 9) die("Error Email Library Setup : Your f_start_idx is invalid!..");
    
    $char_list = array(" ", "!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "<", "=", ">", "?", "@", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "[", "\\", "]", "^", "_", "`", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "{", "|", "}", "~");
    $code_list  = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F");

    $char_index = 0;
    for($f_idx = $f_start_idx; $f_idx <= $f_end_idx; $f_idx++){
      for ($s_idx=0; $s_idx <=15 ; $s_idx++){ 
        $first_code  = $f_idx;
        $second_code = ($isCodeInUpperCase) ? $code_list[$s_idx] : strtolower($code_list[$s_idx]);
        $key_code    = $first_code . $second_code;
        
        $isAddToList = true;
        if($f_idx == $f_end_idx && $s_idx == 15) $isAddToList = false;

        if($isAddToList) $encrypted_code_arr[$key_code] = $char_list[$char_index];
        $char_index += 1;
      }
    }

    return $encrypted_code_arr;
  }

  function findCharEncryptCode($char, $encryptedCode){
    $encrypt = "";

    foreach ($encryptedCode as $code => $value) {
      if($char == $value){
        $encrypt = $code;
        break;
      }
    }

    return $encrypt;
  }

  function encryptString($string){
    $encrypt_string = "";
    $encryptedCode  = $this->encryptedCode();
    $str_length     = strlen($string);

    $start_i = 0;
    for ($i=$str_length; $i >= 0; $i--) { 
     
      if($i > 0){
        
        $int_length = (-($i - 1) == 0) ? 1 : -($i - 1);
        $char = substr($string, $start_i, $int_length);
        $encrypt_string .= $this->findCharEncryptCode($char, $encryptedCode);
        
        $start_i += 1;
      }
    }
    
    return $encrypt_string;
  }

  function findCharDecryptCode($code, $encryptedCode){
    $decrypt = "";

    foreach ($encryptedCode as $key => $value) {
      if($code == $key){
        $decrypt = $value;
        break;
      }
    }

    return $decrypt;
  }

  function decryptString($string){
    $decrypt_string = "";
    $encryptedCode  = $this->encryptedCode();
    $str_length     = strlen($string);

    $start_i = 0;
    for ($i=$str_length; $i >= 0; $i-=2) { 
     
      if($i > 0){
        
        $int_length = (-($i - 2) == 0) ? 2 : -($i - 2);
        $code = substr($string, $start_i, $int_length);
        $decrypt_string .= $this->findCharDecryptCode($code, $encryptedCode);
        
        $start_i += 2;
      }
    }

    return $decrypt_string;
  }

}


?>