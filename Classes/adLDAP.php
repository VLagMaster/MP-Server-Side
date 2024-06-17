<?php
class adLDAP {
    //cesta ke konfiguraci z DOCUMENT_ROOT
    protected $pathToConfigJson =  "/.config/LDAP.json";
    //bude nastaveno při vytvoření
    protected $ldapServer;
    protected $ldapPort;
    protected $ldapBaseDNs;
    protected $ldapAdminDN;
    protected $ldapAdminPassword;
    protected $userLdapBaseDNs;
    protected $groupLdapBaseDNs;
    protected $computerLdapBaseDNs;
    protected $AdminDNs;
    //připojení
    protected $ldapConnection;
    ////////////////////////////////////////////////
    //Při vytvoření třídy načte aktuální konfiguraci připojení k LDAP serveru
    function __construct(){
        //ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        $this->readConfigFromJSON($_SERVER["DOCUMENT_ROOT"] . $this->pathToConfigJson);
        $this->bind(null, null);
    }
    //při zničení třídy odpojení od serveru
    function __destruct(){
        $this->unbind();
    }
    private function parseGUID($objectGUID){
        $objectGUID = str_replace('\\', '\\5C', $objectGUID);
        $objectGUID = str_replace('*', '\\2A', $objectGUID);
        $objectGUID = str_replace('(', '\\28', $objectGUID);
        $objectGUID = str_replace(')', '\\29', $objectGUID);
        $objectGUID = str_replace('Nul', '\\00', $objectGUID);
        return $objectGUID;
    }
    //načtení konfigurace z json - funkční
    private function readConfigFromJSON($path){
        $myfile = fopen($path, "r");
        $configString = fread($myfile, filesize($path));
        fclose($myfile);
        $config = json_decode($configString);
        $this->ldapServer = $config->ldapServer;
        $this->ldapPort = $config->ldapPort;
        $this->ldapBaseDN = $config->ldapBaseDN;
        $this->ldapAdminDN = $config->ldapAdminDN;
        $this->ldapAdminPassword = $config->ldapAdminPassword;
        $this->userLdapBaseDNs = $config->userLdapBaseDNs;
        $this->groupLdapBaseDNs = $config->groupLdapBaseDNs;
        $this->computerLdapBaseDNs = $config->computerLdapBaseDNs;
        $this->AdminDNs = $config->AdminDNs;
    }
    //Připojení k serveru - funkční
    protected function connect(){
        if(!($this->ldapConnection)){
            if($this->ldapConnection = ldap_connect($this->ldapServer, $this->ldapPort)
            or die()){
                ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option( $this->ldapConnection, LDAP_OPT_REFERRALS, 0);
                //ldap_start_tls($this->ldapConnection);
                return true;
            }
            return false;
        }
        return true;
    }
    //ověření uživatele, když bez argumentů, přihlášení pomocí Administrátora - funkční
    protected function bind($DN, $password){
        if($this->connect()){
            if(is_null($DN)){
                if(ldap_bind($this->ldapConnection, $this->ldapAdminDN, $this->ldapAdminPassword)){
                    return true;
                }
            }else if(!empty($DN)){
                if(ldap_bind($this->ldapConnection, $DN, $password)){
                    return true;
                }
            }
        }
        return false;
    }
    //odpojení od serveru
    protected function unbind(){
        return ldap_unbind($this->ldapConnection);
    }
    //Vyhledání uživatelovo DN
    public function searchForUsersDN($login){
        if($this->bind(null, null)){
            $filter = ("cn=" . $this->parseGUID($login));
            foreach($this->userLdapBaseDNs as $DN){
                $search = ldap_search($this->ldapConnection, $DN, $filter, ['distinguishedName']);
                $entries = ldap_get_entries($this->ldapConnection, $search);
                if(isset($entries[0]['distinguishedname'][0])){
                    return $entries[0]['distinguishedname'][0];
                }
            }
        }
        return false;
    }
    //Vyhledání objectGUID pro uživatele, skupiny a počítače
    public function searchForObjectGUID($login, $objectType){
        $DNs = $this->ldapBaseDN;
        switch ($objectType) {
            case "user":
                $DNs = $this->userLdapBaseDNs;
                break;
            case "group":
                $DNs = $this->groupLdapBaseDNs;
                break;
            case "computer":
                $DNs = $this->computerLdapBaseDNs;
                break;
            case "any":
                $DNs = array_merge($this->userLdapBaseDNs, array_merge($this->groupLdapBaseDNs, $this->computerLdapBaseDNs));
                break;
            default:
                return false;
        }
        if($this->bind(null, null)){
            $filter = ("cn=" . $this->parseGUID($login));
            foreach($DNs as $DN) {
                $attributes = array("objectGUID");
                $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                $entry = ldap_first_entry($this->ldapConnection, $search);
                // Get the objectGUID attribute as a binary string.
                if($entry){
                   $objectGUID = ldap_get_values_len($this->ldapConnection, $entry, "objectGUID");
                    if($objectGUID){
                        return $objectGUID[0];
                    }
                }
            }
        }
        return false;
    }
    //Když neověří uživatele vrátí false, jinak vrátí pole s DN a UUID
    public function authenticateUser($login, $password){
        $DN = $this->searchForUsersDN($login);
        if(!empty($DN)){
            if($this->bind($DN, $password)){
                $value['DN'] = $DN;
                $value['objectGUID'] = $this->searchForObjectGUID($login, "user");
                return $value;
            }else{
                return false;
            }
        }
        return false;
    }
    //vyhledání uživatele pomocí jeho objectGUID
    public function searchForUserByObjectGUID($objectGUID)
    {
        if($this->bind(null, null)){
            $filter = ("objectGUID=" . $this->parseGUID($objectGUID));
            $attributes = array("dn", "cn");
            foreach($this->userLdapBaseDNs as $DN) {
                $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                $entries = ldap_get_entries($this->ldapConnection, $search);
                if ($entries && $entries['count'] != 0) {
                    $value['dn'] = $entries[0]['dn'];
                    $value['login'] = $entries[0]['cn'][0];
                    return $value;
                }
            }
        }
        return false;
    }
    public function searchForComputerByObjectGUID($objectGUID)
    {
        if($this->bind(null, null)){
            if($this->bind(null, null)){
                $filter = ("objectGUID=" . $this->parseGUID($objectGUID));
                $attributes = array("dn", "cn");
                foreach($this->computerLdapBaseDNs as $DN) {
                    $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                    $entries = ldap_get_entries($this->ldapConnection, $search);
                    if ($entries && $entries['count'] != 0) {
                        $value['dn'] = $entries[0]['dn'];
                        $value['login'] = $entries[0]['cn'][0];
                        return $value;
                    }
                }
            }
            return false;
        }
    }
    public function searchForObjectByObjectGUID($objectGUID){
        if($this->bind(null, null)){
            if($this->bind(null, null)){
                $DNs = array_merge($this->userLdapBaseDNs, array_merge($this->groupLdapBaseDNs, $this->computerLdapBaseDNs));
                $filter = ("objectGUID=" . $this->parseGUID($objectGUID));
                $attributes = array("dn", "cn");
                foreach($DNs as $DN) {
                    $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                    if($search != false){
                        $entries = ldap_get_entries($this->ldapConnection, $search);
                        if ($entries && $entries['count'] != 0) {
                            $value['dn'] = $entries[0]['dn'];
                            $value['login'] = $entries[0]['cn'][0];
                            return $value;
                        }
                    }
                }
            }
            return false;
        }
    }
    //Vyhledání DN uživatelovo skupin
    public function searchForUsersGroupsDn($objectGUID){
        if($this->bind(null, null)){
            $filter = ("objectGUID=" . $this->parseGUID($objectGUID));
            $attributes = array("memberOf");
            $value = array();
            $i = 0;
            $success = false;
            foreach($this->userLdapBaseDNs as $DN)
            {
                $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                $entries = ldap_get_entries($this->ldapConnection, $search);
                if($entries && $entries['count'] != 0) {
                    $success = true;
                    for($j = 0; isset($entries[0]['memberof'][$j]); $j++) {
                        $value[$i] = $entries[0]['memberof'][$i];
                        $i++;
                    }
                }
            }
            if($success){
                return $value;
            }
        }
        return false;
    }
    //Určí, jestli má uživatel oprávnění spravovat tuto aplikaci
    public function isAdmin($objectGUID){
        if($this->bind(null, null)){
            $DN = $this->searchForUsersDN($objectGUID);
            if($DN){
                foreach($this->AdminDNs as $aDN) {
                    if($DN == $aDN){
                        return true;
                    }
                }
            }
            $DNs = $this->searchForUsersGroupsDn($objectGUID);
            if($DNs != false){
                foreach($DNs as $DN) {
                    foreach($this->AdminDNs as $aDN) {
                        if($DN == $aDN){
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
    //Vrátí ObjectGUID uživatelovo skupin
    public function searchForUsersGroups($objectGUID){
        if($this->bind(null, null)){
            $DN = $this->searchForUsersGroupsDn($objectGUID);
            if($DN){
                $returnObjectGUID = array();
                for($i = 0; isset($DN[$i]); $i++){
                    $filter = "objectClass=*";
                    $attributes = array("objectGUID", "dn", "cn");
                    $search = ldap_search($this->ldapConnection, $DN[$i], $filter, $attributes);
                    $entries = ldap_get_entries($this->ldapConnection, $search);
                    $returnObjectGUID[$i] = $entries[0]['objectguid'][0];
                }
                if($objectGUID[0]){
                    return $returnObjectGUID;
                }
            }
        }
        return false;
    }
    public function getAllObjects($type){
        if($this->bind(NULL, NULL)){
            if($type != "all"){
                switch ($type) {
                    case "computer":
                        $filter = "objectClass=computer";
                        $DNs = $this->computerLdapBaseDNs;
                        break;
                    case "group":
                        $filter = "objectClass=group";
                        $DNs = $this->groupLdapBaseDNs;
                        break;
                    case "user":
                        $filter = "objectClass=user";
                        $DNs = $this->userLdapBaseDNs;
                        break;
                }
                $attributes = ["objectGUID", "distinguishedName", "cn"];
                $output = [];
                $i = 0;
                foreach($DNs as $DN) {
                    $search = ldap_search($this->ldapConnection, $DN, $filter, $attributes);
                    $entries = ldap_get_entries($this->ldapConnection, $search);
                    if($entries['count'] > 0){
                        foreach($entries as $entry) {
                            if(isset($entry['cn'][0])){
                                $output[$i]['dn'] = $entry['distinguishedname'][0];
                                $output[$i]['cn'] = $entry['cn'][0];
                                $output[$i]['objectGUID'] = $entry['objectguid'][0];
                            $i++;
                            }
                        }
                    }
                }
                return $output;
            }else if($type == "all"){
                $i = 0;
                $allObjects = [];
                $objects = $this->getAllObjects("user");
                foreach($objects as $object) {
                    $allObjects[$i]['type'] = "user";
                    $allObjects[$i]['dn'] = $object['dn'];
                    $allObjects[$i]['cn'] = $object['cn'];
                    $allObjects[$i]['objectGUID'] = $object['objectGUID'];
                    $i++;
                }
                $objects = $this->getAllObjects("group");
                foreach($objects as $object) {
                    $allObjects[$i]['type'] = "group";
                    $allObjects[$i]['dn'] = $object['dn'];
                    $allObjects[$i]['cn'] = $object['cn'];
                    $allObjects[$i]['objectGUID'] = $object['objectGUID'];
                    $i++;
                }
                $objects = $this->getAllObjects("computer");
                foreach($objects as $object) {
                    $allObjects[$i]['type'] = "computer";
                    $allObjects[$i]['dn'] = $object['dn'];
                    $allObjects[$i]['cn'] = $object['cn'];
                    $allObjects[$i]['objectGUID'] = $object['objectGUID'];
                    $i++;
                }
                return $allObjects;
            }
        }
    }
}
