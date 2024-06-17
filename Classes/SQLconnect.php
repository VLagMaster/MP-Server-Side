<?php
class SQLconnect{
    //cesta ke konfiguraci z DOCUMENT_ROOT
    protected $pathToConfigSQL = "/.config/SQL.json";
    //bude nastaveno při vytvoření
    protected $sqlServer;
    protected $username;
    protected $password;
    protected $database;
    //připojení
    protected $conn;

    function __construct(){
        $this->readConfigFromJSON($_SERVER['DOCUMENT_ROOT'] . $this->pathToConfigSQL);
        $this->conn = new PDO("mysql:host=$this->sqlServer;dbname=$this->database", $this->username, $this->password);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    protected function readConfigFromJSON($path){
        $file = fopen($path, "r");
        $configString = fread($file, filesize($path));
        fclose($file);
        $config = json_decode($configString);
        $this->sqlServer = $config->sqlServer;
        $this->username = $config->username;
        $this->password = $config->password;
        $this->database = $config->database;
    }
    private function useDB($SQL, $arguments){

        $STMT = $this->conn->prepare($SQL);
        $STMT->execute($arguments);
        return $STMT;
    }
    public function writeObjectGUID($objectGUID){
        $STMT = $this->useDB('SELECT `objectGUID` FROM `AdObject` WHERE `objectGUID` = UNHEX(?);', [bin2hex($objectGUID)]);
        if($STMT->rowCount() == 0){
            $this->useDB('INSERT INTO `AdObject` (`objectGUID`, `isadmin`, `note`) VALUES (UNHEX(?), NULL, NULL);', [bin2hex($objectGUID)]);
        }
    }
    public function writeComputer($objectGUID){
        $STMT = $this->useDB('SELECT `objectGUID` FROM `AdComputer` WHERE `objectGUID` = UNHEX(?);', [bin2hex($objectGUID)]);
        if($STMT->rowCount() == 0){
            $this->useDB('INSERT INTO `AdComputer` (`objectGUID`, `note`, `architecture`, `memory`, `os`) VALUES (UNHEX(?), NULL, NULL, NULL, NULL);', [bin2hex($objectGUID)]);
        }
    }
    public function getAppID($name){
        $STMT = $this->useDB("SELECT swID FROM `Sw` WHERE ? LIKE `name`;", [$name]);
        if($STMT->rowCount() == 0){
            $this->useDB("INSERT INTO `Sw` (`name`, `description`, `icon`, `isManaged`) VALUES (?, ?, NULL, false);", [$name, "added automatically"]);
            $STMT = $this->useDB("SELECT swID FROM `Sw` WHERE ? LIKE `name`;", [$name]);
        }
        $row = $STMT->fetch();
        return $row['swID'];
    }
    public function getAppVersion($app){
        $ID = $this->getAppID($app->Name);
        if(is_null($app->Version)){
            $app->Version = "0";
        }
        $STMT = $this->useDB("SELECT `SW_swID`, `version` FROM `SwVersion` WHERE `SW_swID` = ? AND `version` = ?", [$ID, $app->Version]);
        if($STMT->rowCount() == 0){
            $this->useDB("INSERT INTO `SwVersion` (`SW_swID`, `releaseDate`, `version`) VALUES (?, NULL, ?);", [$ID, $app->Version]);
            $STMT = $this->useDB("SELECT `SW_swID`, `version` FROM `SwVersion` WHERE `SW_swID` = ? AND `version` = ?", [$ID, $app->Version]);
        }
        $row = $STMT->fetch();
        return [$ID, $row['version']];
    }
    public function updateListOfComputerApps($objectGUID, $apps){
        $this->useDB("DELETE FROM `SwInstalled` WHERE `AdComputer_objectGUID` = UNHEX(?);", [bin2hex($objectGUID)]);
        foreach($apps as $app) {
            $appInfo = $this->getAppVersion($app);
            $this->useDB("INSERT INTO `SwInstalled` (`AdComputer_objectGUID`, `SwVersion_SW_swID`, `SwVersion_version`) VALUES (UNHEX(?), ?, ?);", [bin2hex($objectGUID), $appInfo[0], $appInfo[1]]);
        }
    }
    public function updateComputerSpecifications($objectGUID, $input){
        $this->useDB("UPDATE `AdComputer` SET `architecture` = ?, `os` = ?, `memory` = ? WHERE `AdComputer`.`objectGUID` = UNHEX(?);", [$input->Architecture, $input->OS, $input->Memory, bin2hex($objectGUID)]);
    }
    public function getComputerSpecification($objectGUID){
        $STMT = $this->useDB("SELECT * FROM `AdComputer` WHERE `objectGUID` = ?;", [$objectGUID]);
        return $STMT->fetch();
    }
    public function getListOfUpdates($objectGUID){
        $specs = $this->getComputerSpecification($objectGUID);
        $STMT = $this->useDB("SELECT SwVersion.SW_swID FROM SwVersion INNER JOIN SwInstalled ON SwVersion.SW_swID = SwInstalled.SwVersion_SW_swID INNER JOIN SwInstaller ON SwVersion.SW_swID = SwInstaller.SwVersion_SW_swID AND SwVersion.version = SwInstaller.SwVersion_version INNER JOIN SwVersion AS SwVersionInstalled ON SwVersionInstalled.SW_swID = SwInstalled.SwVersion_SW_swID AND SwVersionInstalled.version = SwInstalled.SwVersion_version INNER JOIN Sw ON Sw.swID = SwVersion.SW_swID WHERE SwInstalled.AdComputer_objectGUID = UNHEX(?) AND SwVersion.version != SwInstalled.SwVersion_version AND Sw.isManaged = true AND (SwVersionInstalled.releaseDate < SwVersion.releaseDate OR SwVersionInstalled.releaseDate IS NULL) AND SwInstaller.requiredArchitecture = ? AND (SwInstaller.requiredMemory <= ? OR SwInstaller.requiredMemory IS NULL) AND (SwInstaller.requiredOs <= ? OR SwInstaller.requiredOs IS NULL) GROUP BY Sw.swID ORDER BY SwVersion.releaseDate - SwVersionInstalled.releaseDate DESC;", [bin2hex($objectGUID), $specs['architecture'], $specs['memory'], $specs['os']]);
        $avaiableUpdates = [];
        $i = 0;
        while($row = $STMT->fetch()){
            if(isset($row['SW_swID'])){
                $avaiableUpdates[$i] = $row['SW_swID'];
                $i++;
            }
        }
        return $avaiableUpdates;
    }
    public function getListOfUpdatesJson($objectGUID){
        return json_encode($this->getListOfUpdates($objectGUID));
    }
    public function getListOfInstalls($objectGUID){
        $specs = $this->getComputerSpecification($objectGUID);
        $STMT = $this->useDB("SELECT `SwChange`.Sw_swID, SwChange.RequestedBy FROM `SwChange` INNER JOIN SwVersion ON SwChange.Sw_swID = SwVersion.SW_swID INNER JOIN SwInstaller ON SwVersion.SW_swID = SwInstaller.SwVersion_SW_swID AND SwVersion.version = SwInstaller.SwVersion_version WHERE (? = SwChange.AdComputer_objectGUID OR SwChange.AdComputer_objectGUID IS NULL) AND SwChange.Task = 'Install' AND SwInstaller.requiredArchitecture = ? AND (SwInstaller.requiredMemory <= ? OR SwInstaller.requiredMemory IS null) AND (SwInstaller.requiredOs <= ? OR SwInstaller.requiredOs IS null) AND `SwChange`.Sw_swID NOT IN (SELECT SwInstalled.SwVersion_SW_swID FROM SwInstalled WHERE SwInstalled.AdComputer_objectGUID = ?) GROUP BY SwChange.Sw_swID;", [$objectGUID, $specs['architecture'], $specs['memory'], $specs['os'], $objectGUID]);
        $avaiableInstalls = [];
        $i = 0;
        $avaiableInstalls = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $avaiableInstalls[$i]['ID'] = $row['Sw_swID'];
            $avaiableInstalls[$i]['RequestedByHex'] = bin2hex($row['RequestedBy']);
            $i++;
        }
        return $avaiableInstalls;
    }
    public function getListOfInstallsJson($objectGUID){
        return json_encode($this->getListOfInstalls($objectGUID));
    }
    public function getListOfUninstalls($objectGUID) {
        $specs = $this->getComputerSpecification($objectGUID);
        $STMT = $this->useDB("SELECT DISTINCT(`SwChange`.`Sw_swID`) AS swID, SwChange.RequestedBy FROM `SwChange` INNER JOIN SwInstalled ON SwInstalled.SwVersion_SW_swID = SwChange.Sw_swID AND (SwInstalled.AdComputer_objectGUID = SwChange.AdComputer_objectGUID OR SwChange.AdComputer_objectGUID IS NULL) WHERE (? = SwChange.AdComputer_objectGUID OR SwChange.AdComputer_objectGUID IS NULL) AND SwChange.Task = 'Uninstall' AND SwInstalled.AdComputer_objectGUID = ?", [$objectGUID, $objectGUID]);
        $avaiableUninstalls = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $avaiableUninstalls[$i]['ID'] = $row['swID'];
            $avaiableUninstalls[$i]['RequestedByHex'] = bin2hex($row['RequestedBy']);
            $i++;
        }
        return $avaiableUninstalls;
    }
    public function getListOfUninstallsJson($objectGUID){
        return json_encode($this->getListOfUninstalls($objectGUID));
    }
    public function writeLog($type, $computerGUID, $requesterGUID, $swID, $exitStatus, $time){
        if(is_null($time)){
            $STMT = $this->useDB("INSERT INTO `SwEvent`(`type`, `AdComputer_objectGUID`, `requestedBy`, `Sw_swID`, `exitStatus`, `Time`) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP());", [$type, $computerGUID, $requesterGUID, $swID, $exitStatus]);
        }else{
            $STMT = $this->useDB("INSERT INTO `SwEvent`(`type`, `AdComputer_objectGUID`, `requestedBy`, `Sw_swID`, `exitStatus`, `Time`) VALUES (?, ?, ?, ?, ?, ?);", [$type, $computerGUID, $requesterGUID, $swID, $exitStatus, $time]);
        }
        $STMT = $this->useDB("SELECT LAST_INSERT_ID();", []);
        $row = $STMT->fetch();
        return $row['LAST_INSERT_ID()'];
    }
    public function updateLog($eventID, $state){
        $this->useDB("UPDATE `SwEvent` SET `exitStatus`=? WHERE `idEvent` = ?;", [$state, $eventID]);
    }
    public function WriteLogFromComputer($eventID, $type, $computerGUID, $requesterGUID, $swID, $exitStatus, $dateTime){
        if($eventID == null){
            return $this->writeLog($type, $computerGUID, $requesterGUID, $swID, $exitStatus, $dateTime);
        }else{
            $this->updateLog($eventID, $exitStatus);
            return $eventID;
        }
        return null;
    }
    public function getAppInstaller($swID, $objectGUID){
        $specs = $this->getComputerSpecification($objectGUID);
        $STMT = $this->useDB("SELECT Sw.swID, Sw.name, SwVersion.version, SwInstaller.Path, SwInstaller.`SHA-256` FROM Sw INNER JOIN SwVersion ON Sw.swID = SwVersion.SW_swID INNER JOIN SwInstaller ON SwVersion.SW_swID = SwInstaller.SwVersion_SW_swID AND SwVersion.version = SwInstaller.SwVersion_version WHERE Sw.swID = ? AND SwInstaller.requiredArchitecture = ? AND (SwInstaller.requiredMemory <= ? OR SwInstaller.requiredMemory IS NULL) AND (SwInstaller.requiredOs <= ? OR SwInstaller.requiredOs IS NULL) ORDER BY SwVersion.releaseDate DESC LIMIT 1;", [$_POST['SwID'], $specs['architecture'], $specs['memory'], $specs['os']]);
        include_once($_SERVER['DOCUMENT_ROOT'] . '/Classes/AppInstaller.php');
        if($row = $STMT->fetch()){
            return new AppInstaller($row['swID'], $row['name'], $row['version'], $row['Path'], bin2hex($row['SHA-256']));
        }
    }
    public function getComputerSecret($computerGUID){
        $STMT = $this->useDB("SELECT `secret`, `lastChange` FROM `AdComputerAuth` WHERE `AdComputer_objectGUID` = ?;", [$computerGUID]);
        if($row = $STMT->fetch()){
            return $row;
        }
        return false;
    }
    public function writeComputerSecret($computerGUID, $secret){
        $STMT = $this->useDB("INSERT INTO `AdComputerAuth`(`AdComputer_objectGUID`, `secret`, `lastChange`) VALUES (?,?,UTC_TIMESTAMP())", [$computerGUID, $secret]);
    }
    public function authComputer($computerGUID, $binSecret){
        if($this->getComputerSecret($computerGUID)['secret'] == $binSecret){
            return true;
        }
        return false;
    }
    public function authComputerByHash($computerGUID, $date, $hexHash){
        if(intval(date_diff($date, DateTime::createFromFormat('n/j/Y', gmdate('n/j/Y')))->format("%d")) < 2 && strtolower($hexHash) == hash('sha256', bin2hex($this->getComputerSecret($computerGUID)['secret']) . date('n/j/Y', $date->getTimestamp()))){
            return true;
        }
        return false;
    }
    public function registerComputer($computerGUID){
        if(!$this->getComputerSecret($computerGUID)){
            $secret = random_bytes(16);
            while($secret == 0){
                $secret = random_bytes(16);
            }
            $SQL->writeComputerSecret($computerGUID, $secret);
            return $secret;
        }
        return false;
    }
    public function reRegisterComputer($computerGUID, $oldSecret){
        if($this->getComputerSecret($computerGUID) == $binSecret){
            $secret = random_bytes(16);
            while($secret == 0 && $secret != $oldSecret){
                $secret = random_bytes(16);
            }
            $SQL->writeComputerSecret($computerGUID, $secret);
            return $secret;
        }
        return false;
    }
    public function deleteComputerSecret($computerGUID){
        $this->useDB("DELETE FROM `SwChange` WHERE `AdComputer_objectGUID` = ?", [$computerGUID]);
    }
    public function getAvailableApps($objectGUIDs){
        $questionmarks = "(?";
        for($i = 1; $i < sizeof($objectGUIDs); $i++){
            $questionmarks = $questionmarks . ", ?";
        }
        $questionmarks = $questionmarks . ")";
        $STMT = $this->useDB("SELECT DISTINCT(SwWhitelist.Sw_swID) AS swID, Sw.name, Sw.description FROM SwWhitelist INNER JOIN Sw On SwWhitelist.Sw_swID = Sw.swID INNER JOIN SwInstaller ON SwInstaller.SwVersion_SW_swID = SwWhitelist.Sw_swID INNER JOIN AdComputer ON SwInstaller.requiredArchitecture = AdComputer.architecture AND (SwInstaller.requiredMemory <= AdComputer.memory OR SwInstaller.requiredMemory IS NULL) AND (SwInstaller.requiredOs <= AdComputer.os OR SwInstaller.requiredOs IS NULL) WHERE SwWhitelist.AdObject_objectGUID IN (" . $questionmarks . ") AND SwWhitelist.Sw_swID NOT IN (SELECT SwBlacklist.Sw_swID FROM SwBlacklist WHERE SwBlacklist.AdObject_objectGUID IN " . $questionmarks . ");", array_merge($objectGUIDs, $objectGUIDs));
        $apps = [];
        for($i = 0; $row = $STMT->fetch(); $i++){
            $apps[$i]['ID'] = $row['swID'];
            $apps[$i]['name'] = $row['name'];
            $apps[$i]['description'] = $row['description'];
        }
        return $apps;
    }
    //////////////////////// Management Functions /////////////////////
    public function getAllSW(){
        $STMT = $this->useDB("
        SELECT
            Sw.swID,
            Sw.name,
            Sw.description,
            Sw.isManaged,
            COUNT(DISTINCT SwInstalled.AdComputer_objectGUID) AS installs,
            latest_version.version,
            latest_version.releaseDate
        FROM
            Sw
        LEFT JOIN
            SwInstalled ON SwInstalled.SwVersion_SW_swID = Sw.swID
        LEFT JOIN
            (
                SELECT
                    SwVersion.SW_swID,
                    SwVersion.version,
                    SwVersion.releaseDate
                FROM
                    SwVersion
                INNER JOIN
                    (
                        SELECT
                            SW_swID,
                            MAX(COALESCE(releaseDate, '0000-00-00')) as latestReleaseDate,
                            MAX(version) as latestVersion
                        FROM
                            SwVersion
                        GROUP BY
                            SW_swID
                    ) AS latest ON SwVersion.SW_swID = latest.SW_swID AND (SwVersion.releaseDate = latest.latestReleaseDate OR SwVersion.version = latest.latestVersion)
            ) AS latest_version ON Sw.swID = latest_version.SW_swID
        GROUP BY
            Sw.swID
        ORDER BY
            Sw.isManaged DESC,
            Sw.swID;", []);
        $allSW;
        $i = 0;
        while($row = $STMT->fetch()){
            $allSW[$i]['ID'] = $row['swID'];
            $allSW[$i]['name'] = $row['name'];
            $allSW[$i]['description'] = $row['description'];
            $allSW[$i]['isManaged'] = $row['isManaged'];
            $allSW[$i]['installs'] = $row['installs'];
            $allSW[$i]['lastVersion'] = $row['version'];
            $allSW[$i]['date'] = $row['releaseDate'];
            $i++;
        }
        return $allSW;
    }
    public function getAllSimpleSW(){
        $STMT = $this->useDB("SELECT Sw.swID, Sw.name, Sw.description, Sw.isManaged FROM Sw ORDER BY Sw.isManaged DESC, Sw.swID", []);
        $allSW;
        $i = 0;
        while($row = $STMT->fetch()){
            $allSW[$i]['ID'] = $row['swID'];
            $allSW[$i]['name'] = $row['name'];
            $allSW[$i]['description'] = $row['description'];
            $allSW[$i]['isManaged'] = $row['isManaged'];
            $i++;
        }
        return $allSW;
    }
    public function getSW($id){
        $STMT = $this->useDB("
        SELECT
            Sw.swID,
            Sw.name,
            Sw.description,
            Sw.isManaged,
            COUNT(DISTINCT SwInstalled.AdComputer_objectGUID) AS installs,
            latest_version.version,
            latest_version.releaseDate
        FROM
            Sw
        LEFT JOIN
            SwInstalled ON SwInstalled.SwVersion_SW_swID = Sw.swID
        LEFT JOIN
            (
                SELECT
                    SwVersion.SW_swID,
                    SwVersion.version,
                    SwVersion.releaseDate
                FROM
                    SwVersion
                INNER JOIN
                    (
                        SELECT
                            SW_swID,
                            MAX(COALESCE(releaseDate, '0000-00-00')) as latestReleaseDate,
                            MAX(version) as latestVersion
                        FROM
                            SwVersion
                        GROUP BY
                            SW_swID
                    ) AS latest ON SwVersion.SW_swID = latest.SW_swID AND (SwVersion.releaseDate = latest.latestReleaseDate OR SwVersion.version = latest.latestVersion)
            ) AS latest_version ON Sw.swID = latest_version.SW_swID
            WHERE Sw.swID = ?
        GROUP BY
            Sw.swID
        ORDER BY
            Sw.isManaged DESC;", [$id]);
        $sw;
        if($row = $STMT->fetch()){
            $sw['ID'] = $row['swID'];
            $sw['name'] = $row['name'];
            $sw['description'] = $row['description'];
            $sw['isManaged'] = $row['isManaged'];
            $sw['installs'] = $row['installs'];
            $sw['lastVersion'] = $row['version'];
            $sw['date'] = $row['releaseDate'];
        }
        return $sw;
    }
    public function getSimpleSw($id){
        $STMT = $this->useDB("SELECT Sw.swID, Sw.name, Sw.description FROM Sw WHERE Sw.swID = ? ", [$id]);
        if($row = $STMT->fetch()){
            $sw['ID'] = $row['swID'];
            $sw['name'] = $row['name'];
            $sw['description'] = $row['description'];
        }
        return $sw;
    }
    public function markFailedEvents(){
        $this->useDB("UPDATE `SwEvent` SET `exitStatus`='Failure' WHERE `exitStatus` = 'Ongoing' AND TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP , `Time`) > 3600;", []);
    }
    public function getUnfinishedEvents($limit){
        $this->markFailedEvents();
        if(is_int($limit)){
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID WHERE SwEvent.exitStatus != "Success" ORDER BY SwEvent.idEvent DESC LIMIT ?;', [$limit]);
        }else{
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID WHERE SwEvent.exitStatus != "Success" ORDER BY SwEvent.idEvent DESC LIMIT 15;', []);
        }
        $events = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $events[$i] = $row;
            $i++;
        }
        return $events;
    }
    public function getEvents($limit){
        $this->markFailedEvents();
        if(is_int($limit)){
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID ORDER BY SwEvent.idEvent DESC LIMIT ?;', [$limit]);
        }else{
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID ORDER BY SwEvent.idEvent DESC LIMIT 50;', []);
        }
        $events = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $events[$i] = $row;
            $i++;
        }
        return $events;
    }
    public function getComputerEvents($objectGUID, $limit){
        $this->markFailedEvents();
        if(is_int($limit)){
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID WHERE SwEvent.AdComputer_objectGUID = ? ORDER BY SwEvent.idEvent DESC LIMIT ?;', [$objectGUID, $limit]);
        }else{
            $STMT = $this->useDB('SELECT SwEvent.*, Sw.name FROM SwEvent LEFT JOIN Sw ON Sw.swID = SwEvent.Sw_swID WHERE SwEvent.AdComputer_objectGUID = ? ORDER BY SwEvent.idEvent DESC LIMIT 50;', [$objectGUID]);
        }
        $events = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $events[$i] = $row;
            $i++;
        }
        return $events;
    }
    public function newSW($name, $description, $isManaged){
        $STMT = $this->useDB("INSERT INTO `Sw` (`name`, `description`, `isManaged`) VALUES (?, ?, ?); ", [$name, $description, $isManaged]);
        $STMT = $this->useDB("SELECT Sw.swID, Sw.name, Sw.description, Sw.isManaged FROM `Sw` WHERE Sw.name = ?;", [$name]);
        if($row = $STMT->fetch()){
            $sw['ID'] = $row['swID'];
            $sw['name'] = $row['name'];
            $sw['description'] = $row['description'];
            $sw['isManaged'] = $row['isManaged'];
        }
        return $sw;
    }
    public function editSW($id, $name, $description, $isManaged){
        $STMT = $this->useDB("UPDATE Sw SET name = ? , description = ? , isManaged = ? WHERE swID = ?", [$name, $description, $isManaged, $id]);
        $STMT = $this->useDB("SELECT Sw.swID, Sw.name, Sw.description, Sw.isManaged FROM `Sw` WHERE Sw.swID = ?", [$id]);
        $sw = [];
        if($row = $STMT->fetch()){
            $sw['ID'] = $row['swID'];
            $sw['name'] = $row['name'];
            $sw['description'] = $row['description'];
            $sw['isManaged'] = $row['isManaged'];
        }
        return $sw;
    }
    public function getAllSwVersions($id){
        $STMT = $this->useDB("SELECT Sw.name, SW_swID, version, releaseDate FROM `SwVersion` INNER JOIN Sw ON Sw.swID = SwVersion.SW_swID WHERE SW_swID = ?;", [$id]);
        $allSw = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $allSw[$i]['name'] = $row['name'];
            $allSw[$i]['ID'] = $row["SW_swID"];
            $allSw[$i]['version'] = $row["version"];
            $allSw[$i]['date'] = $row["releaseDate"];
            $i++;
        }
        return $allSw;

    }
    public function addSwVersion($id, $version, $date){
        $STMT = $this->useDB("INSERT INTO `SwVersion` (`SW_swID`, `releaseDate`, `version`) VALUES (?, ?, ?) ", [$id, $date, $version]);
    }
    public function editSwVersion($id, $version, $date){
        $this->useDB("UPDATE `SwVersion` SET `releaseDate` = ? WHERE `SwVersion`.`SW_swID` = ? AND `SwVersion`.`version` = ? ", [$date, $id, $version]);
    }
    public function getSwInstallers($id, $version){
        $STMT = $this->useDB("SELECT Sw.name, SwInstaller.`SwVersion_SW_swID`, SwInstaller.`SwVersion_version`, SwInstaller.`Path`, SwInstaller.`SHA-256`, SwInstaller.`requiredArchitecture`, SwInstaller.`requiredMemory`, SwInstaller.`requiredOs` FROM `SwInstaller` INNER JOIN Sw ON Sw.swID = SwInstaller.SwVersion_SW_swID WHERE `SwVersion_SW_swID` = ? AND `SwVersion_version` = ?;", [$id, $version]);
        $installers = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $installers[$i]['name'] = $row['name'];
            $installers[$i]['ID'] = $row['SwVersion_SW_swID'];
            $installers[$i]['version'] = $row['SwVersion_version'];
            $installers[$i]['path'] = $row['Path'];
            $installers[$i]['SHA-256'] = $row['SHA-256'];
            $installers[$i]['architecture'] = $row['requiredArchitecture'];
            $installers[$i]['memory'] = $row['requiredMemory'];
            $installers[$i]['os'] = $row['requiredOs'];
            $i++;
        }
        return $installers;

    }
    public function getAppsInstalledOn($objectGUID){
        $STMT = $this->useDB("SELECT SwInstalled.SwVersion_SW_swID, SwInstalled.SwVersion_version, SwVersion.version AS 'newestVersion', Sw.name FROM SwInstalled RIGHT JOIN SwVersion ON SwVersion.SW_swID = SwInstalled.SwVersion_SW_swID INNER JOIN Sw On Sw.swID = SwInstalled.SwVersion_SW_swID WHERE SwInstalled.AdComputer_objectGUID = ? GROUP BY SwInstalled.SwVersion_SW_swID ORDER BY (SwInstalled.SwVersion_version = SwVersion_version) ASC, SwVersion.version DESC; ", [$objectGUID]);
        $apps = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $apps[$i]['ID'] = $row['SwVersion_SW_swID'];
            $apps[$i]['name'] = $row['name'];
            $apps[$i]['version'] = $row['SwVersion_version'];
            $apps[$i]['newVersion'] = $row['newestVersion'];
            $apps[$i]['upToDate'] = ($row['SwVersion_version'] == $row['newestVersion']);
            $i++;
        }
        return $apps;
    }
    public function addSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os){
        $this->useDB("INSERT INTO `SwInstaller` (`SwVersion_SW_swID`, `SwVersion_version`, `Path`, `SHA-256`, `requiredArchitecture`, `requiredMemory`, `requiredOs`) VALUES (?, ?, ?, ?, ?, ?, ?) ", [$id, $version, $path, $hash, $architecture, $memory, $os]);
    }
    public function editSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os){
        $this->useDB("UPDATE `SwInstaller` SET `SwVersion_SW_swID` = ?, `SwVersion_version` = ?, `Path` = ?, `SHA-256` = ? , `requiredArchitecture` = ?, `requiredMemory` = ?, `requiredOs` = ? WHERE `SwInstaller`.`SwVersion_SW_swID` = ? AND `SwInstaller`.`SwVersion_version` = ? AND `SwInstaller`.`requiredArchitecture` = ? ", [$id, $version, $path, $hash, $architecture, $memory, $os, $id, $version, $architecture]);
    }
    public function removeSwInstaller($id, $version, $architecture){
        $this->useDB("DELETE FROM `SwInstaller` WHERE `SwVersion_SW_swID` = ? AND `SwVersion_version` = ? AND `requiredArchitecture` = ?", [$id, $version, $architecture]);
    }
    public function getWhitelist(){
        $STMT = $this->useDB("SELECT SwWhitelist.Sw_swID, SwWhitelist.ADObject_objectGUID, Sw.name FROM SwWhitelist INNER JOIN Sw ON Sw.swID = SwWhitelist.Sw_swID", []);
        $i = 0;
        $rules = [];
        while($row = $STMT->fetch()){
            $rules[$i]['objectGUID'] = $row['ADObject_objectGUID'];
            $rules[$i]['ID'] = $row['Sw_swID'];
            $rules[$i]['name'] = $row['name'];
            $i++;
        }
        return $rules;
    }
    public function getBlacklist(){
        $STMT = $this->useDB("SELECT SwBlacklist.Sw_swID, SwBlacklist.ADObject_objectGUID, Sw.name FROM SwBlacklist INNER JOIN Sw ON Sw.swID = SwBlacklist.Sw_swID", []);
        $i = 0;
        $rules = [];
        while($row = $STMT->fetch()){
            $rules[$i]['objectGUID'] = $row['ADObject_objectGUID'];
            $rules[$i]['ID'] = $row['Sw_swID'];
            $rules[$i]['name'] = $row['name'];
            $i++;
        }
        return $rules;
    }
    public function removeRule($id, $objectGUID, $doesPermit){
        if($doesPermit){
            $STMT = $this->useDB("DELETE FROM SwWhitelist WHERE `SwWhitelist`.`AdObject_objectGUID` = ? AND `SwWhitelist`.`Sw_swID` = ?", [$objectGUID, $id]);
        }else{
            $STMT = $this->useDB("DELETE FROM SwBlacklist WHERE `SwBlacklist`.`AdObject_objectGUID` = ? AND `SwBlacklist`.`Sw_swID` = ?", [$objectGUID, $id]);
        }
    }
    public function addRule($id, $objectGUID, $doesPermit){
        if($doesPermit){
            $STMT = $this->useDB("INSERT INTO SwWhitelist (`AdObject_objectGUID`, `Sw_swID`) VALUES (?, ?)", [$objectGUID, $id]);
        }else{
            $STMT = $this->useDB("INSERT INTO SwBlacklist (`AdObject_objectGUID`, `Sw_swID`) VALUES (?, ?)", [$objectGUID, $id]);
        }
    }
    public function installSW($id, $objectGUIDs, $requestedBy){
        if(is_null($objectGUIDs)){
            $STMT = $this->useDB("DELETE FROM `SwChange` WHERE `Sw_swID` = ?", [$id]);
            $STMT = $this->useDB("INSERT INTO `SwChange` (`RequestedBy`, `Sw_swID`, `Task`) VALUES (?, ?, 'Install') ", [$requestedBy, $id]);
        }else{
            foreach($objectGUIDs as $objectGUID) {
                $STMT = $this->useDB("DELETE FROM `SwChange` WHERE `Sw_swID` = ? AND `AdComputer_objectGUID` = ?", [$id, $objectGUID]);
                $STMT = $this->useDB("INSERT INTO `SwChange` (`AdComputer_objectGUID`, `RequestedBy`, `Sw_swID`, `Task`) VALUES (?, ?, ?, 'Install')", [$objectGUID, $requestedBy, $id]);
            }
        }
    }
    public function uninstallSW($id, $objectGUIDs, $requestedBy){
        if(is_null($objectGUIDs)){
            $STMT = $this->useDB("DELETE FROM `SwChange` WHERE `Sw_swID` = ?", [$id]);
            $STMT = $this->useDB("INSERT INTO `SwChange` (`RequestedBy`, `Sw_swID`, `Task`) VALUES (?, ?, 'Uninstall') ", [$requestedBy, $id]);
        }else{
            foreach($objectGUIDs as $objectGUID) {
                $STMT = $this->useDB("DELETE FROM `SwChange` WHERE `Sw_swID` = ? AND `AdComputer_objectGUID` = ?", [$id, $objectGUID]);
                $STMT = $this->useDB("INSERT INTO `SwChange` (`AdComputer_objectGUID`, `RequestedBy`, `Sw_swID`, `Task`) VALUES (?, ?, ?, 'Uninstall')", [$objectGUID, $requestedBy, $id]);
            }
        }
    }
    public function getAllSWChanges(){
        $STMT = $this->useDB("SELECT SwChange.idSwChange, SwChange.Sw_swID, Sw.name, SwChange.AdComputer_objectGUID, SwChange.RequestedBy, SwChange.Task FROM SwChange INNER JOIN Sw ON SwChange.Sw_swID = Sw.swID", []);
        $changes = [];
        $i = 0;
        while($row = $STMT->fetch()){
            $changes[$i]['changeID'] = $row['idSwChange'];
            $changes[$i]['ID'] = $row['Sw_swID'];
            $changes[$i]['name'] = $row['name'];
            $changes[$i]['computerGUID'] = $row['AdComputer_objectGUID'];
            $changes[$i]['requesterGUID'] = $row['RequestedBy'];
            $changes[$i]['task'] = $row['Task'];
            $i++;
        }
        return $changes;
    }
    public function removeSWChange($changeID){
        $STMT = $this->useDB("DELETE FROM `SwChange` WHERE `idSwChange` = ?", [$changeID]);
    }
    public function removeExecutedTasks(){
        $this->useDB('DELETE `SwChange` FROM `SwChange` INNER JOIN SwInstalled ON SwInstalled.SwVersion_SW_swID = SwChange.Sw_swID AND SwChange.AdComputer_objectGUID = SwInstalled.AdComputer_objectGUID WHERE SwChange.AdComputer_objectGUID IS NOT NULL AND SwChange.Task = "Install"; ', []);
        $this->useDB('DELETE `SwChange` FROM `SwChange` LEFT JOIN SwInstalled ON SwInstalled.SwVersion_SW_swID = SwChange.Sw_swID AND SwChange.AdComputer_objectGUID = SwInstalled.AdComputer_objectGUID WHERE SwChange.AdComputer_objectGUID IS NOT NULL AND SwChange.Task = "Uninstall" AND SwInstalled.SwVersion_SW_swID IS NULL; ', []);
    }
}
?>
