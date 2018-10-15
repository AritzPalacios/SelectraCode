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


function getPrice($user, $provider, $contractLength, $green) {
     $discount = 0;
     $price = $provider['price_per_kwh'] * $user['yearly_consumption'];
     if ($green) {
         $discount = $user['yearly_consumption']* 0.05;
     }
     if($contractLength <=1){
         return $price *0.9 * $contractLength - $discount;
     } else {
         if ($contractLength > 1 && $contractLength <= 3) {
             return $price *0.8 * $contractLength - $discount;
         }
         return $price *0.75 * $contractLength - $discount;
     }
     
     return $price * $contractLength - $discount;
}

function getCommission($price, $contractLength, $providerHasCommission, $hasCommission =false) {
    $insuranceFee = round($contractLength*365*0.05, 2);
    $providerFee = round($price - $insuranceFee, 2);
    $selectraFee = round($providerFee*0.125, 2);
    
    if($hasCommission && $providerHasCommission) {
        $providerFee = $providerFee +50;
    }
    
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
    $existModification = false;
    foreach ($jsonData['contract_modifications'] as $value) {
         if ($value['contract_id'] == $contractValue['id']) {
             $canceled = true;
             if(array_key_exists('provider_id', $value)){
                $contractValue['provider_id'] = $value['provider_id'];
                $canceled = false;
            }
            if(array_key_exists('start_date', $value)){
                $contractValue['start_date'] = $value['start_date'];
                $canceled = false;
            }
            $contractValue['end_date'] = $value['end_date'];
            $provider = getProviderInfo($jsonData['providers'], $contractValue['provider_id']);
            if(!$provider){
               continue;
            }
            $datetime1 = date_create($contractValue['end_date']);
            $datetime2 = date_create($contractValue['start_date']);
            $contractLength = date_diff($datetime1, $datetime2);

            $price = getPrice($user, $provider, $contractLength->y, $contractValue['green']);
            $commission = getCommission($price, $contractLength->y, $provider['cancellation_fee'], $canceled);
            $id = count($bills) + 1;
            $bills[]=['commission' => $commission, 'id'=> $id, 'price'=> $price, 'user_id' => $user['id']];
            $existModification = true;
         }
    }
  if(!$existModification){
      $provider = getProviderInfo($jsonData['providers'], $contractValue['provider_id']);
   if(!$provider){
      continue;
   }
   
   $datetime1 = date_create($contractValue['end_date']);
   $datetime2 = date_create($contractValue['start_date']);
   $contractLength = date_diff($datetime1, $datetime2);

   $price = getPrice($user, $provider, $contractLength->y, $contractValue['green']);
   $commission = getCommission($price, $contractLength->y, $provider['cancellation_fee']);
   $id = count($bills) + 1;
   $bills[]=['commission' => $commission, 'id'=> $id, 'price'=> $price, 'user_id' => $user['id']];
   
  }
   
}

$fp = fopen('output.json', 'w');
fwrite($fp, json_encode(['bills' => $bills]));
fclose($fp);