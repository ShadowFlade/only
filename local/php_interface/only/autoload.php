<?php
//автозагрузка классов
spl_autoload_register(function($className)
{
	static $namespace = 'Only\\';
	static $namespaceLength = null;

	if (!isset($namespaceLength))
	{
		$namespaceLength = strlen($namespace);
	}

	if (substr($className, 0, $namespaceLength) === $namespace)
	{
		$classNameRelative = substr($className, $namespaceLength);
		$classRelativePath =  str_replace('\\', '/', $classNameRelative) . '.php';
		$classFullPath = __DIR__ . '/lib/' . $classRelativePath;

		if (file_exists($classFullPath))
		{
			require_once $classFullPath;
		}
	}
});

//инициализация всех обработчиков
\Only\Handler\Register::initHandlers();

//подключаем константы
require $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/only/config.php';