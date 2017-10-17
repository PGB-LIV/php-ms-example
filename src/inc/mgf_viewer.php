<?php
use pgb_liv\php_ms\Reader\MgfReader;

?>
<h2>MGF Viewer</h2>
<form enctype="multipart/form-data" action="?page=mgf_viewer"
    method="POST">
    <fieldset>
        <label for="mgf">MGF File</label> <input name="mgf" id="mgf"
            type="file" />
    </fieldset>

    <fieldset>
        <input type="submit" value="Upload" />
    </fieldset>
</form>
<?php
if (! empty($_FILES) && $_FILES['mgf']['error'] != 0) {
    die('<p>An error occured. Ensure you included a file to upload.</p>');
} elseif (! empty($_FILES) && $_FILES['mgf']['error'] == 0) {
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