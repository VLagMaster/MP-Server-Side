<?php
//starý název getUpdates.php - přejmenováno na sync.php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/adLDAP.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/SQLconnect.php');
if(isset($_POST['computer'])){
    $ad = new adLDAP();
    $SQL = new SQLconnect();
    $objectGUID = $ad->searchForObjectGUID($_POST['computer'], "computer");
    if(isset($_POST['task']) && isset($_POST['hexSecret'])){
        $objectGUID = $ad->searchForObjectGUID($_POST['computer'], "computer");
        if($SQL->authComputer($objectGUID, hex2bin($_POST['hexSecret']))){
            switch($_POST['task']){
                case "Synchronise":
                    $objectGUID = $ad->searchForObjectGUID($_POST['computer'], "computer");
                    $SQL->writeComputer($objectGUID);
                    $SQL->writeObjectGUID($objectGUID);
                    $eventID = $SQL->writeLog("Synchronisation", $objectGUID, $objectGUID, NULL, "Ongoing", NULL);
                    if(isset($_POST['computerSpecifications'])){
                        $input = json_decode($_POST['computerSpecifications']);
                        $SQL->updateComputerSpecifications($objectGUID, $input);
                    }
                    if(isset($_POST['installedApps'])){
                        $input = json_decode($_POST['installedApps']);
                        $SQL->updateListOfComputerApps($objectGUID, $input);
                        $SQL->removeExecutedTasks();
                        $listOfChanges = [];
                        $listOfChanges['Updates'] = $SQL->getListOfUpdates($objectGUID);
                        $listOfChanges['Installs'] = $SQL->getListOfInstalls($objectGUID);
                        $listOfChanges['Uninstalls'] = $SQL->getListOfUninstalls($objectGUID);
                        echo json_encode($listOfChanges);
                    }
                    $SQL->updateLog($eventID, "Success");
                    break;
                case "getListOfUpdates":
                    $eventID = $SQL->writeLog("UpdateCheck", $objectGUID, $objectGUID, NULL, "Ongoing", NULL);
                    echo  $SQL->getListOfUpdatesJson($objectGUID);
                    $SQL->updateLog($eventID, "Success");
                    break;
                case "getListOfInstalls":
                    $eventID = $SQL->writeLog("InstallCheck", $objectGUID, $objectGUID, NULL, "Ongoing", NULL);
                    echo $SQL->getListOfInstallsJson($objectGUID);
                    $SQL->updateLog($eventID, "Success");
                    break;
                case "getListOfUninstalls":
                    $eventID = $SQL->writeLog("UninstallCheck", $objectGUID, $objectGUID, NULL, "Ongoing", NULL);
                    echo $SQL->getListOfUninstallsJson($objectGUID);
                    $SQL->updateLog($eventID, "Success");
                    break;
                case "getAppUpdate":
                    if(isset($_POST['SwID'])){
                        $appInstaller = $SQL->getAppInstaller($_POST['SwID'], $objectGUID);
                        echo json_encode($appInstaller);
                    }
                    break;
                case "reportEvent":
                    if(isset($_POST['log'])){
                        $log = json_decode($_POST['log']);
                        if(isset($log->Type) &&
                            isset($log->Computer))
                        {
                            if($log->RequestedByHex != null){
                                $RequestedBy = hex2bin($log->RequestedByHex);
                            }else{
                                $RequestedBy = $objectGUID;
                            }
                            if($log->DateTime != null){
                                $log->DateTime = new DateTime($log->DateTime);
                                $log->DateTime = date_format($log->DateTime,"Y/m/d H:i:s");
                            }
                            $eventID = $SQL->WriteLogFromComputer($log->IdEvent, $log->Type, $objectGUID, $RequestedBy, $log->SwID, $log->ExitStatus, $log->DateTime);
                            echo $eventID;
                        }
                    }
                    break;
                case "getAvailableApps":
                    $objectGUIDs = [$objectGUID];
                    if(isset($_POST['username'])){
                        $userObjectGUID = $ad->searchForObjectGUID($_POST['username'], "user");
                        if($userObjectGUID){
                            $SQL->writeObjectGUID($userObjectGUID);
                            $objectGUIDs[1] = $userObjectGUID;
                            $groupObjectGUIDs = $ad->searchForUsersGroups($userObjectGUID);
                            $objectGUIDs = array_merge($objectGUIDs, $groupObjectGUIDs);
                        }
                    }
                    $apps = $SQL->getAvailableApps($objectGUIDs);
                    echo json_encode($apps);
                    break;
            }
        }else{
            http_response_code(403);
            die('Forbidden');
        }
    }
}else{
    echo '<form method="post">
    <table>
        <tr>
            <td>
                <label for="computer">Computer</label>
            </td>
            <td>
                <input type="text" name="computer" id="computer" required>
            </td>
        </tr>
        <tr>
            <td>
                <label for="task">task</label>
            </td>
            <td>
                <input type="text" name="task" id="task" required>
            </td>
        </tr>
        <tr>
            <td>
                <label for="SwID">SwID</label>
            </td>
            <td>
                <input type="text" name="SwID" id="SwID">
            </td>
        </tr>
        <tr>
            <td>

            </td>
            <td>
                <input type="submit" name="submit"  value="Send">
            </td>
        </tr>
    </table>
</form>';
}
?>
