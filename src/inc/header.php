<html>
<head>
<title>PhpMs Example Suite</title>
</head>
<body>
    <ul style="float: left; height:100%; margin-right: 25px; ">
<?php
foreach ($_COMMANDS as $fileName => $description) {
    ?>
<li><a href="?page=<?php echo $fileName; ?>"><?php echo $description; ?></a></li>
<?php
}
?>
    </ul>