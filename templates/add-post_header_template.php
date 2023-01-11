<div class="adding-post__input-wrapper form__input-wrapper">
    <label class="adding-post__label form__label" for="<?= $type; ?>-heading">Заголовок <span class="form__input-required">*</span></label>
    <div class="form__input-section <?= $show_error_class; ?>">
        <input class="adding-post__input form__input" id="<?= $type; ?>-heading" type="text" name="<?= $type; ?>-heading" value="<?= $values[$type .'-heading'] ?? ''; ?>" placeholder="Введите заголовок">
        <?= show_error_msg($errors, $type . '-heading'); ?>
    </div>
</div>
