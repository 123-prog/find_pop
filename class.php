<?php

$str = '/fumo';
//readfile(strtolower($str));

$str = base64_encode($str);
$str = base64_decode($str);
$str = lcfirst($str);
$str = base64_encode($str);
$str = str_rot13($str);
$str = lcfirst($str);
$str = str_rot13($str);


echo $str;

