<?php
if (!empty($arResult['ERROR'])): ?>
    <div class="car-rental-error"><?= htmlspecialchars($arResult['ERROR']) ?></div>
<?endif; ?>

    <form method="get" class="car-rental-filter">
        <label>
            С:
            <input type="datetime-local" name="from"
                   value="<?= htmlspecialchars($arResult['FROM']) ?>" required>
        </label>
        <label>
            По:
            <input type="datetime-local" name="to"
                   value="<?= htmlspecialchars($arResult['TO']) ?>" required>
        </label>
        <label>
            Модель:
            <input type="text" name="model"
                   value="<?= htmlspecialchars($arResult['MODEL']) ?>"
                   placeholder="Название или часть названия">
        </label>
        <label>
            Категория:
            <select name="category">
                <option value="">Любая</option>
				<?php foreach ($arResult['CATEGORIES'] as $xmlId => $name): ?>
                    <option value="<?= htmlspecialchars($xmlId) ?>" <?= $arResult['CATEGORY'] === $xmlId ? 'selected' : '' ?>>
						<?= htmlspecialchars($name) ?>
                    </option>
				<?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Найти свободные машины</button>
    </form>

	<?php if (!empty($arResult['CARS'])): ?>
        <h3>Свободные машины (<?= count($arResult['CARS']) ?>)</h3>
        <ul class="car-rental-list">
			<?php foreach ($arResult['CARS'] as $car): ?>
                <li>
                    <strong><?= htmlspecialchars($car['NAME']) ?></strong>
                    (Категория: <?= htmlspecialchars($car['CATEGORY']) ?>)
                </li>
			<?php endforeach; ?>
        </ul>
	<?php else: ?>
        <p>Нет свободных машин в указанный период.</p>
	<?php endif; ?>

    <style>
        .car-rental-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .car-rental-filter label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
            font-size: 14px;
        }

        .car-rental-filter input,
        .car-rental-filter select,
        .car-rental-filter button {
            margin-top: 4px;
            padding: 6px;
        }

        .car-rental-list {
            list-style: none;
            padding: 0;
        }

        .car-rental-list li {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .car-rental-error {
            color: red;
            padding: 10px;
            background: #ffecec;
            border-radius: 4px;
        }
    </style>

<?php  ?>