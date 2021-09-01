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

class ReviewTable extends DataManager
{
    public static function getTableName()
    {
        return 'i_reviews';
    }

    public static function getMap()
    {
        return array(
            new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_ID'),
            )),
            new IntegerField('ELEMENT_ID', array(
                'autocomplete' => false,
                'primary' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_ELEMENT_ID'),
            )),
            new IntegerField('USER_ID', array(
                'autocomplete' => false,
                'primary' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_USER_ID'),
            )),
            new IntegerField('RATING', array(
                'autocomplete' => false,
                'primary' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_RATING'),
                'validation' => function() {
                    return array(
                        new Entity\Validator\Range(1, 5)
                    );
                }
            )),
            new TextField('COMMENT', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_COMMENT'),
                'default_value' => null,
            )),
            new TextField('DEFECTS', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_DEFECTS'),
                'default_value' => null,
            )),
            new StringField('TERM', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_TERM'),
                'default_value' => null,
            )),
            new Entity\BooleanField('PUBLISHED', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_PUBLISHED'),
                'default_value' => 'N',
                'values' => array('N', 'Y'),
            )),
            new Entity\BooleanField('DELETED', array(
                'required' => false,
                'values' => array('N','Y'),
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_DELETED'),
                'default_value' => 'N',
            )),
            new Entity\DateTimeField('CREATED_AT', array(
                'required' => false,
                'title' => Loc::getMessage('OPENSOURCE_REVIEWS_CREATED_AT'),
                'default_value' => (new \Bitrix\Main\Type\DateTime(date('d.m.Y H:i:s', time()), 'd.m.Y H:i:s')),
            )),
            'USER' => new ORM\Fields\Relations\Reference(
                'USER',
                '\Bitrix\Main\User',
                array('=this.USER_ID' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),
            'ELEMENT' => new ORM\Fields\Relations\Reference(
                'ELEMENT',
                '\Bitrix\Iblock\Element',
                array('=this.ELEMENT_ID' => 'ref.ID'),
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
