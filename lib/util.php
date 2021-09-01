<?php

namespace Opensource\Reviews;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;

class Util {
    public static function plural($number, $singular, $plural1, $plural2=null)
    {
        $number = abs($number);
        if(!empty($plural2))
        {
            $p1 = $number%10;
            $p2 = $number%100;
            if($number == 0)
                return $plural1;
            if($p1==1 && !($p2>=11 && $p2<=19))
                return $singular;
            elseif($p1>=2 && $p1<=4 && !($p2>=11 && $p2<=19))
                return $plural2;
            else
                return $plural1;
        }else
        {
            if($number == 1)
                return $singular;
            else
                return $plural1;
        }

    }

    public static function dateToMonthsString($timestamp){
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);

        $months = [
            'января',
            'февраля',
            'марта',
            'апреля',
            'мая',
            'июня',
            'июля',
            'августа',
            'сентября',
            'октября',
            'ноября',
            'декабря'
        ];

        return "{$day} {$months[$month-1]} {$year}";
    }

    public static function checkUser($user_id, $product_id){
        Loader::includeModule('sale');
        if($user_id && $product_id) {
            $product = BasketTable::getList([
                'filter' => [
                    'ORDER.USER_ID' => $user_id,
                    'PRODUCT_ID' => $product_id,
                    'ORDER.STATUS_ID' => 'F',
                ],
                'select' => ['ORDER_ID'],
            ])->fetch();
            if($product){
                return true;
            }

        }

        return false;
    }

    public static function checkProduct($element_id)
    {
        if((int)$element_id) {
            $element = ElementTable::getList([
                'filter' => [
                    'ID' => (int)$element_id,
                    'ACTIVE' => 'Y',
                    'IBLOCK.IBLOCK_TYPE_ID' => 'catalog'
                ],
                'select' => ['ID']
            ])->fetch();
            if($element && (int)$element['ID'] == (int)$element_id){
                return true;
            }
        }


        return false;
    }
}