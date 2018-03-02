<?php
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Writer\FastaWriter;
use pgb_liv\php_ms\Core\Database\Fasta\PeffFastaEntry;

define('FORM_FILE', 'fasta');

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
                            $mod->setResidues(
                                array(
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

if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] == 0) {
    if ($_FILES[FORM_FILE]['error'] != 0) {
        die('Upload Error: ' . $_FILES[FORM_FILE]['error']);
    }
    
    header('Content-type: text/plain;');
    
    $fastaFile = $_FILES[FORM_FILE]['tmp_name'];
    $reader = new FastaReader($fastaFile);
    $writer = new FastaWriter('php://output', new PeffFastaEntry());
    
    foreach ($reader as $entry) {
        $writer->write($entry);
    }
    
    $writer->close();
    
    exit();
}
?>

<h2>FASTA to PEFF</h2>
<?php
if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
}
?>

<p>This tool will convert an existing FASTA file (e.g. generated by
    UnitProt) and convert it to a PEFF file.</p>

<form enctype="multipart/form-data"
    action="?page=fasta2peff&amp;txtonly=1" method="POST">
    <fieldset>
        <label for="fasta">FASTA File</label> <input
            name="<?php echo FORM_FILE; ?>" id="fasta" type="file" />
    </fieldset>

    <fieldset>
        <input type="submit" value="Upload" />
    </fieldset>
</form>

