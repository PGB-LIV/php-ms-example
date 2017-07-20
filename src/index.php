<?php
/**
 * Copyright 2016 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
error_reporting(E_ALL);
ini_set('display_errors', true);

include 'autoload.php';
include 'vendor/autoload.php';
include 'conf/commands.php';

if (! isset($_REQUEST['txtonly'])) {
    include 'inc/header.php';
}

$page = 'welcome';
if (isset($_GET['page'])) {
    $page = $_GET['page'];
}

include 'inc/' . $page . '.php';

if (! isset($_REQUEST['txtonly'])) {
    include 'inc/footer.php';
}
