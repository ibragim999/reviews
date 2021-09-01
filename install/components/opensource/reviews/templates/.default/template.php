<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * @var array $arParams
 * @var array $arResult
 */

$elementID = $arParams['ELEMENT_ID'];
$paramsId = 'IBLOCK_CATALOG_REVIEWS_PARAMS_'.$elementID;
$issetNext = $arResult['COUNT'] > $arParams['LIMIT']+$arParams['OFFSET'];
$_SESSION[$paramsId] = $arParams;

?>

    <div class="reviews my-3 my-sm-4 my-md-5 pt-lg-4">
        <h4 class="title-1 mb-4">Отзывы</h4>
        <div class="row reviews_w">
            <?if(count($arResult['ITEMS'])) { ?>
                <div class="col-12 order-1 order-md-0 col-md-6 col-xl-5 reviews-items">
                    <div id="<?=$arParams['REVIEWS_ID']?>">

                        <?if(!$issetNext){?><div class="last-items"></div><?}?>
                        <?foreach ($arResult['ITEMS'] as $item) { ?>
                            <div class="row mb-5">
                                <div class="col-auto">
                                    <? if($item['AVATAR']){
                                        $avatarUrl = $item['AVATAR']['src'];
                                    } else {
                                        $email = $item['USER_EMAIL'];
                                        $suteUrl = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                        $default = $suteUrl.'/bitrix/components/opensource/reviews/templates/.default/images/user.png';
                                        $avatarUrl = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=" . urlencode($default) . "&s=" . $size;
                                    } ?>
                                    <img width="50"  class="rounded" src="<?=$avatarUrl?>" alt="<?=$item['NAME']?>"/>
                                </div>
                                <div class="col font-weight-normal">
                                    <div class="row">
                                        <div class="font-weight-bold col-auto mb-2"><?=$item['NAME']?></div>
                                        <div class="col-auto ml-auto d-sm-block d-none d-md-none d-lg-block">
                                            <div class="font-weight-light"><?=$item['DATE_STRING']?></div>
                                            <div>
                                                <?for ($i = 0; $i < 5; $i++) { ?>
                                                    <span class="icon icon-star<?=$item['RATING'] > $i?' fill':''?>"></span>
                                                <? } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?if($item['TERM']){?>
                                        <div class="mb-2"><span class="font-weight-bold">Срок использования: </span><?=$item['TERM']?></div>
                                    <?}?>
                                    <?if($item['DEFECTS']){?>
                                        <div class="mb-2"><span class="font-weight-bold">Недостатки: </span><?=$item['DEFECTS']?></div>
                                    <?}?>
                                    <div class=""><span class="font-weight-bold">Комментарий: </span><?=$item['COMMENT']?></div>
                                    <div class="d-sm-none d-md-block d-lg-none mt-3">
                                        <div class="font-weight-light"><?=$item['DATE_STRING']?></div>
                                        <div>
                                            <?for ($i = 0; $i < 5; $i++) { ?>
                                                <span class="icon icon-star<?=$item['RATING'] > $i?' fill':''?>"></span>
                                            <? } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>

                    </div>
                    <? if($issetNext) { ?>
                        <div class="mb-4 reviews-button">
                            <button class="btn reviews-button-next w-100 btn-link link text-decoration-none">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Показать больше отзывов
                            </button>
                        </div>
                    <? } ?>
                </div>
            <? } ?>

            <div class="col-md-auto col-12 mb-5 order-0 order-md-1<?=(count($arResult['ITEMS'])?' ml-auto':'')?>">
                <div>
                    <?foreach ($arResult['RATING_COUNT'] as $rating=>$count) { ?>
                        <div class="d-flex align-items-center rating-info mb-4">
                            <div class="rating-value text-muted"><?=$rating?> <?=\Opensource\Reviews\Util::plural($rating, 'звезда', 'звезд', 'звезды')?></div>
                            <div class="rating-progress" style="width: 200px;">
                                <? $percent = $count/$arResult['COUNT']*100; ?>
                                <div class="progress">
                                    <div class="progress-bar bg-warning"
                                         role="progressbar"
                                         style="width: <?=$percent?>%"
                                         aria-valuenow="<?=$percent?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="rating-count"><?=$count?></div>
                        </div>
                    <? } ?>
                    <?if(\Opensource\Reviews\Util::checkUser($USER->GetID(), $elementID)){?>
                        <div class="text-center text-md-left">
                            <button data-toggle="modal" data-target="#reviewsModal" class="btn btn-primary">
                                Оставить отзыв
                            </button>
                        </div>
                    <?}?>
                </div>
            </div>
        </div>
        <script>
            ReviewsObject.init({
                url: '/bitrix/components/custom/catalog.reviews/templates/.default/ajax.php',
                reviews_id: '<?=$arParams['REVIEWS_ID']?>',
                data: {
                    ELEMENT_ID: <?=$elementID?>,
                    sessid: '<?=bitrix_sessid()?>',
                }
            });
        </script>
        <?if(\Opensource\Reviews\Util::checkUser($USER->GetID(), $elementID)){?>
            <div class="modal fade" id="reviewsModal" tabindex="-1" aria-labelledby="reviewsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-title">
                                <h5 class="title-1" id="exampleModalLabel">Ваш отзыв о тоааре</h5>
                                <h6 class="title-3"><?=$arResult['ELEMENT']['NAME']?></h6>
                            </div>
                            <div class="modal-image">
                                <?if($arResult['ELEMENT']['PICTURE']) { ?>
                                    <img class="rounded" src="<?=$arResult['ELEMENT']['PICTURE']['src']?>" alt="" width="70"/>
                                <? } ?>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="<?=$arParams['REVIEWS_ID']?>_form">
                                <div class="row align-items-center mb-4">
                                    <div class="col-auto">
                                        <div class="d-inline-block align-middle mr-3 font-weight-bold">Оцените покупку</div>
                                        <div class="star-inputs align-middle d-inline-block">
                                            <?for ($i = 5; $i > 0; $i--) { ?>
                                                <input value="<?=$i?>" id="<?$arParams['REVIEWS_ID']?>_star_<?=$i?>" name="RATING" type="radio" class="d-none input-star"/>
                                                <label for="<?$arParams['REVIEWS_ID']?>_star_<?=$i?>" class="icon icon-star"></label>
                                            <? } ?>
                                        </div>
                                    </div>
                                    <div class="col-5 ml-auto text-right">
                                        <label>
                                            <select name="TERM" class="form-control">
                                                <option value="" class="text-muted">Время использования</option>
                                                <option>Не более года</option>
                                                <option>Больше года</option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="sr-only" for="<?$arParams['REVIEWS_ID']?>_COMMENT">Комментарий к отзыву</label>
                                    <textarea id="<?$arParams['REVIEWS_ID']?>_COMMENT"
                                              class="form-control"
                                              name="COMMENT"
                                              rows="4"
                                              placeholder="Комментарий к отзыву..."></textarea>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="sr-only" for="<?$arParams['REVIEWS_ID']?>_DEFECTS">Недостатки</label>
                                    <textarea id="<?$arParams['REVIEWS_ID']?>_DEFECTS"
                                              class="form-control"
                                              name="DEFECTS"
                                              rows="4"
                                              placeholder="Недостатки..."></textarea>
                                </div>
                                <div class="text-right">
                                    <button class="btn btn-primary">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        Оставить отзыа
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?}?>
    </div>
