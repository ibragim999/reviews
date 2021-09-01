<?php

namespace Opensource\Reviews;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM;

Loc::loadMessages(__FILE__);

class ReviewLogTable extends DataManager
{
    public static function getTableName()
    {
        return 'i_reviews_log';
    }

    public static function getForReview($ID){
        return self::getList(['filter' => ['REVIEW_ID' => $ID], 'select' => ['*'], 'order' => ['ID' => 'DESC']]);
    }

    public static function getMap()
    {
        return array(
            'ID' => new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_ID'),
            )),
            'REVIEW_ID' => new IntegerField('REVIEW_ID', array(
                'autocomplete' => false,
                'primary' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_ELEMENT_ID'),
            )),
            'PRICE' => new IntegerField('PRICE', array(
                'autocomplete' => false,
                'primary' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_ELEMENT_ID'),
            )),
            'CREATED_AT' => new Entity\DateTimeField('CREATED_AT', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_CREATED_AT'),
                'default_value' => (new \Bitrix\Main\Type\DateTime(date('d.m.Y H:i:s', time()), 'd.m.Y H:i:s')),
            )),
            'REVIEW' => new ORM\Fields\Relations\Reference(
                'REVIEW',
                '\Opensource\Reviews\ReviewTable',
                array('=this.REVIEW_ID' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),
            'USER' => new ORM\Fields\Relations\Reference(
                'USER',
                '\Bitrix\Main\User',
                array('=this.REVIEW.USER_ID' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),
            'ELEMENT' => new ORM\Fields\Relations\Reference(
                'ELEMENT',
                '\Bitrix\Iblock\Element',
                array('=this.REVIEW.ELEMENT_ID' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),
            'count' => [
                'data_type' => 'integer',
                'expression' => ['count(*)']
            ]
        );

    }

    public static function getCountRating($filter){
        $query = self::getList([
            'filter' => $filter,
            'select' => [
                'count',
                'RATING',
            ],
            'group' => [
                'RATING',
            ]
        ]);


        $out = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ];

        while ($result = $query->fetch()){
            if(isset($out[$result['RATING']])) {
                $out[$result['RATING']] = $result['count'];
            }
        }

        return $out;
    }
}
