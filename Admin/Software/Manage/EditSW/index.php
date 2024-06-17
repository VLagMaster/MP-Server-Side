<?php
include($_SERVER['DOCUMENT_ROOT'] . "/Classes/Console/SwAdminConsole.php");
$swAdminConsole = new SwAdminConsole();
$swAdminConsole->auth();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$swAdminConsole->header("- SW Management")?>
</head>
    <body>
        <?php
            $swAdminConsole->navigationBar("Edit SW");
            if(isset($_POST['Update'])){
                if(!isset($_POST['isManaged'])){
                    $_POST['isManaged'] = 0;
                }else{
                    $_POST['isManaged'] = 1;
                }
                $swAdminConsole->editSW($_POST['ID'], $_POST['name'], $_POST['description'], $_POST['isManaged']);
            }
            if(!isset($_GET['manage'])){
                echo "<h1>Choose software to be edited</h1>";
                echo '<a href="/Admin/Software/Manage/AddSW/">Add new Sw</a>';
                $swAdminConsole->swTable();
            }else{
                ?>
                <div>
                <h1>Edit SW</h1>
                <?=$swAdminConsole->printSwEdit($_GET['ID'])?>
                </div>
                <?php
            }
        ?>
    </body>
</html>
