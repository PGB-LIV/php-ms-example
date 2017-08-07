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

<h2>Tolerance Calculator</h2>
<h3>Convert Tolerance</h3>
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

<p class="centreText">
<?php
$tolerance = new Tolerance($toleranceValue, $toleranceUnit);

echo 'For <strong>' . $mass . 'Da</strong><br />';
if ($toleranceUnit == Tolerance::PPM) {
    echo '<strong>' . $toleranceValue . ' ' . $toleranceUnit . '</strong> is equivalent to <strong>' . $tolerance->getDaltonDelta($mass) . ' Da</strong><br />';
} else {
    echo '<strong>' . $toleranceValue . ' ' . $toleranceUnit . '</strong> is equivalent to <strong>' . $tolerance->getPpmDelta($mass) . ' ppm</strong>';
}
?></p>

<h3>Calculate Difference</h3>

<form method="post" action="?page=tolerance_calc">
    Mass A: <input type="text" name='massA'
        value="<?php echo $massA; ?>" />Da<br /> Mass B: <input
        type="text" name='massB' value="<?php echo $massB; ?>" />Da<br />
    <input type="submit" />
</form>

<p class="centreText">
<?php
$tolerance = new Tolerance(abs($massA - $massB), Tolerance::DA);

echo 'The mass difference of <strong>'.$massA . 'Da</strong> and <strong>' . $massB . 'Da</strong><br />';
echo 'In Daltons, <strong>'.round($tolerance->getDaltonDelta($massB), 5) . ' Da</strong><br />';
echo 'In ppm, <strong>'.round($tolerance->getPpmDelta($massB), 5) . ' ppm</strong>';
?></p>