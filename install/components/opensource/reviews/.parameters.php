<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
use \Bitrix\Main\Loader;

if (!Loader::includeModule("iblock"))
	return;
$useBlogs = \Bitrix\Main\ModuleManager::isModuleInstalled('blog');

$arIBlockTypes = CIBlockParameters::GetIBlockTypes();

$arIBlock = array();
$iblockFilter = (
	!empty($arCurrentValues['IBLOCK_TYPE'])
	? array('TYPE' => $arCurrentValues['IBLOCK_TYPE'], 'ACTIVE' => 'Y')
	: array('ACTIVE' => 'Y')
);
$rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
while ($arr = $rsIBlock->Fetch())
	$arIBlock[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
unset($arr, $rsIBlock, $iblockFilter);

$arComponentParameters = array(
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CATALOG_SC_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockTypes,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"NAME" => GetMessage("CATALOG_SC_IBLOCK_ID"),
			"PARENT" => "BASE",
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y"
		),
		"ELEMENT_ID" => array(
			"NAME" => GetMessage("CATALOG_SC_ELEMENT_ID"),
			"TYPE" => "STRING",
			"PARENT" => "BASE",
			"DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}',
		),
		"ELEMENT_CODE" => array(
			"NAME" => GetMessage("CATALOG_SC_ELEMENT_CODE"),
			"TYPE" => "STRING",
			"PARENT" => "BASE",
			"DEFAULT" => ''
		),
		"URL_TO_COMMENT" => array(
			"NAME" => GetMessage("CATALOG_SC_URL_TO_COMMENT"),
			"TYPE" => "STRING",
			"PARENT" => "BASE"
		),
		"WIDTH" => array(
			"NAME" => GetMessage("CATALOG_SC_WIDTH"),
			"TYPE" => "STRING",
			"PARENT" => "BASE"
		),
		"COMMENTS_COUNT" => array(
			"NAME" => GetMessage("CATALOG_SC_COMMENTS_COUNT"),
			"TYPE" => "STRING",
			"PARENT" => "BASE",
			"DEFAULT" => "5"
		),
		"SHOW_DEACTIVATED" => array(
			"NAME" => GetMessage('CATALOG_SC_SHOW_DEACTIVATED'),
			"TYPE" => "CHECKBOX",
			"PARENT" => "BASE",
			"DEFAULT" => "N"
		),
		"CHECK_DATES" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CATALOG_SC_CHECK_DATES"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"BLOG_USE" => array(
			"NAME" => GetMessage("CATALOG_SC_BLOG_USE"),
			"TYPE" => "CHECKBOX",
			"PARENT" => "BLOG",
			"DEFAULT" => "N",
			"REFRESH" => "Y"
		),
		"CACHE_TIME"  =>  array(
			"DEFAULT" => 0
		)
	)
);

if (!$useBlogs)
{
	unset($arComponentParameters['GROUPS']['BLOG']);
	unset($arComponentParameters['PARAMETERS']['BLOG_USE']);
}

if ($useBlogs && isset($arCurrentValues["BLOG_USE"]) && $arCurrentValues["BLOG_USE"] == "Y")
{
	$arComponentParameters["PARAMETERS"]["BLOG_TITLE"] = array(
		"NAME" => GetMessage("CATALOG_SC_BLOG_TITLE"),
		"TYPE" => "STRING",
		"PARENT" => "BLOG",
		"DEFAULT" => GetMessage("CATALOG_SC_BLOG_TITLE_VALUE")
	);
	$arComponentParameters["PARAMETERS"]["BLOG_URL"] = array(
		"NAME" => GetMessage("CATALOG_SC_BLOG_URL"),
		"TYPE" => "STRING",
		"PARENT" => "BLOG",
		"DEFAULT" => "catalog_comments"
	);
	$arComponentParameters["PARAMETERS"]["PATH_TO_SMILE"] = array(
		"NAME" => GetMessage("CATALOG_SC_PATH_TO_SMILE"),
		"TYPE" => "STRING",
		"PARENT" => "BLOG",
		"DEFAULT" => "/bitrix/images/blog/smile/"
	);
	$arComponentParameters["PARAMETERS"]["EMAIL_NOTIFY"] = array(
		"NAME" => GetMessage("CATALOG_SC_EMAIL_NOTIFY"),
		"TYPE" => "CHECKBOX",
		"PARENT" => "BLOG",
		"DEFAULT" => "N"
	);
	$arComponentParameters["PARAMETERS"]["SHOW_SPAM"] = array(
		"NAME" => GetMessage("CATALOG_SC_SHOW_SPAM"),
		"TYPE" => "CHECKBOX",
		"PARENT" => "BLOG",
		"DEFAULT" => "Y"
	);
	$arComponentParameters["PARAMETERS"]["SHOW_RATING"] = array(
		"NAME" => GetMessage("CATALOG_SC_SHOW_RATING"),
		"TYPE" => "CHECKBOX",
		"PARENT" => "BLOG",
		"DEFAULT" => "N",
		"REFRESH" => "Y"
	);

	if (isset($arCurrentValues["SHOW_RATING"]) && $arCurrentValues["SHOW_RATING"] == "Y")
	{
		$arComponentParameters["PARAMETERS"]["RATING_TYPE"] = array(
			"NAME" => GetMessage("RATING_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"" => GetMessage("RATING_TYPE_CONFIG"),
				"like" => GetMessage("RATING_TYPE_LIKE_TEXT"),
				"like_graphic" => GetMessage("RATING_TYPE_LIKE_GRAPHIC"),
				"standart_text" => GetMessage("RATING_TYPE_STANDART_TEXT"),
				"standart" => GetMessage("RATING_TYPE_STANDART_GRAPHIC"),
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "BLOG",
		);
	}
}
