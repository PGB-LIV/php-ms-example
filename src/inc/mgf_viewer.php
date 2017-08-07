<?php
use pgb_liv\php_ms\Reader\MgfReader;

?>
<h2>MGF Viewer</h2>
<form enctype="multipart/form-data" action="?page=mgf_viewer"
    method="POST">
    MGF File: <input name="mgf" type="file" /><br /> <input
        type="submit" value="Send File" /><br />
</form>
<?php
if (isset($_FILES['mgf'])) {
    
    $mgfFile = $_FILES['mgf']['tmp_name'];
    
    $reader = new MgfReader($mgfFile);
    
    echo '<table class="formattedTable hoverableRow"><thead><tr><th>Title</th><th>m/z</th><th>z</th><th>RT</th><th>Fragments</th></tr></thead><tbody>';
    
    foreach ($reader as $spectra) {
        echo '<tr><td>';
        echo $spectra->getTitle();
        echo '</td><td>';
        echo round($spectra->getMassCharge(), 4);
        echo '</td><td>';
        echo $spectra->getCharge();
        echo '</td><td>';
        echo $spectra->getRetentionTime();
        echo '</td><td>';
        echo count($spectra->getFragmentIons());
        echo '</td></tr>';
    }
    
    echo '</tbody></table>';
}
?>