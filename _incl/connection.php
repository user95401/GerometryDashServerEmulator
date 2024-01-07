<?php
include dirname(__FILE__)."/../_config/connection.php";
@header('Content-Type: text/html; charset=utf-8');
if(!isset($port)) $port = 3306;
try {
    $db = new PDO(
        "mysql:host=$dburl;port=$dbport;dbname=$dbname", $dbuser, $dbpass, 
        array(PDO::ATTR_PERSISTENT => true)
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
    echo "<h1>Connection failed: " . $e->getMessage();
    exit();
}
?>