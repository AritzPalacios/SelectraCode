<?php

function getUserInfo($users, $idUser) {
    
    foreach ($users as $value) {
        if($value['id'] == $idUser){
            return $value;
        }
    }
    return false;
    
}

function getProviderInfo($providers, $idProvider) {
    
    foreach ($providers as $value) {
        if($value['id'] == $idProvider){
            return $value;
        }
    }
    return false;
    
}


function getPrice($user, $provider, $contractLength) {
     $price = $provider['price_per_kwh'] * $user['yearly_consumption'];
     if($contractLength <=1){
         return $price *0.9 * $contractLength;
     } else {
         if ($contractLength > 1 && $contractLength <= 3) {
             return $price *0.8 * $contractLength;
         }
         return $price *0.75 * $contractLength;
     }
     
     return $price * $contractLength;
}

function getCommission($price, $contractLength) {
    $insuranceFee = round($contractLength*365*0.05, 2);
    $providerFee = round($price - $insuranceFee, 2);
    $selectraFee = round($providerFee*0.125, 2);
    
    return ['insurance_fee'=> $insuranceFee, 
        'provider_fee'=> $providerFee, 
        'selectra_fee'=> $selectraFee];
}

$json = './data.json';
$content = file_get_contents($json);
$jsonData = json_decode($content, true);
$bills= [];

foreach ($jsonData['contracts'] as $contractValue) {
   $user = getUserInfo($jsonData['users'], $contractValue['user_id']);
   if(!$user){
      continue;
   }
   $provider = getProviderInfo($jsonData['providers'], $contractValue['provider_id']);
   if(!$provider){
      continue;
   }
   
   $price = getPrice($user, $provider, $contractValue['contract_length']);
   $commission = getCommission($price, $contractValue['contract_length']);
   $id = count($bills) + 1;
   $bills[]=['commission' => $commission, 'id'=> $id, 'price'=> $price, 'user_id' => $user['id']];
   
}

$fp = fopen('output.json', 'w');
fwrite($fp, json_encode(['bills' => $bills]));
fclose($fp);