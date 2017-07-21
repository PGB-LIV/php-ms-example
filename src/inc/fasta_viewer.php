<?php
use pgb_liv\php_ms\Reader\FastaReader;

?>
<form enctype="multipart/form-data" action="?page=fasta_viewer"
    method="POST">
    FASTA File: <input name="fasta" type="file" /><br /> <input
        type="submit" value="Send File" /><br />
</form>
<?php
if (isset($_FILES['fasta'])) {
    
    $fastaFile = $_FILES['fasta']['tmp_name'];
    
    $reader = new FastaReader($fastaFile);
    
    $organisms = array();
    foreach ($reader as $protein) {
        if (! is_null($protein->getOrganismName())) {
            $organisms[$protein->getOrganismName()][] = $protein;
        } else {
            $organisms['Unknown Organism'][] = $protein;
        }
    }
    
    foreach ($organisms as $name => $proteins) {
        echo '<h2>' . $name . '</h2>';
        echo '<table><thead><tr><th>Accession</th><th>Entry Name</th><th>Description</th><th>Gene</th><th>Sequence Version</th></tr></thead><tbody>';
        foreach ($proteins as $protein) {
            echo '<tr><td>';
            echo $protein->getAccession();
            echo '</td><td>';
            echo $protein->getEntryName();
            echo '</td><td>';
            echo $protein->getName();
            echo '</td><td>';
            echo $protein->getGeneName();
            echo '</td><td>';
            echo $protein->getSequenceVersion();
            echo '</td></tr>';
            echo '<tr><td colspan="5" style="font-family: monospace;">';
            echo wordwrap($protein->getSequence(), 120, '<br />', true);
            echo '</td></tr>';
        }
    }
    
    echo '</tbody></table>';
}
?>