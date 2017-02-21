<?php
define('CONFIG_DIR_NOT_FOUND', 1);
define('CONFIG_FILE_NOT_FOUND', 1<<1);
define('HTTPS_NOT_ENABLED', 1<<2);
define('CONFIG_DIR_PATH', __DIR__.'/config');
define('CONFIG_FILE_FULL_NAME', CONFIG_DIR_PATH.'/bots.cfg');
$ic = createInfrastructure(checkInfrastructure());
$CREATED_SUCCESSFULLY = false;
if(isset($_GET['bot'])) { //Processing a request from Telegram
	$config = readConfig();	
	$APIToken = $_GET['bot'];
	if(hash('sha512',$APIToken) == $config->bots[0]->APIToken) {		
		$entityBody = file_get_contents('php://input');	
		$update = json_decode($entityBody);
		processUpdate($update, $APIToken);
	}
}
if(isset($_POST['name'], $_POST['APIToken'])) { //Add a new bot
	addNewBot($_POST['name'], $_POST['APIToken']);
}
function checkInfrastructure() { //Check if there is the config directory in the script directory
	$code = 0; //Everything is OK
	$configDirExists = is_dir(CONFIG_DIR_PATH);
	$configFileExists = is_file(CONFIG_FILE_FULL_NAME);
	if(!$configDirExists)
		$code = $code | CONFIG_DIR_NOT_FOUND;
	if(!$configFileExists)
		$code = $code | CONFIG_FILE_NOT_FOUND;
	if(!isset($_SERVER['HTTPS'])|$_SERVER['HTTPS']=='off')
		$code = $code | HTTPS_NOT_ENABLED;
	return $code;
}
function createInfrastructure($code) {	
	//Directory
	if(($code & CONFIG_DIR_NOT_FOUND) == CONFIG_DIR_NOT_FOUND) {		
		if(mkdir(CONFIG_DIR_PATH))
			$code = $code & ~CONFIG_DIR_NOT_FOUND;				
	}		
	//File	
	if(($code & CONFIG_FILE_NOT_FOUND) == CONFIG_FILE_NOT_FOUND) {
		$file = fopen(CONFIG_FILE_FULL_NAME, 'wb');
		if($file != false)
			$code = $code & ~CONFIG_FILE_NOT_FOUND;	
	}	
	return $code;
}
function readConfig() {
	$configText = file_get_contents(CONFIG_FILE_FULL_NAME);
	$config = json_decode($configText);
	//Validate config
	$valid = is_object($config) && property_exists($config, 'bots');
	if($valid)
		return $config;
	else
		return false;
}
function writeConfig($config) {	
	file_put_contents(CONFIG_FILE_FULL_NAME, json_encode($config));
}
function addNewBot($name, $APIToken) {	
	$bots = array(array('name' => $name , 'APIToken' => $APIToken));	
	$result = setUpBot($name, $APIToken);
	if($result)
		$GLOBALS['CREATED_SUCCESSFULLY'] = true;	
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
	if(!isset($_SERVER['HTTPS'])|$_SERVER['HTTPS']=='off')
		return false;   
	$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?bot='.$apiToken;	
	$params = json_encode(array('url'=>$url));	
	$response = json_decode(sendRequest($params, 'setWebhook', $apiToken));	
	$conf = new Configuration();
	$conf->bots = array(array('name' => $name, 'APIToken' => hash('sha512', $apiToken)));
	writeConfig($conf);
	return $response->ok;
}
function sendMessage ($update, $text, $apiToken) {
	$chat = $update->message->chat->id;
	$params = json_encode(array('chat_id' => $chat, 'text' => $text));
	sendRequest($params, 'sendMessage', $apiToken);
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
		<style>
			#botInfoForm {
				padding: 2em;
			}
			#botInfoForm input[type=text] {
				width: 100%;
			}
			.notificationBox {
				padding: 2em;
			}
		</style>
	</head>
	<body>
		<?php
			if($ic > 0) {
				echo '<p><b>Your server configuration is not compatible with this script.</b></p>';
				if(($ic & CONFIG_DIR_NOT_FOUND) == CONFIG_DIR_NOT_FOUND)
					echo '<p>The script could not create the config directory.</p>';
				if(($ic & CONFIG_FILE_NOT_FOUND) == CONFIG_FILE_NOT_FOUND)
					echo '<p>The script could not create the config file.</p>';
				if(($ic & HTTPS_NOT_ENABLED) == HTTPS_NOT_ENABLED)
					echo '<p>HTTPS is not enabled. This script cannot work via simple http.</p>';				
			}
		?>
		<?php if($ic === 0): ?>
		<form action="" method="POST" id="botInfoForm">
			<p>Name: <input type="text" name="name" /></p>
			<p>API Token: <input type="text" name="APIToken"/></p>
			<input type="submit" value="Link" />
		</form>
		<div class="notificationBox">
			<?php
				if($CREATED_SUCCESSFULLY)
					echo '<h1>Your bot has been set up successfully!</h1>';
			?>			
			<?php
				$config =  readConfig();
				if(!($config === false)) {
					$botName = htmlentities($config->bots[0]->name);
					echo "<h1>Your bot:</h1>";
					echo "<p>{$botName}</p>";
				}
			?>
		</div>
		<?php endif ?>
	</body>
</html>
