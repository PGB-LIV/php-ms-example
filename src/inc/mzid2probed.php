<?php
use pgb_liv\php_ms\Reader\MzIdentMlReaderFactory;
use pgb_liv\php_ms\Writer\ProBedWriter;

set_time_limit(600);

define('FORM_FILE', 'mzidentml');

if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] == 0) {
    $name = $_FILES[FORM_FILE]['name'];
    $mzIdentMlFile = $_FILES[FORM_FILE]['tmp_name'];
    
    if (substr_compare($_FILES[FORM_FILE]['name'], '.gz', strlen($_FILES[FORM_FILE]['name']) - 3) === 0) {
        // This input should be from somewhere else, hard-coded in this example
        $file_name = $_FILES[FORM_FILE]['tmp_name'];
        
        // Raising this value may increase performance
        // read 4kb at a time
        $buffer_size = 4096;
        $out_file_name = $file_name . '_decom';
        
        // Open our files (in binary mode)
        $file = gzopen($file_name, 'rb');
        $out_file = fopen($out_file_name, 'wb');
        
        // Keep repeating until the end of the input file
        while (! gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }
        
        // Files are done, close files
        fclose($out_file);
        gzclose($file);
        
        $mzIdentMlFile = $out_file_name;
    }
    
    $startTime = microtime(true);
    $reader = MzIdentMlReaderFactory::getReader($mzIdentMlFile);
    
    header('Content-type: text/plain;');
    header('Content-Disposition: attachment; filename="' . $_FILES[FORM_FILE]['name'] . '.pro.bed"');
    
    $data = $reader->getAnalysisData();
    
    $headers = array(
        'Converted from ' . $_FILES[FORM_FILE]['name'] . ' in ' . (microtime(true) - $startTime) . ' seconds'
    );
    
    $proBed = new ProBedWriter('php://output', $name, $headers);
    foreach ($data as $spectra) {
        $proBed->write($spectra);
    }
    
    $proBed->close();
    
    exit();
}
?>
<h2>mzIdentML to proBed Converter</h2>
<?php
if (! empty($_FILES) && $_FILES[FORM_FILE]['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
}
?>

<p>Converts an mzIdentML file into the proBed format. Only entries which
    contain chromosome information will be converted.</p>

<form enctype="multipart/form-data" action="?page=mzid2probed&txtonly=1"
    method="POST">
    <fieldset>
        <label for="file">mzIdentML File</label> <input
            name="<?php echo FORM_FILE; ?>" type="file" id="file" />
        (.gz or .mzid supported)
    </fieldset>

    <fieldset>
        <input type="submit" value="Send File" />
    </fieldset>
</form>