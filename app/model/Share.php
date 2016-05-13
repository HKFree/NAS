<?php

namespace App\Model;

/**
 * Description of Share
 *
 * @author bkralik
 */
class Share extends Table {
    /**
    * @var string
    */
    protected $tableName = 'share';
    
    const shareuid = 65000;
    const sharegid = 65000;
    
    const dataBaseUrl = '/mnt/nas';
    const nfsParams = 'rw,all_squash,insecure,removable,anonuid=65000,anongid=65000';
    
    public function exportNFS() {
        $shares = $this->findBy(array(
            'shareType_id' => 3
        ));

        $tmpfname = tempnam("/tmp", "nfsexport");
        $handle = fopen($tmpfname, "w");

        foreach ($shares as $share) {
            $line = self::dataBaseUrl . $share->folder->name . " " . $share->var . "(" . self::nfsParams . ")";
            fwrite($handle, $line."\n");
        }
        
        exec('sudo ../app/scripts/exportnfs.sh '.$tmpfname);
        
        fclose($handle);
    }
    
    public function checkShare($shareString) {
        $tmpfname = tempnam("/tmp", "nfstest");
        $handle = fopen($tmpfname, "w");
        fclose($handle);
        
        exec('sudo ../app/scripts/testnfs.sh '.$tmpfname, $out);
        if(preg_match('/error/', $out[0])) {
            // TODO
        }
                
        unlink($tmpfname);
    }
}
