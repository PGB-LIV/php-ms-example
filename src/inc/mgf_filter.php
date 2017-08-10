<?php
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Writer\MgfWriter;
use pgb_liv\php_ms\Utility\Filter\FilterCharge;
use pgb_liv\php_ms\Utility\Filter\FilterMass;
use pgb_liv\php_ms\Utility\Filter\FilterRetentionTime;

error_reporting(E_ALL);
ini_set('display_errors', true);
set_time_limit(3600);

define('FORM_FILE', 'mgf');

if (isset($_FILES[FORM_FILE])) {
    $filters = array();
    
    if ($_POST['charge_min'] != 'ANY' || $_POST['charge_max'] != 'ANY') {
        $min = $_POST['charge_min'] != 'ANY' ? (int) $_POST['charge_min'] : null;
        $max = $_POST['charge_max'] != 'ANY' ? (int) $_POST['charge_max'] : null;
        
        $filters[] = new FilterCharge($min, $max);
    }
    
    if ($_POST['rt_min'] != '' || $_POST['rt_max'] != '') {
        $min = $_POST['rt_min'] != '' ? (float) $_POST['rt_min'] : null;
        $max = $_POST['rt_max'] != '' ? (float) $_POST['rt_max'] : null;
        
        $filters[] = new FilterRetentionTime($min, $max);
    }
    
    if ($_POST['mass_min'] != '' || $_POST['mass_max'] != '') {
        $min = $_POST['mass_min'] != '' ? (float) $_POST['mass_min'] : null;
        $max = $_POST['mass_max'] != '' ? (float) $_POST['mass_max'] : null;
        
        $filters[] = new FilterMass($min, $max);
    }
    
    $mgfFile = $_FILES[FORM_FILE]['tmp_name'];
    
    $reader = new MgfReader($mgfFile);
    $writer = new MgfWriter('php://output');
    
    header('Content-type: text/plain;');
    header('Content-Disposition: attachment; filename="' . $_FILES[FORM_FILE]['name'] . '"');
    
    foreach ($reader as $spectra) {
        $skip = false;
        foreach ($filters as $filter) {
            if (! $filter->isValidSpectra($spectra)) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) {
            continue;
        }
        
        $writer->write($spectra);
    }
    
    $writer->close();
    exit();
}
?>
<h2>MGF Filter</h2>
<p>This tool allows you to upload an MGF file and filter it by the specified options. A new MGF file will be generated that you can download.</p>

<form enctype="multipart/form-data"
    action="?page=mgf_filter&amp;txtonly=1" method="POST">

    <fieldset>
        <label for="file">MGF File</label><input name="<?php echo FORM_FILE;?>" id="file" 
            type="file" />
    </fieldset>
    <fieldset>
        <label for="minCharge">Min Charge</label><select
            name="charge_min" id="minCharge">
            <option>ANY</option>
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
            <option>5</option>
            <option>6</option>
            <option>7</option>
            <option>8</option>
            <option>9</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="maxCharge">Max Charge</label><select
            name="charge_max" id="maxCharge">
            <option>ANY</option>
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
            <option>5</option>
            <option>6</option>
            <option>7</option>
            <option>8</option>
            <option>9</option>
        </select>
    </fieldset>
    <fieldset>
        <label for="minRt">Min RT</label>
        
        <input type="text" name="rt_min"
            id="minRt">
    </fieldset>
    <fieldset>
        <label for="maxRt">Max RT</label><input type="text" name="rt_max"
            id="maxRt">
    </fieldset>
    <fieldset>
        <label for="minMass">Min Mass</label><input type="text" name="mass_min"
            id="minMass">
    </fieldset>
    <fieldset>
        <label for="maxMass">Max Mass</label><input type="text" name="mass_max"
            id="maxMass">
    </fieldset>

    <input type="submit" value="Upload MGF" />
</form>
