<?php
if(!empty($_SERVER["HTTP_USER_AGENT"])) {
    include dirname(__FILE__)."/html/head.html";
}
include dirname(__FILE__)."/connection.php";

include dirname(__FILE__)."/libs/XORCipher.php";

class ExploitPatch {
	public static function remove($data) {
		$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

		do {
				$old_data = $data;
				$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		}
		while ($old_data !== $data);

		return $data;
	}
	public static function charclean($string) {
		return preg_replace("/[^A-Za-z0-9 ]/", '', $string);
	}
	public static function numbercolon($string){
		return preg_replace("/[^0-9,-]/", '', $string);
	}
	public static function number($string){
		return preg_replace("/[^0-9]/", '', $string);
	}
}

class GeneratePass
{
	public static function GJP2fromPassword($pass) {
		return sha1($pass . "mI29fmAnxgTs");
	}

	public static function GJP2hash($pass) {
		return password_hash(self::GJP2fromPassword($pass), PASSWORD_DEFAULT);
	}

	public static function assignGJP2($accid, $pass) {
		include dirname(__FILE__)."/connection.php";

		$query = $db->prepare("UPDATE accounts SET gjp2 = :gjp2 WHERE accountID = :id");
		$query->execute(["gjp2" => self::GJP2hash($pass), ":id" => $accid]);
	}

	public static function attemptsFromIP() {
		include dirname(__FILE__)."/connection.php";
		$gs = new mainLib();
		$ip = $gs->getIP();
		$newtime = time() - (60*60);
		$query6 = $db->prepare("SELECT count(*) FROM actions WHERE type = '6' AND timestamp > :time AND value2 = :ip");
		$query6->execute([':time' => $newtime, ':ip' => $ip]);
		return $query6->fetchColumn();
	}

	public static function tooManyAttemptsFromIP() {
		return self::attemptsFromIP() > 7;
	}

	public static function logInvalidAttemptFromIP($accid) {
		include dirname(__FILE__)."/connection.php";
		$gs = new mainLib();
		$ip = $gs->getIP();
		$query6 = $db->prepare("INSERT INTO actions (type, value, timestamp, value2) VALUES 
													('6',:accid,:time,:ip)");
		$query6->execute([':accid' => $accid, ':time' => time(), ':ip' => $ip]);
	}

	public static function assignModIPs($accountID, $ip) {
		//this system is most likely going to be removed altogether soon
		include dirname(__FILE__)."/connection.php";
		$gs = new mainLib();
		$modipCategory = $gs->getMaxValuePermission($accountID, "modipCategory");
		if($modipCategory > 0){ //modIPs
			$query4 = $db->prepare("SELECT count(*) FROM modips WHERE accountID = :id");
			$query4->execute([':id' => $accountID]);
			if ($query4->fetchColumn() > 0) {
				$query6 = $db->prepare("UPDATE modips SET IP=:hostname, modipCategory=:modipCategory WHERE accountID=:id");
			}else{
				$query6 = $db->prepare("INSERT INTO modips (IP, accountID, isMod, modipCategory) VALUES (:hostname,:id,'1',:modipCategory)");
			}
			$query6->execute([':hostname' => $ip, ':id' => $accountID, ':modipCategory' => $modipCategory]);
		}
	}

	public static function isGJP2Valid($accid, $gjp2) {
		include dirname(__FILE__)."/connection.php";
		$gs = new mainLib();

		if(self::tooManyAttemptsFromIP()) return -1;

		$userInfo = $db->prepare("SELECT gjp2, isActive FROM accounts WHERE accountID = :accid");
		$userInfo->execute([':accid' => $accid]);
		if($userInfo->rowCount() == 0) return 0;

		$userInfo = $userInfo->fetch();
		if(!($userInfo['gjp2'])) return -2;

		if(password_verify($gjp2, $userInfo['gjp2'])) {
			self::assignModIPs($accid, $gs->getIP());
			return $userInfo['isActive'] ? 1 : -2;
		} else {
			self::logInvalidAttemptFromIP($accid);
			return 0;
		}
		
	}

	public static function isGJP2ValidUsrname($userName, $gjp2) {
		include dirname(__FILE__)."/connection.php";
		$query = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :userName");
		$query->execute([':userName' => $userName]);
		if($query->rowCount() == 0){
			return 0;
		}
		$result = $query->fetch();
		$accID = $result["accountID"];
		return self::isGJP2Valid($accID, $gjp2);
		
	}

	public static function isValid($accid, $pass) {
		include dirname(__FILE__)."/connection.php";
		$gs = new mainLib();

		if(self::tooManyAttemptsFromIP()) return -1;

		$query = $db->prepare("SELECT accountID, salt, password, isActive, gjp2 FROM accounts WHERE accountID = :accid");
		$query->execute([':accid' => $accid]);
		if($query->rowCount() == 0) return 0;
		
		$result = $query->fetch();
		if(password_verify($pass, $result["password"])){
			if(!$result["gjp2"]) self::assignGJP2($accid, $pass);
			self::assignModIPs($accid, $gs->getIP());
			return $result['isActive'] ? 1 : -2;
		} else {
			// Code to validate password hashes created prior to March 2017 has been removed.
			self::logInvalidAttemptFromIP($accid);
			return 0;
		}

	}
	public static function isValidUsrname($userName, $pass){
		include dirname(__FILE__)."/connection.php";
		$query = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :userName");
		$query->execute([':userName' => $userName]);
		if($query->rowCount() == 0){
			return 0;
		}
		$result = $query->fetch();
		$accID = $result["accountID"];
		return self::isValid($accID, $pass);
	}
}

class GJPCheck {
    
	public static function check($gjp, $accountID) {
		include dirname(__FILE__)."/connection.php";
		$ml = new mainLib();
		if($sessionGrants){
			$ip = $ml->getIP();
			$query = $db->prepare("SELECT count(*) FROM actions WHERE type = 16 AND value = :accountID AND value2 = :ip AND timestamp > :timestamp");
			$query->execute([':accountID' => $accountID, ':ip' => $ip, ':timestamp' => time() - 3600]);
			if($query->fetchColumn() > 0){
				return 1;
			}
		}
		$gjpdecode = str_replace("_","/",$gjp);
		$gjpdecode = str_replace("-","+",$gjpdecode);
		$gjpdecode = base64_decode($gjpdecode);
		$gjpdecode = XORCipher::cipher($gjpdecode,37526);
		$validationResult = GeneratePass::isValid($accountID, $gjpdecode);
		if($validationResult == 1 AND $sessionGrants){
			$ip = $ml->getIP();
			$query = $db->prepare("INSERT INTO actions (type, value, value2, timestamp) VALUES (16, :accountID, :ip, :timestamp)");
			$query->execute([':accountID' => $accountID, ':ip' => $ip, ':timestamp' => time()]);
		}
		return $validationResult;
	}

	public static function validateGJPOrDie($gjp, $accountID){
		if(self::check($gjp, $accountID) != 1)
			exit("-1");
	}

	public static function validateGJP2OrDie($gjp2, $accountID){
		if(GeneratePass::isGJP2Valid($accountID, $gjp2) != 1)
			exit("-1");
	}
    
	public static function getAccountIDOrDie(){
		
		if(empty($_POST['accountID'])) exit("-1");

		$accountID = ExploitPatch::remove($_POST["accountID"]);

		if(!empty($_POST['gjp'])) self::validateGJPOrDie($_POST['gjp'], $accountID);
		elseif(!empty($_POST['gjp2'])) self::validateGJP2OrDie($_POST['gjp2'], $accountID);
		else exit("-1");

		return $accountID;
	}

}

class mainLib {
	public function getAudioTrack($id) {
		$songs = [
            "Stereo Madness by ForeverBound",
			"Back on Track by DJVI",
			"Polargeist by Step",
			"Dry Out by DJVI",
			"Base after Base by DJVI",
			"Can't Let Go by DJVI",
			"Jumper by Waterflame",
			"Time Machine by Waterflame",
			"Cycles by DJVI",
			"xStep by DJVI",
			"Clutterfunk by Waterflame",
			"Theory of Everything by DJ Nate",
			"Electroman Adventures by Waterflame",
			"Club Step by DJ Nate",
			"Electrodynamix by DJ Nate",
			"Hexagon Force by Waterflame",
			"Blast Processing by Waterflame",
			"Theory of Everything 2 by DJ Nate",
			"Geometrical Dominator by Waterflame",
			"Deadlocked by F-777",
			"Fingerbang by MDK",
            "Dash by MDK",
			"The Seven Seas by F-777",
			"Viking Arena by F-777",
			"Airborne Robots by F-777",
			"Secret by RobTopGames",
			"Payload by Dex Arson",
			"Beast Mode by Dex Arson",
			"Machina by Dex Arson",
			"Years by Dex Arson",
			"Frontlines by Dex Arson",
			"Space Pirates by Waterflame",
			"Striker by Waterflame",
			"Embers by Dex Arson",
			"Round 1 by Dex Arson",
			"Monster Dance Off by F-777"
        ];
		if($id < 0 || $id >= count($songs))
			return "Unknown by DJVI";
		return $songs[$id];
	}
	public function getDifficulty($diff,$auto,$demon) {
		if($auto != 0) {
			return "Auto";
		}
        else if($demon != 0) {
			return "Demon";
		}
        else{
			switch($diff) {
				case 0:
					return "N/A";
				case 10:
					return "Easy";
				case 20:
					return "Normal";
				case 30:
					return "Hard";
				case 40:
					return "Harder";
				case 50:
					return "Insane";
				default:
					return "Unknown";
			}
		}
	}
	public function getDiffFromStars($stars) {
		$auto = 0;
		$demon = 0;
		switch($stars) {
			case 1:
				$diffname = "Auto";
				$diff = 50;
				$auto = 1;
				break;
			case 2:
				$diffname = "Easy";
				$diff = 10;
				break;
			case 3:
				$diffname = "Normal";
				$diff = 20;
				break;
			case 4:
			case 5:
				$diffname = "Hard";
				$diff = 30;
				break;
			case 6:
			case 7:
				$diffname = "Harder";
				$diff = 40;
				break;
			case 8:
			case 9:
				$diffname = "Insane";
				$diff = 50;
				break;
			case 10:
				$diffname = "Demon";
				$diff = 50;
				$demon = 1;
				break;
			default:
				$diffname = "N/A: " . $stars;
				$diff = 0;
				$demon = 0;
				break;
		}
		return array('diff' => $diff, 'auto' => $auto, 'demon' => $demon, 'name' => $diffname);
	}
	public function getLength($length) {
		switch($length){
			case 0:
				return "Tiny";
			case 1:
				return "Short";
			case 2:
				return "Medium";
			case 3:
				return "Long";
			case 4:
				return "XL";
			case 5:
				return "Plat.";
			default:
				return "Unk";
		}
	}
	public function getGameVersion($version) {
		if($version > 17) {
			return $version / 10;
		}
        else if($version == 11) {
			return "1.8";
		}
        else if($version == 10) {
			return "1.7";
		}
        else {
			$version--;
			return "1.$version";
		}
	}
	public function getDemonDiff($dmn) {
		switch($dmn) {
			case 3:
				return "Easy";
			case 4:
				return "Medium";
			case 5:
				return "Insane";
			case 6:
				return "Extreme";
			default:
				return "Hard";
		}
	}
	public function getDiffFromName($name) {
		$name = strtolower($name);
		$starAuto = 0;
		$starDemon = 0;
		switch ($name) {
			default:
				$starDifficulty = 0;
				break;
			case "easy":
				$starDifficulty = 10;
				break;
			case "normal":
				$starDifficulty = 20;
				break;
			case "hard":
				$starDifficulty = 30;
				break;
			case "harder":
				$starDifficulty = 40;
				break;
			case "insane":
				$starDifficulty = 50;
				break;
			case "auto":
				$starDifficulty = 50;
				$starAuto = 1;
				break;
			case "demon":
				$starDifficulty = 50;
				$starDemon = 1;
				break;
		}
		return array($starDifficulty, $starDemon, $starAuto);
	}
	public function getGauntletName($id){
		$gauntlets = ["Unknown", "Fire", "Ice", "Poison", "Shadow", "Lava", "Bonus", "Chaos", "Demon", "Time", "Crystal", "Magic", "Spike", "Monster", "Doom", "Death"];
		if($id < 0 || $id >= count($gauntlets))
			return $gauntlets[0];
		return $gauntlets[$id];
	}
	function makeTime($delta) { // luto?
		if ($delta < 31536000)
		{
			if ($delta < 2628000)
			{
				if ($delta < 604800)
				{
					if ($delta < 86400)
					{
						if ($delta < 3600)
						{
							if ($delta < 60)
							{
								return $delta." second".($delta == 1 ? "" : "s");
							}
							else
							{
                        					$rounded = floor($delta / 60);
								return $rounded." minute".($rounded == 1 ? "" : "s");
							}
						}
						else
						{
							$rounded = floor($delta / 3600);
							return $rounded." hour".($rounded == 1 ? "" : "s");
						}
					}
					else
					{
						$rounded = floor($delta / 86400);
						return $rounded." day".($rounded == 1 ? "" : "s");
					}
				}
				else
				{
					$rounded = floor($delta / 604800);
					return $rounded." week".($rounded == 1 ? "" : "s");
				}
			}
			else
			{
				$rounded = floor($delta / 2628000); 
				return $rounded." month".($rounded == 1 ? "" : "s");
			}
		}
		else
		{
			$rounded = floor($delta / 31536000);
			return $rounded." year".($rounded == 1 ? "" : "s");
		}
	}
	public function getIDFromPost(){
		include __DIR__ . "/../../config/security.php";
		include_once __DIR__ . "/exploitPatch.php";
		include_once __DIR__ . "/GJPCheck.php";

		if(!empty($_POST["udid"]) AND $_POST['gameVersion'] < 20 AND $unregisteredSubmissions) 
		{
			$id = ExploitPatch::remove($_POST["udid"]);
			if(is_numeric($id)) exit("-1");
		}
		elseif(!empty($_POST["accountID"]) AND $_POST["accountID"]!="0")
		{
			$id = GJPCheck::getAccountIDOrDie();
		}
		else
		{
			exit("-1");
		}
		return $id;
	}
	public function getUserID($extID, $userName = "Undefined") {
		include __DIR__ . "/connection.php";
		if(is_numeric($extID)){
			$register = 1;
		}else{
			$register = 0;
		}
		$query = $db->prepare("SELECT userID FROM users WHERE extID LIKE BINARY :id");
		$query->execute([':id' => $extID]);
		if ($query->rowCount() > 0) {
			$userID = $query->fetchColumn();
		} else {
			$query = $db->prepare("INSERT INTO users (isRegistered, extID, userName, lastPlayed)
			VALUES (:register, :id, :userName, :uploadDate)");

			$query->execute([':id' => $extID, ':register' => $register, ':userName' => $userName, ':uploadDate' => time()]);
			$userID = $db->lastInsertId();
		}
		return $userID;
	}
	public function getAccountName($accountID) {
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT userName FROM accounts WHERE accountID = :id");
		$query->execute([':id' => $accountID]);
		if ($query->rowCount() > 0) {
			$userName = $query->fetchColumn();
		} else {
			$userName = false;
		}
		return $userName;
	}
	public function getUserName($userID) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT userName FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		if ($query->rowCount() > 0) {
			$userName = $query->fetchColumn();
		} else {
			$userName = false;
		}
		return $userName;
	}
	public function getAccountIDFromName($userName) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :usr");
		$query->execute([':usr' => $userName]);
		if ($query->rowCount() > 0) {
			$accountID = $query->fetchColumn();
		} else {
			$accountID = 0;
		}
		return $accountID;
	}
	public function getExtID($userID) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT extID FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		if ($query->rowCount() > 0) {
			return $query->fetchColumn();
		}else{
			return 0;
		}
	}
	public function getUserString($userdata) {
		include __DIR__ . "/connection.php";
		/*$query = $db->prepare("SELECT userName, extID FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		$userdata = $query->fetch();*/
		$extID = is_numeric($userdata['extID']) ? $userdata['extID'] : 0;
		return "{userdata['userID']}:{userdata['userName']}:{extID}";
	}
	public function getSongString($song){
		include __DIR__ . "/connection.php";
		/*$query3=$db->prepare("SELECT ID,name,authorID,authorName,size,isDisabled,download FROM songs WHERE ID = :songid LIMIT 1");
		$query3->execute([':songid' => $songID]);*/
		if($song['ID'] == 0 || empty($song['ID'])){
			return false;
		}
		//$song = $query3->fetch();
		if($song["isDisabled"] == 1){
			return false;
		}
		$dl = $song["download"];
		if(strpos($dl, ':') !== false){
			$dl = urlencode($dl);
		}
		return "1~|~".$song["ID"]."~|~2~|~".str_replace("#", "", $song["name"])."~|~3~|~".$song["authorID"]."~|~4~|~".$song["authorName"]."~|~5~|~".$song["size"]."~|~6~|~~|~10~|~".$dl."~|~7~|~~|~8~|~1";
	}
	public function sendDiscordPM($receiver, $message){
		include __DIR__ . "/../../config/discord.php";
		if(!$discordEnabled){
			return false;
		}
		//findind the channel id
		$data = array("recipient_id" => $receiver);                                                                    
		$data_string = json_encode($data);
		$url = "https://discord.com/api/v8/users/@me/channels";
		//echo $url;
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = 'GMDprivateServer (https://github.com/Cvolton/GMDprivateServer, 1.0)';
		curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($crl, CURLOPT_POSTFIELDS, $data_string);
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Authorization: Bot '.$bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER,$headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		$responseDecode = json_decode($response, true);
		$channelID = $responseDecode["id"];
		//sending the msg
		$data = array("content" => $message);                                                                    
		$data_string = json_encode($data);
		$url = "https://discord.com/api/v8/channels/".$channelID."/messages";
		//echo $url;
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = 'GMDprivateServer (https://github.com/Cvolton/GMDprivateServer, 1.0)';
		curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($crl, CURLOPT_POSTFIELDS, $data_string);
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Authorization: Bot '.$bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER,$headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		return $response;
	}
	public function getDiscordAcc($discordID){
		include __DIR__ . "/../../config/discord.php";
		///getting discord acc info
		$url = "https://discord.com/api/v8/users/".$discordID;
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = 'GMDprivateServer (https://github.com/Cvolton/GMDprivateServer, 1.0)';
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Authorization: Bot '.$bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER,$headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		$userinfo = json_decode($response, true);
		//var_dump($userinfo);
		return $userinfo["username"] . "#" . $userinfo["discriminator"];
	}
	public function randomString($length = 6) {
		$randomString = openssl_random_pseudo_bytes($length);
		if($randomString == false){
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		$randomString = bin2hex($randomString);
		return $randomString;
	}
	public function getAccountsWithPermission($permission){
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT roleID FROM roles WHERE $permission = 1 ORDER BY priority DESC");
		$query->execute();
		$result = $query->fetchAll();
		$accountlist = array();
		foreach($result as &$role){
			$query = $db->prepare("SELECT accountID FROM roleassign WHERE roleID = :roleID");
			$query->execute([':roleID' => $role["roleID"]]);
			$accounts = $query->fetchAll();
			foreach($accounts as &$user){
				$accountlist[] = $user["accountID"];
			}
		}
		return $accountlist;
	}
	public function checkPermission($accountID, $permission){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		//isAdmin check
		$query = $db->prepare("SELECT isAdmin FROM accounts WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$isAdmin = $query->fetchColumn();
		if($isAdmin == 1){
			return 1;
		}
		
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT $permission FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){
				if($role[$permission] == 1){
					return true;
				}
				if($role[$permission] == 2){
					return false;
				}
			}
		}
		$query = $db->prepare("SELECT $permission FROM roles WHERE isDefault = 1");
		$query->execute();
		$permState = $query->fetchColumn();
		if($permState == 1){
			return true;
		}
		if($permState == 2){
			return false;
		}
		return false;
	}
	public function isCloudFlareIP($ip) {
    	$cf_ips = array(
	        '173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22'
	    );
	    foreach ($cf_ips as $cf_ip) {
	        if (ipInRange::ipv4_in_range($ip, $cf_ip)) {
	            return true;
	        }
	    }
	    return false;
	}
	public function getIP(){
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isCloudFlareIP($_SERVER['REMOTE_ADDR'])) //CLOUDFLARE REVERSE PROXY SUPPORT
  			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ipInRange::ipv4_in_range($_SERVER['REMOTE_ADDR'], '127.0.0.0/8')) //LOCALHOST REVERSE PROXY SUPPORT (7m.pl)
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		return $_SERVER['REMOTE_ADDR'];
	}
	public function checkModIPPermission($permission){
		include __DIR__ . "/connection.php";
		$ip = $this->getIP();
		$query=$db->prepare("SELECT modipCategory FROM modips WHERE IP = :ip");
		$query->execute([':ip' => $ip]);
		$categoryID = $query->fetchColumn();
		
		$query=$db->prepare("SELECT $permission FROM modipperms WHERE categoryID = :id");
		$query->execute([':id' => $categoryID]);
		$permState = $query->fetchColumn();
		
		if($permState == 1){
			return true;
		}
		if($permState == 2){
			return false;
		}
		return false;
	}
	public function getFriends($accountID){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$friendsarray = array();
		$query = "SELECT person1,person2 FROM friendships WHERE person1 = :accountID OR person2 = :accountID"; //selecting friendships
		$query = $db->prepare($query);
		$query->execute([':accountID' => $accountID]);
		$result = $query->fetchAll();//getting friends
		if($query->rowCount() == 0){
			return array();
		}
		else
		{//oh so you actually have some friends kden
			foreach ($result as &$friendship) {
				$person = $friendship["person1"];
				if($friendship["person1"] == $accountID){
					$person = $friendship["person2"];
				}
				$friendsarray[] = $person;
			}
		}
		return $friendsarray;
	}
	public function isFriends($accountID, $targetAccountID) {
		if(!is_numeric($accountID) || !is_numeric($targetAccountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT count(*) FROM friendships WHERE person1 = :accountID AND person2 = :targetAccountID OR person1 = :targetAccountID AND person2 = :accountID");
		$query->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		return $query->fetchColumn() > 0;
	}
	public function getMaxValuePermission($accountID, $permission){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$maxvalue = 0;
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT $permission FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){ 
				if($role[$permission] > $maxvalue){
					$maxvalue = $role[$permission];
				}
			}
		}
		return $maxvalue;
	}
	public function getAccountCommentColor($accountID){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT commentColor FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){
				if($role["commentColor"] != "000,000,000"){
					return $role["commentColor"];
				}
			}
		}
		$query = $db->prepare("SELECT commentColor FROM roles WHERE isDefault = 1");
		$query->execute();
		if($query->rowCount() > 0)
			return $query->fetchColumn();
		return "255,255,255";
	}
	public function rateLevel($accountID, $levelID, $stars, $difficulty, $auto, $demon){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		//lets assume the perms check is done properly before
		$query = "UPDATE levels SET starDemon=:demon, starAuto=:auto, starDifficulty=:diff, starStars=:stars, rateDate=:now WHERE levelID=:levelID";
		$query = $db->prepare($query);	
		$query->execute([':demon' => $demon, ':auto' => $auto, ':diff' => $difficulty, ':stars' => $stars, ':levelID'=>$levelID, ':now' => time()]);
		
		$query = $db->prepare("INSERT INTO modactions (type, value, value2, value3, timestamp, account) VALUES ('1', :value, :value2, :levelID, :timestamp, :id)");
		$query->execute([':value' => $this->getDiffFromStars($stars)["name"], ':timestamp' => time(), ':id' => $accountID, ':value2' => $stars, ':levelID' => $levelID]);
		
		
	}
	public function featureLevel($accountID, $levelID, $feature){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = "UPDATE levels SET starFeatured=:feature, rateDate=:now WHERE levelID=:levelID";
		$query = $db->prepare($query);	
		$query->execute([':feature' => $feature, ':levelID'=>$levelID, ':now' => time()]);
		$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('2', :value, :levelID, :timestamp, :id)");
		$query->execute([':value' => $feature, ':timestamp' => time(), ':id' => $accountID, ':levelID' => $levelID]);
	}
	public function verifyCoinsLevel($accountID, $levelID, $coins){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = "UPDATE levels SET starCoins=:coins WHERE levelID=:levelID";
		$query = $db->prepare($query);	
		$query->execute([':coins' => $coins, ':levelID'=>$levelID]);
		
		$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('3', :value, :levelID, :timestamp, :id)");
		$query->execute([':value' => $coins, ':timestamp' => time(), ':id' => $accountID, ':levelID' => $levelID]);
	}
	public function songReupload($url, $name = '', $authorName = '', $publisherID = 0){
		require __DIR__ . "/../../incl/lib/connection.php";
		require_once __DIR__ . "/../../incl/lib/exploitPatch.php";
		$song = str_replace("www.dropbox.com","dl.dropboxusercontent.com",$url);
		if (filter_var($song, FILTER_VALIDATE_URL) == TRUE && substr($song, 0, 4) == "http" and !preg_match("|soundcloud.com|", $song) and !preg_match("|youtube.|", $song) and !preg_match("|drive.google|", $song)) {
			$song = str_replace(["?dl=0","?dl=1"],"",$song); //dropbox get req patch
			$song = trim($song);
			//count test
			$query = $db->prepare("SELECT count(*) FROM songs WHERE download = :download");
			$query->execute([':download' => $song]);	
			$count = $query->fetchColumn();
			if($count != 0) return "-3";
			//name
			if($name == '') {
                $name = ExploitPatch::remove(urldecode(str_replace([".mp3",".webm",".mp4",".wav"], "", basename($song))));
                //NoSiteBrand
                $name = str_replace("(musmore.com", "", $name);
                $name = str_replace("X2Download.com_-_", "", $name);
                $name = str_replace("yt5s.com_-_", "", $name);
                $name = str_replace("(Byfet.com", "", $name);
                $name = str_replace("Y2Mate.is_-_", "", $name);
                $name = str_replace("ytmp3free.cc_", "", $name);
                $name = str_replace("_[mp3pulse.ru]", "", $name);
                $name = str_replace("y2mate.com_-_", "", $name);
                $name = str_replace("_www.lightaudio.ru", "", $name);
                $name = str_replace("- wap.kengu.ru", "", $name);
                $name = str_replace("_(audiohunter.ru", "", $name);
                $name = str_replace("_[gidmp3.ru]", "", $name);
                $name = str_replace("_(EEMUSIC.ru", "", $name);
                $name = str_replace("[mp3can.ru]", "", $name);
                $name = str_replace("www.hotplayer.ru", "", $name);
                $name = str_replace("_(AxeMusic.ru)", "", $name);
                $name = str_replace("_(KillAudio.ru", "", $name);
                $name = str_replace("_(AndroSound.ru", "", $name);
                $name = str_replace("_(OOSOUND.RU", "", $name);
                $name = str_replace("_(Gybka.com", "", $name);
                $name = str_replace("_(mp3zvon.com", "", $name);
                $name = str_replace("_(mp3IQ.net", "", $name);
                $name = str_replace("-www_muzonov_net", "", $name);
                $name = str_replace("dfsfsdfsdf", "", $name);
    		}
    		//author
    		$author ="\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
			if($authorName == '') $author .= str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
			else $author .= "By: ".$authorName;
    		$author.=     "\nReuploaded by ".$this->getAccountName($publisherID);
    		$author.="\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";//yea im crazy III
    		//file inf
			$info = $this->getFileInfo($song);
			$size = $info['size'];
			if(substr($info['type'], 0, 6) != "audio/")
				return "-4";
			$size = round($size / 1024 / 1024, 2);
			$hash = "";
            //content type is music
            if(strpos(get_headers($song, 1)["Content-Type"], 'audio/') != false)
				return "-4";
			//adding to database
			$query = $db->prepare("INSERT INTO songs (name, authorID, authorName, size, download, hash, reuploadTime, publisherID, hostname)
			VALUES (:name, '9', :author, :size, :download, :hash, :reuploadTime, :publisherID, :hostname)");
			$query->execute([':name' => $name, ':download' => $song, ':author' => $author, ':size' => $size, ':hash' => $hash, ':reuploadTime' => time(), ':publisherID' => $publisherID, ':hostname' => $this->getIP()]);
			return $db->lastInsertId();
		}else{
			return "-2";
		}
	}
	public function getFileInfo($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		//curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$data = curl_exec($ch);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		//$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		return ['size' => $size, 'type' => $mime];
	}
	public function suggestLevel($accountID, $levelID, $difficulty, $stars, $feat, $auto, $demon){
		if(!is_numeric($accountID)) return false;
		
		include __DIR__ . "/connection.php";
		$query = "INSERT INTO suggest (suggestBy, suggestLevelID, suggestDifficulty, suggestStars, suggestFeatured, suggestAuto, suggestDemon, timestamp) VALUES (:account, :level, :diff, :stars, :feat, :auto, :demon, :timestamp)";
		$query = $db->prepare($query);
		$query->execute([':account' => $accountID, ':level' => $levelID, ':diff' => $difficulty, ':stars' => $stars, ':feat' => $feat, ':auto' => $auto, ':demon' => $demon, ':timestamp' => time()]);
	}
	public function getListOwner($listID) {
		if(!is_numeric($listID)) return false;
		include __DIR__ . "/connection.php";
		$query = $db->prepare('SELECT accountID FROM lists WHERE listID = :id');
		$query->execute([':id' => $listID]);
		return $query->fetchColumn();
	}
	public function getListLevels($listID) {
		if(!is_numeric($listID)) return false;
		include __DIR__ . "/connection.php";
		$query = $db->prepare('SELECT listlevels FROM lists WHERE listID = :id');
		$query->execute([':id' => $listID]);
		return $query->fetchColumn();
	}
	public function getListDiffName($diff) {
		if($diff == -1) return 'N/A';
		$diffs = ['Auto', 'Easy', 'Normal', 'Hard', 'Harder', 'Extreme', 'Easy Demon', 'Medium Demon', 'Hard Demon', 'Insane Demon', 'Extreme Demon'];
		return $diffs[$diff];
	}
	public function getListName($listID) {
		if(!is_numeric($listID)) return false;
		include __DIR__ . "/connection.php";
		$query = $db->prepare('SELECT listName FROM lists WHERE listID = :id');
		$query->execute([':id' => $listID]);
		return $query->fetchColumn();
	}
}

//five fingers in my ass
include dirname(__FILE__)."/levels.php";
include dirname(__FILE__)."/comments.php";
include dirname(__FILE__)."/lists.php";
include dirname(__FILE__)."/packs.php";