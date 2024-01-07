<?php
//connection.php setup
require "_config/connection.php";
@$test = new MySQLi($dburl, $dbuser, $dbpass, $dbname);
if($test->connect_error) {
    include "_incl/html/head.html";
    //готово
    if(!empty($_POST["dburl"]) and !empty($_POST["dbuser"]) and !empty($_POST["dbname"])) {
        @$test = new MySQLi($_POST["dburl"], $_POST["dbuser"], $_POST["dbpass"], $_POST["dbname"]);
        if(!$test->connect_error) {
            $newcontent = "<?php
\$dburl = \"${_POST['dburl']}\";
\$dbport = ${_POST['dbport']};
\$dbuser = \"${_POST['dbuser']}\";
\$dbpass = \"${_POST['dbpass']}\";
\$dbname = \"${_POST['dbname']}\";
?>";
            file_put_contents("_config/connection.php", $newcontent);
            exit("<p class=\"p-5 text-warning\">\"_config/connection.php\" was updated.<br>RELOAD PAGE</p>");
        }
    }
    echo ("
    <script>
        function showPswd(){
            alert(document.getElementById('dbpass').value);
        }
    </script>
    <div class='container'>
    	<div class='row'>
    		<div class='col-md-12 my-5'>
                <h2 class='text-center'>Test connection failure!</h2>
                <h6 class='text-center text-danger'>".$test->connect_error."</h6>
                <h5 class='text-center text-muted'>You can edit connection variables here:</h5>
                <form method='post' class='px-5 my-3'>
                    <div class='form-floating mb-3'>
                        <input class='form-control' name='dburl' type='text' placeholder='dburl' value='$dburl' required />
                        <label>Server name</label>
                    </div>
                    <div class='form-floating mb-3'>
                        <input class='form-control' name='dbport' type='number' placeholder='dbport' value='$dbport' />
                        <label for='dbport'>Server port</label>
                    </div>
                    <div class='form-floating mb-3'>
                        <input class='form-control' name='dbuser' type='text' placeholder='dbuser' value='$dbuser' required />
                        <label>Username</label>
                    </div>
                    <div class='d-flex flex-row mb-3'> <!-- <- флексит 😎 -->
                        <div class='form-floating w-100'>
                            <input class='form-control' name='dbpass' id='dbpass' type='password' placeholder='dbpass' value='${_POST['dbpass']}' />
                            <label>User password</label>
                        </div>
                        <div class='ps-2'>
                            <button class='form-control h-100' onClick='showPswd()'><i class=\"bi bi-eye h4\"></i></button>
                        </div>
                    </div>
                    <div class='form-floating mb-3'>
                        <input class='form-control' name='dbname' type='text' placeholder='dbname' value='$dbname' required />
                        <label>Database name</label>
                    </div>
                    <div class='d-grid'>
                        <button class='btn btn-primary btn-lg' type='submit'>Сontinue</button>
                    </div>
                </form>
            </div class='container'>
        </div class='row'>
    </div class='col-md-12 my-5'> 
    ");
    exit();
}
require "_incl/_main.php";
?>
<h1 class="text-center w-50">сирвак робит всё ок хех</h1>