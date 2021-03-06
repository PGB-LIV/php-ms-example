<?php
use pgb_liv\php_ms\Search\MsgfPlusSearch;
use pgb_liv\php_ms\Search\Parameters\MsgfPlusSearchParameters;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Core\Tolerance;

set_time_limit(3600);

// TODO: Move to a general config
define('MSGF_JAR', '/mnt/nas/_CLUSTER_SOFTWARE/ms-gf+/current/MSGFPlus.jar');
define('MSGF_THREADS', 6);

$fastaFiles = array();
if (file_exists('conf/fasta')) {
    $files = scandir('conf/fasta');
    
    foreach ($files as $file) {
        $info = pathinfo($file);
        if ($info['extension'] == 'fasta' && stripos($info['filename'], 'revcat') === false) {
            $fastaFiles[$file] = $info['filename'];
        }
    }
}

if (isset($_FILES['mgf']) && $_FILES['mgf']['error'] == 0) {
    if (filesize($_FILES['mgf']['tmp_name']) > 10485760) {
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
    
    if ($_POST['decoy'] == 'Yes') {
        $parameters->setDecoyEnabled(true);
    }
    
    if (isset($_POST['instrument'])) {
        $parameters->setMs2DetectorId((int) $_POST['instrument']);
    }
    
    if (isset($_POST['enzyme'])) {
        $parameters->setEnzyme((int) $_POST['enzyme']);
    }
    
    $search = new MsgfPlusSearch(MSGF_JAR);
    try {
        $idFile = $search->search($parameters);
        
        $info = pathinfo($idFile);
        
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
if (! empty($_FILES) && $_FILES['mgf']['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
}

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
            <option selected="selected">5 ppm</option>
            <option>10 ppm</option>
            <option>15 ppm</option>
            <option>20 ppm</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="instrument">Instrument</label> <select
            name="instrument" id="instrument">
            <option value="0" selected="selected">Low-res LCQ/LTQ</option>
            <option value="1">Orbitrap/FTICR</option>
            <option value="2">TOF</option>
            <option value="3">Q-Exactive</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="enzyme">Enzyme</label> <select name="enzyme"
            id="enzyme">
            <option value="0">Unspecific Cleavage</option>
            <option value="1" selected="selected">Trypsin</option>
            <option value="2">Chymotrypsin</option>
            <option value="3">Lys-C</option>
            <option value="4">Lys-N</option>
            <option value="5">Glutamyl endopeptidase</option>
            <option value="6">Arg-C</option>
            <option value="7">Arg-N</option>
            <option value="8">alphaLP</option>
            <option value="9">No cleavage</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="fixed">Fixed Carbamidomethyl</label> <input
            name="fixed" id="fixed" type="checkbox" value="Yes"
            checked="checked" /><span>Yes</span>
    </fieldset>
    <fieldset>
        <label for="decoy">Search Decoys</label> <input name="decoy"
            id="decoy" type="checkbox" value="Yes" checked="checked" /><span>Yes</span>
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