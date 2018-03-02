<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Utility\Fragment\YFragment;

set_time_limit(600);

define('FORM_IDENT', 'ident');
define('FORM_RAW', 'raw');
?>
<h2>Spectra Annotation</h2>

<p>This tool will map your identifications to your raw data and then
    identifies the possible fragment matches that may have occured.
    Note, this tool relies upon your mzIdentML data having reference to
    the correct MGF entry. Further, if the fragment tolerance is not
    specified then it will automatically default to 10ppm.</p>
<p>Note, only mzIdentML 1.1 and 1.2 are currently supported.</p>

<?php
if (! empty($_FILES) && ($_FILES[FORM_IDENT]['error'] != 0 || $_FILES[FORM_RAW]['error'] != 0)) {
    echo '<p>An error occured. Ensure you included a file to upload.</p>';
}
?>

<form enctype="multipart/form-data" action="?page=spectra_annotator"
    method="POST">
    <fieldset>
        <label for="ident">mzIdentML File</label> <input
            name="<?php echo FORM_IDENT; ?>" id="ident" type="file" />
        (.gz or .mzid supported)
    </fieldset>
    <fieldset>
        <label for="raw">MGF File</label> <input
            name="<?php echo FORM_RAW; ?>" id="raw" type="file" />
    </fieldset>

    <fieldset>
        <input type="submit" value="Send File" />
    </fieldset>
</form>
<?php
if (! empty($_FILES) && $_FILES[FORM_IDENT]['error'] == 0 && $_FILES[FORM_RAW]['error'] == 0) {
    $name = $_FILES[FORM_IDENT]['name'];
    $mzIdentMlFile = $_FILES[FORM_IDENT]['tmp_name'];
    
    if (substr_compare($_FILES[FORM_IDENT]['name'], '.gz', strlen($_FILES[FORM_IDENT]['name']) - 3) === 0) {
        // This input should be from somewhere else, hard-coded in this example
        $file_name = $_FILES[FORM_IDENT]['tmp_name'];
        
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

<?php
    $spectraLookup = array();
    
    $raw = new MgfReader($_FILES[FORM_RAW]['tmp_name']);
    $count = 0;
    foreach ($raw as $spectra) {
        $spectraLookup['index=' . $count] = $spectra;
        $count ++;
    }
    
    $mzidentml = MzIdentMlReaderFactory::getReader($mzIdentMlFile);
    
    $noIdentTitle = false;
    foreach ($mzidentml->getAnalysisData() as $spectra) {
        foreach ($spectra->getIdentifications() as $identification) {
            $identification->getPeptide()->setSequence(
                str_replace('X', '', $identification->getPeptide()
                    ->getSequence()));
            
            if (! isset($spectraLookup[$spectra->getIdentifier()])) {
                continue;
            }
            
            $spectraLookup[$spectra->getIdentifier()]->addIdentification($identification);
        }
    }
    
    $protocolCollection = $mzidentml->getAnalysisProtocolCollection();
    
    $tolerance = null;
    if (isset($protocolCollection['spectrum'])) {
        foreach ($protocolCollection['spectrum'] as $key => $protocol) {
            if (isset($protocol['fragmentTolerance'])) {
                $tolerance = $protocol['fragmentTolerance'][0];
            } elseif (isset($protocol['additions']['user']['Instrument'])) {
                
                // MS-GF+ specifies an instrument rather than tolerance
                if ($protocol['additions']['user']['Instrument'] == 'LowRes') {
                    $tolerance = new Tolerance(0.6, Tolerance::DA);
                }
            }
        }
    }
    
    if (is_null($tolerance)) {
        $tolerance = new Tolerance(10, Tolerance::PPM);
    }
    
    echo 'Fragment Tolerance: ' . $tolerance->getTolerance() . $tolerance->getUnit() . '<br />';
    
    echo '<table style="font-size:0.75em;" class="formattedTable hoverableRow"><thead><tr><th>Scan</th><th>Sequence</th><th>B Ions</th><th>Y Ions</th></tr></thead><tbody>';
    foreach ($spectraLookup as $spectra) {
        if (count($spectra->getIdentifications()) == 0) {
            continue;
        }
        
        foreach ($spectra->getIdentifications() as $identification) {
            $bIons = (new BFragment($identification->getPeptide()))->getIons();
            $yIons = (new YFragment($identification->getPeptide()))->getIons();
            $fragIons = array();
            foreach ($spectra->getFragmentIons() as $ion) {
                $fragIons[] = $ion->getMassCharge();
            }
            
            $bMatches = array();
            foreach ($fragIons as $fragIon) {
                foreach ($bIons as $bIndex => $bIon) {
                    if ($tolerance->isTolerable($fragIon, $bIon)) {
                        $bMatches['b' . $bIndex] = array(
                            $fragIon,
                            $bIon,
                            $tolerance->getDifferencePpm($fragIon, $bIon)
                        );
                    }
                }
            }
            
            $yMatches = array();
            foreach ($fragIons as $fragIon) {
                foreach ($yIons as $index => $yIon) {
                    if ($tolerance->isTolerable($fragIon, $yIon)) {
                        $yMatches['y' . $index] = array(
                            $fragIon,
                            $yIon,
                            $tolerance->getDifferencePpm($fragIon, $yIon)
                        );
                    }
                }
            }
            
            echo '<tr>';
            echo '<td>' . $spectra->getTitle() . '</td>';
            echo '<td>' . $identification->getPeptide()->getSequence() . '</td>';
            echo '<td>' . implode(', ', array_keys($bMatches)) . '</td>';
            echo '<td>' . implode(', ', array_keys($yMatches)) . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody></table>';
}