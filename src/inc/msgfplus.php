<?php
use pgb_liv\php_ms\Search\MsgfPlusSearch;
use pgb_liv\php_ms\Search\Parameters\MsgfPlusSearchParameters;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Core\Tolerance;

error_reporting(E_ALL);
ini_set('display_errors', true);
set_time_limit(3600);

define('MSGF_JAR', '/mnt/nas/_CLUSTER_SOFTWARE/ms-gf+/current/MSGFPlus.jar');
define('MSGF_THREADS', 6);

$files = scandir('conf/fasta');
$fastaFiles = array();
foreach ($files as $file) {
    $info = pathinfo($file);
    if ($info['extension'] == 'fasta') {
        $fastaFiles[$file] = $info['filename'];
    }
}

if (isset($_FILES['mgf']) && $_FILES['mgf']['error'] == 0) {
    if(filesize($_FILES['mgf']['tmp_name']) > 10485760)
    {
        echo 'MGF file too large';
        return;
    }
    
    $mgf = tempnam(sys_get_temp_dir(), 'php-msMsgfPlusRun') . '.mgf';
    copy($_FILES['mgf']['tmp_name'], $mgf);
    
    $fasta = 'conf/fasta/' . $_POST['fasta'] . '.fasta';
    
    // Do Search
    $parameters = new MsgfPlusSearchParameters();
    $parameters->setDatabases($fasta);
    $parameters->setSpectraPath($mgf);
    
    if ($_POST['fixed'] == 'Yes') {
        $modification = new Modification();
        $modification->setMonoisotopicMass(57.02146);
        $modification->setResidues(array(
            'C'
        ));
        $modification->setName('Carbamidomethyl');
        $parameters->addFixedModification($modification);
    }
    
    $modification = new Modification();
    switch ($_POST['variable']) {
        case 'acetyl':
            $modification->setMonoisotopicMass(42.01056);
            $modification->setResidues(array(
                '*'
            ));
            $modification->setPosition(Modification::POSITION_PROTEIN_NTERM);
            $modification->setName('Acetyl');
            break;
        case 'oxidation':
            $modification->setMonoisotopicMass(15.99491);
            $modification->setResidues(array(
                'M'
            ));
            $modification->setName('Oxidation');
            break;
        case 'phospho':
            $modification->setMonoisotopicMass(79.96633);
            $modification->setResidues(
                array(
                    'S',
                    'T',
                    'Y'
                ));
            $modification->setName('Phospho');
            break;
        default:
            unset($modification);
            break;
    }
    
    if (isset($modification)) {
        $parameters->addVariableModification($modification);
    }
    
    $tolData = explode(' ', $_POST['tolerance']);
    $tolerance = new Tolerance((float) $tolData[0], $tolData[1]);
    $parameters->setPrecursorTolerance($tolerance);
    $outputFile = tempnam(sys_get_temp_dir(), 'php-msMsgfPlusRun') . '.mzid';
    
    $parameters->setOutputFile($outputFile);
    $parameters->setNumOfThreads(MSGF_THREADS);
    $search = new MsgfPlusSearch(MSGF_JAR);
    try {
        $search->search($parameters);
        
        $info = pathinfo($outputFile);
        
        header('Location: ?page=mzidentml_viewer&search=' . $info['filename'] . '&name=' . $_FILES['mgf']['name']);
    } catch (InvalidArgumentException $ex) {
        echo '<strong>Search Failed</strong><br />';
        echo $ex->getMessage();
    }
    
    exit();
}
?>
<h2>MS-GF+ Search</h2>

<?php

if (empty($fastaFiles)) {
    echo '<p>No FASTA files on server. To enable this page you need to place .fasta files in /conf/fasta</p>';
    return;
}
?>
<p>This page is intentionally feature limited to reduce CPU load on our
    servers. Usage of this page is limited to a 10 MB MGF file.</p>
<p>Any options not customisable here use MS-GF+ default values</p>

<form enctype="multipart/form-data" action="?page=msgfplus&txtonly=1"
    method="POST">
    <fieldset>
        <label for="mgf">MGF File</label> <input name="mgf" id="mgf"
            type="file" />
    </fieldset>
    <fieldset>
        <label for="fasta">FASTA File</label> <select name="fasta"
            id="fasta">
            <?php
            
            foreach ($fastaFiles as $fastaFile) {
                echo '<option>' . $fastaFile . '</option>';
            }
            ?>
        </select>
    </fieldset>
    <fieldset>
        <label for="tolerance">Precursor Tolerance</label> <select
            name="tolerance" id="tolerance">
            <option>0.1 Da</option>
            <option>0.5 Da</option>
            <option>1.0 Da</option>
            <option>5 ppm</option>
            <option>10 ppm</option>
            <option>15 ppm</option>
            <option>20 ppm</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="fixed">Fixed Carbamidomethyl</label> <input
            name="fixed" id="fixed" type="radio" value="Yes"
            checked="checked" /><span>Yes</span><input name="fixed"
            id="fixed" type="radio" value="No" />No
    </fieldset>
    <fieldset>
        <label for="variable">Variable Modification</label> <select
            name="variable" id="variable">
            <option value="none" selected="selected">None</option>
            <option value="phospho">Phospho (STY)</option>
            <option value="acetyl">Acetyl (Prot N-term)</option>
            <option value="oxidation">Oxidation (M)</option>
        </select>
    </fieldset>

    <fieldset>
        <input type="submit" value="Upload" />
    </fieldset>
</form>