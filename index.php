<?php
require __DIR__ . '/lazadaSearch.php';
$a = new App\Service\Lazada;
echo '<pre>';
var_dump($a->lazadaSearch('japan'));
echo '<pre>';
// add