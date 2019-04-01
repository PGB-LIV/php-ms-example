<?php
use pgb_liv\php_ms\Utility\Digest\DigestFactory;
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Utility\Filter\FilterMass;

set_time_limit(600);
define('FORM_FILE', 'fasta');

if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] == 0) {
    header('Content-type: text/plain;');
    header('Content-Disposition: attachment; filename="' . $_FILES[FORM_FILE]['name'] . '.csv"');
    
    $fastaFile = $_FILES[FORM_FILE]['tmp_name'];
    
    $reader = new FastaReader($fastaFile);
    $digest = DigestFactory::getDigest($_POST['enzyme']);
    $digest->setMaxMissedCleavage((int) $_POST['missedcleave']);
    $digest->setNmeEnabled(false);
    
    if (isset($_POST['nme']) && $_POST['nme'] == 1) {
        $digest->setNmeEnabled(true);
    }
    
    $filter = null;
    if (strlen($_POST['mass']) > 0 && $_POST['mass'] != 0) {
        $tolerance = new Tolerance((float) $_POST['ppm'], Tolerance::PPM);
        $minMass = $_POST['mass'] - $tolerance->getDaltonDelta((float) $_POST['mass']);
        $maxMass = $_POST['mass'] + $tolerance->getDaltonDelta((float) $_POST['mass']);
        
        $filter = new FilterMass($minMass, $maxMass);
    }
    
    foreach ($reader as $protein) {
        $peptides = $digest->digest($protein);
        
        foreach ($peptides as $peptide) {
            if (! is_null($filter) && ! $filter->isValidPeptide($peptide)) {
                continue;
            }
            
            echo $protein->getIdentifier() . ',' . $peptide->getSequence() . ',' . $peptide->getMass() . "\n";
        }
    }
    
    exit();
}
?>
<h2>Protein Digestion</h2>
<?php
if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
}
?>
<p>This tool allows you to upload a FASTA file and to generate a list of
    peptides as would be produced by the chosen enzyme. You may filter
    the peptides to those within a certain mass. The output will be a
    .csv file.</p>
<p>

    The <abbr title="N-terminal Methionine Excision">NME</abbr> option
    sets whether NME should be performed. When enabled any methionine at
    the n-terminus of a protein will be removed. Both the excised and
    non-excised peptide will be returned after digestion. Note, protein
    sequence which do not contain a methionine at the n-terminus will be
    unaffected.
</p>
<form enctype="multipart/form-data"
    action="?page=protein_digest&amp;txtonly=1" method="POST">

    <fieldset>
        <label for="fasta">FASTA File</label> <input
            name="<?php echo FORM_FILE; ?>" type="file" id="fasta" />
    </fieldset>

    <fieldset>
        <label for="enzyme">Enzyme</label> <select name="enzyme"
            id="enzyme">
        <?php
        foreach (DigestFactory::getEnzymes() as $key => $enzyme) {
            echo '<option value="' . $key . '">' . $enzyme . '</option>';
        }
        ?>        
    </select>
    </fieldset>

    <fieldset>
        <label for="nme">N-terminal Methionine Excision</label><input type="checkbox" name="nme"
            id="nme" value="1" />
    </fieldset>

    <fieldset>
        <label for="missedCleave">Missed Cleavages</label><input
            type="text" name="missedcleave" id="missedCleave" value="1" />
    </fieldset>

    <fieldset>
        <label for="mass">Mass Value</label> <input type="text"
            name="mass" id="mass" value="" /> Da &plusmn; <input
            type="text" name="ppm" id="ppm" value="5" class="smallInput" />
        ppm
    </fieldset>

    <input type="submit" value="Send File" />

</form>