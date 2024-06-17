<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/SwAdminConsole.php');
$swAdminConsole = new SwAdminConsole();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$swAdminConsole->header("- SW Management")?>
</head>
    <body>
        <?=$swAdminConsole->navigationBar(NULL)?>
        <h1>Software Version Management</h1>
        <h2>List of SW Versions<h2>
        <?php
            if(isset($_GET['ID'])){
                $swAdminConsole->printSwVersions($_GET['ID']);
            }
        ?>
    </body>
</html>
