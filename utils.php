<?php
/**
 * Обрезает текст до заданного предела
 *
 * @param string $string Исходный текст в виде строки
 * @param number $max_post_length Максимальное количество символов
 *
 * @return string Возвращает строку, обрезанную до $max_post_length, либо исходную строку без изменений,
 * если лимит символов не превышен
 */

function slice_string($string, $max_post_length = 300)
{
    $result_string = $string;

    if (mb_strlen($result_string) > $max_post_length) {
        $words = explode(' ', $result_string);
        $i = 0;
        $result_string = '';
        $addition = '...' . '/n' . 'Читать далее'; // для полного соответствия заданию, где показано, что текст должен обрезаться С УЧЁТОМ добавляемых нами ссылки и многоточий (я тестировал на тексте про озеро);

        while (mb_strlen($result_string . ' ' . $words[$i] . $addition) < $max_post_length) {
            $result_string .= ' ' . $words[$i];
            $i++;
        }

        $result_string = '<p>' . trim($result_string, '/ :–-,;') . '...</p><a class="post-text__more-link" href="#">Читать далее</a>';
        // trim нужен здесь, потому что пробел в начале параграфа добавляется на этапе цикла и trim в начале (равно как и в конце) не поможет;
        // знаки препинания я всё-таки убираю, потому что в задании как бы требуется, чтобы строка обрезалась именно по слову;
    }

    return $result_string;
}

//  Второй вариант функции

function slice_string_2($string, $max_post_length = 300)
{
    $result_string = trim($string);
    $addition = '...' . '/n' . 'Читать далее'; // для полного соответствия заданию, текст вместе с $addition не превышает указанный лимит

    if (mb_strlen($result_string) > $max_post_length) {
        $temp_string = mb_substr($string, 0, $max_post_length - mb_strlen($addition));
        $result_string = '<p>' . mb_substr($temp_string, 0, mb_strripos($temp_string, ' ')) .
            '...</p><a class="post-text__more-link" href="#">Читать далее</a>';
    }

    return $result_string;
}
