<?php
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Utility\Fragment\ZFragment;
use pgb_liv\php_ms\Utility\Fragment\YFragment;
use pgb_liv\php_ms\Utility\Fragment\CFragment;
use pgb_liv\php_ms\Utility\Fragment\AFragment;
use pgb_liv\php_ms\Utility\Fragment\XFragment;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Utility\Fragment\FragmentFactory;

$sequence = 'PEPTIDE';
if (isset($_REQUEST['sequence'])) {
    $sequence = $_REQUEST['sequence'];
}

$sequences = explode("\n", strtoupper($sequence));
$charge = 1;
if (isset($_REQUEST['charge'])) {
    $charge = $_REQUEST['charge'];
}

$modificationPosition = '4';
$modificationMass = '79.966331';
if (isset($_REQUEST['modificationPosition'])) {
    $modificationPosition = $_REQUEST['modificationPosition'];
}

if (isset($_REQUEST['modificationMass'])) {
    $modificationMass = $_REQUEST['modificationMass'];
}

$modificationPositions = explode("\n", $modificationPosition);
$modificationMasses = explode("\n", $modificationMass);

$modifications = array();
for ($i = 0; $i < count($modificationPositions); $i ++) {
    $mass = (float) ($modificationMasses[$i]);
    $location = trim($modificationPositions[$i]);

    if (strlen($location) == 0) {
        continue;
    }

    $modification = new Modification();
    $modification->setMonoisotopicMass($mass);

    if (is_numeric($location)) {
        $modification->setLocation((int) $location);
    } else {
        $modification->setResidues(array(
            trim($location)
        ));
    }

    $modifications[] = $modification;
}

$fragmentMethod = 'All';
if (isset($_REQUEST['fragmentMethod'])) {
    $fragmentMethod = $_REQUEST['fragmentMethod'];
}
?>
<h2>MS Fragment Ion Generator</h2>

<p>Generates the fragment ions for a specified sequence. You can
    generate for multiple sequence by seperating each sequence with a
    new line.</p>
<p>Modifications can be input using either a location or a residue, and
    a mass. Each modification should be seperated by a new line. Use [
    and ] for N and C terminus.</p>
<form method="get" action="#">
    <input type="hidden" name="page" value="peptide_properties" />
    <fieldset>
        <label for="sequence">Sequence</label>
        <textarea id="sequence" name="sequence"><?php echo $sequence; ?></textarea>
    </fieldset>
    <fieldset>
        <label for="charge">Charge</label> <select name="charge"
            id="charge">
        
        <?php
        for ($i = 0; $i <= 9; $i ++) {
            echo '<option value="' . $i . '"';
            if ($charge == $i) {
                echo ' selected="selected"';
            }

            echo '>+' . $i . '</option>';
        }
        ?>
        </select>
    </fieldset>
    <fieldset>
        <label for="modifications">Modifications</label>
        <textarea id="modifications" name="modificationPosition"
            style="width: 2em;"><?php echo $modificationPosition; ?></textarea>
        <textarea name="modificationMass" style="width: 8em;"><?php echo $modificationMass; ?></textarea>
    </fieldset>
    <fieldset>
        <label for="fragmentMethod">Fragment Method</label> <select
            name="fragmentMethod" id="fragmentMethod">
            <option
                <?php $fragmentMethod == 'All' ? ' selected="selected"' : ''; ?>>All</option>
<?php
foreach (FragmentFactory::getFragmentMethods() as $method) {
    echo '<option';
    echo $fragmentMethod == $method ? ' selected="selected"' : '';
    echo '>' . $method . '</option>' . PHP_EOL;
}
?>
        
        </select>
    </fieldset>
    <fieldset>
        <input type="submit" value="Submit" />
    </fieldset>
</form>
<?php
foreach ($sequences as $sequence) {
    $peptide = new Peptide(trim($sequence));
    $mass = $peptide->getMonoisotopicMass();
    foreach ($modifications as $modification) {
        $peptide->addModification($modification);
    }

    echo '<h3>' . wordwrap($peptide->getSequence(), 64, '<br />', true) . '</h3>';
    echo 'Length: ' . $peptide->getLength() . '<br />';
    echo 'Mass: ' . number_format($mass, 4) . 'Da<br />';
    if ($peptide->isModified() > 0) {
        echo 'Mass (Inc. Modifications): ' . number_format($peptide->getMonoisotopicMass(), 4) . 'Da<br />';
    }
    echo 'Formula: ' . preg_replace('/([0-9]+)/', '<sub>$1</sub>', $peptide->getMolecularFormula()) . '<br /><br />';

    if ($fragmentMethod == 'All') {
        $frags = array();
        $frags['a'] = new AFragment($peptide);
        $frags['b'] = new BFragment($peptide);
        $frags['c'] = new CFragment($peptide);

        $frags['x'] = new XFragment($peptide);
        $frags['y'] = new YFragment($peptide);
        $frags['z'] = new ZFragment($peptide);
    } else {
        $frags = FragmentFactory::getMethodFragments($fragmentMethod, $peptide);
    }
    ?>
<h4>Mass/Charge</h4>

<table class="formattedTable hoverableRow centreTable"
    style="width: 25em;">
    <thead>
        <tr>
            <th>Charge State</th>
            <th>Mass</th>
        </tr>
    </thead>
    <tbody>
    <?php
    for ($chargeIndex = 1; $chargeIndex <= 5; $chargeIndex ++) {
        echo '<tr><td>' . $chargeIndex . '+</td>';
        echo '<td>' . number_format($peptide->getMonoisotopicMassCharge($chargeIndex), 4) . '</td></tr>';
    }
    ?>
    </tbody>
</table>

<h4>Fragments</h4>

<table class="formattedTable hoverableRow">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th>#</th>
    <?php
    foreach ($frags as $type => $fragger) {
        ?>
            <th><?php echo $type; ?> Ions</th>
        <?php
    }
    ?>
    <th>#</th>
        </tr>
    </thead>
    <tbody>
<?php
    for ($i = 1; $i <= $peptide->getLength(); $i ++) {
        echo '<tr>';
        echo '<td>' . $sequence[$i - 1] . '</td>';
        echo '<td>' . $i . '</td>';

        foreach ($frags as $type => $fragger) {
            $ions = $fragger->getIons($charge);

            $ionIndex = $i;
            $sequenceIndex = $i;
            if ($fragger->isReversed()) {
                $sequenceIndex = $peptide->getLength() - ($i - 1);
                $ionIndex = $peptide->getLength() - ($i - 1);
            }

            $ion = '&empty;';
            if (isset($ions[$ionIndex])) {
                $ion = $ions[$ionIndex];

                $ion = number_format($ion, 6);
            }

            echo '<td>' . $ion . '</td>';
        }

        echo '<td>' . ($peptide->getLength() + 1 - $i) . '</td>';
        echo '</tr>';
    }
    ?></tbody>
</table>
<hr />
<?php
}
