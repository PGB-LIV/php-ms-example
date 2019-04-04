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

    $headers = array();
    foreach ($reader as $protein) {
        if (! is_null($protein->getOrganism())) {
            $organisms[$protein->getOrganism()->getName()][] = $protein;
        } else {
            $organisms['Unknown Organism'][] = $protein;
        }

        if (! isset($headers['gene']) && ! is_null($protein->getGene())) {
            $headers['gene'] = true;
        }

        if (! isset($headers['chromosome']) && ! is_null($protein->getChromosome())) {
            $headers['chromosome'] = true;
        }

        if (! isset($headers['transcript']) && ! is_null($protein->getTranscript())) {
            $headers['transcript'] = true;
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
        echo '<table class="formattedTable"><thead><tr><th>Identifier</th><th>Description</th>';
        if (isset($headers['gene'])) {
            echo '<th>Gene</th>';
        }

        if (isset($headers['transcript'])) {
            echo '<th>Transcript Type</th>';
        }

        if (isset($headers['chromosome'])) {
            echo '<th>Chromosome</th>';
        }

        echo '</tr></thead><tbody>';
        foreach ($proteins as $protein) {
            $database = $protein->getDatabaseEntry();

            echo '<tr><td>';
            echo '<span title="' . $database->getDatabase()->getName() . '">' . $database->getUniqueIdentifier() .
                '</span>';
            echo '</td><td>';
            echo $protein->getDescription();
            echo '</td>';
            if (isset($headers['gene'])) {
                echo '<td>';
                if (! is_null($protein->getGene())) {
                    $gene = $protein->getGene();
                    echo $gene->getSymbol();

                    if (! is_null($gene->getType())) {
                        echo ' (' . $gene->getType() . ')';
                    }
                } else {
                    echo '&nbsp;';
                }
                echo '</td>';
            }

            if (isset($headers['transcript'])) {
                echo '<td>';
                if (! is_null($protein->getTranscript())) {
                    $transcript = $protein->getTranscript();

                    if (! is_null($gene->getType())) {
                        echo $transcript->getType();
                    }
                } else {
                    echo '&nbsp;';
                }

                echo '</td>';
            }

            if (isset($headers['chromosome'])) {
                echo '<td>';
                if (! is_null($protein->getChromosome())) {
                    $chromosome = $protein->getChromosome();

                    echo $chromosome->getName();
                } else {
                    echo '&nbsp;';
                }
                echo '</td>';
            }

            echo '</tr>';
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