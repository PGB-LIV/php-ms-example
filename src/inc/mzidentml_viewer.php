<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Reader\MzIdentMlReader1r1;
use pgb_liv\php_ms\Core\Modification;

set_time_limit(600);

define('FORM_FILE', 'mzidentml');
?>
<h2>mzIdentML Viewer</h2>

<form enctype="multipart/form-data" action="?page=mzidentml_viewer"
    method="POST">
    <fieldset>
        <label for="file">mzIdentML File</label> <input
            name="<?php echo FORM_FILE; ?>" type="file" id="file" />
        (.gz or .mzid supported)
    </fieldset>

    <fieldset>
        <input type="submit" value="Send File" />
    </fieldset>
</form>
<?php

if (! empty($_FILES) || isset($_GET['search'])) {
    if (! empty($_FILES)) {
        $name = $_FILES[FORM_FILE]['name'];
        $mzIdentMlFile = $_FILES[FORM_FILE]['tmp_name'];
        
        if (substr_compare($_FILES[FORM_FILE]['name'], '.gz', strlen($_FILES[FORM_FILE]['name']) - 3) === 0) {
            // This input should be from somewhere else, hard-coded in this example
            $file_name = $_FILES[FORM_FILE]['tmp_name'];
            
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
    } else {
        $mzIdentMlFile = sys_get_temp_dir() . '/' . $_GET['search'] . '.mzid';
        $name = $_GET['name'];
    }
    
    echo '<h1>' . $name . '</h1>';
    
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
                    echo '[' . ($modification->isFixed() ? 'F' : 'V') . '] ' . $modification->getName() . ' (' .
                         implode(',', $modification->getResidues());
                    
                    if ($modification->getPosition() != Modification::POSITION_ANY) {
                        echo '@' . $modification->getPosition();
                    }
                    
                    echo ') ' . $modification->getMonoisotopicMass() . ' ' . '<br />';
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
    echo '<table style="font-size:0.75em;" class="formattedTable hoverableRow">';
    
    $headerShown = false;
    $scanTitleEnabled = false;
    
    foreach ($reader->getAnalysisData() as $spectra) {
        if (! $headerShown) {
            $scoresHeader = '';
            
            foreach ($spectra->getIdentifications() as $identification) {
                foreach ($identification->getScores() as $scoreName => $scoreValue) {
                    $scoresHeader .= '<th>' . $reader->getCvParamName($scoreName) . '</th>';
                }
                break;
            }
            
            echo '<thead><tr>';
            
            if (! is_null($spectra->getTitle())) {
                echo '<th>Scan</th>';
                $scanTitleEnabled = true;
            }
            
            echo '<th>m/z</th><th>z</th><th>Peptide</th><th>Protein</th><th>Mods</th>' . $scoresHeader .
                 '</tr></thead><tbody>';
            
            $headerShown = true;
        }
        
        foreach ($spectra->getIdentifications() as $identification) {
            if ($identification->getPeptide()->isDecoy()) {
                echo '<tr class="decoy">';
            } else {
                echo '<tr>';
            }
            
            if ($scanTitleEnabled) {
                echo '<td style="font-weight: bold;">' . wordwrap($spectra->getTitle(), 32, '<br />', true) . '</td>';
            }
            
            echo '<td>' . number_format($spectra->getMassCharge(), 2) . '</td>';
            echo '<td>' . $spectra->getCharge() . '</td>';
            
            echo '<td class="sequence">' . wordwrap($identification->getPeptide()->getSequence(), 16, '<br />', true) . '</td>';
            echo '<td>' . $identification->getPeptide()
                ->getProtein()
                ->getAccession() . '</td>';
            
            echo '<td>';
            $mods = array();
            foreach ($identification->getPeptide()->getModifications() as $modification) {
                $mods[$modification->getName()][] = $modification->getLocation();
            }
            
            foreach ($mods as $name => $positions) {
                echo '[' . implode(',', $positions) . ']' . $name . ' ';
            }
            echo '</td>';
            
            foreach ($identification->getScores() as $scoreName => $scoreValue) {
                echo '<td>' . $scoreValue . '</td>';
            }
            
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    
    ?>

<p class="decoy">* Decoy results</p>

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
                echo '<h4>' . $hypothesis['protein']->getAccession() . '</h4>';
                echo '<p>' . $hypothesis['protein']->getDescription() . '</p>';
                
                echo '<dl style="float: left; margin-left: 1em;">';
                foreach ($hypothesis['cvParam'] as $cvParam) {
                    echo '<dt>' . $reader->getCvParamName($cvParam['accession']) . '</dt>';
                    echo '<dd>' . (isset($cvParam['value']) ? $cvParam['value'] : '&nbsp') . '</dd>';
                }
                echo '</dl>';
                
                echo '<ul style="float: left; margin-left: 1em;">';
                foreach ($hypothesis['peptides'] as $peptide) {
                    echo '<li>' . $peptide->getSequence() . '</li>';
                }
                echo '</ul>';
                
                echo '<hr style="clear: both;" />';
            }
        }
    } else {
        echo '<p>No protein group data present.</p>';
    }
}
