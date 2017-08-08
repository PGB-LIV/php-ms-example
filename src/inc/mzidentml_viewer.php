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
<ul style="float: right;">
    <li><a href="#software">Software</a></li>
    <li><a href="#protocol">Protocol</a></li>
    <li><a href="#peptides">Peptide Spectrum Matches</a></li>
    <li><a href="#proteins">Protein Groups</a></li>
</ul>

<a name="software" />
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

<a name="protocol" />
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

<a name="peptides" />
<h2>Peptide Spectrum Matches</h2>
<?php
    foreach ($reader->getAnalysisData() as $spectra) {
        echo '<h3>' . $spectra->getTitle() . '</h3>';
        
        echo '<ul style="float: right;">';
        echo '<li>m/z: ' . number_format($spectra->getMassCharge(), 4) . 'Da</li>';
        echo '<li>Mass: ' . number_format($spectra->getMass(), 4) . 'Da</li>';
        echo '<li>Charge: ' . $spectra->getCharge() . '</li>';
        echo '</ul>';
        
        foreach ($spectra->getIdentifications() as $identification) {
            echo '<h4 style="margin-left: 1em;">' . $identification->getPeptide()->getSequence() . ' <em>(' . $identification->getPeptide()
                ->getProtein()
                ->getAccession() . ')</em></h4>';
            
            echo '<ul style="float: left; margin-left: 1em;">';
            
            foreach ($identification->getScores() as $scoreName => $scoreValue) {
                echo '<li>' . $scoreName . ': ' . $scoreValue . '</li>';
            }
            echo '</ul>';
            
            echo '<ul style="float: left;">';
            foreach ($identification->getPeptide()->getModifications() as $modification) {
                echo '<li>@' . $modification->getLocation() . ' ' . $modification->getName() . ' (' . $modification->getMonoisotopicMass() . ')</li>';
            }
            echo '</ul>';
        }
        
        echo '<hr style="clear: both;" />';
    }
    
    ?>

<a name="proteins" />
<h2>Protein Groups</h2>

<p>Each table shows the protein accession and the associated peptide
    evidence within the group.</p>
<?php
    $proteinGroups = $reader->getProteinDetectionList();
    if (! is_null($proteinGroups)) {
        foreach ($proteinGroups as $id => $group) {
            echo '<h3>' . $id . '</h3>';
            foreach ($group as $id => $hypothesis) {
                echo '<table class="formattedTable">';
                echo '<tr>';
                echo '<th rowspan="' . (count($hypothesis['peptides']) + 1) . '">';
                echo $hypothesis['protein']->getAccession() . '</th>';
                echo '<td>' . $hypothesis['protein']->getDescription() . '</th></tr>';
                
                foreach ($hypothesis['peptides'] as $peptide) {
                    echo '<td>' . $peptide->getSequence() . '</td>';
                    echo '</tr>';
                }
                echo '</table><hr />';
            }
        }
    }
    else
    {
        echo '<p>No protein group data present.</p>';
    }
}
