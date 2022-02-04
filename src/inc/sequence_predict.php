<?php
use pgb_liv\php_ms\Core\AminoAcidMono;
use pgb_liv\php_ms\Core\Tolerance;
use pgb_liv\php_ms\Constant\MoleculeConstants;

set_time_limit(120);

define('TOLERANCE', 500);

function addEntry($obsMass, $result)
{
    $entry = array();
    $entry['mass'] = $result['mass'];
    $entry['delta_ppm'] = Tolerance::getDifferencePpm($obsMass, $result['mass']);
    $entry['delta_da'] = $obsMass - $result['mass'];
    $entry['sequence'] = '';
    $entry['modification'] = '&nbsp';
    $entry['nl'] = '&nbsp';

    ksort($result['aa']);
    foreach ($result['aa'] as $key => $value) {
        $entry['sequence'] .= str_pad('', $value, $key);
    }

    if (isset($result['mod'])) {
        $entry['modification'] = $result['mod'];
    }

    if (isset($result['nl'])) {
        $entry['nl'] = $result['nl'];
    }

    return $entry;
}

function sortPpm($a, $b)
{
    if ($a['delta_ppm'] == $b['delta_ppm']) {
        if ($a['sequence'] == $b['sequence']) {
            if ($a['modification'] == $b['modification']) {
                return 0;
            }

            return $a['modification'] > $b['modification'] ? 1 : 0;
        }

        return $a['sequence'] > $b['sequence'] ? 1 : 0;
    }

    return abs($a['delta_ppm']) > abs($b['delta_ppm']) ? 1 : 0;
}

function isModAllowed(array $sequence, array $modification)
{
    if (! empty($sequence)) {
        return true;
    }

    foreach (array_keys($sequence['aa']) as $aa) {
        if (isset($modification['specificity'][$aa])) {
            return true;
        }
    }

    return false;
}

function addAA($sequence, $residue, $mass)
{
    if ($sequence == null) {
        $sequence = array(
            'mass' => 0,
            'aa' => array()
        );
    }

    if (! isset($sequence['aa'][$residue])) {
        $sequence['aa'][$residue] = 0;
    }

    $sequence['aa'][$residue] ++;
    $sequence['mass'] += $mass;

    return $sequence;
}

function addMod($sequence, $name, $mass)
{
    if ($sequence == null) {
        $sequence = array(
            'mass' => 0,
            'aa' => array()
        );
    }

    $sequence['mod'] = $name;
    $sequence['mass'] += $mass;

    return $sequence;
}

function addNl($sequence, $name, $mass)
{
    $sequence['nl'] = $name;
    $sequence['mass'] -= $mass;

    return $sequence;
}

function getAA(array $aminoAcids, Tolerance $tolerance, $obsMass, $sequence = null)
{
    global $modifications, $nlAllowed;

    if ($sequence == null) {
        $sequence = array(
            'mass' => 0,
            'aa' => array()
        );
    }

    $resultSet = array();

    foreach ($modifications as $key => $modification) {
        if (! isModAllowed($sequence, $modification)) {
            continue;
        }

        if ($tolerance->isTolerable($obsMass, $modification['mass'])) {
            $entry = addMod($sequence, $key, $modification['mass']);
            $resultSet[] = $entry;
        } elseif ($nlAllowed && $tolerance->isTolerable($obsMass, $modification['mass'] - MoleculeConstants::WATER_MASS)) {
            $entry = addMod($sequence, $key, $modification['mass']);
            $entry = addNl($entry, 'H2O', MoleculeConstants::WATER_MASS);

            $resultSet[] = $entry;
        } elseif ($nlAllowed && $tolerance->isTolerable($obsMass, $modification['mass'] - MoleculeConstants::AMONIA_MASS)) {
            $entry = addMod($sequence, $key, $modification['mass']);
            $entry = addNl($entry, 'NH3', MoleculeConstants::AMONIA_MASS);

            $resultSet[] = $entry;
        }
    }

    foreach ($aminoAcids as $residue => $expMass) {
        if ($tolerance->isTolerable($obsMass, $expMass)) {
            $entry = addAA($sequence, $residue, $expMass);
            $resultSet[] = $entry;
        } elseif ($nlAllowed && $tolerance->isTolerable($obsMass, $expMass - MoleculeConstants::WATER_MASS)) {
            $entry = addAA($sequence, $residue, $expMass);
            $entry = addNl($entry, 'H2O', MoleculeConstants::WATER_MASS);

            $resultSet[] = $entry;
        } elseif ($nlAllowed && $tolerance->isTolerable($obsMass, $expMass - MoleculeConstants::AMONIA_MASS)) {
            $entry = addAA($sequence, $residue, $expMass);
            $entry = addNl($entry, 'NH3', MoleculeConstants::AMONIA_MASS);

            $resultSet[] = $entry;
        } elseif ($obsMass > $expMass) {
            $entry = addAA($sequence, $residue, $expMass);

            $sub = getAA($aminoAcids, $tolerance, $obsMass - $expMass, $entry);

            foreach ($sub as $entry) {
                $resultSet[] = $entry;
            }
        }
    }

    return $resultSet;
}

$mass = 128.09;
$ptmAllowed = 0;
$nlAllowed = 0;

if (isset($_POST['mass'])) {
    $mass = (float) $_POST['mass'];
}

if (isset($_POST['ptmAllowed']) && $_POST['ptmAllowed']) {
    $ptmAllowed = 1;
}

if (isset($_POST['nlAllowed']) && $_POST['nlAllowed']) {
    $nlAllowed = 1;
}

$modifications = array();

if ($ptmAllowed) {
    $xml = new SimpleXMLElement('http://www.unimod.org/xml/unimod.xml', null, true, 'umod', true);
    foreach ($xml->modifications->mod as $modification) {
        $fullName = (string) $modification->attributes()['full_name'];

        if (strpos($fullName, 'substitution') !== false) {
            continue;
        }

        $key = (string) $modification->attributes()['title'];

        $modifications[$key] = array();
        $modifications[$key]['mass'] = (string) $modification->delta->attributes()['mono_mass'];

        $modifications[$key]['composition'] = '';
        foreach ($modification->delta->element as $element) {
            $modifications[$key]['composition'] .= (string) $element->attributes()['symbol'];
            $modifications[$key]['composition'] .= (string) $element->attributes()['number'];
        }

        $modifications[$key]['specificity'] = array();
        foreach ($modification->specificity as $specificity) {
            $modifications[$key]['specificity'][(string) $specificity->attributes()['site']] = 1;
        }
    }
}

$tolerance = new Tolerance(TOLERANCE, Tolerance::PPM);
?>

<h2>Sequence Predictor</h2>

<p>This tool will attempt to predict the possible sequence molecule for
    a specified mass. You can toggle on modifications (Unimod) and
    neutral losses (H2O, NH3). Sequences should be considered as the
    collection of amino acids present as no ordering of the chain can be
    predicted with this tool.</p>

<form method="post" action="?page=sequence_predict">
    <fieldset>
        <label for="mass"> Mass</label> <input type="text" name="mass"
            id="mass" value="<?php echo $mass; ?>" />Da<br />
    </fieldset>
    <fieldset>
        <label for="ptmAllowed"> Modifications</label> <input
            type="checkbox" name="ptmAllowed" id="ptmAllowed"
            <?php echo $ptmAllowed ? 'checked="checked" ' : '' ?>
            value="1" /><br />
    </fieldset>
    <fieldset>
        <label for="nlAllowed"> Neutral Loss</label> <input
            type="checkbox" name="nlAllowed" id="nlAllowed"
            <?php echo $nlAllowed ? 'checked="checked" ' : '' ?>
            value="1" /><br />
    </fieldset>
    <fieldset>
        <input type="submit" value="Search" />
    </fieldset>
</form>

<?php
if (empty($_POST)) {
    return;
}

$aa = new AminoAcidMono();
$reflection = new ReflectionClass($aa);
$aminoAcids = $reflection->getConstants();
$aminoAcids['J'] = $aminoAcids['I'];
unset($aminoAcids['I']);
unset($aminoAcids['L']);

$results = getAA($aminoAcids, $tolerance, $mass);

$resultOutput = array();
foreach ($results as $result) {
    $resultOutput[] = addEntry($mass, $result);
}

uasort($resultOutput, 'sortPpm');
?>
<table class="formattedTable hoverableRow centreTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Sequence</th>
            <th>Modification</th>
            <th>Loss</th>
            <th>Mass (Da)</th>
            <th>Delta (ppm)</th>
            <th>Delta (mDa)</th>
        </tr>
    </thead>
    <tbody>
<?php
$count = 1;

$lastEntry = array(
    'sequence' => '',
    'modification' => '',
    'nl' => ''
);
foreach ($resultOutput as $entry) {

    if ($entry['sequence'] == $lastEntry['sequence'] && $entry['modification'] == $lastEntry['modification'] &&
        $entry['nl'] == $lastEntry['nl']) {
        continue;
    }

    echo '<tr>';

    echo '<td>' . $count ++ . '</td>';
    echo '<td>' . $entry['sequence'] . '</td>';
    echo '<td>' . $entry['modification'] . '</td>';
    echo '<td>' . $entry['nl'] . '</td>';
    echo '<td>' . round($entry['mass'], 4) . '</td>';
    echo '<td>' . round($entry['delta_ppm'], 2) . '</td>';
    echo '<td>' . round($entry['delta_da'] * 1000, 2) . '</td>';

    echo '</tr>';

    $lastEntry = $entry;
}
?>
    </tbody>
</table>