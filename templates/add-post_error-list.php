<div class="form__invalid-block <?= !$errors ? 'visually-hidden' : ''; ?>">
    <b class="form__invalid-slogan">Пожалуйста, исправьте следующие ошибки:</b>
    <ul class="form__invalid-list">
        <?php foreach ($errors as $key => $value): ?>
            <li class="form__invalid-item"><?= $value; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
