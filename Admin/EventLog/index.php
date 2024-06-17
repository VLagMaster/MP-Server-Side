<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/EventLogAdminConsole.php');
$eventLogAdminConsole = new EventLogAdminConsole();
$eventLogAdminConsole->auth();
?>
<!DOCTYPE HTML>
<html>
<head>
    <?=$eventLogAdminConsole->header("- Event Log")?>
</head>
    <body>
        <?php
            $eventLogAdminConsole->navigationBar(NULL);
        ?>
        <h1>Manage Logs<h1>
        <h2>Unsuccesfull events</h2>
        <?php
            $eventLogAdminConsole->getBadEvents();
        ?>
        <h2>All Events</h2>
        <?php
            $eventLogAdminConsole->getAllEvents();
        ?>
    </body>
</html>
