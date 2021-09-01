<?php

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Opensource\Reviews\ReviewTable;
use Bitrix\Main\Result;

class OpenSourceReviewsComponent extends CBitrixComponent
{
    /**
     * @var ErrorCollection
     */
    public $errorCollection;


    /**
     * CustomOrder constructor.
     * @param CBitrixComponent|null $component
     * @throws Bitrix\Main\LoaderException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('opensource.reviews');

        $this->errorCollection = new ErrorCollection();
    }

    public function onIncludeComponentLang()
    {
        Loc::loadLanguageFile(__FILE__);
    }

    public function onPrepareComponentParams($arParams = []): array
    {
        $arParams['LIMIT'] = $arParams['LIMIT'] ?? 2;
        $arParams['OFFSET'] = $arParams['OFFSET'] ?? 0;
        $arParams['REVIEWS_ID'] = 'reviews_'.uniqid();
        $_SESSION['OPENSOURCE_REVIEWS_COMPONENT_PARAMS'.$arParams['ELEMENT_ID']] = $arParams;
        return $arParams;
    }

    public function executeComponent()
    {
        global $USER;
        if($USER->IsAuthorized()) {
            $filter = [
                ['LOGIC' => 'OR',
                    ['=PUBLISHED' => 'Y', '=DELETED' => 'N'],
                    ['=USER_ID' => $USER->GetID()]],
                '=ELEMENT_ID' => $this->arParams['ELEMENT_ID'],
            ];
        } else {
            $filter = [
                '=DELETED' => 'N',
                '=PUBLISHED' => 'Y',
                'ELEMENT_ID' => $this->arParams['ELEMENT_ID'],
            ];
        }

        $this->arResult['ITEMS'] = ReviewTable::getList([
            'select' => [
                '*',
                'USER_NAME' => 'USER.NAME',
                'USER_LAST_NAME' => 'USER.LAST_NAME',
                'USER_EMAIL' => 'USER.EMAIL',
                'USER_LOGIN' => 'USER.LOGIN',
                'USER_PHOTO' => 'USER.PERSONAL_PHOTO'
            ],
            'filter' => $filter,
            'order' => ['ID' => 'DESC'],
            'limit' => $this->arParams['LIMIT'],
            'offset' => $this->arParams['OFFSET'],
        ])->fetchAll();

        foreach ($this->arResult['ITEMS'] as &$ITEM){
            $ITEM['DATE_STRING'] = \Opensource\Reviews\Util::dateToMonthsString(strtotime($ITEM['CREATED_AT']));

            foreach($ITEM as $key=>$value){
                $ITEM[$key] = htmlspecialcharsEx($value);
            }

            $ITEM['NAME'] = implode(' ', [$ITEM['USER_NAME'], $ITEM['USER_LAST_NAME']]);
            if($ITEM['USER_PHOTO']) {
                $ITEM['AVATAR'] = CFile::ResizeImageGet($ITEM['USER_PHOTO'], ['width' => 50, 'height' => 50], BX_RESIZE_IMAGE_PROPORTIONAL);
            }
        }

        $this->arResult['RATING_COUNT'] = ReviewTable::getCountRating($filter);

        $this->arResult['COUNT'] = ReviewTable::getCount($filter);


        $this->includeComponentTemplate();
    }



    public function validate($values, $fields = []){
        $result = new Result();

        if(!$fields){
            $fields = ['ELEMENT_ID', 'TERM', 'COMMENT', 'DEFECTS', 'RATING'];
        }

        if(in_array('ELEMENT_ID', $fields) && (!isset($values['ELEMENT_ID']) || !(int)($values['ELEMENT_ID']))){
            $result->addError(new \Bitrix\Main\Error('Не выбран товар', 'ELEMENT_ID'));
        } elseif(in_array('ELEMENT_ID', $fields) && !\Bitrix\Iblock\ElementTable::getList(
            ['filter' => ['ID' => $values['ELEMENT_ID'], 'ACTIVE' => 'Y', 'IBLOCK_ID' => $this->arParams['IBLOCK_ID']]]
        )->fetch()){
            $result->addError(new \Bitrix\Main\Error('Не выбран тов2ар'.json_encode($this->arParams), 'ELEMENT_ID'));
        }

        if(in_array('TERM', $fields) && (!isset($values['TERM']) || !$values['TERM'])){
            $result->addError(new \Bitrix\Main\Error('Не указано время использования', 'TERM'));
        }

        if(in_array('COMMENT', $fields) && (!isset($values['COMMENT']) || !$values['COMMENT'])){
            $result->addError(new \Bitrix\Main\Error('Введите комментарий', 'COMMENT'));
        }

        if(in_array('RATING', $fields) && (!isset($values['RATING']) || $values['RATING'] < 1 || $values['RATING'] > 5)){
            $result->addError(new \Bitrix\Main\Error('Оцените товар', 'RATING'));
        }

        return $result;
    }
}















