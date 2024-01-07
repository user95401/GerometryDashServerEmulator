<?php
//five fingers in my ass
class levels
{
    public function uploadGJLevel()
    {
        //error_reporting(0);
        if (!empty($_SERVER["HTTP_USER_AGENT"]))
            die(http_response_code(403));
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $mainLib = new mainLib();
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        //тут мы получаем всю дату
        $gjp = ExploitPatch::remove($_POST["gjp"]);
        $gameVersion = ExploitPatch::remove($_POST["gameVersion"]);
        $userName = ExploitPatch::charclean($_POST["userName"]);
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $levelName = ExploitPatch::charclean($_POST["levelName"]);
        //TODO: переместить код исправления описания в функцию
        $levelDesc = ExploitPatch::remove($_POST["levelDesc"]);
        if ($gameVersion < 20) {
            $rawDesc = $levelDesc;
            $levelDesc = str_replace('+', '-', base64_encode($rawDesc));
            $levelDesc = str_replace('/', '_', $levelDesc);
        } else {
            $rawDesc = str_replace('-', '+', $levelDesc);
            $rawDesc = str_replace('_', '/', $rawDesc);
            $rawDesc = base64_decode($rawDesc);
        }
        if (strpos($rawDesc, '<c') !== false) {
            $tags = substr_count($rawDesc, '<c');
            if ($tags > substr_count($rawDesc, '</c>')) {
                $tags = $tags - substr_count($rawDesc, '</c>');
                for ($i = 0; $i < $tags; $i++) {
                    $rawDesc .= '</c>';
                }
                $levelDesc = str_replace('+', '-', base64_encode($rawDesc));
                $levelDesc = str_replace('/', '_', $levelDesc);
            }
        }
        $levelVersion = ExploitPatch::remove($_POST["levelVersion"]);
        $levelLength = ExploitPatch::remove($_POST["levelLength"]);
        $audioTrack = ExploitPatch::remove($_POST["audioTrack"]);
        $secret = ExploitPatch::remove($_POST["secret"]);
        $binaryVersion = !empty($_POST["binaryVersion"]) ? ExploitPatch::remove($_POST["binaryVersion"]) : 0;
        $auto = !empty($_POST["auto"]) ? ExploitPatch::remove($_POST["auto"]) : 0;
        $original = !empty($_POST["original"]) ? ExploitPatch::remove($_POST["original"]) : 0;
        $twoPlayer = !empty($_POST["twoPlayer"]) ? ExploitPatch::remove($_POST["twoPlayer"]) : 0;
        $songID = !empty($_POST["songID"]) ? ExploitPatch::remove($_POST["songID"]) : 0;
        $objects = !empty($_POST["objects"]) ? ExploitPatch::remove($_POST["objects"]) : 0;
        $coins = !empty($_POST["coins"]) ? ExploitPatch::remove($_POST["coins"]) : 0;
        $requestedStars = !empty($_POST["requestedStars"]) ? ExploitPatch::remove($_POST["requestedStars"]) : 0;
        //TODO: проверить, является ли это оптимальной дополнительной строкой для старых уровней
        $extraString = !empty($_POST["extraString"]) ? ExploitPatch::remove($_POST["extraString"]) : "29_29_29_40_29_29_29_29_29_29_29_29_29_29_29_29";
        $levelString = ExploitPatch::remove($_POST["levelString"]);
        //TODO: optionally utilize the 1.9 parameter instead
        $levelInfo = !empty($_POST["levelInfo"]) ? ExploitPatch::remove($_POST["levelInfo"]) : "";
        //TODO: optionally utilize the 2.2 parameter instead
        $unlisted = !empty($_POST["unlisted1"]) ? ExploitPatch::remove($_POST["unlisted1"]) :
            (!empty($_POST["unlisted"]) ? ExploitPatch::remove($_POST["unlisted"]) : 0);
        $unlisted2 = !empty($_POST["unlisted2"]) ? ExploitPatch::remove($_POST["unlisted2"]) : $unlisted;
        $ldm = !empty($_POST["ldm"]) ? ExploitPatch::remove($_POST["ldm"]) : 0;
        $wt = !empty($_POST["wt"]) ? ExploitPatch::remove($_POST["wt"]) : 0;
        $wt2 = !empty($_POST["wt2"]) ? ExploitPatch::remove($_POST["wt2"]) : 0;
        $settingsString = !empty($_POST["settingsString"]) ? ExploitPatch::remove($_POST["settingsString"]) : "";
        if (isset($_POST["password"])) {
            $password = ExploitPatch::remove($_POST["password"]);
        } else {
            $password = 1;
            if ($gameVersion > 17) {
                $password = 0;
            }
        }
        $id = $gs->getIDFromPost();
        $hostname = $gs->getIP();
        $userID = $mainLib->getUserID($id, $userName);
        $uploadDate = time();
        $query = $db->prepare("SELECT count(*) FROM levels WHERE uploadDate > :time AND (userID = :userID OR hostname = :ip)");
        $query->execute([':time' => $uploadDate - 60, ':userID' => $userID, ':ip' => $hostname]);
        if ($query->fetchColumn() > 0) {
            exit("-1");
        }
        $query = $db->prepare("INSERT INTO levels (levelName, gameVersion, binaryVersion, userName, levelDesc, levelVersion, levelLength, audioTrack, auto, password, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, secret, uploadDate, userID, extID, updateDate, unlisted, hostname, isLDM, wt, wt2, unlisted2, settingsString)
        VALUES (:levelName, :gameVersion, :binaryVersion, :userName, :levelDesc, :levelVersion, :levelLength, :audioTrack, :auto, :password, :original, :twoPlayer, :songID, :objects, :coins, :requestedStars, :extraString, :levelString, :levelInfo, :secret, :uploadDate, :userID, :id, :uploadDate, :unlisted, :hostname, :ldm, :wt, :wt2, :unlisted2, :settingsString)");
        if ($levelString != "" and $levelName != "") {
            $querye = $db->prepare("SELECT levelID FROM levels WHERE levelName = :levelName AND userID = :userID");
            $querye->execute([':levelName' => $levelName, ':userID' => $userID]);
            $levelID = $querye->fetchColumn();
            $lvls = $querye->rowCount();
            if ($lvls == 1) {
                $query = $db->prepare("UPDATE levels SET levelName=:levelName, gameVersion=:gameVersion,  binaryVersion=:binaryVersion, userName=:userName, levelDesc=:levelDesc, levelVersion=:levelVersion, levelLength=:levelLength, audioTrack=:audioTrack, auto=:auto, password=:password, original=:original, twoPlayer=:twoPlayer, songID=:songID, objects=:objects, coins=:coins, requestedStars=:requestedStars, extraString=:extraString, levelString=:levelString, levelInfo=:levelInfo, secret=:secret, updateDate=:uploadDate, unlisted=:unlisted, hostname=:hostname, isLDM=:ldm, wt=:wt, wt2=:wt2, unlisted2=:unlisted2, settingsString=:settingsString WHERE levelName=:levelName AND extID=:id");
                $query->execute([':levelName' => $levelName, ':gameVersion' => $gameVersion, ':binaryVersion' => $binaryVersion, ':userName' => $userName, ':levelDesc' => $levelDesc, ':levelVersion' => $levelVersion, ':levelLength' => $levelLength, ':audioTrack' => $audioTrack, ':auto' => $auto, ':password' => $password, ':original' => $original, ':twoPlayer' => $twoPlayer, ':songID' => $songID, ':objects' => $objects, ':coins' => $coins, ':requestedStars' => $requestedStars, ':extraString' => $extraString, ':levelString' => "", ':levelInfo' => $levelInfo, ':secret' => $secret, ':levelName' => $levelName, ':id' => $id, ':uploadDate' => $uploadDate, ':unlisted' => $unlisted, ':hostname' => $hostname, ':ldm' => $ldm, ':wt' => $wt, ':wt2' => $wt2, ':unlisted2' => $unlisted2, ':settingsString' => $settingsString]);
                file_put_contents("../../data/levels/$levelID", $levelString);
                echo $levelID;
            } else {
                $query->execute([':levelName' => $levelName, ':gameVersion' => $gameVersion, ':binaryVersion' => $binaryVersion, ':userName' => $userName, ':levelDesc' => $levelDesc, ':levelVersion' => $levelVersion, ':levelLength' => $levelLength, ':audioTrack' => $audioTrack, ':auto' => $auto, ':password' => $password, ':original' => $original, ':twoPlayer' => $twoPlayer, ':songID' => $songID, ':objects' => $objects, ':coins' => $coins, ':requestedStars' => $requestedStars, ':extraString' => $extraString, ':levelString' => "", ':levelInfo' => $levelInfo, ':secret' => $secret, ':uploadDate' => $uploadDate, ':userID' => $userID, ':id' => $id, ':unlisted' => $unlisted, ':hostname' => $hostname, ':ldm' => $ldm, ':wt' => $wt, ':wt2' => $wt2, ':unlisted2' => $unlisted2, ':settingsString' => $settingsString]);
                $levelID = $db->lastInsertId();
                file_put_contents("../../data/levels/$levelID", $levelString);
                echo $levelID;
            }
        } else {
            echo -1;
        }
    }
    public function updateGJDesc()
    {
        //error_reporting(0);
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        $GJPCheck = new GJPCheck();
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $mainLib = new mainLib();
        //here im getting all the data
        $levelDesc = ExploitPatch::remove($_POST["levelDesc"]);
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        if (isset($_POST['udid']) && !empty($_POST['udid'])) {
            $id = ExploitPatch::remove($_POST["udid"]);
            if (is_numeric($id)) {
                exit("-1");
            }
        } else {
            $id = GJPCheck::getAccountIDOrDie();
        }
        $levelDesc = str_replace('-', '+', $levelDesc);
        $levelDesc = str_replace('_', '/', $levelDesc);
        $rawDesc = base64_decode($levelDesc);
        if (strpos($rawDesc, '<c') !== false) {
            $tags = substr_count($rawDesc, '<c');
            if ($tags > substr_count($rawDesc, '</c>')) {
                $tags = $tags - substr_count($rawDesc, '</c>');
                for ($i = 0; $i < $tags; $i++) {
                    $rawDesc .= '</c>';
                }
                $levelDesc = str_replace('+', '-', base64_encode($rawDesc));
                $levelDesc = str_replace('/', '_', $levelDesc);
            }
        }
        $query = $db->prepare("UPDATE levels SET levelDesc=:levelDesc WHERE levelID=:levelID AND extID=:extID");
        $query->execute([':levelID' => $levelID, ':extID' => $id, ':levelDesc' => $levelDesc]);
        echo 1;
    }
    public function suggestGJStars()
    {
        //error_reporting(0);
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();

        $gjp = ExploitPatch::remove($_POST["gjp"]);
        $stars = ExploitPatch::remove($_POST["stars"]);
        $feature = ExploitPatch::remove($_POST["feature"]);
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $accountID = GJPCheck::getAccountIDOrDie();
        $difficulty = $gs->getDiffFromStars($stars);

        if ($gs->checkPermission($accountID, "actionRateStars")) {
            $gs->rateLevel($accountID, $levelID, $stars, $difficulty["diff"], $difficulty["auto"], $difficulty["demon"]);
            $gs->featureLevel($accountID, $levelID, $feature);
            $gs->verifyCoinsLevel($accountID, $levelID, 1);
            echo 1;
        } else if ($gs->checkPermission($accountID, "actionSuggestRating")) {
            $gs->suggestLevel($accountID, $levelID, $difficulty["diff"], $stars, $feature, $difficulty["auto"], $difficulty["demon"]);
            echo 1;
        } else {
            echo -2;
        }
    }
    public function reportGJLevel()
    {
        chdir(dirname(__FILE__));
        //error_reporting(0);
        include "../lib/connection.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        if ($_POST["levelID"]) {
            $levelID = ExploitPatch::remove($_POST["levelID"]);
            $ip = $gs->getIP();
            $query = "SELECT count(*) FROM reports WHERE levelID = :levelID AND hostname = :hostname";
            $query = $db->prepare($query);
            $query->execute([':levelID' => $levelID, ':hostname' => $ip]);

            if ($query->fetchColumn() == 0) {
                $query = $db->prepare("INSERT INTO reports (levelID, hostname) VALUES (:levelID, :hostname)");
                $query->execute([':levelID' => $levelID, ':hostname' => $ip]);
                echo $db->lastInsertId();
            } else {
                echo -1;
            }
        }
    }
    public function rateGJStars()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        $gjp = ExploitPatch::remove($_POST["gjp"]);
        $stars = ExploitPatch::remove($_POST["stars"]);
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $accountID = GJPCheck::getAccountIDOrDie();
        $permState = $gs->checkPermission($accountID, "actionRateStars");
        if ($permState) {
            $difficulty = $gs->getDiffFromStars($stars);
            $gs->rateLevel($accountID, $levelID, 0, $difficulty["diff"], $difficulty["auto"], $difficulty["demon"]);
        }
        echo 1;
    }
    public function rateGJDemon()
    {
        //error_reporting(0);
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        if (!isset($_POST["gjp"]) or !isset($_POST["rating"]) or !isset($_POST["levelID"]) or !isset($_POST["accountID"])) {
            exit("-1");
        }
        $gjp = ExploitPatch::remove($_POST["gjp"]);
        $rating = ExploitPatch::remove($_POST["rating"]);
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $id = GJPCheck::getAccountIDOrDie();
        if ($gs->checkPermission($id, "actionRateDemon") == false) {
            exit("-1");
        }
        $auto = 0;
        $demon = 0;
        switch ($rating) {
            case 1:
                $dmn = 3;
                $dmnname = "Easy";
                break;
            case 2:
                $dmn = 4;
                $dmnname = "Medium";
                break;
            case 3:
                $dmn = 0;
                $dmnname = "Hard";
                break;
            case 4:
                $dmn = 5;
                $dmnname = "Insane";
                break;
            case 5:
                $dmn = 6;
                $dmnname = "Extreme";
                break;
        }
        $timestamp = time();
        $query = $db->prepare("UPDATE levels SET starDemonDiff=:demon WHERE levelID=:levelID");
        $query->execute([':demon' => $dmn, ':levelID' => $levelID]);
        $query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('10', :value, :levelID, :timestamp, :id)");
        $query->execute([':value' => $dmnname, ':timestamp' => $timestamp, ':id' => $id, ':levelID' => $levelID]);
        echo $levelID;
    }
    public function getGJLevels()
    {
        //header
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        require "../lib/generateHash.php";

        //initializing variables
        $lvlstring = "";
        $userstring = "";
        $songsstring = "";
        $lvlsmultistring = [];
        $str = "";
        $order = "uploadDate";
        $orderenabled = true;
        $ordergauntlet = false;
        $params = array("NOT unlisted = 1");
        $morejoins = "";

        if (!empty($_POST["gameVersion"])) {
            $gameVersion = ExploitPatch::number($_POST["gameVersion"]);
        } else {
            $gameVersion = 0;
        }
        if (!is_numeric($gameVersion)) {
            exit("-1");
        }
        if ($gameVersion == 20) {
            $binaryVersion = ExploitPatch::number($_POST["binaryVersion"]);
            if ($binaryVersion > 27) {
                $gameVersion++;
            }
        }
        if (!empty($_POST["type"])) {
            $type = ExploitPatch::number($_POST["type"]);
        } else {
            $type = 0;
        }
        if (!empty($_POST["diff"])) {
            $diff = ExploitPatch::numbercolon($_POST["diff"]);
        } else {
            $diff = "-";
        }


        //ADDITIONAL PARAMETERS
        if ($gameVersion == 0) {
            $params[] = "levels.gameVersion <= 18";
        } else {
            $params[] = "levels.gameVersion <= '$gameVersion'";
        }
        if (!empty($_POST["featured"]) and $_POST["featured"] == 1) {
            $params[] = "starFeatured = 1";
        }
        if (!empty($_POST["original"]) and $_POST["original"] == 1) {
            $params[] = "original = 0";
        }
        if (!empty($_POST["coins"]) and $_POST["coins"] == 1) {
            $params[] = "starCoins = 1 AND NOT levels.coins = 0";
        }
        if (!empty($_POST["epic"]) and $_POST["epic"] == 1) {
            $params[] = "starEpic = 1";
        }
        if (!empty($_POST["uncompleted"]) and $_POST["uncompleted"] == 1) {
            $completedLevels = ExploitPatch::numbercolon($_POST["completedLevels"]);
            $params[] = "NOT levelID IN ($completedLevels)";
        }
        if (!empty($_POST["onlyCompleted"]) and $_POST["onlyCompleted"] == 1) {
            $completedLevels = ExploitPatch::numbercolon($_POST["completedLevels"]);
            $params[] = "levelID IN ($completedLevels)";
        }
        if (!empty($_POST["song"])) {
            if (empty($_POST["customSong"])) {
                $song = ExploitPatch::number($_POST["song"]);
                $song = $song - 1;
                $params[] = "audioTrack = '$song' AND songID = 0";
            } else {
                $song = ExploitPatch::number($_POST["song"]);
                $params[] = "songID = '$song'";
            }
        }
        if (!empty($_POST["twoPlayer"]) and $_POST["twoPlayer"] == 1) {
            $params[] = "twoPlayer = 1";
        }
        if (!empty($_POST["star"])) {
            $params[] = "NOT starStars = 0";
        }
        if (!empty($_POST["noStar"])) {
            $params[] = "starStars = 0";
        }
        if (!empty($_POST["gauntlet"])) {
            $ordergauntlet = true;
            $order = "starStars";
            $gauntlet = ExploitPatch::remove($_POST["gauntlet"]);
            $query = $db->prepare("SELECT * FROM gauntlets WHERE ID = :gauntlet");
            $query->execute([':gauntlet' => $gauntlet]);
            $actualgauntlet = $query->fetch();
            $str = $actualgauntlet["level1"] . "," . $actualgauntlet["level2"] . "," . $actualgauntlet["level3"] . "," . $actualgauntlet["level4"] . "," . $actualgauntlet["level5"];
            $params[] = "levelID IN ($str)";
            $type = -1;
        }
        if (!empty($_POST["len"])) {
            $len = ExploitPatch::numbercolon($_POST["len"]);
        } else {
            $len = "-";
        }
        if ($len != "-" and !empty($len)) {
            $params[] = "levelLength IN ($len)";
        }

        //DIFFICULTY FILTERS
        switch ($diff) {
            case -1:
                $params[] = "starDifficulty = '0'";
                break;
            case -3:
                $params[] = "starAuto = '1'";
                break;
            case -2:
                if (!empty($_POST["demonFilter"])) {
                    $demonFilter = ExploitPatch::number($_POST["demonFilter"]);
                } else {
                    $demonFilter = 0;
                }
                $params[] = "starDemon = 1";
                switch ($demonFilter) {
                    case 1:
                        $params[] = "starDemonDiff = '3'";
                        break;
                    case 2:
                        $params[] = "starDemonDiff = '4'";
                        break;
                    case 3:
                        $params[] = "starDemonDiff = '0'";
                        break;
                    case 4:
                        $params[] = "starDemonDiff = '5'";
                        break;
                    case 5:
                        $params[] = "starDemonDiff = '6'";
                        break;
                    default:
                        break;
                }
                break;
            case "-";
                break;
            default:
                if ($diff) {
                    $diff = str_replace(",", "0,", $diff) . "0";
                    $params[] = "starDifficulty IN ($diff) AND starAuto = '0' AND starDemon = '0'";
                }
                break;
        }
        //TYPE DETECTION
        //TODO: the 2 non-friend types that send GJP in 2.11
        if (!empty($_POST["str"])) {
            $str = ExploitPatch::remove($_POST["str"]);
        }
        if (isset($_POST["page"]) and is_numeric($_POST["page"])) {
            $offset = ExploitPatch::number($_POST["page"]) . "0";
        } else {
            $offset = 0;
        }
        switch ($type) {
            case 0:
            case 15: //most liked, changed to 15 in GDW for whatever reason
                $order = "likes";
                if (!empty($str)) {
                    if (is_numeric($str)) {
                        $params = array("levelID = '$str'");
                    } else {
                        $params[] = "levelName LIKE '%$str%'";
                    }
                }
                break;
            case 1:
                $order = "downloads";
                break;
            case 2:
                $order = "likes";
                break;
            case 3: //TRENDING
                $uploadDate = time() - (7 * 24 * 60 * 60);
                $params[] = "uploadDate > $uploadDate ";
                $order = "likes";
                break;
            case 5:
                $params[] = "levels.userID = '$str'";
                break;
            case 6: //featured
            case 17: //featured GDW //TODO: make this list of daily levels
                $params[] = "NOT starFeatured = 0";
                $order = "rateDate DESC,uploadDate";
                break;
            case 16: //HALL OF FAME
                $params[] = "NOT starEpic = 0";
                $order = "rateDate DESC,uploadDate";
                break;
            case 7: //MAGIC
                $params[] = "objects > 9999";
                break;
            case 10: //MAP PACKS
            case 19: //unknown but same as map packs (on real GD type 10 has star rated filter and 19 doesn't)
                $order = false;
                $params[] = "levelID IN ($str)";
                break;
            case 11: //AWARDED
                $params[] = "NOT starStars = 0";
                $order = "rateDate DESC,uploadDate";
                break;
            case 12: //FOLLOWED
                $followed = ExploitPatch::numbercolon($_POST["followed"]);
                $params[] = "users.extID IN ($followed)";
                break;
            case 13: //FRIENDS
                $accountID = GJPCheck::getAccountIDOrDie();
                $peoplearray = $gs->getFriends($accountID);
                $whereor = implode(",", $peoplearray);
                $params[] = "users.extID IN ($whereor)";
                break;
            case 21: //DAILY SAFE
                $morejoins = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
                $params[] = "dailyfeatures.type = 0";
                $order = "dailyfeatures.feaID";
                break;
            case 22: //WEEKLY SAFE
                $morejoins = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
                $params[] = "dailyfeatures.type = 1";
                $order = "dailyfeatures.feaID";
                break;
            case 23: //EVENT SAFE (assumption)
                $morejoins = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
                $params[] = "dailyfeatures.type = 2";
                $order = "dailyfeatures.feaID";
                break;
        }
        //ACTUAL QUERY EXECUTION
        $querybase = "FROM levels LEFT JOIN songs ON levels.songID = songs.ID LEFT JOIN users ON levels.userID = users.userID $morejoins";
        if (!empty($params)) {
            $querybase .= " WHERE (" . implode(" ) AND ( ", $params) . ")";
        }
        $query = "SELECT levels.*, songs.ID, songs.name, songs.authorID, songs.authorName, songs.size, songs.isDisabled, songs.download, users.userName, users.extID $querybase $morejoins ";
        if ($order) {
            if ($ordergauntlet) {
                $query .= "ORDER BY $order ASC";
            } else {
                $query .= "ORDER BY $order DESC";
            }
        }
        $query .= " LIMIT 10 OFFSET $offset";
        //echo $query;
        $countquery = "SELECT count(*) $querybase";
        //echo $query;
        $query = $db->prepare($query);
        $query->execute();
        //echo $countquery;
        $countquery = $db->prepare($countquery);
        $countquery->execute();
        $totallvlcount = $countquery->fetchColumn();
        $result = $query->fetchAll();
        $levelcount = $query->rowCount();
        foreach ($result as &$level1) {
            if ($level1["levelID"] != "") {
                $lvlsmultistring[] = $level1["levelID"];
                if (!empty($gauntlet)) {
                    $lvlstring .= "44:$gauntlet:";
                }
                $lvlstring .= "1:" . $level1["levelID"] . ":2:" . $level1["levelName"] . ":5:" . $level1["levelVersion"] . ":6:" . $level1["userID"] . ":8:10:9:" . $level1["starDifficulty"] . ":10:" . $level1["downloads"] . ":12:" . $level1["audioTrack"] . ":13:" . $level1["gameVersion"] . ":14:" . $level1["likes"] . ":17:" . $level1["starDemon"] . ":43:" . $level1["starDemonDiff"] . ":25:" . $level1["starAuto"] . ":18:" . $level1["starStars"] . ":19:" . $level1["starFeatured"] . ":42:" . $level1["starEpic"] . ":45:" . $level1["objects"] . ":3:" . $level1["levelDesc"] . ":15:" . $level1["levelLength"] . ":30:" . $level1["original"] . ":31:" . $level1['twoPlayer'] . ":37:" . $level1["coins"] . ":38:" . $level1["starCoins"] . ":39:" . $level1["requestedStars"] . ":46:1:47:2:40:" . $level1["isLDM"] . ":35:" . $level1["songID"] . "|";
                if ($level1["songID"] != 0) {
                    $song = $gs->getSongString($level1);
                    if ($song) {
                        $songsstring .= $song . "~:~";
                    }
                }
                $userstring .= $gs->getUserString($level1) . "|";
            }
        }
        $lvlstring = substr($lvlstring, 0, -1);
        $userstring = substr($userstring, 0, -1);
        $songsstring = substr($songsstring, 0, -3);
        echo $lvlstring . "#" . $userstring;
        if ($gameVersion > 18) {
            echo "#" . $songsstring;
        }
        echo "#" . $totallvlcount . ":" . $offset . ":10";
        echo "#";
        echo GenerateHash::genMulti($lvlsmultistring);
    }
    public function getGJDailyLevel()
    {
        chdir(dirname(__FILE__));
        $type = !empty($_POST["type"]) ? $_POST["type"] :
            (!empty($_POST["weekly"]) ? $_POST["weekly"] : 0);

        $midnight = ($type == 1) ? strtotime("next monday") : strtotime("tomorrow 00:00:00");
        include "../lib/connection.php";
        //Getting DailyID
        $current = time();
        $query = $db->prepare("SELECT feaID FROM dailyfeatures WHERE timestamp < :current AND type = :type ORDER BY timestamp DESC LIMIT 1");
        $query->execute([':current' => $current, ':type' => $type]);
        if ($query->rowCount() == 0)
            exit("-1");
        $dailyID = $query->fetchColumn();
        if ($type == 1)
            $dailyID += 100001;
        //Time left
        $timeleft = $midnight - $current;
        //output
        echo $dailyID . "|" . $timeleft;
    }
    public function downloadGJLevel()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require "../lib/XORCipher.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        require "../lib/generateHash.php";
        require "../lib/GJPCheck.php";
        if (empty($_POST["gameVersion"])) {
            $gameVersion = 1;
        } else {
            $gameVersion = ExploitPatch::remove($_POST["gameVersion"]);
        }
        if (empty($_POST["levelID"])) {
            exit("-1");
        }
        $extras = !empty($_POST["extras"]) && $_POST["extras"];
        $inc = !empty($_POST["inc"]) && $_POST["inc"];
        $ip = $gs->getIP();
        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $binaryVersion = !empty($_POST["binaryVersion"]) ? ExploitPatch::remove($_POST["levelID"]) : 0;
        $feaID = 0;
        if (!is_numeric($levelID)) {
            echo -1;
        } else {
            switch ($levelID) {
                case -1: //Daily level
                    $query = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 0 ORDER BY timestamp DESC LIMIT 1");
                    $query->execute([':time' => time()]);
                    $result = $query->fetch();
                    $levelID = $result["levelID"];
                    $feaID = $result["feaID"];
                    $daily = 1;
                    break;
                case -2: //Weekly level
                    $query = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 1 ORDER BY timestamp DESC LIMIT 1");
                    $query->execute([':time' => time()]);
                    $result = $query->fetch();
                    $levelID = $result["levelID"];
                    $feaID = $result["feaID"];
                    $feaID = $feaID + 100001;
                    $daily = 1;
                    break;
                case -3: //Event level
                    $query = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 2 ORDER BY timestamp DESC LIMIT 1");
                    $query->execute([':time' => time()]);
                    $result = $query->fetch();
                    $levelID = $result["levelID"];
                    $feaID = $result["feaID"];
                    //The feaID range for event levels on real GD is currently unknown, as there haven't been any yet. As such, offsetting this is to be implemented in the future
                    //$feaID = $feaID + 100001;
                    $daily = 1;
                    break;
                default:
                    $daily = 0;
            }
            //downloading the level
            if ($daily == 1)
                $query = $db->prepare("SELECT levels.*, users.userName, users.extID FROM levels LEFT JOIN users ON levels.userID = users.userID WHERE levelID = :levelID");
            else
                $query = $db->prepare("SELECT * FROM levels WHERE levelID = :levelID");

            $query->execute([':levelID' => $levelID]);
            $lvls = $query->rowCount();
            if ($lvls != 0) {
                $result = $query->fetch();

                //Verifying friends only unlisted
                if ($result["unlisted2"] != 0) {
                    $accountID = GJPCheck::getAccountIDOrDie();
                    if (!($result["extID"] == $accountID || $gs->isFriends($accountID, $result["extID"])))
                        exit("-1");
                }

                //adding the download
                $query6 = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID=:levelID AND ip=INET6_ATON(:ip)");
                $query6->execute([':levelID' => $levelID, ':ip' => $ip]);
                if ($inc && $query6->fetchColumn() < 2) {
                    $query2 = $db->prepare("UPDATE levels SET downloads = downloads + 1 WHERE levelID = :levelID");
                    $query2->execute([':levelID' => $levelID]);
                    $query6 = $db->prepare("INSERT INTO actions_downloads (levelID, ip) VALUES 
														(:levelID,INET6_ATON(:ip))");
                    $query6->execute([':levelID' => $levelID, ':ip' => $ip]);
                }
                //getting the days since uploaded... or outputting the date in Y-M-D format at least for now...
                $uploadDate = date("d-m-Y G-i", $result["uploadDate"]);
                $updateDate = date("d-m-Y G-i", $result["updateDate"]);
                //password xor
                $pass = $result["password"];
                $desc = $result["levelDesc"];
                if ($gs->checkModIPPermission("actionFreeCopy") == 1) {
                    $pass = "1";
                }
                $xorPass = $pass;
                if ($gameVersion > 19) {
                    if ($pass != 0)
                        $xorPass = base64_encode(XORCipher::cipher($pass, 26364));
                } else {
                    $desc = ExploitPatch::remove(base64_decode($desc));
                }
                //submitting data
                if (file_exists("../../data/levels/$levelID")) {
                    $levelstring = file_get_contents("../../data/levels/$levelID");
                } else {
                    $levelstring = $result["levelString"];
                }
                if ($gameVersion > 18) {
                    if (substr($levelstring, 0, 3) == 'kS1') {
                        $levelstring = base64_encode(gzcompress($levelstring));
                        $levelstring = str_replace("/", "_", $levelstring);
                        $levelstring = str_replace("+", "-", $levelstring);
                    }
                }
                $response = "1:" . $result["levelID"] . ":2:" . $result["levelName"] . ":3:" . $desc . ":4:" . $levelstring . ":5:" . $result["levelVersion"] . ":6:" . $result["userID"] . ":8:10:9:" . $result["starDifficulty"] . ":10:" . $result["downloads"] . ":11:1:12:" . $result["audioTrack"] . ":13:" . $result["gameVersion"] . ":14:" . $result["likes"] . ":17:" . $result["starDemon"] . ":43:" . $result["starDemonDiff"] . ":25:" . $result["starAuto"] . ":18:" . $result["starStars"] . ":19:" . $result["starFeatured"] . ":42:" . $result["starEpic"] . ":45:" . $result["objects"] . ":15:" . $result["levelLength"] . ":30:" . $result["original"] . ":31:" . $result['twoPlayer'] . ":28:" . $uploadDate . ":29:" . $updateDate . ":35:" . $result["songID"] . ":36:" . $result["extraString"] . ":37:" . $result["coins"] . ":38:" . $result["starCoins"] . ":39:" . $result["requestedStars"] . ":46:" . $result["wt"] . ":47:" . $result["wt2"] . ":48:" . $result["settingsString"] . ":40:" . $result["isLDM"] . ":27:$xorPass";
                if ($daily == 1)
                    $response .= ":41:" . $feaID;
                if ($extras)
                    $response .= ":26:" . $result["levelInfo"];
                //2.02 stuff
                $response .= "#" . GenerateHash::genSolo($levelstring) . "#";
                //2.1 stuff
                $somestring = $result["userID"] . "," . $result["starStars"] . "," . $result["starDemon"] . "," . $result["levelID"] . "," . $result["starCoins"] . "," . $result["starFeatured"] . "," . $pass . "," . $feaID;
                $response .= GenerateHash::genSolo2($somestring);
                if ($daily == 1) {
                    $response .= "#" . $gs->getUserString($result);
                } elseif ($binaryVersion == 30) {
                    /*
                                 This was only part of the response for a brief time prior to GD 2.1's relase.
                                 This binary version corresponds to the original release of Geometry Dash World.
                                 It is currently unknown if it's required, so it is left in for now.
                             */
                    $response .= "#" . $somestring;
                }
                echo $response;
            } else {
                echo -1;
            }
        }
    }
    public function deleteGJLevelUser()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $mainLib = new mainLib();

        $levelID = ExploitPatch::remove($_POST["levelID"]);
        $accountID = GJPCheck::getAccountIDOrDie();

        if (!is_numeric($levelID)) {
            exit("-1");
        }

        $userID = $mainLib->getUserID($accountID);
        $query = $db->prepare("DELETE from levels WHERE levelID=:levelID AND userID=:userID AND starStars = 0 LIMIT 1");
        $query->execute([':levelID' => $levelID, ':userID' => $userID]);
        $query6 = $db->prepare("INSERT INTO actions (type, value, timestamp, value2) VALUES 
											(:type,:itemID, :time, :ip)");
        $query6->execute([':type' => 8, ':itemID' => $levelID, ':time' => time(), ':ip' => $userID]);
        if (file_exists("../../data/levels/$levelID") and $query->rowCount() != 0) {
            rename("../../data/levels/$levelID", "../../data/levels/deleted/$levelID");
        }
        echo "1";
    }
}
?>