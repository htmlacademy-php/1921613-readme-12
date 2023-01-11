<div class="adding-post__input-wrapper form__input-wrapper">
    <label class="adding-post__label form__label" for="<?= $type; ?>-tags">Теги</label>
    <div class="form__input-section">
        <input class="adding-post__input form__input" id="<?= $type; ?>-tags" type="text" name="<?= $type; ?>-tags" value="<?= $values[$type] ?? ''; ?>" placeholder="Введите теги">
    </div>
</div>
