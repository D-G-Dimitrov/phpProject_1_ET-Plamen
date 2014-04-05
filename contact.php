<?php

//$data['time_start'] = microtime();
session_set_cookie_params(10800, '', '', '', TRUE);
session_start();
include './includes/functions.php';
//$_SESSION['token'] = (int) (time() / rand());
//echo '<pre>' . print_r($_POST, TRUE) . '</pre>';

if (isset($_SESSION['isLogged']) && isset($_SESSION['admin']) &&
        ($_SESSION['isLogged'] === TRUE) && ($_SESSION['admin'] === TRUE)) {



#--------ADMIN-----GET---SINGLE---MSG----PROCESING------------

    if (isset($_GET['msg_id']) && $_GET['msg_id'] != '') {
        //echo '<pre>' . print_r($_GET['msg_id'], TRUE) . '</pre>';

        $db = conectTo();
        $id_selector = mysqli_real_escape_string($db, trim($_GET['msg_id']));
        $selectTxt = mysqli_query($db, 'SELECT * FROM `messages` WHERE 
            `msg_id`="' . $id_selector . '" ');
        if ($selectTxt !== FALSE) {
            while ($res_obj = mysqli_fetch_assoc($selectTxt)) {

                $data ['msg'][] = $res_obj;
            }
        }
        //echo '<pre>' . print_r($data ['msg'], TRUE) . '</pre>';
        //$data['max_msg'] = $_SESSION['max_msg'];
        if (isset($_SESSION['max_msg']) && $_SESSION['max_msg'] != '') {//Брояч на msg_id -tata
            $data['max_msg'] = $_SESSION['max_msg'];
        } else {
            $selectMax = mysqli_query($db, 'SELECT `msg_id` FROM `messages` ORDER BY `msg_id` DESC 
            LIMIT 1');
            if ($selectMax !== FALSE) {
                while ($max_id = mysqli_fetch_assoc($selectMax)) {
                    $data['max_msg'] = $max_id['msg_id'];
                }
            }
        }
        mysqli_close($db);
    } else {
        //------------ADMIN--ALL-----MSG---SELECTOR----------------------

        $db = conectTo();
        $selectMsg = mysqli_query($db, 'SELECT `msg_id`,`msg_date`, `msg_title` FROM `messages` ORDER BY  `messages`.`msg_id` ASC ');
        if ($selectMsg !== FALSE) {
            while ($res_obj = mysqli_fetch_assoc($selectMsg)) {

                $data ['msg'][] = $res_obj;
                $_SESSION['max_msg'] = $res_obj['msg_id'];
            }
        }
        //echo '<pre>' . print_r($data ['msg'], TRUE) . '</pre>';

        mysqli_close($db);
    }

    //-------------------------------------
    $data['menu'] = './templates/nav_menu_admin.php';
    $data['body'] = './templates/admin_contact_template.php';
} else {
#-----------USER---VIEW---TEXT---BLOCK---BY--ID----------------------------------------------
    //setcookie(session_name(), session_id(), time() - 10801, '', '', '', TRUE);
#------------------------------------------

    $data['menu'] = './templates/nav_menu.php';
    $data['body'] = './templates/contact_template.php';
#----------------------------------------------------
    $db = conectTo();
    $sql = mysqli_query($db, 'SELECT * FROM `content` ORDER BY  `content`.`id` ASC LIMIT 4, 1');
    if ($sql !== FALSE) {

        while ($res = mysqli_fetch_assoc($sql)) {
            $data['cat_select'][$res['category']] = $res;
        }
    }
//echo '<pre>' . print_r($data['cat_select'], TRUE) . '</pre>';
//TODO
    mysqli_close($db);

#-----USER-POST---PROCESSING--------------------------------------------------------



    if (isset($_POST['send']) && $_POST['send'] != '') {
        if (isset($_POST['mail_token']) && $_POST['mail_token'] == $_SESSION['token']) {



            $error = array();
#----------------------------------------------
            if ($_POST['auth_code'] == '') {
                $error[] = 'Не е въведен Код за проверка';
            } else {
                $auth = (trim($_POST['auth_code']));
                //echo '<pre>' . print_r($auth, TRUE) . '</pre>';
                //echo '<pre>' . print_r($_SESSION['auth_code'], TRUE) . '</pre>';
                if (checkLenght($auth, 5, 5) !== TRUE) {

                    $error[] = 'Не е въведен целия Код за проверка';
                }
                if ($auth != $_SESSION['auth_code']) {
                    $error[] = 'Грешен Код за проверка';
                }
            }
#----------------------------------------------
            if ($_POST['title'] == '') {
                $error[] = 'Няма въведено Заглавие';
                $title = NULL;
            } else {
                $title = trim($_POST['title']);
                $title = html2txt($title);
                if (checkLenght($title, 3, 250) !== TRUE) {
                    $error[] = 'Дължината на Заглавието трябва да е от 3 до 250 символа';
					$title = NULL;
                }
            }
#-----------------------------------------------
            if ($_POST['sender_mail'] == "") {
                $error[] = 'Няма въведен Е-майл';
                $mail = NULL;
            } else {
                $mail = trim($_POST['sender_mail']);
                $mail = html2txt($mail);
                if (checkLenght($mail, 3, 250) !== TRUE) {
                    $error[] = 'Дължината на Е-майла трябва да е от 3 до 250 символа';
                } else {
                    if (preg_match('/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-]+[.][a-zA-Z0-9\-\.]+$/', $mail) !== 1) {
                        $error[] = 'Невалиден Е-майл';
                    }
                }
            }
#-----------------------------------------------
            if ($_POST['msg'] == '') {
                $error[] = 'Няма въведено Съобщение';
                $msg = NULL;
            } else {
                $msg = trim($_POST['msg']);
                $msg = html2txt($msg);
                if (checkLenght($msg, 3, 5100) !== TRUE) {
                    $error[] = 'Дължината на Съобщението трябва да е минимум 3 символа';
                }
            }
#---------------------------------------------------
            if (count($error) == 0) {
                //TODO
                unset($_POST);
                $db = conectTo();
                $sql = mysqli_query($db, 'INSERT INTO `messages`(`msg_date`, `msg_title`, `msg_mail`, `msg_text`) 
                VALUES ("' . date('Y-m-d H:i:s') . '","' . mysqli_real_escape_string($db, $title) . '",
                    "' . mysqli_real_escape_string($db, $mail) . '","' . mysqli_real_escape_string($db, $msg) . '")');
                mysqli_close($db);
                if ($sql === TRUE) {
                    $data['input_error'] = 'Съобщението е изпратено Успешно';

                    ss_mail($title, $mail, $msg);
                } else {
                    $data['input_error'] = 'Грешка!Временно не можете да изпращате Съобщения';
                }
            } else {
                unset($_POST);
                $data['input_error'] = $error;

                $data['return_title'] = $title;
                $data['return_mail'] = $mail;
                $data['return_msg'] = $msg;
            }
        } else {
            //echo '<pre>' . print_r('token', TRUE) . '</pre>';
        }
    }

#------AUTH--CODE--IMG--------------------------------

    $num = substr(mt_rand(11111, mt_getrandmax()), -5);
    $_SESSION['auth_code'] = $num;
    $png = imagecreatetruecolor(70, 40);
    imagesavealpha($png, true);

    $trans_colour = imagecolorallocatealpha($png, 0, 0, 0, 127);
    imagefill($png, 0, 0, $trans_colour);

    $red = imagecolorallocate($png, 255, 0, 0);

    imagestring($png, 5, 5, 12, $num, $red);

    imagepng($png, './assets/img/auth_indication.jpg');
    imagedestroy($png);
}

//-----------------------------------------------------


$db = conectTo();
meta_query('4', $db, $data);
mysqli_close($db);

$token = (int) (time() / rand());
$_SESSION['token'] = $token;
$data['mail_token'] = $token;
#--------------------------------------


$data['atm'] = '4';

render($data, './templates/layouts/base_layout.php');
