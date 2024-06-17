<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
class PermissionsAdminConsole extends AdminConsole{
    public function deleteRule($id, $objectGUID, $isWhite){
        $SQL = new SQLconnect();
        $SQL->removeRule($id, $objectGUID, $isWhite);
    }
    public function addRule($id, $objectGUID, $isWhite){
        $SQL = new SQLconnect();
        $SQL->addRule($id, $objectGUID, $isWhite);
    }
    public function listWhitelist(){
        $SQL = new SQLconnect();
        $ad = new adLDAP();
        $whitelist = $SQL->getWhitelist();
        for($i = 0; $i < count($whitelist); $i++){
            $whitelist[$i]['cn'] = $ad->searchForObjectByObjectGUID($whitelist[$i]['objectGUID'])['login'];
        }
        ?>
        <table>
            <tr>
                <th>
                    Object
                </th>
                <th>
                    objectGUID
                </th>
                <th>
                    Sw ID
                </th>
                <th>
                    Sw Name
                </th>
                <th>
                    Manage
                </th>
            </tr>
        <?php
        foreach ($whitelist as $rule) {
            ?>
                <form method="post">
                   <tr>
                        <td>
                            <?=$rule['cn']?>
                        </td>
                        <td>
                            <?=bin2hex($rule['objectGUID'])?>
                            <input type="hidden" value="<?=bin2hex($rule['objectGUID'])?>" name="hexObjectGUID">
                        </td>
                        <td>
                            <?=$rule['ID']?>
                            <input type="hidden" value="<?=$rule['ID']?>" name="ID">
                        </td>
                        <td>
                            <?=$rule['name']?>
                        </td>
                        <td>
                            <input type="submit" value="remove" name="deleteWhite">
                        </td>
                   </tr>
                </form>
            <?php
        }
        ?>
        </table>
        <?php
    }
    public function listBlacklist(){
        $SQL = new SQLconnect();
        $ad = new adLDAP();
        $blacklist = $SQL->getBlacklist();
        for($i = 0; isset($blacklist[$i]['objectGUID']); $i++){
            $blacklist[$i]['cn'] = $ad->searchForObjectByObjectGUID($blacklist[$i]['objectGUID'])['login'];
        }
        ?>
        <table>
            <tr>
                <th>
                    Object
                </th>
                <th>
                    objectGUID
                </th>
                <th>
                    Sw ID
                </th>
                <th>
                    Sw Name
                </th>
                <th>
                    Manage
                </th>
            </tr>
            <?php
            foreach ($blacklist as $rule) {
                ?>
                <form method="post">
                    <tr>
                        <td>
                            <?=$rule['cn']?>
                        </td>
                        <td>
                            <?=bin2hex($rule['objectGUID'])?>
                            <input type="hidden" value="<?=bin2hex($rule['objectGUID'])?>" name="hexObjectGUID">
                        </td>
                        <td>
                            <?=$rule['ID']?>
                            <input type="hidden" value="<?=$rule['ID']?>" name="ID">
                        </td>
                        <td>
                            <?=$rule['name']?>
                        </td>
                        <td>
                            <input type="submit" value="delete" name="deleteBlack">
                        </td>
                    </tr>
                </form>
                <?php
            }
            ?>
        </table>
        <?php
    }
    public function echoPrintSw(){
        $SQL = new SQLconnect();
        $allSw = $SQL->getAllSimpleSW();
        ?>
        <table>
        <tr>
            <th>
                ID
            </th>
            <th>
                name
            </th>
            <th>
                description
            </th>
            <th>
                Is Managed
            </th>
            <th>
            </th>
            <th>
            </th>
        </tr>
        <?php
        foreach($allSw as $sw) {
            ?>

            <tr>
            <form method="post">
                <td>
                    <?=$sw['ID']?>
                    <input type="hidden" value="<?=$sw['ID']?>" name="ID">
                </td>
                <td>
                    <?=$sw['name']?>
                </td>
                <td>
                    <?=$sw['description']?>
                </td>
                <td>
                <?php
                    if($sw['isManaged']){
                        echo("✔️ Yes");
                    }else{
                        echo("❌ No");
                    }
                ?>
                </td>
                <td>
                    <input type="submit" value="permit" name="addRule">
                </td>
                <td>
                    <input type="submit" value="deny" name="addRule">
                </td>
            </form>
            </tr>


            <?php
        }

        ?>
        </table>
        <?php
    }
    public function SelectObjectsForAnApp($swID, $type){
        $SQL = new SQLconnect();
        $sw = $SQL->getSimpleSw($swID);
        ?>
        <form method="post">
            <table>
                <tr>
                    <th>
                        Picked Sw:
                    </th>
                    <th>
                        ID
                    </th>
                    <th>
                        <?=$sw['ID']?>
                        <input type="hidden" value="<?=$sw['ID']?>" name="ID">
                    </th>
                    <th>
                        name
                    </th>
                    <th>
                        <?=$sw['name']?>
                    </th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>
                        Object Type
                    </th>
                    <th>
                        Object Name
                    </th>
                    <th>
                        ObjectGUID
                    </th>
                    <th>
                        DN
                    </th>
                    <th>
                        Include
                    </th>
                    <th>
                        <input type="submit" name="addRule" value="<?=$type?>">
                    </th>
                </tr>
                <?php
                    $ad = new adLDAP();
                    $objects = $ad->getAllObjects("all");
                    foreach($objects as $object) {
                        ?>
                        <tr>
                            <td>
                                <?=$object['type']?>
                            </td>
                            <td>
                                <?=$object['cn']?>
                            </td>
                            <td>
                                <?=bin2hex($object['objectGUID'])?>
                            </td>
                            <td>
                                <?=$object['dn']?>
                            </td>
                            <td>
                                <input type="checkbox" name="hexObjectGUIDs[]" value="<?=bin2hex($object['objectGUID'])?>">
                            </td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </form>
        <?php
    }
    public function applyRules($ID, $objectGUIDs, $type){
        $SQL = new SQLconnect();
        switch ($type) {
            case "permit":
                $doesPermit = true;
                break;
            case "deny";
                $doesPermit = false;
                break;
        }
        if(isset($doesPermit)){
            foreach($objectGUIDs as $objectGUID) {
                if(strlen($objectGUID) == 16){
                    $SQL->writeObjectGUID($objectGUID);
                    $SQL->addRule($ID, $objectGUID, $doesPermit);
                }
            }
        }
    }
}

?>
