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
    <fieldset>
        <label for="mass"> Mass</label> <input type="text" name="mass"
            id="mass" value="<?php echo $mass; ?>" />Da<br />
    </fieldset>
    <fieldset>
        <label for="tolVal"> Tolerance Value</label> <input type="text"
            name='tolVal' value="<?php echo $toleranceValue; ?>"
            id="tolVal" /> <select name="tolUnit">
            <option
                <?php echo $toleranceUnit == 'ppm' ? 'selected="selected"' : ''; ?>>ppm</option>
            <option
                <?php echo $toleranceUnit == 'Da' ? 'selected="selected"' : ''; ?>>Da</option>
        </select>
    </fieldset>
    <fieldset>
        <input type="submit" value="Convert" />
    </fieldset>
</form>

<p class="centreText">
<?php
$tolerance = new Tolerance($toleranceValue, $toleranceUnit);

echo 'For <strong>' . $mass . 'Da</strong><br />';
if ($toleranceUnit == Tolerance::PPM) {
    echo '<strong>' . $toleranceValue . ' ' . $toleranceUnit . '</strong> is equivalent to <strong>' .
         $tolerance->getDaltonDelta($mass) . ' Da</strong><br />';
} else {
    echo '<strong>' . $toleranceValue . ' ' . $toleranceUnit . '</strong> is equivalent to <strong>' .
         $tolerance->getPpmDelta($mass) . ' ppm</strong>';
}
?></p>

<h3>Calculate Difference</h3>

<form method="post" action="?page=tolerance_calc">
    <fieldset>
        <label for="massA"> Mass A</label> <input type="text"
            name="massA" id="massA" value="<?php echo $massA; ?>" />Da
    </fieldset>
    <fieldset>
        <label for="massB">Mass B</label> <input type="text"
            name="massB" id="massB" value="<?php echo $massB; ?>" />Da
    </fieldset>
    <fieldset>
        <input type="submit" value="Convert" />
    </fieldset>
</form>

<p class="centreText">
<?php
$tolerance = new Tolerance(abs($massA - $massB), Tolerance::DA);

echo 'The mass difference of <strong>' . $massA . 'Da</strong> and <strong>' . $massB . 'Da</strong><br />';
echo 'In Daltons, <strong>' . round($tolerance->getDaltonDelta($massB), 5) . ' Da</strong><br />';
echo 'In ppm, <strong>' . round($tolerance->getPpmDelta($massB), 5) . ' ppm</strong>';
?></p>