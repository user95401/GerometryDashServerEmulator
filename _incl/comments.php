<?php
class comments
{
    public function deleteGJAccComment()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php"; //this is connection.php too
        $gs = new mainLib();
        $commentID = ExploitPatch::remove($_POST["commentID"]);
        $accountID = GJPCheck::getAccountIDOrDie();

        $userID = $gs->getUserID($accountID);
        if ($gs->checkPermission($accountID, "actionDeleteComment") == 1) {
            $query = $db->prepare("DELETE FROM acccomments WHERE commentID = :commentID LIMIT 1");
            $query->execute([':commentID' => $commentID]);
        } else {
            $query = $db->prepare("DELETE FROM acccomments WHERE commentID=:commentID AND userID=:userID LIMIT 1");
            $query->execute([':userID' => $userID, ':commentID' => $commentID]);
        }
        echo "1";
    }
    public function deleteGJComment()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php"; //this is connection.php too
        $gs = new mainLib();
        $commentID = ExploitPatch::remove($_POST["commentID"]);
        $accountID = GJPCheck::getAccountIDOrDie();

        $userID = $gs->getUserID($accountID);
        $query = $db->prepare("DELETE FROM comments WHERE commentID=:commentID AND userID=:userID LIMIT 1");
        $query->execute([':commentID' => $commentID, ':userID' => $userID]);
        if ($query->rowCount() == 0) {
            $query = $db->prepare("SELECT users.extID FROM comments INNER JOIN levels ON levels.levelID = comments.levelID INNER JOIN users ON levels.userID = users.userID WHERE commentID = :commentID");
            $query->execute([':commentID' => $commentID]);
            $creatorAccID = $query->fetchColumn();
            if ($creatorAccID == $accountID || $gs->checkPermission($accountID, "actionDeleteComment") == 1) {
                $query = $db->prepare("DELETE FROM comments WHERE commentID=:commentID LIMIT 1");
                $query->execute([':commentID' => $commentID]);
            }
        }
        echo "1";

    }
    public function getGJAccountComments()
    {
        chdir(dirname(__FILE__));
        //error_reporting(0);
        include "../lib/connection.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();
        $commentstring = "";
        $accountid = ExploitPatch::remove($_POST["accountID"]);
        $page = ExploitPatch::remove($_POST["page"]);
        $commentpage = $page * 10;
        $userID = $gs->getUserID($accountid);
        $query = "SELECT comment, userID, likes, isSpam, commentID, timestamp FROM acccomments WHERE userID = :userID ORDER BY timeStamp DESC LIMIT 10 OFFSET $commentpage";
        $query = $db->prepare($query);
        $query->execute([':userID' => $userID]);
        $result = $query->fetchAll();
        if ($query->rowCount() == 0) {
            exit("#0:0:0");
        }
        $countquery = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID");
        $countquery->execute([':userID' => $userID]);
        $commentcount = $countquery->fetchColumn();
        foreach ($result as &$comment1) {
            if ($comment1["commentID"] != "") {
                $uploadDate = date("d/m/Y G:i", $comment1["timestamp"]);
                $commentstring .= "2~" . $comment1["comment"] . "~3~" . $comment1["userID"] . "~4~" . $comment1["likes"] . "~5~0~7~" . $comment1["isSpam"] . "~9~" . $uploadDate . "~6~" . $comment1["commentID"] . "|";
            }
        }
        $commentstring = substr($commentstring, 0, -1);
        echo $commentstring;
        echo "#" . $commentcount . ":" . $commentpage . ":10";
    }
    public function getGJComments()
    {
        chdir(dirname(__FILE__));
        //error_reporting(0);
        include "../lib/connection.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        $gs = new mainLib();

        $commentstring = "";
        $userstring = "";
        $users = array();

        $binaryVersion = isset($_POST['binaryVersion']) ? ExploitPatch::remove($_POST["binaryVersion"]) : 0;
        $gameVersion = isset($_POST['gameVersion']) ? ExploitPatch::remove($_POST["gameVersion"]) : 0;
        $mode = isset($_POST["mode"]) ? ExploitPatch::remove($_POST["mode"]) : 0;
        $count = (isset($_POST["count"]) and is_numeric($_POST["count"])) ? ExploitPatch::remove($_POST["count"]) : 10;
        $page = isset($_POST['page']) ? ExploitPatch::remove($_POST["page"]) : 0;

        $commentpage = $page * $count;

        if ($mode == 0)
            $modeColumn = "commentID";
        else
            $modeColumn = "likes";

        if (isset($_POST['levelID'])) {
            $filterColumn = 'levelID';
            $filterToFilter = '';
            $displayLevelID = false;
            $filterID = ExploitPatch::remove($_POST["levelID"]);
            $userListJoin = $userListWhere = $userListColumns = "";
        } elseif (isset($_POST['userID'])) {
            $filterColumn = 'userID';
            $filterToFilter = 'comments.';
            $displayLevelID = true;
            $filterID = ExploitPatch::remove($_POST["userID"]);
            $userListColumns = ", levels.unlisted";
            $userListJoin = "INNER JOIN levels ON comments.levelID = levels.levelID";
            $userListWhere = "AND levels.unlisted = 0";
        } else
            exit(-1);

        $countquery = "SELECT count(*) FROM comments $userListJoin WHERE ${filterToFilter}${filterColumn} = :filterID $userListWhere";
        $countquery = $db->prepare($countquery);
        $countquery->execute([':filterID' => $filterID]);
        $commentcount = $countquery->fetchColumn();
        if ($commentcount == 0) {
            exit("-2");
        }


        $query = "SELECT comments.levelID, comments.commentID, comments.timestamp, comments.comment, comments.userID, comments.likes, comments.isSpam, comments.percent, users.userName, users.icon, users.color1, users.color2, users.iconType, users.special, users.extID FROM comments LEFT JOIN users ON comments.userID = users.userID ${userListJoin} WHERE comments.${filterColumn} = :filterID ${userListWhere} ORDER BY comments.${modeColumn} DESC LIMIT ${count} OFFSET ${commentpage}";
        $query = $db->prepare($query);
        $query->execute([':filterID' => $filterID]);
        $result = $query->fetchAll();
        $visiblecount = $query->rowCount();

        foreach ($result as &$comment1) {
            if ($comment1["commentID"] != "") {
                $uploadDate = date("d/m/Y G.i", $comment1["timestamp"]);
                $commentText = ($gameVersion < 20) ? base64_decode($comment1["comment"]) : $comment1["comment"];
                if ($displayLevelID)
                    $commentstring .= "1~" . $comment1["levelID"] . "~";
                $commentstring .= "2~" . $commentText . "~3~" . $comment1["userID"] . "~4~" . $comment1["likes"] . "~5~0~7~" . $comment1["isSpam"] . "~9~" . $uploadDate . "~6~" . $comment1["commentID"] . "~10~" . $comment1["percent"];
                if ($comment1['userName']) { //TODO: get rid of queries caused by getMaxValuePermission and getAccountCommentColor
                    $extID = is_numeric($comment1["extID"]) ? $comment1["extID"] : 0;
                    if ($binaryVersion > 31) {
                        $badge = $gs->getMaxValuePermission($extID, "modBadgeLevel");
                        $colorString = $badge > 0 ? "~12~" . $gs->getAccountCommentColor($extID) : "";

                        $commentstring .= "~11~${badge}${colorString}:1~" . $comment1["userName"] . "~7~1~9~" . $comment1["icon"] . "~10~" . $comment1["color1"] . "~11~" . $comment1["color2"] . "~14~" . $comment1["iconType"] . "~15~" . $comment1["special"] . "~16~" . $extID;
                    } elseif (!in_array($comment1["userID"], $users)) {
                        $users[] = $comment1["userID"];
                        $userstring .= $comment1["userID"] . ":" . $comment1["userName"] . ":" . $extID . "|";
                    }
                    $commentstring .= "|";
                }
            }
        }

        $commentstring = substr($commentstring, 0, -1);
        echo $commentstring;
        if ($binaryVersion < 32) {
            $userstring = substr($userstring, 0, -1);
            echo "#$userstring";
        }
        echo "#${commentcount}:${commentpage}:${visiblecount}";
    }
    public function uploadGJAccComment()
    {
        chdir(dirname(__FILE__));
        //error_reporting(0);
        include "../lib/connection.php";
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/mainLib.php";
        require_once "../lib/commands.php";
        $mainLib = new mainLib();
        $userName = ExploitPatch::remove($_POST["userName"]);
        $comment = ExploitPatch::remove($_POST["comment"]);
        $accountID = GJPCheck::getAccountIDOrDie();
        $userID = $mainLib->getUserID($accountID, $userName);
        $uploadDate = time();
        //usercheck
        if ($accountID != "" and $comment != "") {
            $decodecomment = base64_decode($comment);
            if (Commands::doProfileCommands($accountID, $decodecomment)) {
                exit("-1");
            }
            $query = $db->prepare("INSERT INTO acccomments (userName, comment, userID, timeStamp)
										VALUES (:userName, :comment, :userID, :uploadDate)");
            $query->execute([':userName' => $userName, ':comment' => $comment, ':userID' => $userID, ':uploadDate' => $uploadDate]);
            echo 1;
        } else {
            echo -1;
        }
    }
    public function uploadGJComment()
    {
        chdir(dirname(__FILE__));
        include "../lib/connection.php";
        require_once "../lib/mainLib.php";
        $mainLib = new mainLib();
        require_once "../lib/GJPCheck.php";
        require_once "../lib/exploitPatch.php";
        require_once "../lib/commands.php";

        $userName = !empty($_POST['userName']) ? ExploitPatch::remove($_POST['userName']) : "";
        $gameVersion = !empty($_POST['gameVersion']) ? ExploitPatch::number($_POST['gameVersion']) : 0;
        $comment = ExploitPatch::remove($_POST['comment']);
        $comment = ($gameVersion < 20) ? base64_encode($comment) : $comment;
        $levelID = ExploitPatch::number($_POST["levelID"]);
        $percent = !empty($_POST["percent"]) ? ExploitPatch::remove($_POST["percent"]) : 0;

        $id = $mainLib->getIDFromPost();
        $register = is_numeric($id);
        $userID = $mainLib->getUserID($id, $userName);
        $uploadDate = time();
        $decodecomment = base64_decode($comment);
        if (Commands::doCommands($id, $decodecomment, $levelID)) {
            exit($gameVersion > 20 ? "temp_0_Command executed successfully!" : "-1");
        }
        if ($id != "" and $comment != "") {
            $query = $db->prepare("INSERT INTO comments (userName, comment, levelID, userID, timeStamp, percent) VALUES (:userName, :comment, :levelID, :userID, :uploadDate, :percent)");
            $query->execute([':userName' => $userName, ':comment' => $comment, ':levelID' => $levelID, ':userID' => $userID, ':uploadDate' => $uploadDate, ':percent' => $percent]);
            echo 1;
            if ($register) {
                //TODO: improve this
                if ($percent != 0) {
                    $query2 = $db->prepare("SELECT percent FROM levelscores WHERE accountID = :accountID AND levelID = :levelID");
                    $query2->execute([':accountID' => $id, ':levelID' => $levelID]);
                    $result = $query2->fetchColumn();
                    if ($query2->rowCount() == 0) {
                        $query = $db->prepare("INSERT INTO levelscores (accountID, levelID, percent, uploadDate)
				VALUES (:accountID, :levelID, :percent, :uploadDate)");
                    } else {
                        if ($result < $percent) {
                            $query = $db->prepare("UPDATE levelscores SET percent=:percent, uploadDate=:uploadDate WHERE accountID=:accountID AND levelID=:levelID");
                            $query->execute([':accountID' => $id, ':levelID' => $levelID, ':percent' => $percent, ':uploadDate' => $uploadDate]);
                        }
                    }
                }
            }
        } else {
            echo -1;
        }
    }
}