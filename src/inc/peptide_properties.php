<?php
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Utility\Fragment\ZFragment;
use pgb_liv\php_ms\Utility\Fragment\YFragment;
use pgb_liv\php_ms\Utility\Fragment\CFragment;

$sequence = 'PEPTIDE';
if (isset($_REQUEST['sequence'])) {
    $sequence = $_REQUEST['sequence'];
}
?>
<h2>Peptide Properties</h2>


<form method="get" action="#">
    <input type="hidden" name="page" value="peptide_properties" /> <label
        for="sequence">Sequence</label> <input type="text" id="sequence"
        name="sequence" value="<?php echo $sequence; ?>" /> <input
        type="submit" value="Submit" />
</form>
<?php

$peptide = new Peptide($sequence);

echo '<h3>' . $peptide->getSequence() . '</h3>';
echo 'Length: ' . $peptide->getLength() . '<br />';
echo 'Mass: ' . $peptide->getMass() . 'Da<br />';
echo 'Formula: ' . $peptide->getMolecularFormula() . '<br /><br />';

$frags = array();
$frags['B Ions'] = new BFragment($peptide);
$frags['Z Ions'] = new ZFragment($peptide);
$frags['C Ions'] = new CFragment($peptide);
$frags['Y Ions'] = new YFragment($peptide);

?>
<h3>Fragments</h3>

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
        $ion = '&nbsp;';
        if (isset($ions[$i])) {
            $ion = $ions[$i];
        }
        
        echo '<td>' . $sequence[$i - 1] . '</td><td>' . $ion . '</td>';
    }
    
    echo '</tr>';
}
?></tbody>
</table>