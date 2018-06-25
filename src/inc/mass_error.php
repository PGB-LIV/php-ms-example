<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Utility\Fragment\YFragment;
use pgb_liv\php_ms\Writer\MgfWriter;

set_time_limit(600);

if (isset($_GET['file'])) {
    header('Content-type: text/plain;');
    header('Content-Disposition: attachment; filename="' . $_GET['name'] . '"');
    
    $precursorTolerance = new Tolerance((float) $_GET['precursor'], Tolerance::PPM);
    
    $fragmentTolerance = null;
    if (isset($_GET['fragment'])) {
        $fragmentTolerance = new Tolerance((float) $_GET['fragment'], Tolerance::PPM);
    }
    
    $reader = new MgfReader('/tmp/phpms-' . $_GET['file']);
    $writer = new MgfWriter('php://output');
    
    foreach ($reader as $spectra) {
        $beforeMW = $spectra->getMonoisotopicMass();
        
        $shift = $precursorTolerance->getDaltonDelta($beforeMW);
        $spectra->setMonoisotopicMass($beforeMW - $shift);
        
        if (! is_null($fragmentTolerance)) {
            foreach ($spectra->getFragmentIons() as $fragmentIon) {
                $beforeMW = $fragmentIon->getMonoisotopicMass();
                
                $shift = $fragmentTolerance->getDaltonDelta($beforeMW);
                $fragmentIon->setMonoisotopicMass($beforeMW - $shift);
            }
        }
        
        $writer->write($spectra);
    }
    
    $writer->close();
    
    exit();
}

define('FORM_IDENT', 'ident');
define('FORM_RAW', 'raw');
?>
<h2>Mass Error &amp; Tolerance Calibration</h2>

<p>This tool will analyse your search results and raw data to identify
    whether your instrument calibration is misaligned.</p>
<p>Note, only mzIdentML 1.1 and 1.2 are currently supported.</p>

<?php
if (! empty($_FILES) && ($_FILES[FORM_IDENT]['error'] != 0 || $_FILES[FORM_RAW]['error'] != 0)) {
    echo '<p>An error occured. Ensure you included a file to upload.</p>';
}
?>

<form enctype="multipart/form-data" action="?page=mass_error"
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
if (empty($_FILES) || $_FILES[FORM_IDENT]['error'] != 0 || $_FILES[FORM_RAW]['error'] != 0) {
    return;
}

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

$mgfName = basename($_FILES[FORM_RAW]['tmp_name']);
move_uploaded_file($_FILES[FORM_RAW]['tmp_name'], '/tmp/phpms-' . $mgfName);

$raw = new MgfReader('/tmp/phpms-' . $mgfName);
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

$fragmentTolerance = null;
if (isset($protocolCollection['spectrum'])) {
    foreach ($protocolCollection['spectrum'] as $key => $protocol) {
        if (isset($protocol['fragmentTolerance'])) {
            $fragmentTolerance = $protocol['fragmentTolerance'][0];
        } elseif (isset($protocol['additions']['user']['Instrument'])) {
            
            // MS-GF+ specifies an instrument rather than tolerance
            if ($protocol['additions']['user']['Instrument'] == 'LowRes') {
                $fragmentTolerance = new Tolerance(0.6, Tolerance::DA);
            }
        }
    }
}

if (is_null($fragmentTolerance)) {
    $fragmentTolerance = new Tolerance(10, Tolerance::PPM);
}

$daDeltas = array();
$ppmDeltas = array();
$fragmentDaDeltas = array();
$fragmentPpmDeltas = array();

foreach ($spectraLookup as $spectra) {
    if (count($spectra->getIdentifications()) == 0) {
        continue;
    }
    
    foreach ($spectra->getIdentifications() as $identification) {
        $bIons = (new BFragment($identification->getSequence()))->getIons();
        $yIons = (new YFragment($identification->getSequence()))->getIons();
        $fragIons = array();
        foreach ($spectra->getFragmentIons() as $ion) {
            $fragIons[] = $ion->getMonoisotopicMassCharge();
        }
        
        foreach ($fragIons as $fragIon) {
            foreach ($bIons as $bIndex => $bIon) {
                if ($fragmentTolerance->isTolerable($fragIon, $bIon)) {
                    $daDelta = $fragIon - $bIon;
                    $ppmDelta = Tolerance::getDifferencePpm($fragIon, $bIon);
                    
                    $key = '' . round($daDelta, 2);
                    if ($key == '-0') {
                        $key = '0';
                    }
                    
                    if (! isset($fragmentDaDeltas[$key])) {
                        $fragmentDaDeltas[$key] = 0;
                    }
                    
                    $fragmentDaDeltas[$key] ++;
                    
                    $key = '' . round($ppmDelta, 1);
                    if (! isset($fragmentPpmDeltas[$key])) {
                        $fragmentPpmDeltas[$key] = 0;
                    }
                    
                    $fragmentPpmDeltas[$key] ++;
                }
            }
        }
        
        foreach ($fragIons as $fragIon) {
            foreach ($yIons as $index => $yIon) {
                if ($fragmentTolerance->isTolerable($fragIon, $yIon)) {
                    $daDelta = $fragIon - $yIon;
                    $ppmDelta = Tolerance::getDifferencePpm($fragIon, $yIon);
                    
                    $key = '' . round($daDelta, 2);
                    if ($key == '-0') {
                        $key = '0';
                    }
                    
                    if (! isset($fragmentDaDeltas[$key])) {
                        $fragmentDaDeltas[$key] = 0;
                    }
                    
                    $fragmentDaDeltas[$key] ++;
                    
                    $key = '' . round($ppmDelta, 1);
                    if (! isset($fragmentPpmDeltas[$key])) {
                        $fragmentPpmDeltas[$key] = 0;
                    }
                    
                    $fragmentPpmDeltas[$key] ++;
                }
            }
        }
        
        $daDelta = $spectra->getMonoisotopicMass() - $identification->getSequence()->getMonoisotopicMass();
        $ppmDelta = Tolerance::getDifferencePpm($spectra->getMonoisotopicMass(),
            $identification->getSequence()->getMonoisotopicMass());
        
        $key = '' . round($daDelta, 2);
        if ($key == '-0') {
            $key = '0';
        }
        
        if (! isset($daDeltas[$key])) {
            $daDeltas[$key] = 0;
        }
        
        $daDeltas[$key] ++;
        $key = '' . round($ppmDelta, 1);
        if (! isset($ppmDeltas[$key])) {
            $ppmDeltas[$key] = 0;
        }
        
        $ppmDeltas[$key] ++;
    }
}

ksort($daDeltas, SORT_NUMERIC);
ksort($ppmDeltas, SORT_NUMERIC);

ksort($fragmentDaDeltas, SORT_NUMERIC);
ksort($fragmentPpmDeltas, SORT_NUMERIC);

$precursorShift = array_search(max($ppmDeltas), $ppmDeltas);
$fragmentShift = array_search(max($fragmentPpmDeltas), $fragmentPpmDeltas);
?>
<script type="text/javascript"
    src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(precursorPlots);
      google.charts.setOnLoadCallback(fragmentPlots);
      
      function precursorPlots() {
        var data = google.visualization.arrayToDataTable([
            ['Daltons', 'Frequency']
            <?php
            foreach ($daDeltas as $da => $freq) {
                echo ',[\'' . $da . '\', ' . $freq . ']';
            }
            ?>
          ]);

        var options = {
          title: 'Mass Error (Da)',
          legend: { position: 'none' },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('precursorDa'));
        chart.draw(data, options);

        var data = google.visualization.arrayToDataTable([
            ['ppm', 'Frequency']
            <?php
            foreach ($ppmDeltas as $ppm => $freq) {
                if ($ppm > $precursorShift + 15 || $ppm < $precursorShift - 15) {
                    continue;
                }
                echo ',[\'' . $ppm . '\', ' . $freq . ']';
            }
            ?>
          ]);

        var options = {
          title: 'Mass Error (ppm)',
          legend: { position: 'none' },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('precursorPpm'));
        chart.draw(data, options);
      }
      
      function fragmentPlots() {
        var data = google.visualization.arrayToDataTable([
            ['Daltons', 'Frequency']
            <?php
            foreach ($fragmentDaDeltas as $da => $freq) {
                echo ',[\'' . $da . '\', ' . $freq . ']';
            }
            ?>
          ]);

        var options = {
          title: 'Mass Error (Da)',
          legend: { position: 'none' },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('fragmentDa'));
        chart.draw(data, options);

        var data = google.visualization.arrayToDataTable([
            ['ppm', 'Frequency']
            <?php
            foreach ($fragmentPpmDeltas as $ppm => $freq) {
                if ($ppm > $precursorShift + 15 || $ppm < $precursorShift - 15) {
                    continue;
                }
                
                echo ',[\'' . $ppm . '\', ' . $freq . ']';
            }
            ?>
          ]);

        var options = {
          title: 'Mass Error (ppm)',
          legend: { position: 'none' },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('fragmentPpm'));
        chart.draw(data, options);
      }
    </script>

<h3>Precursor Error</h3>

<p>The precursor error values are generated by calculating the expected
    mass for all sequences identified within the identification data.</p>

<div id="precursorPpm"></div>
<div id="precursorDa"></div>

<h3>Fragment Tolerances</h3>

<p>The fragment error values are generated by calculating the possible
    expected B/Y ions for each sequence and matching against observed
    spectra. Note, this information is only provided for general
    interest purposes as there is insufficient data available to
    accurately adjust this. In ETD data these plots should be
    disregarded.</p>

<div id="fragmentPpm"></div>
<div id="fragmentDa"></div>

<h3>Recommendations</h3>

<?php
echo '<p>Your precursor error indicates a ' . $precursorShift . 'ppm shift is occuring.</p>';
echo '<p>Your fragment error indicates a ' . $fragmentShift . 'ppm shift is occuring.</p>';

echo '<p><a href="?page=mass_error&amp;file=' . $mgfName . '&amp;name=' . $_FILES[FORM_RAW]['name'] . '&amp;precursor=' .
    $precursorShift . '&amp;txtonly=1">Download adjusted precursor MGF</a></p>';
echo '<p><a href="?page=mass_error&amp;file=' . $mgfName . '&amp;name=' . $_FILES[FORM_RAW]['name'] . '&amp;precursor=' .
    $precursorShift . '&amp;fragment=' . $fragmentShift .
    '&amp;txtonly=1">Download adjusted precursor and fragment MGF</a></p>';