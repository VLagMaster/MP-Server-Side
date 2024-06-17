<?php
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
    $adminConsole = new AdminConsole();
    $adminConsole->auth();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$adminConsole->header("- Admin Console")?>
</head>
    <body>
        <?php
            $adminConsole->navigationBar(NULL);
        ?>
    </body>
</html>
