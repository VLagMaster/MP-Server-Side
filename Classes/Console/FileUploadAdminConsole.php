<?php
include($_SERVER['DOCUMENT_ROOT'] . '/Classes/Console/AdminConsole.php');
class FileUploadAdminConsole extends AdminConsole{
    private $path = NULL;
    private $webPath = "/Files/";
    function __construct(){
        $this->path = $_SERVER['DOCUMENT_ROOT'] . "/Files/";
    }
    public function removeInstaller($path){
        if(file_exists($this->path . $path) && str_starts_with(realpath($this->path . $path), $this->path)){
            unlink($this->path . $path);
            if(file_exists($this->path . $path . ".hash")){
                unlink($this->path . $path . ".hash");
            }
        }
    }
    public function getAllInstallers(){
        $files = scandir($this->path);
        $installers = [];
        $i = 0;
        foreach($files as $file) {
            if(filetype($this->path . $file) == "file" && str_ends_with($file, ".msi")){
                $installers[$i]['filename'] = $file;
                if(file_exists($this->path . $file . ".hash")){
                    $installers[$i]['hash'] = file_get_contents($this->path . $file . ".hash");
                }
                $i++;
            }
        }
        ?>
        <table>
        <tr>
            <th>
                Filename
            </th>
            <th>
                Link
            </th>
            <th>
                SHA-256
            </th>
            <th>
                Manage
            </th>
        </tr>
        <?php
            foreach($installers as $installer) {
                ?>
                    <tr>
                    <form method="post">
                        <td>
                            <input type="hidden" name="filename" value="<?=$installer['filename']?>">
                            <?=$installer['filename']?>
                        </td>
                        <td>
                            <a href="<?=$this->webPath . $installer['filename']?>"><?=$this->webPath . $installer['filename']?>
                            </a>
                        </td>
                        <td>
                            <?=bin2hex($installer['hash'])?>
                        </td>
                        <td>
                            <input type="submit" name="remove" value="remove">
                        </td>
                    </form>
                    </tr>
                <?php
            }

        ?>
        </table>
        <?php
    }
    private function getAbsolutePath($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ($part === '.' || $part === '..'){
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return "/" . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
    public function UploadInstaller($installer){
        $targetFile = $this->path . basename($installer["name"]);
        if(str_starts_with($this->getAbsolutePath($targetFile), $this->getAbsolutePath($this->path)) && !file_exists($targetFile) &&  (strtolower(pathinfo($installer['name'],PATHINFO_EXTENSION)) == "msi") && move_uploaded_file($installer['tmp_name'], $this->getAbsolutePath($targetFile))){
            $hashFile = fopen($this->getAbsolutePath($targetFile) . ".hash", "w");
            fwrite($hashFile, hash_file("sha256", $this->getAbsolutePath($targetFile), true));
        }
    }
}
?>