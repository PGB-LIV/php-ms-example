<?php
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Writer\FastaWriter;
use pgb_liv\php_ms\Core\Database\Fasta\PeffFastaEntry;

function getModData(Modification $mod, $obo)
{
    $idFound = false;
    foreach ($obo as $line) {
        $line = trim($line);
        $kvp = explode(': ', $line);
        
        if (isset($kvp[1]) && $kvp[1] == $mod->getName()) {
            $idFound = true;
        }
        
        if ($idFound) {
            switch ($kvp[0]) {
                case 'name':
                    $mod->setName($kvp[1]);
                    break;
                case 'xref':
                    $cleanValue = substr($kvp[2], 1, - 1);
                    switch ($kvp[1]) {
                        case 'DiffMono':
                            $mod->setMonoisotopicMass((float) $cleanValue);
                            break;
                        case 'DiffAvg':
                            $mod->setAverageMass((float) $cleanValue);
                            break;
                        case 'Origin':
                            $mod->setResidues(array(
                                $cleanValue
                            ));
                            break;
                        default:
                            // Ignore all else
                            break;
                    }
                    break;
                case '[Term]':
                    return;
                default:
                    // Ignore all else
                    break;
            }
        }
    }
}
if (! empty($_FILES)) {
    if ($_FILES['fasta']['error'] != 0) {
        die('Upload Error: ' . $_FILES['fasta']['error']);
    }
    
    header('Content-type: text/plain;');
    
    $fastaFile = $_FILES['fasta']['tmp_name'];
    $reader = new FastaReader($fastaFile);
    $writer = new FastaWriter('php://output', new PeffFastaEntry());
    
    foreach ($reader as $entry) {
        $writer->write($entry);
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html>
<body>

    <form enctype="multipart/form-data" action="?page=fasta2peff&amp;txtonly=1"
        method="POST">
        <!-- MAX_FILE_SIZE must precede the file input field -->
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
        <!-- Name of input element determines name in $_FILES array -->
        Send this file: <input name="fasta" type="file" /> <input
            type="submit" value="Send File" />
    </form>

</body>
</html>
