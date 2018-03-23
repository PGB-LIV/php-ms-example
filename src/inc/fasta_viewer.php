<?php
use pgb_liv\php_ms\Reader\FastaReader;
?>
<h2>FASTA Viewer</h2>
<form enctype="multipart/form-data" action="?page=fasta_viewer"
    method="POST">
    <fieldset>
        <label for="fasta">FASTA File</label> <input name="fasta"
            id="fasta" type="file" />
    </fieldset>

    <fieldset>
        <input type="submit" value="Upload" />
    </fieldset>
</form>
<?php
if (! empty($_FILES) && $_FILES['fasta']['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
} elseif (! empty($_FILES) && $_FILES['fasta']['error'] == 0) {
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
    ?>
<ul style="float: right;">
<?php
    
    foreach (array_keys($organisms) as $name) {
        echo '<li><a href="#' . ($name) . '">' . $name . '</a></li>';
    }
    ?>
</ul>

<?php
    $modColour = array();
    foreach ($organisms as $name => $proteins) {
        echo '<a id="' . ($name) . '"></a>';
        echo '<h2>' . $name . '</h2>';
        echo '<table class="formattedTable"><thead><tr><th>Identifier</th><th>Entry Name</th><th>Description</th><th>Gene</th><th>Sequence Version</th></tr></thead><tbody>';
        foreach ($proteins as $protein) {
            echo '<tr><td>';
            echo $protein->getUniqueIdentifier();
            echo '</td><td>';
            echo $protein->getEntryName();
            echo '</td><td>';
            echo $protein->getName();
            echo '</td><td>';
            echo $protein->getGeneName();
            echo '</td><td>';
            echo $protein->getSequenceVersion();
            echo '</td></tr>';
            echo '<tr><td colspan="5" style="font-family: monospace; font-size: 0.9em; padding-left: 1em;">';
            
            $index = 0;
            if (! $protein->isModified()) {
                echo wordwrap($protein->getSequence(), 80, '<br />', true);
            } else {
                $locations = array();
                foreach ($protein->getModifications() as $modification) {
                    if (! isset($locations[$modification->getLocation()])) {
                        $locations[$modification->getLocation()] = '';
                    }
                    
                    if (! isset($modColour[$modification->getName()])) {
                        $modColour[$modification->getName()] = 'rgb(' . rand(100, 255) . ',' . rand(100, 255) . ',' .
                             rand(100, 255) . ')';
                    }
                    
                    $locations[$modification->getLocation()] = $modification->getName() . '';
                }
                
                foreach (str_split($protein->getSequence(), 1) as $char) {
                    $aaIndex = $index + 1;
                    
                    if (isset($locations[$aaIndex])) {
                        echo '<b><span style="font-weight: bold; background-color: ' . $modColour[$locations[$aaIndex]] .
                             ';" title="' . $locations[$aaIndex] . '">';
                    }
                    
                    echo $char;
                    
                    if (isset($locations[$aaIndex])) {
                        echo '</span></b>';
                    }
                    
                    $index ++;
                    if ($index % 80 == 0) {
                        echo '<br />';
                    }
                }
            }
            echo '</td></tr>';
        }
        
        echo '</tbody></table>';
    }
}
?>