<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>

<div class="car-rental">
	<?php if (!empty($arResult['ERROR'])): ?>
        <div class="car-rental__error"><?= htmlspecialchars($arResult['ERROR']) ?></div>
	<?php endif; ?>

    <form method="get" class="car-rental__filter" novalidate>
        <div class="car-rental__field">
            <label class="car-rental__label">С:</label>
            <input
                    type="datetime-local"
                    name="from"
                    class="car-rental__input"
                    value="<?= htmlspecialchars($arResult['FROM']) ?>"
                    required
            >
        </div>

        <div class="car-rental__field">
            <label class="car-rental__label">По:</label>
            <input
                    type="datetime-local"
                    name="to"
                    class="car-rental__input"
                    value="<?= htmlspecialchars($arResult['TO']) ?>"
                    required
            >
        </div>

        <div class="car-rental__field">
            <label class="car-rental__label">Модель:</label>
            <input
                    type="text"
                    name="model"
                    class="car-rental__input"
                    value="<?= htmlspecialchars($arResult['MODEL']) ?>"
                    placeholder="Название или часть названия"
            >
        </div>

        <div class="car-rental__field">
            <label class="car-rental__label">Категория:</label>
            <select name="category" class="car-rental__select">
                <option value="">Любая</option>
				<?php foreach ($arResult['CATEGORIES'] as $xmlId => $name): ?>
                    <option
                            value="<?= htmlspecialchars($xmlId) ?>"
						<?= $arResult['CATEGORY'] === $xmlId ? 'selected' : '' ?>
                    >
						<?= htmlspecialchars($name) ?>
                    </option>
				<?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="car-rental__submit">Найти свободные машины</button>
    </form>

	<?php if (!empty($arResult['CARS'])): ?>
        <h3 class="car-rental__title">Свободные машины (<?= count($arResult['CARS']) ?>)</h3>
        <ul class="car-rental__list">
			<?php foreach ($arResult['CARS'] as $car): ?>
                <li class="car-rental__item">
                    <strong><?= htmlspecialchars($car['NAME']) ?></strong>
                    (Категория: <?= htmlspecialchars
					($car['PROPERTY_CATEGORY_VALUE'] ?: 'любая') ?>)
                </li>
			<?php endforeach; ?>
        </ul>
	<?php else: ?>
        <p class="car-rental__empty">Нет свободных машин в указанный период.</p>
	<?php endif; ?>
</div>