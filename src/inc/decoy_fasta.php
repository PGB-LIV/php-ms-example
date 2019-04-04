<?php
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Writer\FastaWriter;
use pgb_liv\php_ms\Core\Entry\DatabaseEntry;
use pgb_liv\php_ms\Core\Database\DefaultDatabase;

define('FORM_FILE', 'fasta');

if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] == 0) {
    $decoyPrefix = $_POST['prefix'];

    $fastaFile = $_FILES[FORM_FILE]['tmp_name'];
    $reader = new FastaReader($fastaFile);

    header('Content-Disposition: attachment; filename="' . $decoyPrefix . $_FILES[FORM_FILE]['name'] . '"');
    header('Content-type: text/plain;');

    $writer = new FastaWriter('php://output');

    $databases = array();

    foreach ($reader as $protein) {
        // Write non-decoy
        $writer->write($protein);

        $decoy = clone $protein;

        $dbEntry = $protein->getDatabaseEntry();
        $decoyDbKey = $decoyPrefix . $dbEntry->getDatabase()->getPrefix();

        if (! isset($databases[$decoyDbKey])) {
            $database = new DefaultDatabase();
            $database->setPrefix($decoyDbKey);
            $database->setName($dbEntry->getDatabase()
                ->getName() . ' (Decoy)');
            $databases[$decoyDbKey] = $database;
        }

        $decoyEntry = new DatabaseEntry($databases[$decoyDbKey]);
        $decoyEntry->setUniqueIdentifier($dbEntry->getUniqueIdentifier());
        $decoy->setDatabaseEntry($decoyEntry);

        $decoy->setIsDecoy(true);

        $decoy->reverseSequence();

        // Write decoy
        $writer->write($decoy);
    }

    $writer->close();

    exit();
}
?>
<h2>Decoy FASTA</h2>

<?php
if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
}
?>
<p>This tool will generate a decoy database for the uploaded FASTA file
    by reversing each protein. The output will contain both the uploaded
    data and the decoy database.</p>
<form enctype="multipart/form-data"
    action="?page=decoy_fasta&amp;txtonly=1" method="POST">
    <fieldset>
        <label for="file">FASTA File</label> <input
            name="<?php echo FORM_FILE; ?>" type="file" id="file" />
    </fieldset>
    <fieldset>
        <label for="prefix">Decoy Prefix</label> <input type="text"
            value="DECOY_" name="prefix" id="prefix" />
    </fieldset>
    <fieldset>
        <input type="submit" value="Generate Decoys" />
    </fieldset>
</form>