<?php
if(!isset($_SESSION) && isset($_POST['task']) && isset($_POST['computer'])){
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/adLDAP.php');
    include($_SERVER['DOCUMENT_ROOT'] . '/Classes/SQLconnect.php');
    $ad = new adLDAP();
    $SQL = new SQLconnect();
    $computerGUID = $ad->searchForObjectGUID($_POST['computer'], "computer");
    switch ($_POST['task']) {
        case "register":
            if($computerGUID){
                if(!$SQL->getComputerSecret($computerGUID)){
                    $secret = random_bytes(16);
                    while($secret == 0){
                        $secret = random_bytes(16);
                    }
                    $SQL->writeComputerSecret($computerGUID, $secret);
                    echo bin2hex($secret);
                    break;
                }
            }
            echo "0";
            break;
        case "auth":
            if($computerGUID && isset($_POST['hexSecret'])){
                $secret = $SQL->getComputerSecret($computerGUID);
                if($secret['secret'] == hex2bin($_POST['hexSecret'])){
                    echo "1";
                    break;
                }
            }
            echo "0";
            break;
        case "refreshSecret":
            if($computerGUID && isset($_POST['hexSecret']) && $SQL->authComputer($computerGUID, hex2bin($_POST['hexSecret']))){
                $oldSecret = $SQL->getComputerSecret($computerGUID);
                $secret = random_bytes(16);
                while($secret == 0 && $secret != $oldSecret){
                    $secret = random_bytes(16);
                }
                $SQL->writeComputerSecret($computerGUID, $secret);
                echo bin2hex($secret);
                break;
            }
            break;
    }
}
if(isset($_SESSION['computer']) && !isset($_POST['task'])){
    echo "success in auth";
}else if(!isset($_POST['task'])){
    echo "nevim uz to ztracim";
}
?>
