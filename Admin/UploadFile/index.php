<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/FileUploadAdminConsole.php');
$fileUploadAdminConsole = new FileUploadAdminConsole();
$fileUploadAdminConsole->auth();
if(isset($_POST['remove']) && isset($_POST['filename'])){
    $fileUploadAdminConsole->removeInstaller($_POST['filename']);
}
if(isset($_POST['upload']) && isset($_FILES['installer'])){
    $fileUploadAdminConsole->UploadInstaller($_FILES['installer']);
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <?=$fileUploadAdminConsole->header("- File Upload")?>
    </head>
    <body>
        <?=$fileUploadAdminConsole->navigationBar(NULL)?>
        <h2>Upload Installers</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="installer">
            <input type="submit" name="upload" value="upload">
        </form>
        <h2>All Installers</h2>
        <?=$fileUploadAdminConsole->getAllInstallers()?>
    </body>
</html>
