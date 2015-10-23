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
    
    const dataBaseUrl = '/mnt/nas';
    
    public function exportNFS() {
        $shares = $this->findBy(array(
            'shareType_id' => 3
        ));

        $tmpfname = tempnam("/tmp", "nfsexport");
        $handle = fopen($tmpfname, "w");

        foreach ($shares as $share) {
            $line = self::dataBaseUrl.$share->folder->name." ".$share->var."(rw,all_squash,no_subtree_check,anonuid=65534,anongid=65534)";
            fwrite($handle, $line."\n");
        }
        
        fclose($handle);
    }
}
