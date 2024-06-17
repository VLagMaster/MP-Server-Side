<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
    class EventLogAdminConsole extends AdminConsole{
        function getBadEvents(){
            $SQL = new SQLconnect();
            $events = $SQL->getUnfinishedEvents(NULL);
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
        function getAllEvents(){
            $SQL = new SQLconnect();
            $events = $SQL->getEvents(NULL);
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
            if(!isset($this->objectCache[$event['AdComputer_objectGUID']]['login'])){
                $this->objectCache[$event['AdComputer_objectGUID']] = $ad->searchForComputerByObjectGUID($event['AdComputer_objectGUID']);
            }
            $event['computer'] = $this->objectCache[$event['AdComputer_objectGUID']]['login'];
            if($event['AdComputer_objectGUID'] == $event['requestedBy']){
                $event['requestedBy'] = $event['computer'];
            }else{
                if(!isset($this->objectCache[$event['requestedBy']]['login'])){
                    $this->objectCache[$event['requestedBy']] = $ad->searchForObjectByObjectGUID($event['requestedBy']);
                }
                $event['requestedBy'] = $this->objectCache[$event['requestedBy']]['login'];
            }
            return $event;
        }
        function echoEvent($event){
            echo "<tr>";
                echo "<td>" . $event['idEvent'] . "</td>";
                echo "<td>" . $event['type'] . "</td>";
                echo "<td>" . $event['computer'] . "</td>";
                echo "<td>" . $event['requestedBy'] . "</td>";
                echo "<td>" . $event['name'] . "</td>";
                echo "<td>" . $event['exitStatus'] . "</td>";
                echo "<td>" . $event['Time'] . "</td>";
            echo "</tr>";
        }
        private $objectCache = [];
    }
?>
