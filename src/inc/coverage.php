<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Reader\MzIdentMlReader1r1;
use pgb_liv\php_ms\Reader\FastaReader;

set_time_limit(600);

define('FORM_MZIDENTML', 'mzidentml');
define('FORM_FASTA', 'fasta');
?>
<h2>Sequence Coverage</h2>
<?php
if (! empty($_FILES) && ($_FILES[FORM_MZIDENTML]['error'] != 0 || $_FILES[FORM_FASTA]['error'] != 0)) {
    echo '<p>An error occured. Ensure you included a file to upload.</p>';
}
?>

<p>Calculate the protein-level sequence coverage from an mzIdentML file
    (must contain protein group results) and the corresponding FASTA
    file.</p>
<p>Note, only mzIdentML 1.1 and 1.2 are currently supported.</p>

<form enctype="multipart/form-data" action="?page=coverage"
    method="POST">
    <fieldset>
        <label for="mzidentml">mzIdentML File</label> <input
            name="<?php echo FORM_MZIDENTML; ?>" id="mzidentml"
            type="file" /> (.gz or .mzid supported)
    </fieldset>
    <fieldset>
        <label for="fasta">FASTA File</label> <input
            name="<?php echo FORM_FASTA; ?>" id="fasta" type="file" />
    </fieldset>

    <fieldset>
        <input type="submit" value="Send File" />
    </fieldset>
</form>
<?php

if (! empty($_FILES) && $_FILES[FORM_MZIDENTML]['error'] == 0 && $_FILES[FORM_FASTA]['error'] == 0) {
    $name = $_FILES[FORM_MZIDENTML]['name'];
    $mzIdentMlFile = $_FILES[FORM_MZIDENTML]['tmp_name'];

    if (substr_compare($_FILES[FORM_MZIDENTML]['name'], '.gz', strlen($_FILES[FORM_MZIDENTML]['name']) - 3) === 0) {
        // This input should be from somewhere else, hard-coded in this example
        $file_name = $_FILES[FORM_MZIDENTML]['tmp_name'];

        // Raising this value may increase performance
        // read 4kb at a time
        $buffer_size = 4096;
        $out_file_name = $file_name . '_decom';

        // Open our files (in binary mode)
        $file = gzopen($file_name, 'rb');
        $out_file = fopen($out_file_name, 'wb');

        // Keep repeating until the end of the input file
        while (! gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        $mzIdentMlFile = $out_file_name;
    }
    ?>
<h3><?php echo $name; ?></h3>

<p>Only protein sequences which have any coverage are shown. Protein
    sequence with no coverage or peptide sequence referencing protein
    sequences not found in the FASTA file (i.e. generated decoys) will
    not be shown.</p>
<?php
    $mzident = MzIdentMlReaderFactory::getReader($mzIdentMlFile);

    $fasta = new FastaReader($_FILES[FORM_FASTA]['tmp_name']);

    $proteins = array();
    foreach ($fasta as $protein) {
        $proteins[$protein->getIdentifier()] = $protein->getSequence();
    }

    $peptides = array();
    foreach ($mzident->getAnalysisData() as $spectra) {
        foreach ($spectra->getIdentifications() as $identification) {
            foreach ($identification->getPeptide()->getProteins() as $proteinEntry) {
                $acc = $proteinEntry->getProtein()->getIdentifier();

                $hit = array();
                $hit['mods'] = $identification->getPeptide()->getModifications();
                $hit['proteinEntry'] = $proteinEntry;

                $peptides[$acc][] = $hit;
            }
        }
    }

    $modLookup = array();
    foreach ($mzident->getAnalysisProtocolCollection()[MzIdentMlReader1r1::PROTOCOL_SPECTRUM] as $protocol) {
        if (isset($protocol['modifications'])) {
            foreach ($protocol['modifications'] as $modification) {
                if (! isset($modLookup[$modification->getName()])) {
                    $modLookup[$modification->getName()]['abbr'] = substr($modification->getName(), 0, 1);
                    $modLookup[$modification->getName()]['colour'] = dechex(rand(0, 255)) . dechex(rand(0, 255)) .
                        dechex(rand(0, 255));
                }
            }
        }
    }

    echo '<dl style="float: right;">';
    foreach ($modLookup as $name => $properties) {
        echo '<dt class="sequence" style="background-color: #' . $properties['colour'] . '">' . $properties['abbr'] .
            '</dt>';
        echo '<dd>' . $name . '</dd>';
    }

    echo '</dl>';

    foreach ($peptides as $accession => $hits) {
        // Skip decoy
        if (! isset($proteins[$accession])) {
            // Check if some element has been dropped
            $isFound = false;
            foreach (array_keys($proteins) as $protein) {
                if (strpos($protein, $accession) !== false) {
                    $accession = $protein;
                    $isFound = true;
                    break;
                }
            }

            if (! $isFound) {
                continue;
            }
        }

        echo '<h4>' . $accession . '</h4>';

        $sequence = $proteins[$accession];

        $coverage = array();
        for ($i = 0; $i < strlen($sequence); $i ++) {
            $coverage[$i] = 0;
        }

        $mods = array();
        foreach ($hits as $peptide) {
            for ($i = $peptide['proteinEntry']->getStart(); $i <= $peptide['proteinEntry']->getEnd(); $i ++) {
                $coverage[$i - 1] = 1;
            }

            foreach ($peptide['mods'] as $modification) {
                $pos = $peptide['proteinEntry']->getStart() + $modification->getLocation();
                $pos -= 2;
                if (! isset($mods[$pos])) {
                    $mods[$pos] = array();
                }

                $mods[$pos][] = $modification->getName();
                $mods[$pos] = array_unique($mods[$pos]);
            }
        }

        ksort($coverage);

        $pepIndex = 0;
        do {
            $modTier = 0;
            do {
                $foundModTier = false;
                $lastIndex = 0;
                for ($i = 0; $i < 100; $i ++) {
                    $modIndex = $pepIndex + $i;

                    if ($pepIndex >= strlen($sequence)) {
                        break;
                    }

                    if (isset($mods[$modIndex][$modTier])) {
                        $position = $modIndex - $lastIndex;
                        $position -= $pepIndex;
                        $lastIndex += $position + 1;

                        $colour = $modLookup[$mods[$modIndex][$modTier]]['colour'];
                        $abbr = $modLookup[$mods[$modIndex][$modTier]]['abbr'];
                        echo '<span class="sequence" style="background-color: #' . $colour . '; margin-left: ' .
                            $position . 'ch">' . $abbr . '</span>';
                        $foundModTier = true;
                    }
                }

                $modTier ++;

                if ($foundModTier) {
                    echo '<br />' . PHP_EOL;
                }
            } while ($foundModTier);

            $isCovering = false;

            echo '<span class="sequence">';
            for ($i = 0; $i < 100; $i ++) {
                $seqIndex = $pepIndex + $i;

                if ($seqIndex >= strlen($sequence)) {
                    break;
                }

                if ($coverage[$seqIndex] == 1 && ! $isCovering) {
                    $isCovering = true;
                    echo '<span style="background-color: #d1ffd1;">';
                }

                if ($coverage[$seqIndex] == 0 && $isCovering) {
                    $isCovering = false;
                    echo '</span>';
                }

                echo $sequence[$seqIndex];
            }

            if ($isCovering) {
                $isCovering = false;
                echo '</span>';
            }

            echo '</span><br />' . PHP_EOL;

            $pepIndex += 100;
            if ($pepIndex > strlen($sequence)) {
                break;
            }
        } while ($pepIndex < strlen($sequence));

        echo '<hr />';
    }
}