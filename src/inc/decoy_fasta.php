<?php
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Writer\FastaWriter;

if (! empty($_FILES)) {
    if ($_FILES['fasta']['error'] != 0) {
        die('Upload Error: ' . $_FILES['fasta']['error']);
    }
    
    $fastaFile = $_FILES['fasta']['tmp_name'];
    $reader = new FastaReader($fastaFile);
    
    header('Content-type: text/plain;');
    $writer = new FastaWriter('php://output');
    foreach ($reader as $entry) {
        // Write non-decoy
        $writer->write($entry);
        
        // Create decoy
        $entry->setUniqueIdentifier('DECOY_' . $entry->getUniqueIdentifier());
        $entry->reverseSequence();
        
        // Write decoy
        $writer->write($entry);
    }
    
    $writer->close();
    
    exit();
}
?>
<form enctype="multipart/form-data" action="?page=decoy_fasta&amp;txtonly=1"
    method="POST">
    FASTA File: <input name="fasta" type="file" /><br /> <input
        type="submit" value="Send File" /><br />
</form>