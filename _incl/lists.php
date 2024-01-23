<?php
class lists
{
    public function getGJLevelLists()
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
        $morejoins = "";

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
        if (!empty($_POST["demonFilter"])) {
            $demonFilter = ExploitPatch::number($_POST["demonFilter"]);
        } else {
            $demonFilter = 0;
        }


        //ADDITIONAL PARAMETERS
        if (!empty($_POST["star"]) or (!empty($_POST["featured"]) and $_POST["featured"] == 1)) {
            $params[] = "NOT starStars = 0";
        }

        //DIFFICULTY FILTERS
        switch ($diff) {
            case -1:
                $params[] = "starDifficulty = '-1'";
                break;
            case -3:
                $params[] = "starDifficulty = '0'";
                break;
            case -2:
                $params[] = "starDifficulty = 5+" . $demonFilter;
                break;
            case "-";
                break;
            default:
                if ($diff) {
                    $params[] = "starDifficulty IN ($diff)";
                }
                break;
        }
        //TYPE DETECTION
        if (!empty($_POST["str"])) {
            $str = ExploitPatch::remove($_POST["str"]);
        }
        if (isset($_POST["page"]) and is_numeric($_POST["page"])) {
            $offset = ExploitPatch::number($_POST["page"]) . "0";
        } else {
            $offset = 0;
        }
        $params[] = "unlisted = 0";
        switch ($type) {
            case 0:
                $order = "likes";
                if (!empty($str)) {
                    if (is_numeric($str)) {
                        $params = array("listID = '$str'");
                    } else {
                        $params[] = "listName LIKE '%$str%'";
                    }
                }
                break;
            case 1:
                $order = "downloads";
                break;
            case 2:
                $order = "likes";
                break;
            case 3: // TRENDING
                $order = "downloads";
                $params[] = 'lists.uploadDate > ' . (time() - 604800);
                break;
            case 4: // RECENT
                $order = "uploadDate";
                break;
            case 5:
                $params[] = "lists.accountID = '$str'";
                break;
            case 6: // TOP LISTS
                $params[] = "lists.starStars > 0";
                $params[] = "lists.starFeatured > 0";
                $order = "downloads";
                break;
            case 11: // RATED
                $params[] = "lists.starStars > 0";
                $order = "downloads";
                break;
            case 12: //FOLLOWED
                $followed = ExploitPatch::numbercolon($_POST["followed"]);
                if (empty($followed))
                    $followed = 0; // No SQL syntax error today
                $params[] = "lists.accountID IN ($followed)";
                break;
            case 13: //FRIENDS
                $accountID = GJPCheck::getAccountIDOrDie();
                $peoplearray = $gs->getFriends($accountID);
                $whereor = implode(",", $peoplearray);
                $params[] = "lists.accountID IN ($whereor)";
                break;
            case 7: // MAGIC
            case 27: // SENT
                $params[] = "suggest.suggestLevelId < 0";
                $order = "suggest.timestamp";
                $morejoins = "LEFT JOIN suggest ON lists.listID*-1 LIKE suggest.suggestLevelId";
                break;
        }
        //ACTUAL QUERY EXECUTION
        $querybase = "FROM lists LEFT JOIN users ON lists.accountID LIKE users.extID $morejoins";
        if (!empty($params)) {
            $querybase .= " WHERE (" . implode(" ) AND ( ", $params) . ")";
        }
        $query = "SELECT lists.*, UNIX_TIMESTAMP(uploadDate) AS uploadDateUnix, UNIX_TIMESTAMP(updateDate) AS updateDateUnix, users.userID, users.userName, users.extID $querybase";
        if ($order) {
            $query .= "ORDER BY $order DESC";
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
        foreach ($result as &$list) {
            if (!$list['uploadDateUnix'])
                $list['uploadDateUnix'] = 0;
            if (!$list['updateDateUnix'])
                $list['updateDateUnix'] = 0;
            $lvlstring .= "1:{$list['listID']}:2:{$list['listName']}:3:{$list['listDesc']}:5:{$list['listVersion']}:49:{$list['accountID']}:50:{$list['userName']}:10:{$list['downloads']}:7:{$list['starDifficulty']}:14:{$list['likes']}:19:{$list['starFeatured']}:51:{$list['listlevels']}:55:{$list['starStars']}:56:{$list['countForReward']}:28:{$list['uploadDateUnix']}:29:{$list['updateDateUnix']}" . "|";
            $userstring .= $gs->getUserString($list) . "|";
        }
        if (empty($lvlstring))
            exit("-1");
        if (!empty($str) and is_numeric($str) and $levelcount == 1) {
            $ip = $gs->getIP();
            $query6 = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID=:listID AND ip=INET6_ATON(:ip)");
            $query6->execute([':listID' => '-' . $str, ':ip' => $ip]);
            if ($query6->fetchColumn() < 2) {
                $query2 = $db->prepare("UPDATE lists SET downloads = downloads + 1 WHERE listID = :listID");
                $query2->execute([':listID' => $str]);
                $query6 = $db->prepare("INSERT INTO actions_downloads (levelID, ip) VALUES 
				(:listID,INET6_ATON(:ip))");
                $query6->execute([':listID' => '-' . $str, ':ip' => $ip]);
            }
        }
        $lvlstring = substr($lvlstring, 0, -1);
        $userstring = substr($userstring, 0, -1);
        echo $lvlstring . "#" . $userstring;
        echo "#" . $totallvlcount . ":" . $offset . ":10";
        echo "#";
        //echo GenerateHash::genMulti($lvlsmultistring);
    }
    public function deleteGJLevelList()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        $accountID = GJPCheck::getAccountIDOrDie();
        $listID = ExploitPatch::number($_POST["listID"]);
        if (is_numeric($listID) and $accountID == $gs->getListOwner($listID)) {
            $list = $db->prepare('DELETE FROM lists WHERE listID = :listID');
            $list->execute([':listID' => $listID]);
            exit("1");
        } else
            exit("-1");
    }
    public function uploadGJLevelList()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        $accountID = GJPCheck::getAccountIDOrDie();
        $listID = ExploitPatch::number($_POST["listID"]);
        $listName = !empty(ExploitPatch::remove($_POST["listName"])) ? ExploitPatch::remove($_POST["listName"]) : "Unnamed list";
        $listDesc = ExploitPatch::remove($_POST["listDesc"]);
        $listLevels = ExploitPatch::remove($_POST["listLevels"]);
        $difficulty = ExploitPatch::number($_POST["difficulty"]);
        $listVersion = ExploitPatch::number($_POST["listVersion"]) == 0 ? 1 : ExploitPatch::number($_POST["listVersion"]);
        $original = ExploitPatch::number($_POST["original"]);
        $unlisted = ExploitPatch::number($_POST["unlisted"]);
        $secret = ExploitPatch::remove($_POST["secret"]);

        if ($secret != "Wmfd2893gb7")
            exit("-100");
        if (count(explode(',', $listLevels)) == 0)
            exit("-6");
        if (!is_numeric($accountID))
            exit("-9");

        if ($listID != 0) {
            $list = $db->prepare('SELECT * FROM lists WHERE listID = :listID AND accountID = :accountID');
            $list->execute([':listID' => $listID, ':accountID' => $accountID]);
            $list = $list->fetch();
            if (!empty($list)) {
                $list = $db->prepare('UPDATE lists SET listDesc = :listDesc, listVersion = :listVersion, listlevels = :listlevels, starDifficulty = :difficulty, original = :original, unlisted = :unlisted, updateDate = :timestamp WHERE listID = :listID');
                $list->execute([':listID' => $listID, ':listDesc' => $listDesc, ':listVersion' => $listVersion, ':listlevels' => $listLevels, ':difficulty' => $difficulty, ':original' => $original, ':unlisted' => $unlisted, ':timestamp' => time()]);
                exit($listID);
            }
        }
        $list = $db->prepare('INSERT INTO lists (listName, listDesc, listVersion, accountID, listlevels, starDifficulty, original, unlisted, uploadDate) VALUES (:listName, :listDesc, :listVersion, :accountID, :listlevels, :difficulty, :original, :unlisted, :timestamp)');
        $list->execute([':listName' => $listName, ':listDesc' => $listDesc, ':listVersion' => $listVersion, ':accountID' => $accountID, ':listlevels' => $listLevels, ':difficulty' => $difficulty, ':original' => $original, ':unlisted' => $unlisted, ':timestamp' => time()]);
        echo $db->lastInsertId();
    }
}
?>