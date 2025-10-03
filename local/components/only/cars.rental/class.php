<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use CBitrixComponent;

Loc::loadMessages(__FILE__);

class CarsRental extends CBitrixComponent
{
	/** @var int */
	protected $carsIblockId;

	/** @var int */
	protected $rentsIblockId;

	/** @var int */
	protected $propCarId;

	/** @var int */
	protected $propCategoryId;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['IBLOCK_TYPE'] = trim($arParams['IBLOCK_TYPE'] ?: 'content');
		$arParams['IBLOCK_CARS_CODE'] = trim($arParams['IBLOCK_CARS_CODE'] ?: 'cars');
		$arParams['IBLOCK_RENTS_CODE'] = trim($arParams['IBLOCK_RENTS_CODE'] ?: 'rents');

		return $arParams;
	}

	public function executeComponent()
	{
		try {
			if (!$this->checkModules()) {
				return;
			}

			$this->getIblockIds();
			$this->getPropertyIds();

			$this->arResult['CARS'] = $this->getAvailableCars();
			$this->arResult['CATEGORIES'] = $this->getCategoryList();

			$this->arResult['FROM'] = $_GET['from'] ?? '';
			$this->arResult['TO'] = $_GET['to'] ?? '';
			$this->arResult['MODEL'] = trim($_GET['model'] ?? '');
			$this->arResult['CATEGORY'] = trim($_GET['category'] ?? '');

			$this->includeComponentTemplate();
		} catch (\Throwable $e) {
			ShowError($e->getMessage());
		}
	}

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('iblock')) {
			ShowError(Loc::getMessage('CAR_RENTAL_LIST_ERROR_MODULE'));
			return false;
		}
		return true;
	}

	protected function getIblockIds(): void
	{
		$iblocksDB = IblockTable::getList([
				'filter' => ['CODE' => ['cars', 'rents']],
				'select' => ['ID', 'NAME', 'CODE']]
		);
		$iblocks = [];

		while ($iblock = $iblocksDB->fetch()) {
			$iblocks[$iblock['CODE']] = $iblock;
		}

		if (!isset($iblocks['cars']) || !isset($iblocks['rents'])) {
			throw new SystemException('Инфоблоки cars или rents не найдены');
		}
		$cars = $iblocks['cars'];
		$rents = $iblocks['rents'];

		if (!$cars || !$rents) {
			throw new SystemException('Инфоблоки cars или rents не найдены');
		}

		$this->carsIblockId = (int)$cars['ID'];
		$this->rentsIblockId = (int)$rents['ID'];
	}

	protected function getPropertyIds(): void
	{
		$carProp = PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $this->rentsIblockId,
				'=CODE'      => 'CAR'
			],
			'select' => ['ID']
		])->fetch();

		$catProp = PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $this->carsIblockId,
				'=CODE'      => 'CATEGORY'
			],
			'select' => ['ID']
		])->fetch();

		if (!$carProp || !$catProp) {
			throw new SystemException('Не найдены свойства CAR или CATEGORY');
		}

		$this->propCarId = (int)$carProp['ID'];
		$this->propCategoryId = (int)$catProp['ID'];
	}

	protected function parseDateTime(string $str): ?\DateTime
	{
		if (!$str) {
			return null;
		}
		$format = 'Y-m-d\TH:i';
		return \DateTime::createFromFormat($format, $str);
	}


	/**
	 * Возвращает список свободных машин
	 */
	protected function getAvailableCars(): array
	{

		$to = $this->convertToBitrixFormat($_GET['to']);
		$from = $this->convertToBitrixFormat($_GET['from']);

		global $DB;
		$to = date($DB->DateFormatToPHP(
			\CLang::GetDateFormat()),
			$to->getTimestamp()
		);
		$from = date($DB->DateFormatToPHP(
			\CLang::GetDateFormat()),
			$from->getTimestamp()
		);

		$toFormatted = DateTime::createFromFormat('d.m.Y H:i:s', $to);
		$toFormatted = $toFormatted->format('Y-m-d H:i:s');

		$fromFormatted = DateTime::createFromFormat('d.m.Y H:i:s', $from);
		$fromFormatted = $fromFormatted->format('Y-m-d H:i:s');


		$overlappingRentFilter = [
			'=IBLOCK_ID'          => $this->rentsIblockId,
			'ACTIVE'              => 'Y',
			'<PROPERTY_RENT_FROM' => $toFormatted,
			'>PROPERTY_RENT_TO'   => $fromFormatted,
		];

		$selectRents = ['ID', 'PROPERTY_CAR'];

		$busyRentsDB = \CIBlockElement::GetList(
			false,
			$overlappingRentFilter,
			false,
			false,
			$selectRents
		);


		$busyCarIds = [];
		while ($rent = $busyRentsDB->fetch()) {
			if (!empty($rent['PROPERTY_CAR_VALUE'])) {
				$busyCarIds[] = $rent['PROPERTY_CAR_VALUE'];
			}
		}


		$busyCarIds = array_unique($busyCarIds);

		if (empty($busyCarIds)) {
			$carsFilter = [
				'=IBLOCK_ID' => $this->carsIblockId,
				'ACTIVE'     => 'Y',
			];
		} else {
			$carsFilter = [
				'=IBLOCK_ID' => $this->carsIblockId,
				'ACTIVE'     => 'Y',
				'!ID'        => $busyCarIds,  // Исключаем занятые машины
			];
		}

		$model = trim($_GET['model'] ?? '');
		$category = trim($_GET['category'] ?? '');

		if ($category !== '') {
			$carsFilter['=PROPERTY_CATEGORY'] = $category;
		}

		if ($model !== '') {
			$carsFilter['%NAME'] = $model;
		}

		$availableCarsDB = \CIBlockElement::GetList(
			false,
			$carsFilter,
			false,
			false,
			['ID', 'NAME', 'PROPERTY_CATEGORY']
		);

		$cars = [];
		while ($car = $availableCarsDB->fetch()) {
			$cars[] = $car;
		}

		return $cars;
	}

	private function convertToBitrixFormat(?string $dateString): DateTime
	{
		if (empty($dateString)) {
			return new DateTime();
		}

		$date = DateTime::createFromFormat('Y-m-d\TH:i', $dateString);
		if (!$date) {
			$date = new DateTime($dateString);
		}

		return $date;
	}

	protected function getCategoryList(): array
	{
		$enumList = PropertyEnumerationTable::getList([
			'filter' => ['=PROPERTY_ID' => $this->propCategoryId],
			'select' => ['XML_ID', 'VALUE'],
			'order'  => ['SORT' => 'ASC', 'VALUE' => 'ASC']
		])->fetchAll();

		$result = [];
		foreach ($enumList as $item) {
			$result[htmlspecialcharsbx($item['XML_ID'])] = htmlspecialcharsbx($item['VALUE']);
		}
		return $result;
	}
}