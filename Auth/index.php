<?php
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/SQLconnect.php');
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/adLDAP.php');
?>
<?php
session_start();
if(isset($_SESSION['UUID'])){
    header('Location: ../');
    exit();
}
if(isset($_POST["username"]) && isset($_POST["password"])){
    $LDAP = new adLDAP();
    $user = $LDAP->authenticateUser($_POST["username"], $_POST["password"]);
    if($user){
        $_SESSION['objectGUID'] = $user['objectGUID'];
        $sql = new SQLconnect();
        $sql->writeObjectGUID($_SESSION['objectGUID']);
        header('Location: ../');
        exit();
    }else{
        echo "Verification error, check your credentials or try again later";
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <link rel="stylesheet" href="/css/global.css">
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MP Software Manager - Log In</title>
</head>
<body>
<div>
<h1>Log In</h1>
<form method="post">
    <table>
        <tr>
            <td>
                <label for="username">Username</label>
            </td>
            <td>
                <input type="text" name="username" id="username" required>
            </td>
        </tr>
        <tr>
            <td>
                <label for="password">Password</label>
            </td>
            <td>
                <input type="password" name="password" id="password" required>
            </td>
        </tr>
        <tr>
            <td>

            </td>
            <td>
                <input type="submit" name="submit"  value="Log In">
            </td>
        </tr>
</table>
</div>
</form>
</body>
</html>
