<?php
class AppInstaller {
    public $ID;
    public $Name;
    public $Version;
    public $Path;
    public $HexSHA256;
    function __construct($ID, $name, $version, $path, $hexSHA256){
        $this->ID = $ID;
        $this->Name = $name;
        $this->Version = $version;
        $this->Path = $path;
        $this->HexSHA256 = $hexSHA256;
    }
}
