<?php
$CONFIG_DIR_PATH = './config';
$CONFIG_FILE_FULL_NAME = './config/bots.cfg';
$SUCCESS = false;
processNewBot();
if(isset($_GET['bot'])) { //Processing a request from Telegram
	$config = readConfig();	
	$APIToken = $_GET['bot'];
	if(hash('sha512',$APIToken) == $config->bots[0]->APIToken) {		
		$entityBody = file_get_contents('php://input');	
		$update = json_decode($entityBody);
		processUpdate($update, $APIToken);
	}
}
function sendMessage ($update, $text, $apiToken) {
	$chat = $update->message->chat->id;
	$params = json_encode(array('chat_id' => $chat, 'text' => $text));
	sendRequest($params, 'sendMessage', $apiToken);
}
function checkInfrastructure() { //Check if there is the config directory in the script directory
	$configDirExists = is_dir($GLOBALS['CONFIG_DIR_PATH']);
	$configFileExists = is_file($GLOBALS['CONFIG_FILE_FULL_NAME']);
	if(!$configDirExists)
		return 1;
	if(!$configFileExists)
		return 2;	
	return 0;
}
function createInfrastructure($level) {
	$cfgDirCreated = false;
	$cfgFileCreated = false;
	//Directory
	if($level == 1)			
		$cfgDirCreated = mkdir($GLOBALS['CONFIG_DIR_PATH']);		
	else
		$cfgDirCreated = true;	
	//File	
	if($level == 1 || $level == 2) {
		$file = fopen($GLOBALS['CONFIG_FILE_FULL_NAME'], 'wb');
		if($file != false)
			$cfgFileCreated = fclose($file);
	}else
		$cfgFileCreated = true;
	
	if(!$cfgDirCreated)
		return 1;
	if(!$cfgFileCreated)
		return 2;
	return 0;
}
function readConfig() {
	$configText = file_get_contents($GLOBALS['CONFIG_FILE_FULL_NAME']);
	$config = json_decode($configText);
	return $config;
}
function writeConfig($config) {	
	file_put_contents($GLOBALS['CONFIG_FILE_FULL_NAME'], json_encode($config));
}
function processNewBot() {
	$name = null;
	$APIToken = null;
	if(isset($_POST['name']))
		$name = $_POST['name'];
	if(isset($_POST['APIToken']))
		$APIToken = $_POST['APIToken'];
	if(($name != false )&& ($APIToken != null)) {
	$bots = array(array('name' => $name , 'APIToken' => $APIToken));	
		$result = setUpBot($name, $APIToken);
		if($result)
			$GLOBALS['SUCCESS'] = true;
	}
}
function sendRequest($json, $methodName, $apiToken) {	
	$url = 'https://api.telegram.org/bot'.$apiToken.'/'.$methodName;
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/json\r\n",
			'method'  => 'POST',
			'content' => $json
		)
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) { /* Handle error */ }
	return $result;
}
function setUpBot($name, $apiToken) {
	$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?bot='.$apiToken;	
	$params = json_encode(array('url'=>$url));	
	$response = json_decode(sendRequest($params, 'setWebhook', $apiToken));	
	$conf = new Configuration();
	$conf->bots = array(array('name' => $name, 'APIToken' => hash('sha512', $apiToken)));
	writeConfig($conf);
	return $response->ok;
}
//UPDATE PROCESSING ***EDIT HERE***
function processUpdate($update, $APIToken) {
	$hash = hash('md5', $update->message->text);
	sendMessage($update, 'Your message MD5 hash is: ['.$hash.'].', $APIToken);	
}
class Configuration 
{
	public $bots;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Your bots</title>
	</head>
	<body>
		<form action="" method="POST">
			Name: <input type="text" name="name" />
			API Token: <input type="text" name="APIToken" />
			<input type="submit" value="Link" />
		</form>
		<div>
			<?php
				if($SUCCESS)
					echo '<h1>Your bot has been set up successfully!</h1>';
			?>
			<h1>Your bot:</h1>
			<?php
				$C =  readConfig();
				echo $C->bots[0]->name;
			?>
		</div>
	</body>
</html>