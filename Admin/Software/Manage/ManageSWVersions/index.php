<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/SwAdminConsole.php');
$swAdminConsole = new SwAdminConsole();
$swAdminConsole->auth();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$swAdminConsole->header("- SW Manage")?>
</head>
<body>
    <?=$swAdminConsole->navigationBar(NULL)?>
<h1>Software Version Management</h1>
<h2>List of SW Versions</h2>
    <?php
        if(isset($_POST['submit'])){
            switch ($_POST['submit']) {
                case "add":
                    if($_POST['date'] == ""){
                        $_POST['date'] = NULL;
                    }
                    $swAdminConsole->addSwVersion($_POST['ID'], $_POST['version'], $_POST['date']);
                    $swAdminConsole->printSwVersions($_POST['ID']);
                    break;
                case "edit";
                    if($_POST['date'] == ""){
                        $_POST['date'] = NULL;
                    }
                    $swAdminConsole->editSWVersion($_POST['ID'], $_POST['version'], $_POST['date']);
                    $swAdminConsole->printSwVersions($_POST['ID']);
                    break;
                case "Manage Installers":
                    $swAdminConsole->ShowSwInstallers($_POST['ID'], $_POST['version']);
                    $swAdminConsole->printSwVersions($_POST['ID']);
                    break;
                case "Add Installer":
                    if($_POST['memory'] == ""){
                        $_POST['memory'] = NULL;
                    }
                    if($_POST['os'] == ""){
                        $_POST['os'] = NULL;
                    }
                    $swAdminConsole->AddSwInstaller($_POST['ID'], $_POST['version'], $_POST['path'], hex2bin($_POST['hexHash']), $_POST['architecture'], $_POST['memory'], $_POST['os']);
                    $swAdminConsole->ShowSwInstallers($_POST['ID'], $_POST['version']);
                    break;
                case "Edit Installer":
                    if($_POST['memory'] == ""){
                        $_POST['memory'] = NULL;
                    }
                    if($_POST['os'] == ""){
                        $_POST['os'] = NULL;
                    }
                    $swAdminConsole->EditSwInstaller($_POST['ID'], $_POST['version'], $_POST['path'], hex2bin($_POST['hexHash']), $_POST['architecture'], $_POST['memory'], $_POST['os']);
                    $swAdminConsole->ShowSwInstallers($_POST['ID'], $_POST['version']);
                    break;
                case "Remove Installer":
                    if($_POST['memory'] == ""){
                        $_POST['memory'] = NULL;
                    }
                    if($_POST['os'] == ""){
                        $_POST['os'] = NULL;
                    }
                    $swAdminConsole->RemoveSwInstaller($_POST['ID'], $_POST['version'], $_POST['path'], hex2bin($_POST['hexHash']), $_POST['architecture'], $_POST['memory'], $_POST['os']);
                    $swAdminConsole->ShowSwInstallers($_POST['ID'], $_POST['version']);
                    break;
            }

        }else if(isset($_POST['ID'])){
            $swAdminConsole->printSwVersions($_POST['ID']);

        }else{
            ?>
            <form method="post">
                <label>SwID</label><input type="number" name="ID" id="ID" required>
                <input type="submit">
            </form>
            <?php
        }
    ?>
</body>
</html>
