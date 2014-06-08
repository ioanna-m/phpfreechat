<?php

include_once 'container/channels.php';
include_once 'container/users.php';

class Container_messages {
  
  /**
   * cid : recipient
   * uid : sender
   * msg : message to send
   */
  static public function postMsgToChannel($cid, $uid, $body, $type = 'msg') {

	
	//Detect Links
	$body = Container_messages::replaceLinks($body);
	
	//Detect Emoticons
	$body = Container_messages::addEmoticons($body);

    $mid = self::generateMid($cid);
    $msg = array(
      'id'        => $mid,
      'sender'    => $uid,
      'recipient' => 'channel|'.$cid,
      'type'      => $type,
      'body'      => $body,
      'timestamp' => time(),
    );

	
    // json encode msg before storing
    $msg = json_encode($msg);

    // search users subscribed to the channel
    foreach (Container_channels::getChannelUsers($cid) as $subuid) {
      // post this message on each users subscribed on the channel
      // /users/:uid/pending/
      if ($subuid != $uid) { // don't post message to the sender
        $umdir = Container_users::getDir().'/'.$subuid.'/messages';
        file_put_contents($umdir.'/'.$mid, $msg);
      }
    }
    
    return $msg;
  }
  
  static public function addEmoticons($body){
	$emoticons = array();
	$emoticons[":("] = "face-sad.png";
	$emoticons[":)"] = "face-smile.png";
	$emoticons[":D"] = "face-smile-big.png";
	$emoticons[":o"] = "face-surprise.png";
	$emoticons[";)"] = "face-wink.png";
	$emoticons[":*"] = "face-kiss.png";
	$emoticons[":|"] = "face-plain.png";
	$emoticons[":'("] = "face-crying.png";
	  
	foreach ($emoticons as $face => $img) {
		$body = str_replace($face,'<img src="../client/emotes/'.$img.'" />',$body);
	}
	
	return $body;
	  
  }
  
  static public function replaceLinks($body){
	
	@preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $body, $findings, PREG_PATTERN_ORDER);
	
	if((isset($findings))&&(is_array($findings))){
		$i=0;
		foreach ($findings[0] as $key => $value) {
			$i++;
						
			$body = str_replace($value,'<a href="http://'.$value.'" target="_blank">Link ['.$i.']</a>',$body);
		}
	}
	return $body;
	
  }
  
  /**
   * Generates a unique message id
   */
  static public function generateMid($cid) {
    $mid = microtime(true).'.'.uniqid('', true);
    return $mid;
  }

}