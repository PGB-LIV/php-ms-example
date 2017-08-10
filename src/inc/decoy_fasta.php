<?php
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Writer\FastaWriter;

define('FORM_FILE', 'fasta');

if (! empty($_FILES)) {
    if ($_FILES[FORM_FILE]['error'] != 0) {
        die('Upload Error: ' . $_FILES[FORM_FILE]['error']);
    }
    
    $decoyPrefix = $_POST['prefix'];
    
    $fastaFile = $_FILES[FORM_FILE]['tmp_name'];
    $reader = new FastaReader($fastaFile);
    
    header('Content-type: text/plain;');
    $writer = new FastaWriter('php://output');
    foreach ($reader as $entry) {
        // Write non-decoy
        $writer->write($entry);
        
        // Create decoy
        $entry->setUniqueIdentifier($decoyPrefix . $entry->getUniqueIdentifier());
        $entry->reverseSequence();
        
        // Write decoy
        $writer->write($entry);
    }
    
    $writer->close();
    
    exit();
}
?>
<h2>Decoy FASTA</h2>

<p>This panel will generate a decoy database for the uploaded FASTA file
    by reversing each protein. The output will contain both the uploaded
    data and the decoy database.</p>
<form enctype="multipart/form-data"
    action="?page=decoy_fasta&amp;txtonly=1" method="POST">
    <fieldset>
        <label for="file">FASTA File</label> <input name="<?php echo FORM_FILE; ?>"
            type="file" id="file" />
    </fieldset>
    <fieldset>
        <label for="prefix">Decoy Prefix</label> 
        <input type="text" value="DECOY_" name="prefix" id="prefix" />
    </fieldset>
    <fieldset>
        <input type="submit" value="Generate Decoys" />
    </fieldset>
</form>