<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/SwAdminConsole.php');
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
$swAdminConsole->navigationBar(NULL);
?>
<h2>Software Management</h2>
<p>
    <a href="/Admin/Software/Manage/AddSW">Add SW</a>
    <a href="/Admin/Software/Manage/InstallSW">Install SW</a>
    <a href="/Admin/Software/Manage/ShowSWChanges">Show SW Changes</a>
</p>
<div>
    <?php
        $swAdminConsole->swTable();
    ?>
</div>
</body>
</html>
