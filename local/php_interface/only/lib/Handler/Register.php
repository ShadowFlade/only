<?php

namespace Only\Handler;

class Register
{
	private static $eventManager;

	public static function initHandlers()
	{
		self::$eventManager = \Bitrix\Main\EventManager::getInstance();
		self::initIblockHandlers();
	}

	private static function initIblockHandlers()
	{
		self::$eventManager->addEventHandler(
			'iblock',
			'OnBeforeIBlockElementUpdate',
			["\Only\Handler\Product", "checkDontAllowDelete"]);
	}

}

