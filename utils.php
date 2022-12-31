<?php

date_default_timezone_set('Europe/Moscow');

/**
 * Обрезает текст до заданного предела
 *
 * @param string $string Исходный текст в виде строки
 * @param string $link Текст ссылки для перехода к полному тексту
 * @param number $max_post_length Максимальное количество символов
 * @return string Возвращает строку, обрезанную до $max_post_length, либо исходную строку без изменений,
 * если лимит символов не превышен
 */

function slice_string($string, $link = '', $max_post_length = 300)
{
    $result_string = trim($string);

    if (mb_strlen($result_string) > $max_post_length) {
        $words = explode(' ', $result_string);
        $i = 0;
        $result_string = '';

        while (mb_strlen($result_string . ' ' . $words[$i]) < $max_post_length) {
            $result_string .= ' ' . $words[$i];
            $i++;
        }

        $result_string = trim(
                $result_string,
                '/ :–-,;'
            ) . '...' . '<a class="post-text__more-link" href="' . $link . '">Читать далее</a>';
        // trim нужен здесь, потому что пробел в начале параграфа добавляется на этапе цикла и trim в начале (равно как и в конце) не поможет;
        // знаки препинания я всё-таки убираю, потому что в задании как бы требуется, чтобы строка обрезалась именно по слову;
    }

    return $result_string;
}

//  Второй вариант функции

function slice_string_2($string, $max_post_length = 300)
{
    $result_string = trim($string);

    if (mb_strlen($result_string) > $max_post_length) {
        $temp_string = mb_substr($string, 0, $max_post_length);
        $result_string = '<p>' . mb_substr(
                $temp_string,
                0,
                mb_strripos($temp_string, ' ')
            ) . '...</p><a class="post-text__more-link" href="#">Читать далее</a>';
    }

    return $result_string;
}

/**
 * Заменяет потенциально опасные символы в являющемся строкой элементе на HTML-мнемоники, делая текст безопасным для вывода на страницу
 * @param mixed $value Входящий элемент любого типа
 */

function secure(&$value)
{
    if (is_string($value)) {
        $value = htmlspecialchars($value);
    }
}

/**
 * Преобразует дату в формат «дд.мм.гггг чч:мм», необходимый для атрибута title
 *
 * @param string $date Дата в виде строки
 * @return string Строка с датой в формате «дд.мм.гггг чч:мм»
 */

function get_title_date($date)
{
    $date = strtotime($date);
    return date('d.m.Y H:i', $date);
}

/**
 * Получает интервал времени с прошедшего до текущего момента времени в формате "n минут/часов/etc. назад"
 *
 * - если до текущего времени прошло меньше 60 минут, то формат будет вида «% минут назад»;
 * - если до текущего времени прошло больше 60 минут, но меньше 24 часов, то формат будет вида «% часов назад»;
 * - если до текущего времени прошло больше 24 часов, но меньше 7 дней, то формат будет вида «% дней назад»;
 * - если до текущего времени прошло больше 7 дней, но меньше 5 недель, то формат будет вида «% недель назад»;
 * - если до текущего времени прошло больше 5 недель, то формат будет вида «% месяцев назад».
 * - если до текущего времени прошло больше 1 года, то формат будет вида «% лет назад».
 *
 * @param string $date Дата, с которой начинается отсчёт
 * @return string Строка, отражающая количество времени, прошедшего с $date
 */

function format_date($date)
{
    $post_date = date_create($date);
    $current_date = date_create('now');
    $diff = date_diff($current_date, $post_date); // Разница между current_date и post_date в виде объекта

    $result = '';

    if ($diff->invert === 0) {
        $result = 'Дата ещё не наступила!';
    } else {
        $minutes_in_hour = 60; // Кол-во минут в 1 часе
        $hours_in_day = 24; // Кол-во часов в 1 сутках;

        $minutes = $diff->i;
        $hours = $diff->h;
        $days = $diff->days;

        if ($days === 0) {
            if ($minutes >= $minutes_in_hour / 2) { // часы округляются вверх
                $hours++;
            }

            $result = $hours ?
                $hours . ' час' . get_noun_plural_form($hours, '', 'а', 'ов')
                :
                $minutes . ' минут' . get_noun_plural_form($minutes, 'у', 'ы', '');
        } else {
            $days_in_week = 7; // Кол-во дней в 1 неделе;
            $days_in_month = 30; // Кол-во дней в 1 месяце;
            $days_in_year = 365; // Кол-во дней в 1 году;
            $five_weeks = 35; // 5 недель;

            $years = $diff->y;

            if ($hours >= $hours_in_day / 2) { // дни округляются вверх
                $days++;
            }

            if ($days < $days_in_week) {
                $result = $days . ' ' . get_noun_plural_form($days, 'день', 'дня', 'дней');
            } elseif ($days < $five_weeks) {
                $weeks = round($days / $days_in_week);
                $result = $weeks . ' недел' . get_noun_plural_form($weeks, 'ю', 'и', 'ь');
            } elseif ($days < $days_in_year) {
                $months = round($days / $days_in_month);
                $result = $months . ' месяц' . get_noun_plural_form($months, '', 'а', 'ев');
            } elseif ($years) {
                $years = ($days >= $days_in_year / 2) ? $years++ : $years;
                $result = $years . ' ' . get_noun_plural_form($years, 'год', 'года', 'лет');
            }
        }
    }
    return $result;
}

/**
 * Выполняет запрос в базу данных
 *
 * @param mysqli $src_db Подключение к БД
 * @param string $query Текст запроса
 * @param string $mode Режим выполнения функции:
 *        'all' - для вывода всех данных в виде двумерного массива,
 *        'row' - для вывода одной строки данных в виде одномерного ассоц. массива,
 *        'col' - для вывода всех значений искомого поля в виде нумерованного массива,
 *        'one' - для вывода одного значения поля в виде строки
 * @return mixed Полученные данные в виде, заданном режимом $mode
 */
function get_data_from_db(mysqli $src_db, string $query, string $mode = 'all')
{
    $result = mysqli_query($src_db, $query);

    if (!$result) {
        echo mysqli_error($src_db);
        exit();
    }

    switch ($mode) {
        case 'all':
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            break;
        case 'row':
            $data = mysqli_fetch_assoc($result);
            break;
        case 'col':
            $data = mysqli_fetch_all($result);
            $data = array_column($data, 0);
            break;
        case 'one':
            $data = mysqli_fetch_row($result)[0];
            break;
        default:
            $data = [];
    }

    return $data;
}

/**
 * Приводит ссылки к единому виду, обрезая протокол
 *
 * @param string $link_text Текст ссылки
 */
function trim_link(string $link_text): string
{
    $scheme = parse_url($link_text, PHP_URL_SCHEME);

    if ($scheme) {
        $link_text = 'https' . str_replace($scheme, '', $link_text);
    } else {
        $link_text = 'https://' . $link_text;
    }

    return $link_text;
}

/**
 * Подгатавливает шаблон страницы
 *
 * @param string $page Название файла шаблона
 * @param array $params Массив с данными для передачи из сценария в шаблон
 * @param string $main_content Основное содержимое страницы, передаваемое в шаблон
 */
function build_page($page, $params, $main_content): string
{
    return include_template($page, $params + ['main_content' => $main_content]);
}

/**
 * Возвращает значение поля input из данных формы
 *
 * @param string $name Атрибут name поля input
 */
function getPostVal($name)
{
    return $_POST[$name] ?? '';
}

/**
 * Валидирует загруженный файл
 *
 * @param string $page Название поля input, из которого загружается файл
 * @param array $allowed_types Массив с разрешёнными MIME-типами файлов
 * @return boolean Булево значение проверки
 */
function validateFile($resource, $allowed_types)
{
    $validity = false;
    if (isset($_FILES[$resource]) && !empty($_FILES[$resource]['name'])) {
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_name = $_FILES[$resource]['tmp_name'];
        $file_size = $_FILES[$resource]['size'];
        $file_type = finfo_file($file_info, $file_name);

        if (in_array($file_type, $allowed_types) && $file_size < 200000) {
            $validity = true;
        }
    }
    return $validity;
}

/**
 * Производит валидацию полей формы
 *
 * @param string $type Модификатор запроса типа
 * @return array Массив с ошибками валидации
 */
function validateField($type)
{
    $errors = [];
    $required_fields = [];

    // типы файлов, допустимых для загрузки в форме
    $allowed_file_types = array(
        'image/jpeg',
        'image/png',
        'image/gif',
    );

    // список обязательных полей варьируется в зависимости от типа поста
    switch ($type) {
        case 'photo':
            $required_fields = [
                'photo-heading' => 'Заголовок',
                'userpic_file_photo' => 'Выбрать фото',
            ];
            break;
        case 'text':
            $required_fields = [
                'text-heading' => 'Заголовок',
                'post-text' => 'Текст поста',
            ];
            break;
        case 'quote':
            $required_fields = [
                'quote-heading' => 'Заголовок',
                'cite-text' => 'Текст цитаты',
                'quote-author' => 'Автор',
            ];
            break;
        case 'video':
            $required_fields = [
                'video-heading' => 'Заголовок',
                'video-url' => 'Ссылка YouTube',
            ];
            break;
        case 'link':
            $required_fields = [
                'link-heading' => 'Заголовок',
                'post-link' => 'Ссылка',
            ];
    }

    // проверка обязательных к заполнению полей
    foreach ($required_fields as $key => $value) {
        if (empty($_POST[$key])) {

            // Обработка обязательного поля загрузки изображения
            if ($key === 'userpic_file_photo') {

                // валидация загруженного файла
                if (!validateFile($key, $allowed_file_types)) {

                    // поле со ссылкой обрабатывается, если файл не был приложен или не прошёл валидацию
                    if ($_POST['photo-url']) {
                        $photo_url = $_POST['photo-url'];
                        $err_msg = 'Ссылка из интернета. Введите действующую ссылку на изображение в формате jpg, png или gif.';

                        // проверка валидности ссылки
                        if (parse_url($photo_url, PHP_URL_PATH) && filter_var($photo_url, FILTER_VALIDATE_URL)) {

                            // проверка доступности и работоспособности ссылки
                            $response = get_headers($photo_url);
                            if (stripos($response[0], "200 OK")) {
                                $file = uploadFileFromURL('photo-url');
                                $file_type = finfo_open(FILEINFO_MIME_TYPE);
                                $file_type = finfo_file($file_type, $file);

                                // проверка типа загружаемого файла
                                if (!in_array($file_type, $allowed_file_types)) {
                                    $errors['photo-url'] = $err_msg;
                                }
                            } else {
                                $errors['photo-url'] = $err_msg;
                            }
                        } else {
                            $errors['photo-url'] = $err_msg;
                        }
                    } else {
                        $errors[$key] = $value . '. Загрузите картинку в формате jpg, png или gif. Максимальный размер файла: 200Кб.';
                    }
                }
            } else {
                $errors[$key] = $value . '. Это поле должно быть заполнено.';
            }
        } elseif ($key === 'video-url') { // проверка валидности ссылки на youtube
            if (filter_var($_POST[$key], FILTER_VALIDATE_URL)) {
                if (!check_youtube_url($_POST[$key])) {
                    $errors[$key] = $value . '. ' . check_youtube_url($_POST[$key]);
                }
            } else {
                $errors[$key] = $value . '. URL должен быть корректным';
            }
        } elseif ($key === 'post-link') { // проверка ссылки на валидность
            if (!filter_var($_POST[$key], FILTER_VALIDATE_URL)) {
                $errors[$key] = $value . '. URL должен быть корректным';
            }
        }
    }
    return $errors;
}

/**
 * Загружает файл из input с типом file и перемещает полученный файл в папку uploads в корне проекта
 *
 * @param string $resource Ключ массива $_FILES
 * @return string Путь к загруженному и перемещённому файлу
 */
function uploadFile($resource)
{
    $file_name = $_FILES[$resource]['name'];
    $file_path = 'uploads/' . $file_name;
    move_uploaded_file($_FILES[$resource]['tmp_name'], $file_path);
    copy($file_path, '../img/');

    return $file_path;
}

/**
 * Загружает файл по ссылке из текстового поля формы и перемещает полученный файл в папку uploads в корне проекта
 *
 * @param string $field Название (name) поля формы
 * @return string Путь к загруженному файлу
 */
function uploadFileFromURL($field)
{
    $file = file_get_contents($_POST[$field]);
    $file_name = $field;
    $file_path = 'uploads/' . $file_name;

    // добавление расширения файла
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_info = finfo_file($file_info, $file_path);
    $ext = substr($file_info, '6');
    $file_url = "uploads/$file_name.$ext";

    file_put_contents($file_url, $file);

    return $file_url;
}
