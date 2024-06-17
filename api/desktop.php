<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/adLDAP.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/SQLconnect.php');
if(isset($_POST['computerVerify'])){
    $verify = json_decode($_POST['computerVerify']);
    $ad = new adLDAP();
    $SQL = new SQLconnect();
    $computerGUID = $ad->searchForObjectGUID($verify->ComputerName, "computer");
    if($computerGUID && $SQL->authComputerByHash($computerGUID,  DateTime::createFromFormat('Y-m-d', $verify->DateOnlyUTC), $verify->HexHashSecretWithDateOnlyUTC) && isset($_POST['username']) && isset($_POST['password']) && $ad->authenticateUser($_POST['username'], $_POST['password'])){
        if($ad->authenticateUser($_POST['username'], $_POST['password']) && isset($_POST['task'])){
            $userGUID = $ad->searchForObjectGUID($_POST['username'], "user");
            if(isset($_POST['task'])){
                $objectGUIDs = [$computerGUID];
                $userObjectGUID = $ad->searchForObjectGUID($_POST['username'], "user");
                if($userObjectGUID){
                    $SQL->writeObjectGUID($userObjectGUID);
                    $objectGUIDs[1] = $userObjectGUID;
                    $groupObjectGUIDs = $ad->searchForUsersGroups($userObjectGUID);
                    $objectGUIDs = array_merge($objectGUIDs, $groupObjectGUIDs);
                }
            }
            switch ($_POST['task']) {
                case "getAvailableApps":
                    $apps = $SQL->getAvailableApps($objectGUIDs);
                    echo json_encode($apps);
                    break;
                case "install":
                    if(isset($_POST['apps'])){
                        $requestedApps = json_decode($_POST['apps']);
                        $appsSpecs = $SQL->getAllowedApps($objectGUIDs);
                        $apps = [];
                        $i = 0;
                        foreach($appsSpecs as $allowedApp) {
                            $apps[$i] = $allowedApp['ID'];
                            $i++;
                        }
                        foreach($requestedApps as $app) {
                            if(in_array($app, $apps))
                            $SQL->installSW($app, [$computerGUID], $userGUID);
                        }
                    }
                    break;
                case "uninstall":
                    if(isset($_POST['apps'])){
                        $requestedApps = json_decode($_POST['apps']);
                        $appsSpecs = $SQL->getAllowedApps($objectGUIDs);
                        $apps = [];
                        $i = 0;
                        foreach($appsSpecs as $allowedApp) {
                            $apps[$i] = $allowedApp['ID'];
                            $i++;
                        }
                        foreach($requestedApps as $app) {
                            if(in_array($app, $apps))
                            $SQL->uninstallSW($app, [$computerGUID], $userGUID);
                        }
                    }
                    break;
                case "getLastSynchronisation":

                    break;
            }
        }
    }else{
        http_response_code(403);
        die('Forbidden');
    }
}
?>
