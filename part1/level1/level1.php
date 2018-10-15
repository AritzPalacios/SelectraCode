<?php

$json = './data.json';
$content = file_get_contents($json);
$jsonData = json_decode($content, true);
$bills= [];
foreach ($jsonData['users'] as $userValue) {
    foreach ($jsonData['providers'] as $providerValue) {
        if($providerValue['id'] == $userValue['provider_id']){
            $id = count($bills) + 1;
            $price = $providerValue['price_per_kwh'] * $userValue['yearly_consumption'];
            $bills[]=['id'=> $id, 'price'=> $price, 'user_id' => $userValue['id']];
            break;
        }
    }
}

$fp = fopen('output.json', 'w');
fwrite($fp, json_encode(['bills' => $bills]));
fclose($fp);