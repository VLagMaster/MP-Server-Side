<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
class DeviceManagementAdminConsole extends AdminConsole{
    function listAllDevices(){
        $ad = new adLDAP();
        $computers = $ad->getAllObjects("computer");
        $SQL = new SQLconnect();
        for($i = 0; isset($computers[$i]); $i++){
            $specs = $SQL->getComputerSpecification($computers[$i]['objectGUID']);
            if($specs){
                $computers[$i]['memory'] = $specs['memory'];
                $computers[$i]['note'] = $specs['note'];
                $computers[$i]['architecture'] = $specs['architecture'];
                $computers[$i]['os'] = $specs['os'];
            }else{
                $computers[$i]['memory'] = "";
                $computers[$i]['note'] = "";
                $computers[$i]['architecture'] = "";
                $computers[$i]['os'] = "";
            }

        }
        echo "<table>";
            echo "
            <tr>
                <th>Name</th>
                <th>DN</th>
                <th>objectGUID</th>
                <th>OS</th>
                <th>Architecture</th>
                <th>Memory</th>
                <th>Note</th>
                <th>Manage</th>
                <th></th>
                <th></th>
            </tr>";
            foreach($computers as $computer) {
                echo '<form method="post">';
                    echo "<tr>";
                        echo "<td>" . $computer['cn'] . "</td>";
                        echo "<td>" . $computer['dn'] . "</td>";
                        ?>
                        <td style="font-family:'Source Code Pro'">
                            <?=bin2hex($computer['objectGUID'])?>
                            <input type="hidden" name="hexComputer" value="<?=bin2hex($computer['objectGUID'])?>">
                        </td>
                        <?php
                        echo "<td>" . $computer['os'] . "</td>";
                        echo "<td>" . $computer['architecture'] . "</td>";
                        echo "<td>" . $computer['memory'] . "</td>";
                        echo "<td>" . $computer['note'] . "</td>";
                        echo "<td>" . '<input type="submit" name="submit" value="Show Events"' . "</td>";
                        echo "<td>" . '<input type="submit" name="submit" value="Show installed apps"' . "</td>";
                        echo "<td>" . '<input type="submit" name="submit" value="reset Secret"' . "</td>";
                    echo "</tr>";
                echo '</form>';
            }
        echo "</table>";
    }
    function resetComputerSecret($objectGUID){
        $SQL = new SQLconnect();
        $SQL->deleteComputerSecret($objectGUID);
    }
    function getComputerEvents($objectGUID){
            $SQL = new SQLconnect();
            $events = $SQL->getComputerEvents($objectGUID, NULL);
            echo "<table>";
                echo "
                <tr>
                    <th>ID</th>
                    <th>Task</th>
                    <th>Computer</th>
                    <th>Requested by</th>
                    <th>Software</th>
                    <th>State</th>
                    <th>Date and Time</th>
                </tr>";
                foreach($events as $event) {
                    $this->echoEvent($this->processEvent($event));
                }
            echo "</table>";
            return;
        }
        function processEvent($event){
            $ad = new adLDAP();
            $event['computer'] = $ad->searchForComputerByObjectGUID($event['AdComputer_objectGUID']);
            $event['computer'] = $event['computer']['login'];
            if($event['AdComputer_objectGUID'] == $event['requestedBy']){
                $event['requestedBy'] = $event['computer'];
            }else{
                $event['requestedBy'] = $ad->searchForObjectByObjectGUID($event['requestedBy'])['login'];
            }
            return $event;
        }
        function echoEvent($event){
            echo "<tr>";
                echo "<th>" . $event['idEvent'] . "</th>";
                echo "<th>" . $event['type'] . "</th>";
                echo "<th>" . $event['computer'] . "</th>";
                echo "<th>" . $event['requestedBy'] . "</th>";
                echo "<th>" . $event['name'] . "</th>";
                echo "<th>" . $event['exitStatus'] . "</th>";
                echo "<th>" . $event['Time'] . "</th>";
            echo "</tr>";
        }
        function getAppsOn($objectGUID){
            $SQL = new SQLconnect();
            $apps = $SQL->getAppsInstalledOn($objectGUID);
            $ad = new adLDAP();
            $computer = $ad->searchForComputerByObjectGUID($objectGUID);
            echo "
            <table>
                    <tr>
                        <th>Computer</th>
                        <th>SwID</th>
                        <th>SwName</th>
                        <th>SwVersion</th>
                        <th>Up to date</th>
                        <th>Manage</th>
                        <th></th>
                        <th></th>
                    </tr>";
            foreach($apps as $app) {
                $app['upToDate'] = "❌ No";
                if($app['upToDate']){
                    $app['upToDate'] = "✔️ Yes";
                }
                ?>
                    <tr>

                        <td><?=$computer['login']?></td>
                        <td><?=$app['ID']?></td>
                        <td><?=$app['name']?></td>
                        <td><?=$app['version']?></td>
                        <td><?=$app['upToDate']?></td>
                        <td>
                            <form method="post" action="/Admin/Software/Manage/InstallSW">
                            <input type="hidden" name="ID" value="<?=$app['ID']?>">
                            <input type="hidden" name="selection[]" value="<?=bin2hex($objectGUID)?>">
                            <input type="submit" name="submit" value="uninstall">
                            </form>
                        </td>

                    </tr>
                <?php
            }
            echo "
            </table>
            ";

        }
}
?>
