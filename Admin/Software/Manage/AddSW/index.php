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
        $swAdminConsole->navigationBar("Manage SW");
        if(isset($_POST['submit'])){
            $checked = 0;
            if(isset($_POST['isManaged'])){
                $checked = 1;
            }
            $swAdminConsole->addSW($_POST['name'], $_POST['description'], $checked);
        }
    ?>
    <h1>Add new Software</h1>
    <form method="post">
        <table>
            <tr>
                <th>Sw name (SQL LIKE)</th><th><input type="text" name="name" id="name"></th>
            </tr>
            <tr>
                <th>Sw Description</th><th><input type="text" name="description" id="description"></th>
            </tr>
            <tr>
                <th>Is Managed</th><th><input type="checkbox" name="isManaged" checked></th>
            </tr>
            <tr>
                <th></th><th><input type="submit" name="submit" value="submit"></th>
            </tr>
        </table>
    </form>
</body>
</html>
