<?php
namespace Opensource\Reviews;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use CSaleUserAccount;

class Account {

    public static function getAccount($user_id){
        Loader::includeModule('sale');
        $account = CSaleUserAccount::GetList([], ['USER_ID' => $user_id])->Fetch();
        if(!$account){
            CSaleUserAccount::Add([
                'USER_ID' => $user_id,
                'CURRENCY' => Option::get("sale", "default_currency","KZT"),
                'LOCKED' => 'N',
                'CURRENT_BUDGET' => 0,
            ]);
            $account = CSaleUserAccount::GetList([], ['USER_ID' => $user_id])->Fetch();
        }

        return $account;
    }

    public static function change($user_id, $price = false){
        $account = static::getAccount($user_id);
        if($price === false){
            $price = Option::get('opensource.reviews', 'price', 1);
        }

        if($price != 0 && $account) {
            CSaleUserAccount::Update($account['ID'], ['CURRENT_BUDGET' => $price + $account['CURRENT_BUDGET']]);
        }
    }

}