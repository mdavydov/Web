<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$outp = '[ {"name":"Smile 1","icon":"smile1.svg", "desc":"This is a very cool smile", "price":0.99},'.
          '{"name":"Smile 2","icon":"smile2.svg", "desc":"Another cool smile", "price":2.99},'.
          '{"name":"Smile 3","icon":"smile3.svg", "desc":"Smiles continued with <p> :-)...", "price":3.99} ]';

echo($outp);
?>

