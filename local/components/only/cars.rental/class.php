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
	protected array $iblocks;


	protected int $carsIblockId;

	protected int $rentsIblockId;


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
			ShowError('Module Iblock is not loaded');
			return false;
		}
		return true;
	}

	protected function getIblockIds(): void
	{
		$iblocksDB = IblockTable::getList([
				'filter' => ['CODE' => ['cars', 'rents']],
				'select' => ['ID', 'CODE']]
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

		$this->carsIblockId = $cars['ID'];
		$this->rentsIblockId = $rents['ID'];

		if (!$cars || !$rents) {
			throw new SystemException('Инфоблоки cars или rents не найдены');
		}

	}


	private function convertToBitrixFormat(?string $dateString): string
	{
		$format = 'Y-m-d H:i:s';

		if (empty($dateString)) {
			return (new DateTime())->format($format);
		}

		$date = DateTime::createFromFormat('Y-m-d\TH:i', $dateString);
		if (!$date) {
			$date = new DateTime($dateString);
		}

		$dateFormatted = $date->format($format);

		return $dateFormatted;
	}


	/**
	 * Возвращаем список свободных машин
	 */
	protected function getAvailableCars(): array
	{
		$isDebug = false;
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$to = trim($request->get('to'));
		$from = trim($request->get('from'));

		$to = $this->convertToBitrixFormat($to);
		$from = $this->convertToBitrixFormat($from);
		if($isDebug) {
			echo "<pre>";
			print_r($from) . "\n";
			echo "<pre/><br/>";
			echo "<pre>";
			print_r($to) . "\n";
			echo "<pre/><br/>";
		}




		$overlappingRentFilter = [
			'=IBLOCK_ID'          => $this->rentsIblockId,
			'ACTIVE'              => 'Y',
			'<PROPERTY_RENT_FROM' => $to,
			'>PROPERTY_RENT_TO'   => $from,
		];

		$selectRents = ['ID', 'PROPERTY_CAR', 'PROPERTY_RENT_FROM', 'PROPERTY_RENT_TO'];

		$busyRentsDB = \CIBlockElement::GetList(
			false,
			$overlappingRentFilter,
			false,
			false,
			$selectRents
		);


		$busyCarIds = [];
		while ($rent = $busyRentsDB->fetch()) {
			if($isDebug) {
				echo "<pre>";
				print_r($rent) . "\n";
				echo "<pre/><br/>";
			}


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


		$model = trim($request->get('model'));
		$category = trim($request->get('category'));


		if ($category !== '') {
			$carsFilter['=PROPERTY_CATEGORY_VALUE'] = $category;
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


	protected function getCategoryList(): array
	{
		$el = \CIBlockProperty::GetList(
			false,
			['CODE' => 'CATEGORY', 'IBLOCK_ID' => $this->carsIblockId]
		)->Fetch();
		$categoryPropId = $el['ID'];

		$enumList = PropertyEnumerationTable::getList([
			'filter' => ['=PROPERTY_ID' => $categoryPropId],
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