<?php

// название поля загрузки картинки в форме добавления поста
define('UPLOAD_IMG_NAME', 'userpic_file_photo');

// название поля загрузки картинки в форме регистрации нового пользователя
define('NEW_USER_IMG_NAME', 'userpic_file');

// массив с допустимыми для загрузки в форме типами файлов
define('ALLOWED_IMG_TYPES', array(
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
));

define('MAX_FILE_SIZE', 10485760); // максимальный размер файла: 10 * 1024 * 1024 (10 Мб)

define('MAX_FILE_SIZE_USER', 10); // Мб, вывод ограничения на размер файла для пользователя

define('UPLOAD_PATH', 'uploads/'); // папка сохранения файлов, загружаемых из формы

define('SEARCH', 'search_text'); // название поля формы поиска

// часовой пояс по умолчанию
date_default_timezone_set('Europe/Moscow');

/**
 * Обрезает текст до заданного предела
 *
 * @param string $string Исходный текст в виде строки
 * @param string $link Текст ссылки для перехода к полному тексту
 * @param boolean $use_target_blank Должна ли ссылка открываться в новой вкладке
 * @param number $max_post_length Максимальное количество символов
 * @return string Возвращает строку, обрезанную до $max_post_length, либо исходную строку без изменений,
 * если лимит символов не превышен
 */
function slice_string($string, $link = '', $use_target_blank = false, $max_post_length = 300)
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
            ) . '...' . '<a class="post-text__more-link" href="' .
            $link . '"' .
            ($use_target_blank ? ' target="_blank"' : '') . '>Читать далее</a>';
        // trim нужен здесь, потому что пробел в начале параграфа добавляется на этапе цикла и trim в начале (равно как и в конце) не поможет;
        // знаки препинания я всё-таки убираю, потому что в задании как бы требуется, чтобы строка обрезалась именно по слову;
    }

    return str_replace('&amp;#13;&amp;#10;', "<br>", $result_string);
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
    $data = [];
    $result = mysqli_query($src_db, $query);

    if ($result) {
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
        }
    } else {
        echo mysqli_error($src_db);
        exit;
    }

    return $data;
}

/**
 * Заполняет элемент массива ошибок
 *
 * @param array $err Массив для заполнения
 * @param string $field Название поля
 * @param string $type Тип ошибки
 * @param string $heading Заголовок ошибки
 * @param string $text Пояснительный текст ошибки
 */
function fill_errors(&$err, $field, $type, $heading, $text) {
    $err[$field] = array(
        'type' => $type,
        'heading' => $heading,
        'text' => $text,
    );
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
 * Валидирует загруженную картинку
 *
 * @param string $field_name Название поля input, из которого загружается файл
 * @return boolean Пройдена ли проверка
 */
function validate_image_type($field_name)
{
    $validity = false;
    $file_name = $_FILES[$field_name]['tmp_name'];
    $file_type = mime_content_type($file_name);

    if (in_array($file_type, ALLOWED_IMG_TYPES)) {
        $validity = true;
    }
    return $validity;
}

/**
 * Загружает файл по URL и сохраняет его
 *
 * @param string $url Ссылка на файл
 * @param array $err Массив к заполнению ошибками валидации файла
 * @param string $destination Путь для перемещения загруженного файла
 * @return false|string Конечное имя загруженного файла или false, если файл не прошёл одну из проверок
 */
function download_file_from_url(&$err, $url, $destination = UPLOAD_PATH)
{
    $result = false;
    $err_text = '';
    $err_heading = 'Ссылка из интернета';
    $file_extension = pathinfo($url, PATHINFO_EXTENSION) ?? '';

    // проверка на текстовое расширение в ссылке
    if (array_key_exists($file_extension, ALLOWED_IMG_TYPES)) {
        if ($file_extension === 'jpeg') {
            $file_extension = 'jpg';
        }

        if (file_get_contents($url)) {
            $file_content = file_get_contents($url);
            $file_name = uniqid() . ".$file_extension";
            $file_path = $destination . $file_name;

            if (file_put_contents($file_path, $file_content)) {
                $result = validate_file($err, $err_heading, 'photo-url', $file_path) ? $file_name : false;
            } else {
                $err_type = 'Ошибка копирования файла';
                $err_text = 'Не удалось загрузить файл на сервер. Попробуйте снова позднее';
            }
            // удаление невалидного файла
            if (!$result) {
                unlink($file_path);
            }
        } else {
            $err_type = 'Не удалось загрузить файл';
            $err_text = 'Не удалось получить файл, убедитесь в правильности ссылки';
        }
    } else {
        $err_type = 'Неверный тип файла';
        $err_text = 'Файл по ссылке не является изображением в формате jpg, png или gif';
    }

    if ($err_text) {
        fill_errors($err, 'photo-url', $err_type, $err_heading, $err_text);
    }

    return $result;
}

/**
 * Валидирует переданный файл по mime-типу и размеру, заполняя переданный по ссылке массив ошибками
 *
 * @param &$err array Массив для заполнения ошибками валидации
 * @param $err_heading string Заголовок текста ошибки
 * @param $field_name string Название поля формы
 * @param $file_path string Путь до файла
 * @return boolean Значение валидации
 */
function validate_file(&$err, $err_heading, $field_name, $file_path) {
    $valid_file = false;

    // проверка настоящего типа файла
    $file_type = mime_content_type($file_path);
    if (in_array($file_type, ALLOWED_IMG_TYPES)) {
        // проверка размера файла
        if (filesize($file_path) < MAX_FILE_SIZE) {
            clearstatcache();
            $valid_file = true;
        } else {
            $err_type = 'Размер файла';
            $err_text = 'Размер файла не должен превышать ' . MAX_FILE_SIZE_USER . ' Мб';
        }
    } else {
        $err_type = 'Неверный тип файла';
        $err_text = 'Файл по ссылке не является изображением в формате jpg, png или gif';
    }

    // заполнение массива ошибками
    if (!$valid_file) {
        fill_errors($err, $field_name, $err_type, $err_heading, $err_text);
    }

    return $valid_file;
}

/**
 * Проверяет валидность ссылки с обязательной частью component в теле ссылки
 * и заполняет массив с ошибками
 *
 * @param string $url Текст ссылки
 * @param array $err Массив к заполнению ошибками валидации ссылки
 * @param string $field_name Название валидируемого поля
 * @param int $component Код компонента (для parse_url)
 * @return boolean Валидность ссылки
 */
function validate_url(&$err, $url, $field_name, $component = PHP_URL_HOST)
{
    $url_validity = false;
    if (filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, $component)) {
        if ($headers = get_headers($url)) {
            foreach ($headers as $header) {
                if (strpos($header, '200 OK') !== 0) {
                    $url_validity = true;
                    break;
                }
            }
        }
    }

    if (!$url_validity) {
        switch ($field_name) {
            case 'video-url':
                $args = array(
                    'Недействительная ссылка',
                    'Ссылка YouTube',
                    'Введите корректную ссылку на видео с YouTube',
                );
                break;
            case 'photo-url':
                $args = array(
                    'Файл отсутствует',
                    'Ссылка из интернета',
                    'Не удалось получить файл, убедитесь в правильности ссылки',
                );
                break;
            case 'post-link':
                $args = array(
                    'Некорректная ссылка',
                    'Ссылка',
                    'Введите корректный URL',
                );
                break;
            default:
                $args = array(
                    'Некорректная ссылка',
                    'Ссылка',
                    'Введите корректный URL',
                );
        }
        array_unshift($args, $field_name);
        fill_errors($err, ...$args);
    }

    return $url_validity;
}

/**
 * Формирует и выводит шаблон блока с текстом ошибки заполнения поля на основе массива ошибок
 *
 * @param array $err Массив с ошибками
 * @param string $field_name Название поля
 * @return string Итоговый HTML-шаблон
 */
function show_error_msg($err, $field_name) {
    $params = [];

    if (array_key_exists($field_name, $err)) {
        if (isset($err[$field_name])) {
            $err_msg = '<b>' . $err[$field_name]['heading'] . '</b>. ' . $err[$field_name]['text'];
        }

        $params = array(
            'err_msg' => $err_msg ?? '',
            'error_type' => $err[$field_name]['type'],
        );
    }

    return include_template('form_error_template.php', $params);
}

/**
 * Удаляет повторяющиеся и концевые пробелы в строке
 *
 * @param string $str Строка
 */
function trim_extra_spaces($str) {
    return trim(preg_replace('/\s{2,}/',' ', $str));
}

/**
 * Выполняет процедуру валидации и загрузки изображения из формы
 *
 * @param array $err Массив для заполнения ошибками
 * @param string $files_name Название поля для загрузки файла
 * @return mixed Имя загруженного файла, либо false, если файл не прошёл валидацию
 */
function upload_image(&$err, $files_name) {
    $file_src = $_FILES[$files_name];
    $file = false;
    $file_error = $file_src['error'];
    $err_heading = 'Изображение';
    switch ($file_error) {
        case UPLOAD_ERR_OK:
            if (validate_image_type($files_name)) {
                // проверка размера файла
                if ($file_src['size'] > MAX_FILE_SIZE) {
                    $err_type = 'Размер файла';
                    $err_text = 'Размер файла не должен превышать ' . MAX_FILE_SIZE_USER . ' Мб';
                } else {
                    // происходит загрузка файла
                    $file_name = $file_src['name'];
                    $file_path = UPLOAD_PATH . $file_name;
                    // перемещение файла в папку uploads и обработка ошибки перемещения
                    if (move_uploaded_file($file_src['tmp_name'], $file_path)) {
                        $file = $file_name;
                    } else {
                        $err_type = 'Ошибка при копировании файла';
                        $err_text = 'Не удалось загрузить файл, попробуйте снова позднее';
                    }
                }
            } else {
                $err_type = 'Неверный тип файла';
                $err_text = 'Неверный тип файла. Загрузите изображение в формате jpg, png или gif';
            }
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $err_type = 'Размер файла';
            $err_text = 'Размер файла не должен превышать ' . MAX_FILE_SIZE_USER . ' Мб';
            break;
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
            $err_type = 'Файл отсутствует';
            $err_text = 'Файл не был загружен или загрузился с ошибками. Попробуйте ещё раз';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            $err_type = 'Не удалось записать файл';
            $err_text = 'Ошибка сервера или PHP-модуля. Пожалуйста, попробуйте ещё раз позднее';
            break;
    }
    if (isset($err_text)) {
        fill_errors($err, $files_name, $err_type, $err_heading, $err_text);
    }
    return $file;
}

/**
 * Валидирует e-mail адрес и заполняет массив с ошибками
 *
 * @param array $err Массив для заполнения ошибками
 * @param string $email Адрес эл. почты
 * @return false|string Исходный e-mail, либо false, если адрес не прошёл проверку
 */
function validate_email(&$err, $email) {
    $err_heading = 'Электронная почта';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    } else {
        $err_type = 'Некорректный email-адрес';
        $err_text = 'Введите корректный адрес электронной почты';
        $email = false;
        fill_errors($err, 'email', $err_type, $err_heading, $err_text);
    }

    return $email;
}

/**
 * Выполняет проверку e-mail адреса на существование в БД
 *
 * @param mysqli $db Подключение к базе данных
 * @param array $err Массив для заполнения ошибками
 * @param string $email Адрес эл. почты
 * @param boolean $new Проверяется ли e-mail для регистрации нового пользователя
 * @return boolean Значение проверки
 */
function check_email($db, &$err, $email, $new = false) {
    $email_check = false;
    $query = "SELECT id FROM user WHERE user_email = '$email'";
    $result = get_data_from_db($db, $query, 'one');
    $err_heading = 'Электронная почта';

    if ($new) {
        if ($result > 0) {
            $err_type = 'Пользователь уже существует';
            $err_text = 'Пользователь с такой электронной почтой уже существует';
        } else {
            $email_check = true;
        }
    } else {
        if (!$result) {
            $err_type = 'Пользователя не существует';
            $err_text = 'Пользователь с таким E-mail не найден';
        } else {
            $email_check = true;
        }
    }

    // заполнить массив с ошибками, если таковые возникли
    if (isset($err_text)) {
        fill_errors($err, 'email', $err_type, $err_heading, $err_text);
    }

    return $email_check;
}

/**
 * Получает хэштеги публикации
 *
 * @param mysqli $db Подключение к БД
 * @param int $post_id Идентификатор поста
 * @param string $mode Режим извлечения данных (см. get_data_from_db)
 * @return array|mixed Список хэштегов в виде массива
 */
function get_hashtags($db, $post_id, $mode = 'col') {
    $query = "SELECT hashtag_name,
                     ht.id
              FROM post AS p
                  JOIN post_hashtag_link AS phl
                      ON phl.post_id = p.id
                  JOIN hashtag AS ht
                      ON ht.id = phl.hashtag_id
              WHERE phl.post_id = '$post_id'";
    return get_data_from_db($db, $query, $mode);
}

/**
 * Проверяет поля на заполненность
 *
 * @param array $err Массив для заполнения ошибками
 * @param array $req Массив со списком обязательных полей формы
 * @param array $post_data Массив с данными из формы
 */
function check_if_empty(&$err, $req, $post_data) {
    foreach ($req as $key => $value) {
        if (empty($post_data[$key])) {
            fill_errors($err, $key, 'Пустое поле', $value, 'Это поле должно быть заполнено');
        }
    }
}

/**
 * Проверяет существование публикации в базе данных
 *
 * @param $db mysqli Подключение к базе данных
 * @param $post_id int ID поста
 * @return bool Значение проверки
 */
function check_post($db, $post_id) {
    $query = "SELECT id FROM post WHERE id = $post_id";
    return boolval(get_data_from_db($db, $query, 'one'));
}

/**
 * Получает все комментарии к публикации
 *
 * @param $db mysqli Подключение к базе данных
 * @param $post_id int ID публикации
 * @param $limit int Ограничение количества получаемых комментариев
 * @return array Все данные комментариев
 */
function get_comments($db, $post_id, $limit = 0) {
    // запрос на получение комментариев к публикации
    $query = 'SELECT c.*,
                     u.user_avatar,
                     u.user_name
              FROM comment AS c
                  INNER JOIN user AS u
                      ON c.user_id = u.id
              WHERE post_id = ' . $post_id . '
              ORDER BY c.comment_create_dt DESC';

    if ($limit) {
        $query .= " LIMIT $limit";
    }

    return get_data_from_db($db, $query);
}

/**
 * Осуществляет добавление нового комментария к публикации
 *
 * @param $db mysqli Подключение к базе данных
 * @param $err array Массив для заполнения ошибками
 * @param $user_id int ID пользователя - автора публикации
 * @param $post_id int ID публикации
 */
function add_comment($db, &$err, $user_id, $post_id) {
    $comment_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
    $comment_text = str_replace('&#13;&#10;', ' ', $comment_data['comment-text']);
    $comment_text = trim($comment_text); // обрезка лишних пробелов

    // проверка на пустой комментарий
    if (empty($comment_text)) {
        $err_type = 'Поле не заполнено';
        $err_heading = 'Пустой комментарий';
        $err_text = 'Напишите комментарий';
    } elseif (mb_strlen($comment_text) < 4) { // проверка на количество символов
        $err_type = 'Слишком короткий комментарий';
        $err_heading = 'Длина меньше 4 символов';
        $err_text = 'Добавьте ещё пару слов';
    } else {
        // проверка существования публикации
        $post_exists = check_post($db, $post_id);

        if ($post_exists && !$err) {
            // подготовка выражения
            $query = "INSERT INTO comment (
                                comment_content,
                                user_id,
                                post_id
                             )
                             VALUES (?, ?, ?)"; // 3 поля
            $stmt = mysqli_prepare($db, $query);

            // данные для подстановки
            $query_vars = array(
                $comment_text,
                $_SESSION['user']['id'],
                $post_id,
            );

            // выполнение подготовленного выражения
            mysqli_stmt_bind_param($stmt, 'sii', ...$query_vars);
            mysqli_stmt_execute($stmt);
            header('Location: profile.php' .
                '?user_id=' . $user_id .
                '&show_comments=' . $post_id .
                '#post_id=' . $post_id);
            exit;
        }
    }
    if (isset($err_text)) { // заполнить массив с ошибками, если таковые возникли
        $field_name = 'comment-text';
        fill_errors($err, $field_name, $err_type, $err_heading, $err_text);
    }
}

/**
 * Проверяет существование пользователя в таблице базы данных
 * @param $db mysqli Подключение к базе данных
 * @param $user_id int ID пользователя
 * @return bool Значение проверки
 */
function check_user($db, $user_id) {
    $query = "SELECT id FROM user WHERE id = $user_id";
    return (bool)get_data_from_db($db, $query, 'one');
}

/**
 * Записывает в сессию количество непрочитанных сообщений аутент. польз-ля
 *
 * @param $db mysqli Подключение к БД
 * @return int Количество непрочитанных сообщений пользователя
 */
function get_unread_msg_count($db) {
    $query = 'SELECT COUNT(id) FROM message
              WHERE message_receiver_id = '
                  . $_SESSION['user']['id'] . '
                  AND is_read = 0';
    return get_data_from_db($db, $query, 'one');
}

/**
 * Проверяет наличие данных о подписке аутент. польз-ля на профиль user_id
 * @param $db mysqli Подключение к БД
 * @param $user_id int Идентификатор пользователя
 * @return bool Значение проверки
 */
function check_subscription($db, $user_id) {
    $query = 'SELECT id
              FROM follower_list
              WHERE following_user_id = ' . $_SESSION['user']['id'] . '
                  AND followed_user_id = ' . $user_id;
    return (bool)get_data_from_db($db, $query, 'one');
}
