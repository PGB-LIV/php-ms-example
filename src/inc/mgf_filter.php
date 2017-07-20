<?php
use pgb_liv\php_ms\Reader\MgfReader;
use pgb_liv\php_ms\Writer\MgfWriter;
use pgb_liv\php_ms\Utility\Filter\FilterCharge;
use pgb_liv\php_ms\Utility\Filter\FilterMass;
use pgb_liv\php_ms\Utility\Filter\FilterRetentionTime;

error_reporting(E_ALL);
ini_set('display_errors', true);
set_time_limit(3600);

if (isset($_FILES['fasta'])) {
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
    
    $mgfFile = $_FILES['fasta']['tmp_name'];
    
    $reader = new MgfReader($mgfFile);
    $writer = new MgfWriter('php://output');
    
    header('Content-type: text/plain;');
    header('Content-Disposition: attachment; filename="' . $_FILES['fasta']['name'] . '"');
    
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
<form enctype="multipart/form-data"
    action="?page=mgf_filter&amp;txtonly=1" method="POST">
    MGF File: <input name="fasta" type="file" /><br /> Charge Range: <select
        name="charge_min">
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
    </select>- <select name="charge_max">
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
    </select><br /> RT Range: <input type="text" name="rt_min" value="" />-<input
        type="text" name="rt_max" value="" /><br /> Mass Range: <input
        type="text" name="mass_min" value="" />-<input type="text"
        name="mass_max" value="" /><br /> <input type="submit"
        value="Send File" /><br />
</form>
