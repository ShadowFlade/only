<?php

namespace Only\Handler;

use Bitrix\Main\EventResult;

class Product
{
	public static function checkDontAllowDelete(&$arFIelds)
	{
		$id = $arFIelds['ID'];
		$catalogIblockId = \CATALOG_IBLOCK_ID;

		if (
			$arFIelds['IBLOCK_ID'] != $catalogIblockId
			|| empty($arFIelds['ID'])
		) {
			return;
		}

		$el = \CIBlockElement::GetList(//на всякий случай если обновляем не
		// из админки
			false,
			['ID' => $id, 'IBLOCK_ID' => $catalogIblockId],
			false,
			false,
			['DATE_CREATE','ID','NAME']
		)->fetch();
		$productName = $el['NAME'];

		if (empty($el)) {
			return;
		}
		$dateCreatedStr = $el['DATE_CREATE'];
		$format = 'd.m.Y H:i:s';
		$pastTime = new \DateTime('-7 days');
		$dateCreated = date_create_from_format($format, $dateCreatedStr);
		$isOlderThan7Days = $dateCreated < $pastTime;

		if (!$isOlderThan7Days) {
			global $APPLICATION;
			$msg ="Товар $productName был создан менее одной недели назад и не может быть изменен";
			$APPLICATION->ThrowException($msg);
			return false;
		}
	}
}