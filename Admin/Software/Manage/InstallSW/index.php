<?php
include($_SERVER['DOCUMENT_ROOT'] . "/Classes/Console/SwAdminConsole.php");
$swAdminConsole = new SwAdminConsole();
$swAdminConsole->auth();
?>
<!DOCTYPE html>
<html>
<head>
    <?=$swAdminConsole->header("- SW Management")?>
</head>
<body>
    <?php
        $swAdminConsole->navigationBar("Manage SW");
        if(isset($_POST['submit'])){
            if(isset($_POST['ID'])){
                if(isset($_POST['everywhere'])){
                    $swAdminConsole->manageSW($_POST['ID'], NULL , $_SESSION['objectGUID'] ,$_POST['submit']);
                }else if ($_POST['selection']){
                    $computerGUIDs = [];
                    foreach($_POST['selection'] as $hexComputerGUID) {
                        array_push($computerGUIDs, hex2bin($hexComputerGUID));
                    }
                    $swAdminConsole->manageSW($_POST['ID'], $computerGUIDs ,$_SESSION['objectGUID'] ,$_POST['submit']);
                }
                else {
                    $swAdminConsole->SelectFromComputers($_POST['ID'], $_POST['submit']);
                }
            }
        }else{
            ?>
            <h2>Select SW to be installed</h2>
            <?=$swAdminConsole->selectFromSoftware()?>
            <?php
        }
    ?>
</body>
</html>
