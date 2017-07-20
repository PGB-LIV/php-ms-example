<?php
use pgb_liv\php_ms\Utility\Digest\DigestFactory;
use pgb_liv\php_ms\Reader\FastaReader;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Utility\Filter\FilterMass;
?>
<form enctype="multipart/form-data" action="?page=protein_digest"
    method="POST">
    FASTA File: <input name="fasta" type="file" /><br /> Enzyme: <select
        name="enzyme">
        <?php
        foreach (DigestFactory::getEnzymes() as $key => $enzyme) {
            echo '<option value="' . $key . '">' . $enzyme . '</option>';
        }
        ?>
        
    </select><br /> NME: <input type="checkbox" name="nme" value="1" /><br />
    Missed Cleavages: <input type="text" name="missedcleave" value="1" /><br />
    Peptide Mass: <input type="text" name="mass" value="0" /> (If 0,
    filter unused)<br /> Mass Tolerance: <input type="text" name="ppm"
        value="5" /> ppm<br /> <input type="submit" value="Send File" /><br />
</form>

<?php
if (isset($_FILES['fasta'])) {
    $fastaFile = $_FILES['fasta']['tmp_name'];
    
    $reader = new FastaReader($fastaFile);
    $digest = DigestFactory::getDigest($_POST['enzyme']);
    $digest->setMaxMissedCleavage((int) $_POST['missedcleave']);
    $digest->setNmeEnabled(false);
    
    if (isset($_POST['nme']) && $_POST['nme'] == 1) {
        $digest->setNmeEnabled(true);
    }
    
    $filter = null;
    if ($_POST['mass'] != 0) {
        $tolerance = new Tolerance((float) $_POST['ppm'], Tolerance::PPM);
        $minMass = $_POST['mass'] - $tolerance->getDaltonDelta((float) $_POST['mass']);
        $maxMass = $_POST['mass'] + $tolerance->getDaltonDelta((float) $_POST['mass']);
        
        $filter = new FilterMass($minMass, $maxMass);
    }
    
    foreach ($reader as $protein) {
        $headerShown = false;
        
        $peptides = $digest->digest($protein);
        foreach ($peptides as $peptide) {
            if (! is_null($filter) && ! $filter->isValidPeptide($peptide)) {
                continue;
            }
            
            if (! $headerShown) {
                echo '<hr />';
                echo '<strong>' . $protein->getUniqueIdentifier() . '</strong><br />';
                $headerShown = true;
            }
            
            echo ' ' . $peptide->getSequence() . ' ' . $peptide->getMass() . '<br />';
        }
    }
}