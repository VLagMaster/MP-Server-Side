<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/SwAdminConsole.php');
$swAdminConsole = new SwAdminConsole();
$swAdminConsole->auth();
if(isset($_POST['remove']) && isset($_POST['changeID'])){
    $swAdminConsole->removeSwChange($_POST['changeID']);
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$swAdminConsole->header(NULL)?>
</head>
<body>
    <?=$swAdminConsole->navigationBar(NULL)?>
    <h1>Current SW Changes</h1>
    <?=$swAdminConsole->listSwChanges()?>
</body>
</html>
