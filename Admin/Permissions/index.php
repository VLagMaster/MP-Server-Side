<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/PermissionsAdminConsole.php');
$permissionsAdminConsole = new PermissionsAdminConsole();
$permissionsAdminConsole->auth();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$permissionsAdminConsole->header("- Manage Permissions")?>
</head>
    <body>
        <?=$permissionsAdminConsole->navigationBar(NULL)?>
        <h1>Permissions management</h1>
        <?php
        if(isset($_POST['deleteWhite'])){
            $permissionsAdminConsole->deleteRule($_POST['ID'], hex2bin($_POST['hexObjectGUID']), 1);
        }else if(isset($_POST['deleteBlack'])){
            $permissionsAdminConsole->deleteRule($_POST['ID'], hex2bin($_POST['hexObjectGUID']), 0);
        }
        if(isset($_POST['addRule'])){
            if(!isset($_POST['ID'])){
                $permissionsAdminConsole->echoPrintSw();
            }else if(!isset($_POST['hexObjectGUIDs'])){
                $permissionsAdminConsole->SelectObjectsForAnApp($_POST['ID'], $_POST['addRule']);
            }else{
                $objectGUIDs = [];
                foreach($_POST['hexObjectGUIDs'] as $hexval) {
                    array_push($objectGUIDs, hex2bin($hexval));
                }
                switch ($_POST['addRule']) {
                    case "permit":
                        $permissionsAdminConsole->applyRules($_POST['ID'], $objectGUIDs, "permit");
                        break;
                    case "deny":
                        $permissionsAdminConsole->applyRules($_POST['ID'], $objectGUIDs, "deny");
                        break;
                }
            }
        }
        ?>
        <form method="post">
            <input type="submit" name="addRule" value="Add Rule">
        </form>
        <h2>Complete whitelist</h2>
        <?=$permissionsAdminConsole->listWhitelist()?>
        <h2>Complete blacklist</h2>
        <?=$permissionsAdminConsole->listBlacklist()?>
    </body>
</html>
