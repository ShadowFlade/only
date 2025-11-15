<?php

namespace Only\Handler;

use Bitrix\Main\EventResult;

class Product
{
	public static function checkDontAllowModify(&$arFIelds)
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
			['DATE_CREATE', 'ID', 'NAME']
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
			$msg = "Товар $productName был создан менее одной недели назад и не может быть изменен";
			$APPLICATION->ThrowException($msg);
			return false;
		}
	}

	public static function checkDontAllowDelete($id)
	{
		$el = \CIBlockElement::GetList(
			false,
			['ID' => $id, 'IBLOCK_ID' => \OFFERS_IBLOCK_ID],
			false,
			false,
			['DATE_CREATE', 'ID', 'NAME', 'SHOW_COUNTER']
		)->fetch();
		if (!$el) {
			return;
		}
		\Bitrix\Main\Diag\Debug::writeToFile(
			$el,
			date("d.m.Y H:i:s"),
			"local/log.log"
		);


		global $USER;

		$curUserLogin = $USER->GetLogin();
		$curUserId = $USER->GetID();
		$productName = $el['NAME'];
		$showsCounter = $el['SHOW_COUNTER'];
//		в админке текст сообщения такой "Пользователь #LOGIN#[#USER_ID#] пытается удалить популярный товар #PRODUCT_NAME#, у которого #SHOWS_COUNTER# показов на сайте"
//		в админке добавил почтовое событие IBLOCK_CANCEL_PRODUCT_DELETE и
// шаблон к нему
		if ($el['SHOW_COUNTER'] > 10_000) {
			global $APPLICATION;
			$fields = [
				'LOGIN'         => $curUserLogin,
				'USER_ID'       => $curUserId,
				'PRODUCT_NAME'  => $productName,
				'SHOWS_COUNTER' => $showsCounter,
			];
			\Bitrix\Main\Mail\Event::Send(
				[
					'EVENT_NAME' => 'IBLOCK_CANCEL_PRODUCT_DELETE',//кому
					// отправлять указал в шаблоне
					'LID'        => 's1',
					'C_FIELDS'   => $fields,
					'DATE'       => date('d.m.Y H:i'),
				]
			);
			$errMsg = "Нельзя удалять товар у которого больше 10т. просмотров";
			$APPLICATION->ThrowException($errMsg);
			return false;
		}
	}
}