<?php
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Utility\Filter\FilterLength;

$sequence = 'PEPTIDE';
if (isset($_POST['sequence'])) {
    $sequence = $_POST['sequence'];
}
?>


<!DOCTYPE html>
<html>
<body>

    <form enctype="multipart/form-data" action="fasta_filter.php"
        method="POST">
        <!-- MAX_FILE_SIZE must precede the file input field -->
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
        <!-- Name of input element determines name in $_FILES array -->
        FASTA File: <input name="fasta" type="file" /> <input
            type="submit" value="Send File" />
        Peptide Mass
    </form>

</body>
</html>
<?php
if (! empty($_FILES)) {
    if ($_FILES['fasta']['error'] != 0) {
        die('Upload Error: ' . $_FILES['fasta']['error']);
    }
    
    header('Content-type: text/plain;');
    
    $fastaFile = $_FILES['fasta']['tmp_name'];
    $reader = new FastaReader($fastaFile);
    
    $filter = new FilterLength(10, 15);
    
    foreach ($reader as $protein) {
        $peptides = $digest->digest($protein);
        
        echo $protein->getUniqueIdentifier() . PHP_EOL;
        foreach ($peptides as $peptide) {
            if (! $filter->isValidPeptide($peptide)) {
                continue;
            }
            
            echo "\t" . $peptide->getSequence() . "\t" . $peptide->calculateMass() . PHP_EOL;
        }
    }
}
