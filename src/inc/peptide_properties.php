<?php
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Utility\Fragment\ZFragment;
use pgb_liv\php_ms\Utility\Fragment\YFragment;
use pgb_liv\php_ms\Utility\Fragment\CFragment;
use pgb_liv\php_ms\Core\Modification;

$sequence = 'PEPTIDE';
if (isset($_REQUEST['sequence'])) {
    $sequence = $_REQUEST['sequence'];
}

$sequences = explode("\n", $sequence);
$charge = 1;
if (isset($_REQUEST['charge'])) {
    $charge = $_REQUEST['charge'];
}

$modificationPosition = 'T';
$modificationMass = '79.97';
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
    $mass = (float) $modificationMasses[$i];
    $location = $modificationPositions[$i];
    
    if (strlen($location) == 0)
    {
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
?>
<h2>MS Fragment Ion Generator</h2>

<p>Generates the fragment ions for a specified peptide. You can generate for multiple peptides by seperating each peptide with a new line.</p>
<p>Modifications can be input using either a location or a residue, and a mass. Each modification should be seperated by a new line. Use [ and ] for N and C terminus.</p>
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
        for ($i = 1; $i <= 9; $i ++) {
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
        <input type="submit" value="Submit" />
    </fieldset>
</form>
<?php
foreach ($sequences as $sequence) {
    $peptide = new Peptide(trim($sequence));
    
    foreach ($modifications as $modification) {
        $peptide->addModification($modification);
    }
    
    echo '<h3>' . $peptide->getSequence() . '</h3>';
    echo 'Length: ' . $peptide->getLength() . '<br />';
    echo 'Mass: ' . $peptide->getMass() . 'Da<br />';
    echo 'Formula: ' . preg_replace('/([0-9]+)/', '<sub>$1</sub>', $peptide->getMolecularFormula()) . '<br /><br />';
    
    $frags = array();
    $frags['B Ions'] = new BFragment($peptide);
    $frags['Z Ions'] = new ZFragment($peptide);
    $frags['C Ions'] = new CFragment($peptide);
    $frags['Y Ions'] = new YFragment($peptide);
    ?>
<h4>Fragments</h4>

<table class="formattedTable hoverableRow">
    <thead>
    <?php
    echo '<tr>';
    foreach ($frags as $type => $fragger) {
        ?>
            <th colspan="2"><?php echo $type; ?></th>
        <?php
    }
    echo '</tr><tr>';
    foreach ($frags as $type => $fragger) {
        ?>
            <th>#</th>
        <th>Ion</th>
        <?php
    }
    echo '</tr>';
    ?>
    </thead>
    <tbody>
<?php
    for ($i = 1; $i <= $peptide->getLength(); $i ++) {
        echo '<tr>';
        
        foreach ($frags as $type => $fragger) {
            $ions = $fragger->getIons();
            
            $ionIndex = $i;
            if ($fragger->isReversed())
            {
                $ionIndex = $peptide->getLength() -($i - 1);
            }
                
            $ion = '∅';
            if (isset($ions[$ionIndex])) {
                $ion = $ions[$ionIndex];
                
                if ($charge > 1) {
                    $ion -= 1.007276466879;
                    $ion += 1.007276466879 * $charge;
                    $ion /= $charge;
                }
                
                $ion = number_format($ion, 6);
            }
            
            echo '<td>' . $sequence[$ionIndex - 1] . '</td><td>' . $ion . '</td>';
        }
        
        echo '</tr>';
    }
    ?></tbody>
</table>
<hr />
<?php
}
