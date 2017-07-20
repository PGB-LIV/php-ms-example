<?php
use pgb_liv\php_ms\Core\Peptide;
use pgb_liv\php_ms\Utility\Fragment\BFragment;
use pgb_liv\php_ms\Utility\Fragment\ZFragment;
use pgb_liv\php_ms\Utility\Fragment\YFragment;
use pgb_liv\php_ms\Utility\Fragment\CFragment;

$sequence = 'PEPTIDE';
if (isset($_POST['sequence'])) {
    $sequence = $_POST['sequence'];
}
?>

<form method="post" action="?page=peptide_properties">
    Sequence: <input type="text" name='sequence'
        value="<?php echo $sequence; ?>" /> <input type="submit" />
</form>
<?php

$peptide = new Peptide($sequence);

echo 'Sequence: ' . $peptide->getSequence() . '<br />';
echo 'Length: ' . $peptide->getLength() . '<br />';
echo 'Mass: ' . $peptide->getMass() . 'Da<br />';
echo 'Formula: ' . $peptide->getMolecularFormula() . '<br /><br />';

$frags = array();
$frags['B Ions'] = new BFragment($peptide);
$frags['Z Ions'] = new ZFragment($peptide);
$frags['C Ions'] = new CFragment($peptide);
$frags['Y Ions'] = new YFragment($peptide);

?>
<table>
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