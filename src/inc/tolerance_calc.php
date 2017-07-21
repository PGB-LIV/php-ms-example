<?php
use pgb_liv\php_ms\Core\Tolerance;

$mass = '972.56';
$massA = '972.56';
$massB = '972.57';

$toleranceValue = 10;
$toleranceUnit = 'ppm';

if (isset($_POST['mass'])) {
    $mass = (float) $_POST['mass'];
}

if (isset($_POST['tolVal'])) {
    $toleranceValue = (float) $_POST['tolVal'];
}

if (isset($_POST['tolUnit'])) {
    $toleranceUnit = $_POST['tolUnit'];
}

if (isset($_POST['massA'])) {
    $massA = (double) $_POST['massA'];
}

if (isset($_POST['massB'])) {
    $massB = (double) $_POST['massB'];
}
?>

<h2>Convert Tolerance</h2>
<form method="post" action="?page=tolerance_calc">
    Mass: <input type="text" name='mass' value="<?php echo $mass; ?>" />Da<br />
    Tolerance Value: <input type="text" name='tolVal'
        value="<?php echo $toleranceValue; ?>" /> <select name="tolUnit">
        <option
            <?php echo $toleranceUnit == 'ppm' ? 'selected="selected"' : ''; ?>>ppm</option>
        <option
            <?php echo $toleranceUnit == 'Da' ? 'selected="selected"' : ''; ?>>Da</option>
    </select><br /> <input type="submit" />
</form>
<?php
$tolerance = new Tolerance($toleranceValue, $toleranceUnit);

echo $mass . 'Da<br />';
echo $toleranceValue . ' ' . $toleranceUnit . ' = ' . $tolerance->getDaltonDelta($mass) . ' Da<br />';
echo $toleranceValue . ' ' . $toleranceUnit . ' = ' . $tolerance->getPpmDelta($mass) . ' ppm';
?>

<h2>Calculate Difference</h2>

<form method="post" action="?page=tolerance_calc">
    Mass A: <input type="text" name='massA'
        value="<?php echo $massA; ?>" />Da<br /> Mass B: <input
        type="text" name='massB' value="<?php echo $massB; ?>" />Da<br />
    <input type="submit" />
</form>
<?php
$tolerance = new Tolerance(abs($massA - $massB), Tolerance::DA);

echo $massA . 'Da - ' . $massB . 'Da = <br />';
echo round($tolerance->getDaltonDelta($massB), 5) . ' Da<br />';
echo round($tolerance->getPpmDelta($massB), 5) . ' ppm';
?>