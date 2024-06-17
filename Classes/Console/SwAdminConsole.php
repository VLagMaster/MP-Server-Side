<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
class SwAdminConsole extends AdminConsole{
    protected $swManagementPath = "/Admin/Software/Manage/EditSW/";
    function swTable() {
        echo "<table>
        <tr>
            <th>ID</th><th>Name</th><th>Last update (Date)</th><th>Number of installs</th><th>Description</th><th>Is managed</th><th>Manage</th>
        <tr>";
        $SQL = new SQLconnect();
        $allSW = $SQL->getAllSW();
        foreach($allSW as $sw){
            $this->swTableRow($sw['ID'], $sw['name'], $sw['lastVersion'], $sw['date'], $sw['installs'], $sw['description'], $sw['isManaged']);
        }
        echo "</table>";
    }

    function swTableRow($ID, $name, $lastVersion, $date, $installs, $note, $isManaged){
        ?>
        <tr>
            <form method="get" action="/Admin/Software/Manage/EditSW/">
            <th><?=$ID?><input type="hidden" value="<?=$ID?>" name="ID"></th>
            <th><?=$name?></th>
            <th><?=$lastVersion?></th>
            <th><?=$installs?></th>
            <th><?=$note?></th>
            <?php
            if($isManaged){
                echo("<th>✔️ Yes</th>");
            }else{
                echo("<th>❌ No</th>");
            }
            ?>
            <th>
                    <input type="submit" name="manage" value="manage">
            </th>
            </form>
        </tr>
        <?php
    }
    function printSwInfo($id){
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Last update (Date)</th>
                <th>Number of installs</th>
                <th>Description</th>
                <th>Is managed</th>
                <th>Manage</th>
            <tr>
        <?php
        $SQL = new SQLconnect();
        $sw = $SQL->getSw($id);
        $this->swTableRow($sw['ID'], $sw['name'], $sw['lastVersion'], $sw['date'], $sw['installs'], $sw['description'], $sw['isManaged']);

    }
    function printSwEdit($id){
        $SQL = new SQLconnect();
        $sw = $SQL->getSw($id);
        $swManagementPath = "/Admin/Software/Manage/EditSW/";
        $checked = "";
        if($sw['isManaged']){
            $checked="checked";
        }
        ?>
            <table>
            <form method="post" action="<?=$swManagementPath?>">
                <tr>
                    <th>ID</th>
                    <td><?=(int)$sw['ID']?><input type="hidden" value="<?=(int)$sw['ID']?>" name="ID" id="ID"></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td> <input type="text" value="<?=$sw['name']?>" name="name" id="name" required></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td> <input type="text" value="<?=$sw['description']?>" name="description"></td>
                </tr>
                <tr>
                    <th>isManaged</th>
                    <td> <input type="checkbox" <?=$checked?> name="isManaged" id="isManaged"></td>
                </tr>
                <tr>
                    <th>Number of installs</th>
                    <td><?=$sw['installs']?></td>
                </tr>
                <tr>
                    <th>Last version</th>
                    <td><?=$sw['lastVersion']?></td>
                </tr>
                <tr>
                    <th></th>
                    <td><?=$sw['date']?></td>
                </tr>
                <tr>
                    <th></th>
                    <th><input type="submit" name="Update" value="Update"></th>
                </tr>
            </form>
            <tr>
            <th colspan=    "2">
                <form method="post" action="/Admin/Software/Manage/ManageSWVersions">
                    <input type="hidden" name="ID" value="<?=(int)$sw['ID']?>" id="id">
                    <input type="submit" name="edit" value="edit Versions">
                </form>
            </th>
            </tr>
            </table>

        <?php
    }
    function editSW($id, $name, $description, $isManaged){
        $SQL = new SQLconnect();
        return $SQL->editSW($id, $name, $description, $isManaged);
    }
    function addSW($name, $description, $isManaged){
        $SQL = new SQLconnect();
        return $SQL->newSW($name, $description, $isManaged);
    }
    function printSwVersions($id){
        $SQL = new SQLconnect();
        $versions = $SQL->getAllSwVersions($id);
        $sw = $SQL->getSimpleSw($id);
        echo '<table>';
        ?>
            <tr>
                <th>SwName</th><th>SwID</th><th>Version</th><th>Date</th><th>Manage</th><th></th>
            </tr>
            <tr>
                <form method="post" action="/Admin/Software/Manage/ManageSWVersions/">
                    <td><?=$sw['name']?></td>
                    <td><?=$sw['ID']?><input type="hidden" name="ID" value="<?=$sw['ID']?>"></td>
                    <td><input type="text" name="version" required></td>
                    <td><input type="date" name="date"></td>
                    <td><input type="submit" name="submit" value="add"></td>
                </form>
            </tr>
        <?php
        foreach($versions as $version) {
            ?>
            <tr>
                <form method="post" action="/Admin/Software/Manage/ManageSWVersions/">
                    <td><?=$version['name']?></td>
                    <td><?=$version['ID']?><input type="hidden" name="ID" value="<?=$version['ID']?>"></td>
                    <td><?=$version['version']?><input type="hidden" name="version" value="<?=$version['version']?>"></td>
                    <td><input type="date" name="date" value="<?=$version['date']?>"></td>
                    <td><input type="submit" name="submit" value="edit"></td>
                    <td><input type="submit" name="submit" value="Manage Installers"></td>
                </form>
            </tr>
            <?php
        }
        echo "</table>";
    }
    function AddSwVersion($id, $version, $date){
        $SQL = new SQLconnect();
        $SQL->addSwVersion($id, $version, $date);
    }
    function EditSwVersion($id, $version, $date){
        $SQL = new SQLconnect();
        $SQL->editSwVersion($id, $version, $date);
    }
    function ShowSwInstallers($id, $version){
        $SQL = new SQLconnect();
        $sw = $SQL->getSimpleSw($id);
        ?>
        <table>
            <tr>
                <th>Software</th>
                <th>SwID</th>
                <th>Version</th>
                <th>Path to installer</th>
                <th>SHA-256 hash</th>
                <th>architeture</th>
                <th>memory</th>
                <th>os</th>
                <th></th>
            </tr>
            <tr>
            <form method="post">
                <td><?=$sw['name']?></td>
                <td><input type="number" name="ID" id="ID" readonly required value="<?=$id?>"></td>
                <td><input type="text" name="version" id="version" required readonly value="<?=$version?>"></td>
                <td><input type="text" name="path" id="path" required></td>
                <td><input type="text" name="hexHash" id="hexHash" required></td>
                <td><input type="text" name="architecture" id="architecture" required></td>
                <td><input type="number" name="memory" id="memory"></td>
                <td><input type="text" name="os" id="os"></td>
                <td><input type="submit" name="submit" value="Add Installer"></td>
            </form>
            </tr>
        <?php
        $installers = $SQL->getSwInstallers($id, $version);
        foreach($installers as $installer) {
            ?>
            <form method="post">
                <tr>
                    <td><?=$sw['name']?></td>
                    <td><input type="number" name="ID" id="ID" readonly required value="<?=$installer['ID']?>"></td>
                    <td><input type="text" name="version" id="version" required readonly value="<?=$installer['version']?>"></td>
                    <td><input type="text" name="path" id="path" required value="<?=$installer['path']?>"></td>
                    <td><input type="text" name="hexHash" id="hexHash" required value="<?=bin2hex($installer['SHA-256'])?>"></td>
                    <td><input type="text" name="architecture" id="architecture" required readonly value="<?=$installer['architecture']?>"></td>
                    <td><input type="number" name="memory" id="memory" value="<?=$installer['memory']?>"></td>
                    <td><input type="text" name="os" id="os" value="<?=$installer['os']?>"></td>
                    <td><input type="submit" name="submit" value="Edit Installer"></td>
                    <td><input type="submit" name="submit" value="Remove Instaler"></td>
                </tr>
            </form>
            <?php
        }
        echo '
        </table>
        ';
    }
    function AddSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os){
        $SQL = new SQLconnect();
        $SQL->addSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os);
    }
    function EditSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os){
        $SQL = new SQLconnect();
        $SQL->editSwInstaller($id, $version, $path, $hash, $architecture, $memory, $os);
    }
    function RemoveSwInstaller($id, $version, $architecture){
        $SQL = new SQLconnect();
        $SQL->removeSwInstaller($id, $version, $architecture);
    }
    function SelectFromComputers($swID, $submitValue){
        $ad = new adLDAP();
        $SQL = new SQLconnect();
        $computers = $ad->getAllObjects("computer");
        ?>
        <form method="post">
        <table>
        <input type="hidden" name="ID" value="<?=$swID?>">

        <tr>
            <th>
                Computer
            </th>
            <th>
                objectGUID
            </th>
            <th>
                Select
            </th>
        </tr>
        <tr>
            <th>
            </th>
            <th>
                everywhere <input type="checkbox" name="everywhere">
            </th>
            <th>
                <input type="submit" name="submit" value="<?=$submitValue?>">
            </th>
        </tr>
        <?php
        foreach($computers as $computer) {
            ?>
            <tr>
                <td>
                    <?=$computer['cn']?>
                </td>
                <td>
                    <?=bin2hex($computer['objectGUID'])?>
                </td>
                <td>
                    <input type="checkbox" name="selection[]" value="<?=bin2hex($computer['objectGUID'])?>">
                </td>
            </tr>
            <?php
        }
        ?>
        </table>
        </form>
        <?php
    }
    public function selectFromSoftware(){
        $SQL = new SQLconnect();
        $allSw = $SQL->getAllSimpleSW();
        ?>
        <table>
            <tr>
                <th>
                    Sw ID
                </th>
                <th>
                    Name
                </th>
                <th>
                    Description
                </th>
                <th>
                    isManaged
                </th>
                <th>
                </th>
                <th>
                </th>
            </tr>
        <?php
        foreach($allSw as $sw) {
            ?>
            <form method="post">
                <tr>
                    <td>
                        <?=$sw['ID']?>
                        <input type="hidden" name="ID" value="<?=$sw['ID']?>">
                    </td>
                    <td>
                        <?=$sw['name']?>
                    </td>
                    <td>
                        <?=$sw['description']?>
                    </td>
                    <td>
                        <?=$this->printIsManaged($sw['isManaged'])?>
                    </td>
                    <td>
                        <input type="submit" name="submit" value="install">
                    </td>
                    <td>
                        <input type="submit" name="submit" value="uninstall">
                    </td>
                </tr>
            </form>
            <?php
        }
        ?>
        </table>
    <?php
    }
    public function printIsManaged($isManaged){
        if($isManaged){
            echo("✔️ Yes");
        }else{
            echo("❌ No");
        }
    }
    public function manageSW($ID, $objectGUIDs, $computerGUID, $task){
        $SQL = new SQLconnect();
        if($task == "install"){
            $SQL->installSW($ID, $objectGUIDs, $computerGUID);
        }else if($task == "uninstall"){
            $SQL->uninstallSW($ID, $objectGUIDs, $computerGUID);
        }
    }
    public function removeSwChange($changeID){
        $SQL = new SQLconnect();
        $SQL->removeSWChange($changeID);
    }
    public function listSwChanges(){
        $SQL = new SQLconnect();
        $changes = $SQL->getAllSWChanges();
        $ad = new adLDAP();
        $objectCache = [];
        ?>
        <table>
        <tr>
            <th>
                Change ID
            </th>
            <th>
                Task
            </th>
            <th>
                Sw ID
            </th>
            <th>
                name
            </th>
            <th>
                Computer
            </th>
            <th>
                Requested By
            </th>
            <th>
                Manage
            </th>
        </tr>
        <?php
        foreach($changes as $change) {
            $objectCache[$change[NULL]] = "everywhere";
            if(!isset($objectCache[$change['computerGUID']])){
                $objectCache[$change['computerGUID']] = $ad->searchForComputerByObjectGUID($change['computerGUID'])['login'];
            }
            if(!isset($objectCache[$change['requesterGUID']])){
                $objectCache[$change['requesterGUID']] = $ad->searchForObjectByObjectGUID($change['requesterGUID'])['login'];
            }
            ?>
            <tr>
                <form method="post">
                <td>
                    <input type="hidden" name="changeID" value="<?=$change['changeID']?>">
                    <?=$change['changeID']?>
                </td>
                <td>
                    <?=$change['task']?>
                </td>
                <td>
                    <?=$change['ID']?>
                </td>
                <td>
                    <?=$change['name']?>
                </td>
                <td>
                    <?=$objectCache[$change['computerGUID']]?>
                </td>
                <td>
                    <?=$objectCache[$change['requesterGUID']]?>
                </td>
                <td>
                    <input type="submit" name="remove" value="remove">
                </td>
                </form>
            </tr>
            <?php
        }
        ?>

        <?php
    }
}
?>
