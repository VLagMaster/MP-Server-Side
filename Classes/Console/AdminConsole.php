<?php
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/adLDAP.php');
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/SQLconnect.php');
    class AdminConsole{
        protected $ad;
        protected $SQL;
        function __construct(){
            $ad = new adLDAP();
            $SQL = new SQLconnect();
        }
        function auth(){
            session_start();
            if(!isset($_SESSION['objectGUID'])) {
                header("Location: /Auth");
                exit();
            }
            $ad = new adLDAP();
            if(!$ad->isAdmin($_SESSION['objectGUID'])){
                http_response_code(403);
                die('Forbidden');
            }
        }
        function navigationBar($page){
            echo '<div class="header">';
                echo "<table>";
                    echo "<tr>";
                        if(isset($page)){
                            echo "<th>" . $page . "</th>";
                        }
                        echo '<th><a href="/Admin/">Admin Console</a></th>';
                        echo '<th><a href="/Admin/Devices/">Manage Devices</a></th>';
                        echo '<th><a href="/Admin/EventLog/">Manage Events</a></th>';
                        echo '<th><a href="/Admin/Software/">Manage Software</a></th>';
                        echo '<th><a href="/Admin/Permissions/">Manage Permissions</a></th>';
                        echo '<th><a href="/Admin/UploadFile/">Upload Installers</a></th>';
                        echo '<th><a href="/Auth/logout.php">Log Out</a></th>';
                    echo "</tr>";
                echo "</table>";
            echo "</div>";
        }
        function header($page){
            ?>
                <link rel="stylesheet" href="/css/global.css">
                <meta charset="UTF-8">
                <meta name="viewport"
                    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title>MP Software Manager <?=$page?></title>
            <?php
        }
    }
?>
