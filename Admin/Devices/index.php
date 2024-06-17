<?php
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/DeviceManagementAdminConsole.php');
    $deviceManagementAdminConsole = new DeviceManagementAdminConsole();
    $deviceManagementAdminConsole->auth();


?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$deviceManagementAdminConsole->header("- Manage Devices")?>
</head>
<body>
    <?php
        $deviceManagementAdminConsole->navigationBar(NULL);
        if(isset($_POST['submit'])){
            switch ($_POST['submit']) {
                case "Show Events":
                    echo "<h1>Computer Events</h1>";
                    $deviceManagementAdminConsole->getComputerEvents(hex2bin($_POST['hexComputer']));
                    break;
                case "Show installed apps":
                    echo "Installed Apps";
                    $deviceManagementAdminConsole->getAppsOn(hex2bin($_POST['hexComputer']));
                    break;
                case "reset Secret":
                    echo "<h2>Password resetted</h2>";
                    $deviceManagementAdminConsole->resetComputerSecret(hex2bin($_POST['hexComputer']));
                    break;
            }
        }
    ?>
    <h1>Device management</h1>
    <h2>List of all devices</h2>
    <?php
        $deviceManagementAdminConsole->listAllDevices();
    ?>
</body>
</html>
