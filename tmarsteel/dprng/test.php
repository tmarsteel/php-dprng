<?php

include("DPRNG.php");

use tmarsteel\dprng\DPRNG;

$rng = new DPRNG();

echo $rng->next() . PHP_EOL;
echo $rng->nextDouble(0.3, 7.5) . PHP_EOL;
echo dechex($rng->nextInt(0, 0x7FFFFFFFFFFFF)) . PHP_EOL;