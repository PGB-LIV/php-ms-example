<!DOCTYPE html>
<html lang="en">
<title>PhpMs Demo Suite</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="theme-color" content="#009688" />
<link rel="stylesheet" href="css/style.css" />
<link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Roboto'>
<style>
html, body, h1, h2, h3, h4, h5, h6 {
    font-family: "Roboto", sans-serif
}
</style>
<body>

    <div id="pagecontainer">

<h1>phpMs Demo Suite</h1>
        <div id="grid">

            <ul id="navigation">
<?php
foreach ($_COMMANDS as $fileName => $description) {
    ?>
<li><a href="?page=<?php echo $fileName; ?>"><?php echo $description; ?></a></li>
<?php
}
?>
            </ul>
            <div id="content">
                <div class="panel">