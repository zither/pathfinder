<?php

//$costs = file_get_contents("costs");
//preg_match_all("#(.) (\d+)#", $costs, $result);
//$costsArray = array_combine($result[1], $result[2]);
//file_put_contents("costs.json", json_encode($costsArray, JSON_PRETTY_PRINT));

$costs = json_decode(file_get_contents("costs.json"), true);
$averageCost = array_sum(array_values($costs)) / count($costs);
print($averageCost);
