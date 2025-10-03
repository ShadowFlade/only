<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
}

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\ElementTable;

if (!Loader::includeModule('iblock')) {
	die('Модуль iblock не установлен');
}

$iblocksDB = IblockTable::getList([
		'filter' => ['CODE'=>['cars','rents']],
		'select' => ['ID', 'NAME','CODE']]
);
$iblocks = [];

while ($iblock = $iblocksDB->fetch()) {
	$iblocks[$iblock['CODE']] = $iblock;
}

if (!isset($iblocks['cars']) || !isset($iblocks['rents'])) {
	die('не удалось обнаружить инфоблок машины или аренды машин');
}

echo "🧹 Начинаем очистку...\n";

$carsIblockId = $iblocks['cars']['ID'];
$rentsIblockId = $iblocks['rents']['ID'];

if ($rentsIblockId) {
	$rentElements = ElementTable::getList([
		'filter' => ['IBLOCK_ID' => $rentsIblockId],
		'select' => ['ID']
	])->fetchAll();

	foreach ($rentElements as $elem) {
		\CIBlockElement::Delete($elem['ID']);
	}
	echo "🗑️ Удалено " . count($rentElements) . " элементов из 'rents'\n";
}

if ($carsIblockId) {
	// Удаляем все элементы из cars
	$carElements = ElementTable::getList([
		'filter' => ['IBLOCK_ID' => $carsIblockId],
		'select' => ['ID']
	])->fetchAll();

	foreach ($carElements as $elem) {
		\CIBlockElement::Delete($elem['ID']);
	}
	echo "🗑️ Удалено " . count($carElements) . " элементов из 'cars'\n";
}



//Создание 5 машин

$cars = [];
$categories = ['1', '2', '3', '1', '2'];


for ($i = 1; $i <= 5; $i++) {
	$el = new \CIBlockElement();
	$arr = [
		'IBLOCK_ID' => $carsIblockId,
		'NAME' => "Машина #$i",
		'ACTIVE' => 'Y',
		'PROPERTY_VALUES' => [
			"CATEGORY" => $categories[$i - 1]
		],
		"CODE" => \CUtil::translit("Машина #$i", "ru", ["replace_space" => "-", "replace_other" => "-"])
	];
	\Bitrix\Main\Diag\Debug::writeToFile(
		$arr,
		date("d.m.Y H:i:s"),
		"local/cars.log"
	);

	$carId = $el->Add($arr);

	if (!$carId) {
		echo "Ошибка создания машины #$i: " . $el->LAST_ERROR . "\n";
	} else {
		echo "Машина #$i создана (ID: $carId, категория: {$categories[$i - 1]})\n";
		$cars[] = $carId;
	}
}

//Создание пользователей и аренд

$users = [];
$userEmails = ['spiderman@example.com', 'wolverine@example.com', 'daredevil@example.com', 'venom@example.com', 'WEAREVENOM@example.com'];

foreach ($userEmails as $email) {
	$user = \Bitrix\Main\UserTable::getList(['filter' => ['=EMAIL' => $email], 'select' =>
		['ID']])->fetch();
	if ($user) {
		$users[] = $user['ID'];
	} else {
		$user = new \CUser();
		$userId = $user->Add([
			'EMAIL' => $email,
			'LOGIN' => $email,
			'NAME' => 'Имя',
			'LAST_NAME' => substr($email, 0, strpos($email, '@')),
			'PASSWORD' => '123456',
			'CONFIRM_PASSWORD' => '123456',
			'ACTIVE' => 'Y'
		]);
		if (!$userId) {
			echo "Ошибка создания пользователя $email: " . $user->LAST_ERROR . "\n";
			// Используем ID админа как fallback
			$users[] = 1;
		} else {
			$users[] = $userId;
			echo "Пользователь $email создан (ID: $userId)\n";
		}
	}
}

// Создаём аренды
$now = new DateTime();
for ($i = 0; $i < 5; $i++) {
	$rentFrom = clone $now;
	$rentFrom->modify("+{$i} days");
	$rentTo = clone $rentFrom;
	$rentTo->modify("+2 days");

	$el = new \CIBlockElement();
	$rentArr = [
		'IBLOCK_ID' => $rentsIblockId,
		'NAME' => "Аренда машины #{$cars[$i]}",
		'ACTIVE' => 'Y',
		'PROPERTY_VALUES' => [
			"CAR" => $cars[$i],
			"CLIENT" => $users[$i],
			"RENT_FROM" => $rentFrom->format('d.m.Y H:i:s'),
			"RENT_TO" => $rentTo->format('d.m.Y H:i:s')
		],
		'CODE' => "Аренда машины #{$cars[$i]} " . rand(0,99999)
	];
	\Bitrix\Main\Diag\Debug::writeToFile(
		$rentArr,
		date("d.m.Y H:i:s"),
		"local/rent.log"
	);


	$rentId = $el->Add($rentArr);

	if (!$rentId) {
		echo "Ошибка создания аренды для машины {$cars[$i]}: " . $el->LAST_ERROR . "\n";
	} else {
		echo "Аренда #$rentId создана: машина {$cars[$i]}, клиент {$users[$i]}, с " . $rentFrom->format('Y-m-d H:i') . " по " . $rentTo->format('Y-m-d H:i') . "\n";
	}
}

echo "\n✅ Готово! Создано 5 машин и 5 аренд.Наверно\n";