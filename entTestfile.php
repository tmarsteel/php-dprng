<?php 

include(__DIR__ . "/tmarsteel/dprng/DPRNG.php");
use tmarsteel\dprng\DPRNG;

$rng = new DPRNG();

$fp = fopen("out-dprng.bin", "w+");

for ($i = 0;$i < 25600;$i++)
{
	fwrite($fp, chr($rng->nextInt(0, 255)));
}

fclose($fp);

$fp = fopen("out-rand.bin", "w+");

for ($i = 0;$i < 25600;$i++)
{
	fwrite($fp, chr(rand(0, 255)));
}

fclose($fp);

$fp = fopen("out-mcrypt.bin", "w+");

for ($i = 0;$i < 25600;$i++)
{
	fwrite($fp, mcrypt_create_iv(1));
}

fclose($fp);