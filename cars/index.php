<?
define("HIDE_SIDEBAR", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->IncludeComponent(
	"only:cars.rental",
	".default",
	[
		"IBLOCK_TYPE"       => "content",
		"IBLOCK_CARS_CODE"  => "cars",
		"IBLOCK_RENTS_CODE" => "rents",
	],
	false
);
?>