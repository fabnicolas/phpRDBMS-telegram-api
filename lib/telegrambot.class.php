<?php
class TelegramBot{
	var $base_bot_url;
	var $token=null;
	
	function __construct($token){
		$this->token=$token;
		$this->base_bot_url="https://api.telegram.org/bot".$token."/";
	}
	
	private function callTelegramAPI($command, $data){
		return file_get_contents(($this->base_bot_url).$command."?".http_build_query($data));
	}
	
	function sendMessage($text, $chat_id){
		$this->callTelegramAPI("sendMessage",['text'=>$text,'chat_id'=>$chat_id]);
	}
	
	function sendPhoto($image_url, $chat_id, $callback=null){
		$url = ($this->base_bot_url)."sendPhoto?chat_id=".$chat_id;
		$post_fields = array('chat_id' => $chat_id, 'photo' => new CURLFile(realpath($image_url)));
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
		$output = curl_exec($ch);
		if($callback!=null) $callback();
	}
	
	function getUpdates($offset=0,$limit=100,$timeout=0){
		return $this->callTelegramAPI("getUpdates",['offset'=>$offset,'limit'=>$limit,'timeout'=>$timeout]);
	}

	function parseUpdates($updates,$chat_id=null){
		$result = json_decode($updates);
		if($result->ok){
			if(array_key_exists('result', $result)){
				$updates=$result->result;
				$parsed_update=array();
				foreach($updates as $key=>$update){
					$message=$update->message;
	
					// Reconstruct user message in a more human readable way with only necessary infos
					$parsed_message=array();
					$parsed_message['update_id']=$update->update_id;
					$parsed_message['message_id']=$message->message_id;
					$parsed_message['from_id']=$message->from->id;
					$parsed_message['from_username']=$message->from->username;
					$parsed_message['date']=$message->date;
					$parsed_message['text']=$message->text;

					if($chat_id!=null && $chat_id==$parsed_message['from_id']){
						// As PHP is not strongly typed, we can just return straight away a single element.
						return $parsed_message;
					}else{
						// Save the parsed message into a list.
						$parsed_update[]=$parsed_message;
					}
				}
				return $parsed_update;
			}else return false;
		}else return false;
	}

	function getParsedUpdates($chat_id=null,$offset=0,$limit=100,$timeout=0){
		return $this->parseUpdates($this->getUpdates($offset,$limit,$timeout),$chat_id);
	}
}
?>