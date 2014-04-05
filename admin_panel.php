<?php

session_set_cookie_params(10800, '', '', '', TRUE);
session_start();
include './includes/functions.php';

#echo '<pre>' . print_r(session_status(), TRUE) . '</pre>';
#echo '<pre>' . print_r(session_get_cookie_params(), TRUE) . '</pre>';
#echo '<pre>' . print_r($_COOKIE, TRUE) . '</pre>';

if (isset($_POST['login']) && $_POST['login'] === 'Вход') {
    if (!isset($_POST['token'])) {
        $_POST['token'] = NULL;
    }
    if (!isset($_SESSION['token'])) {
        $_SESSION['token'] = NULL;
    }
    if (isset($_POST['token']) && $_SESSION['token'] != NULL && $_POST['token'] != NULL && $_POST['token'] == $_SESSION['token']) {
        //echo '<pre>' . print_r('READY', TRUE) . '</pre>';
        if (( isset($_POST['name'], $_POST['pass']) ) && $_POST ['name'] != '' && $_POST['pass'] != '') {


            $db = conectTo();
            check_conection($db);
            $name = mysqli_real_escape_string($db, trim($_POST['name']));
            $pass = mysqli_real_escape_string($db, trim($_POST['pass']));
            unset($_POST);

            if (validate($name, $pass, 4, 50, 50)) {
                $select = mysqli_query($db, 'SELECT `user_name`, `user_pass`, `user_admin`, `user_date` FROM `users` 
            WHERE `user_name`="' . $name . '" AND `user_pass`="' . $pass . '"');
                if ($select !== FALSE) {
                    $result = mysqli_fetch_assoc($select);
                } else {
                    header('Location: index.php');
                    exit();
                }
                mysqli_close($db);
                if ($result ['user_name'] == $name && $result ['user_pass'] == $pass) {
                    $_SESSION['isLogged'] = TRUE;
                    if ($result ['user_admin'] == 1) {
                        $_SESSION['admin'] = TRUE;
                        //echo '<pre>' . print_r($result, TRUE) . '</pre>';
                    } else {
                        header('Location: index.php');
                        unset($_POST);
                        unset($_SESSION);
                        exit();
                    }
                } else {
                    $data['input_error'] = 'Грешно Име/Парола';
                }
            } else {
                $data['input_error'] = 'Грешка!Дължина на Име/Парола 5-50 символа';
            }
        } else {
            $data['input_error'] = 'Въведи Име/Парола';
        }
    }
} else {
    $data['input_error'] = NULL;
}

$token = (int) (time() / rand());
$_SESSION['token'] = $token;
$data['token'] = $token;



#----------------------------------------------------------
# След Вход


if (isset($_SESSION['isLogged']) && isset($_SESSION['admin']) &&
        ($_SESSION['isLogged'] === TRUE) && ($_SESSION['admin'] === TRUE)) {

    #пълни опциите на селекта
    $db = conectTo();
    $sql_cat = mysqli_query($db, 'SELECT  `id`,`opis` FROM  `content` ORDER BY  `content`.`id` ASC');
    if ($sql_cat !== FALSE) {

        while ($res_cat = mysqli_fetch_assoc($sql_cat)) {
            $data['cat_select'][$res_cat['id']] = $res_cat['opis'];
        }
    }
    //echo '<pre>' . print_r($data['cat_select'], TRUE) . '</pre>';
    //TODO
    mysqli_close($db);

    if (isset($_POST['send']) && $_POST['send'] != '') {
        //echo '<pre>' . print_r($_POST, TRUE) . '</pre>';
        if (isset($_POST['category'], $_POST['title'], $_POST['msg']) && $_POST['category'] != '' && $_POST['category'] != '0') {
            $db = conectTo();

            $id = mysqli_real_escape_string($db, trim($_POST['category']));
            $title = mysqli_real_escape_string($db, trim($_POST['title']));
            $text = mysqli_real_escape_string($db, trim($_POST['msg']));
            unset($_POST);
            if (checkLenght($title, 5, 500) && checkLenght($text, 5, 5100)) {

                $insert = mysqli_query($db, 'UPDATE `content` SET `title`="' . $title . '",`text`="' . $text . '" 
                    WHERE `id`="' . $id . '"');
                if ($insert === TRUE) {
                    $data['input_error'] = 'Запсът е Успешен';
                }
                mysqli_close($db);
            } else {
                $data['input_error'] = 'Грешка!Дължината на Заглавието трябва да е м/у 5-255 символа,а на текстовото поле м/у 5-5100 символа .';
            }
        } else {
            $data['input_error'] = 'Въведи Категория/Заглавие/Текст ';
        }
    }
    #-------Trie----Tekstovite---Poleta
    if (isset($_POST['delete']) && $_POST['delete'] != '') {
        if (isset($_POST['category']) && $_POST['category'] != '' && $_POST['category'] != '0') {
            $db = conectTo();
            $id = mysqli_real_escape_string($db, trim($_POST['category']));
            unset($_POST);
            $clear = mysqli_query($db, 'UPDATE `content` SET `title`="",`text`="" WHERE `id`="' . mysqli_real_escape_string($db, $id) . '"');
            if ($clear === TRUE) {
                $data['input_error'] = 'Текстовото Поле е Изтрито Успешно';
            } else {
                $data['input_error'] = 'Текстовото Поле Не е Изтрито';
            }
            mysqli_close($db);
        } else {
            $data['input_error'] = 'Не е избрана Категория ';
        }
    }

    $data['title'] = 'Админ Панел';

    $data['menu'] = './templates/nav_menu_admin.php';
    $data['body'] = './templates/text_edit_template.php';
} else {
    //setcookie(session_name(), session_id(), time() - 10801, '', '', '', TRUE);

    $data['title'] = 'Вход';
    $data['menu'] = './templates/nav_menu_admin.php';
    $data['body'] = './templates/admin_login_template.php';
}

$data['atm'] = $data['title'];

render($data, './templates/layouts/admin_layout.php');
