<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Request;
use Bitrix\Sale\Location\Search\Finder;
use Bitrix\Sale\Location\TypeTable;
use Bitrix\Sale\PropertyValue;
use OpenSource\Order\LocationHelper;
use Bitrix\Sale\Delivery;
use OpenSource\Order\OrderHelper;
use Opensource\Reviews\ReviewLogTable;
use Opensource\Reviews\ReviewTable;

class OpenSourceReviewsAjaxController extends Controller
{
    /**
     * @param Request|null $request
     * @throws LoaderException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        Loader::includeModule('sale');
        Loader::includeModule('opensource.reviews');
    }

    /**
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'addReview' => [
                'prefilters' => []
            ],
            'nextReviews' => [
                'prefilters' => []
            ],
        ];
    }

    private function getComponent($elementId){
        CBitrixComponent::includeComponentClass('opensource:reviews');

        $componentClass = new OpenSourceReviewsComponent();

        $arParams = $_SESSION['OPENSOURCE_REVIEWS_COMPONENT_PARAMS'.$elementId] ?? [];
        $arParams['IS_AJAX'] = 'Y';

        $componentClass->onPrepareComponentParams($arParams);
        $componentClass->arParams = $arParams;

        return $componentClass;
    }

    public function addReviewAction($values){
        global $USER, $APPLICATION;

        if(!$USER->IsAuthorized()){
            $this->errorCollection->add([new \Bitrix\Main\Error('Для продолжения необходимо авторизоваться!')]);
            return [];
        }

        if(!\Opensource\Reviews\Util::checkProduct($values['ELEMENT_ID'])){
            $this->errorCollection->add([new \Bitrix\Main\Error('Товар не найден!')]);
            return [];
        }

        if(!\Opensource\Reviews\Util::checkUser($USER->GetID(), $values['ELEMENT_ID'])){
            $this->errorCollection->add([new \Bitrix\Main\Error('Ошибка!')]);
            return [];
        }

        $componentClass = $this->getComponent((int)$values['ELEMENT_ID']);

        $result = $componentClass->validate($values);

        $errors = $result->getErrors();
        if($errors){
            $this->errorCollection->add($errors);
            return [];
        }

        $resultAdd = ReviewTable::add([
            'USER_ID' => $USER->GetId(),
            'ELEMENT_ID' => $values['ELEMENT_ID'],
            'TERM' => $values['TERM'],
            'COMMENT' => $values['COMMENT'],
            'DEFECTS' => $values['DEFECTS'],
            'RATING' => $values['RATING'],
        ]);
        if($resultAdd->isSuccess()){
            if(Option::get('opensource.reviews', "reward_type", 'M') === 'N'){
                $price = Option::get('opensource.reviews', 'price', 1);
                \Opensource\Reviews\Account::change($USER->GetID(), $price);

                ReviewLogTable::add([
                    'REVIEW_ID' => $resultAdd->getId(),
                    'PRICE' => $price,
                ]);
            }
            $review = ReviewTable::getList([
                'filter' => [
                    'ID' => $resultAdd->getId()
                ],
                'select' => [
                    '*',
                    'USER_EMAIL' => 'USER.EMAIL',
                    'USER_NAME' => 'USER.NAME',
                    'USER_LAST_NAME' => 'USER.LAST_NAME',
                    'PRODUCT_NAME' => 'ELEMENT.NAME',
                ]
            ])->fetch();
            CEvent::Send('REVIEW_ADD', SITE_ID, $review);
        }

        ob_start();
        $APPLICATION->IncludeComponent('opensource:reviews', '', $componentClass->arParams);
        $html = ob_get_contents();
        ob_end_clean();

        return [
            'html' => $html,
        ];

    }

    public function nextReviewsAction($ELEMENT_ID, $page){
        global $APPLICATION;
        $componentClass = $this->getComponent((int)$ELEMENT_ID);

        $result = $componentClass->validate(['ELEMENT_ID' => $ELEMENT_ID], ['ELEMENT_ID']);

        $errors = $result->getErrors();
        if($errors){
            $this->errorCollection->add($errors);
            return [];
        }

        $componentClass->arParams['OFFSET'] = $page * $componentClass->arParams['LIMIT'];
        ob_start();
        $APPLICATION->IncludeComponent('opensource:reviews', '', $componentClass->arParams);
        $html = ob_get_contents();
        ob_end_clean();

        return [
            'html' => $html,
        ];
    }

}














