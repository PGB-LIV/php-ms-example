<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Reader\MzIdentMlReader1r1;

set_time_limit(600);
?>
<h2>MzIdentML Viewer</h2>

<form enctype="multipart/form-data" action="?page=mzidentml_viewer"
    method="POST">
    <fieldset>
        <label for="file">MzIdentML File</label> <input name="mzidentml"
            type="file" id="file" /> (.gz or .mzid supported)
    </fieldset>

    <fieldset>
        <input type="submit" value="Send File" />
    </fieldset>
</form>
<?php

if (! empty($_FILES)) {
    $mzIdentMlFile = $_FILES['mzidentml']['tmp_name'];
    
    if (substr_compare($_FILES['mzidentml']['name'], '.gz', strlen($_FILES['mzidentml']['name']) - 3, strlen($_FILES['mzidentml']['name'])) === 0) {
        // This input should be from somewhere else, hard-coded in this example
        $file_name = $_FILES['mzidentml']['tmp_name'];
        
        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time
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
    echo '<h1>' . $_FILES['mzidentml']['name'] . '</h1>';
    
    $reader = MzIdentMlReaderFactory::getReader($mzIdentMlFile);
    ?>
<h2>Software</h2>
<?php
    foreach ($reader->getAnalysisSoftwareList() as $software) {
        echo $software['name'] . ' ';
        if (isset($software['version'])) {
            echo $software['version'];
        }
        
        echo '<br />';
    }
    ?>

<h2>Protocol</h2>

<?php
    $protocolCollection = $reader->getAnalysisProtocolCollection();
    
    if (isset($protocolCollection['spectrum'])) {
        echo '<h3>Spectrum Protocol</h3>';
        
        foreach ($protocolCollection['spectrum'] as $key => $protocol) {
            echo '<h4>Software</h4>';
            
            echo $protocol['software']['name'] . ' ';
            if (isset($protocol['software']['version'])) {
                echo $protocol['software']['version'];
            }
            
            echo '<br />';
            if (isset($protocol['fragmentTolerance']) || isset($protocol['parentTolerance'])) {
                echo '<h4>Tolerances</h4>';
                
                if (isset($protocol['fragmentTolerance'])) {
                    foreach ($protocol['fragmentTolerance'] as $tolerance) {
                        echo 'Fragment: ' . $tolerance->getTolerance() . ' ' . $tolerance->getUnit() . '<br />';
                    }
                }
                
                if (isset($protocol['parentTolerance'])) {
                    foreach ($protocol['parentTolerance'] as $tolerance) {
                        echo 'Precursor: ' . $tolerance->getTolerance() . ' ' . $tolerance->getUnit() . '<br />';
                    }
                }
            }
            
            if (isset($protocol['enzymes'])) {
                echo '<h4>Enzymes</h4>';
                
                foreach ($protocol['enzymes'] as $enzyme) {
                    if (isset($enzyme['EnzymeName']['name'])) {
                        $name = $enzyme['EnzymeName']['name'];
                    } else {
                        $name = $enzyme['id'];
                    }
                    
                    echo $name . ' (' . $enzyme['missedCleavages'] . ' Missed cleavages)<br />';
                }
            }
            
            if (isset($protocol['modifications'])) {
                echo '<h4>Modifications</h4>';
                
                foreach ($protocol['modifications'] as $modification) {
                    echo '[' . ($modification->isFixed() ? 'F' : 'V') . '] ' . $modification->getName() . ' ' . $modification->getMonoisotopicMass() . ' ' . implode('', $modification->getResidues()) . '<br />';
                }
            }
        }
    }
    if (isset($protocolCollection['protein'])) {
        $protocol = $protocolCollection['protein'];
        echo '<h3>Protein Protocol</h3>';
        echo '<h4>Software</h4>';
        
        echo $protocol['software']['name'] . ' ';
        if (isset($protocol['software']['version'])) {
            echo $protocol['software']['version'];
        }
        echo '<h4>Threshold</h4>';
        
        foreach ($protocol['threshold'] as $threshold) {
            echo $threshold[MzIdentMlReader1r1::CV_ACCESSION] . ': ' . $threshold['name'];
        }
    }
    ?>
<h2>Results</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Peptide</th>
            <th>Protein</th>
            <th>m/z</th>
            <th>Charge</th>
            <th colspan="4">Scores</th>
    
    </thead>
    <tbody>
<?php
    foreach ($reader->getAnalysisData() as $key => $spectra) {
        echo '<tr>';
        echo '<td>' . $key . '</td>';
        echo '<td>' . $spectra->getTitle() . '</td>';
        echo '<td>' . current($spectra->getIdentifications())->getPeptide()->getSequence() . '</td>';
        echo '<td>' . current($spectra->getIdentifications())->getPeptide()
            ->getProtein()
            ->getAccession() . '</td>';
        echo '<td align="right">' . number_format($spectra->getMassCharge(), 4) . '</td>';
        echo '<td align="right">' . $spectra->getCharge() . '</td>';
        
        foreach (current($spectra->getIdentifications())->getScores() as $score) {
            echo '<td align="right">' . $score . '</td>';
        }
        
        echo '</tr>';
    }
    ?>   
    </tbody>
</table>

<hr />
<?php
}