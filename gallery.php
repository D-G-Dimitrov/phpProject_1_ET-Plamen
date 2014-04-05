<?php

//$data['time_start'] = microtime();
session_set_cookie_params(10800, '', '', '', TRUE);
session_start();

include './includes/functions.php';

#echo '<pre>' . print_r($_FILES, TRUE) . '</pre>';
#echo '<pre>' . print_r($_POST, TRUE) . '</pre>';

if (file_exists('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'dir_view') === FALSE) {
    if (file_exists('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'thumbs') === FALSE) {
        if (file_exists('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images') === FALSE) {

            mkdir('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images');
        }

        mkdir('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'thumbs');
    }
    mkdir('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'dir_view');
}

$pic_dir = './gallery/images/';
//$pic_dir = '.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;





if ((isset($_SESSION['isLogged']) && isset($_SESSION['admin']) &&
        ($_SESSION['isLogged'] === TRUE) && ($_SESSION['admin'] === TRUE))) {

    if (isset($_GET['delete']) && $_GET['delete'] != '' && $_GET['delete'] != 0) {

#-----ADMIN-----DELETE---------#
        $db = conectTo();
        $del_pic_id = mysqli_real_escape_string($db, trim($_GET['delete']));
        $query_pic_name = mysqli_query($db, 'SELECT `pic_name`,`dir_name` FROM `gallery` WHERE `pic_id`="' . $del_pic_id . '"');
        if ($query_pic_name) {
            $res = mysqli_fetch_assoc($query_pic_name);
            $del_pic_name = $res['pic_name'];
            $del_dir_name = $res['dir_name'];
            #-----------------------------------
            if ($del_dir_name != '' && $_GET['dir'] == '') {
                $del_sql = 'DELETE FROM `gallery` WHERE `dir_name`="' . $del_dir_name . '"';
                $dir_sql = mysqli_query($db, 'SELECT `pic_name` FROM `gallery` WHERE `dir_name`="' . $del_dir_name . '"');
                if ($dir_sql) {

                    while ($res2 = mysqli_fetch_assoc($dir_sql)) {
                        $del_pic_name = $res2['pic_name'];
                        if (remove_pic($del_pic_name) == FALSE) {
                            $data ['input_error'] = 'Директорията НЕ е Изтрита';
                            $del_pic_name = NULL;
                            break;
                        }
                    }
                }
            } else {
                $del_sql = 'DELETE FROM `gallery` WHERE `pic_id`="' . $del_pic_id . '"';
            }
//echo '<pre>' . print_r($del_pic_name, TRUE) . '</pre>';
            if ($del_pic_name != '') {
                if (mysqli_query($db, $del_sql)) {

                    if (remove_pic($del_pic_name)) {
                        $data ['input_error'] = 'Снимката е Изтрита Успешно';
                    } else {
                        $data ['input_error'] = 'Директорията е Изтрита Успешно';
                    }
                } else {
                    $data ['input_error'] = 'Снимката НЕ е Изтрита';
                }
            }
        }
        mysqli_close($db);
        unset($_GET);
    }




    if (isset($_POST['send'])) {
        if (( isset($_FILES['selectFile']) ) && $_POST['send'] != '' && $_FILES['selectFile']['name'] != '') {
            //echo '<pre>' . print_r($_FILES, TRUE) . '</pre>';
            if ($_FILES ['selectFile'] ['size'] != '') {
                if ($_FILES ['selectFile'] ['size'] < $fmax_size) {
                    if ($_FILES['selectFile']['error'] == 0) {

                        if (isset($_POST['title']) && $_POST['title'] != '') {
                            $title = trim($_POST['title']);

                            if (checkLenght($title, 3, 100)) {


                                $text = trim($_POST['text']);


                                if (trim($_POST['dir_input']) == '') {
                                    $dir_name = trim($_POST['dir_name']);
                                } else {
                                    if (checkLenght(trim($_POST['dir_input']), 2, 100)) {
                                        $dir_name = trim($_POST['dir_input']);
                                    } else {
                                        $data['input_error'] = 'Грешка ! Категорията/Директорията трябва да има дължина от 2-100 символа ';
                                    }
                                }
                                $pic_name = time() . '__' . $_FILES['selectFile']['name'];

                                if (!file_exists('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pic_name)) {
                                    $new_dir = '.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

                                    $db = conectTo();
                                    check_conection($db);

                                    if (mysqli_query($db, 'INSERT INTO `gallery`(`pic_name`,`dir_name`, `pic_title`, `pic_text`) 
                                                VALUES ("' . mysqli_real_escape_string($db, $pic_name) . '"
                                                    ,"' . mysqli_real_escape_string($db, $dir_name) . '"
                                                    ,"' . mysqli_real_escape_string($db, $title) . '"
                                                    ,"' . mysqli_real_escape_string($db, $text) . '")')) {

                                        if (move_uploaded_file($_FILES ['selectFile'] ['tmp_name'], '.' . DIRECTORY_SEPARATOR . 'gallery'
                                                        . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pic_name)) {
                                            if (createThumbs('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pic_name, '.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR .
                                                            'images' . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . '', 200)) {
                                                if (createThumbs('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pic_name, '.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR .
                                                                'images' . DIRECTORY_SEPARATOR . 'dir_view' . DIRECTORY_SEPARATOR . '', 90)) {

                                                    $data ['input_error'] = 'Снимката е качена Успешно';
                                                    unset($_POST, $_FILES);
                                                    mysqli_close($db);
                                                } else {

                                                    if (unlink('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images'
                                                                    . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $pic_name)) {

                                                        $data['input_error'] = 'Грешка при създаване на Thumbnail/dir_view/.Качения фаил е изтрит';
                                                        exit_delete_at_error($db);
                                                    } else {
                                                        $data['input_error'] = 'Грешка при създаване на Thumbnail/dir_view/.Качения фаил НЕ е изтрит!!!';
                                                        exit_delete_at_error($db);
                                                    }
                                                }
                                            } else {

                                                if (unlink('.' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . 'images'
                                                                . DIRECTORY_SEPARATOR . $pic_name)) {

                                                    $data['input_error'] = 'Грешка при създаване на Thumbnail.Качения фаил е изтрит';
                                                    exit_delete_at_error($db);
                                                } else {
                                                    $data['input_error'] = 'Грешка при създаване на Thumbnail.Качения фаил НЕ е изтрит!!!';
                                                    exit_delete_at_error($db);
                                                }
                                            }
                                        } else {
                                            $data['input_error'] = 'Грешка при качването в директория ' . $new_dir;
                                            exit_delete_at_error($db);
                                        }
                                    } else {

                                        $data['input_error'] = 'Грешка при запис в База Данни : ' . check_conection($db) . '';
                                    }
                                } else {
                                    $data['input_error'] = 'Снимката не е качена!Съществува снимка с Име ' . $_FILES['selectFile'] ['name'] . '';
                                }
                            } else {
                                $data['input_error'] = 'Грешка ! Снимката трябва да има Заглавие с дължина от 3-255 символа ';
                            }
                        } else {
                            $data['input_error'] = 'Грешка !Няма въведено Заглавие .Снимката трябва да има Заглавие с дължина от 3-255 символа ';
                        }
                    } else {
                        $data['input_error'] = 'Грешка при качването :' . $_FILES['selectFile']['error'] . '!';
                    }
                } else {
                    $data['input_error'] = 'Грешка ! Големината на файла преишава ' . ($fmax_size / (1024 * 1024)) . ' Mb.';
                }
            } else {
                $data['input_error'] = 'Грешка ! Празен файл';
            }
        } else {
            $data['input_error'] = 'Не е избрана снимка !';
        }
    }
    $data['menu'] = './templates/file_upload_template.php';
//
//
} else {
//setcookie(session_name(), session_id(), time() - 10801, '', '', '', TRUE);
    $data['menu'] = './templates/nav_menu.php';
}


//--------------CATEGORI--SELECTOR----------------------//
//$data['dir_name'];

if (isset($_GET['dir']) && $_GET['dir'] != '') {

    $db = conectTo();
    $dir = mysqli_real_escape_string($db, trim($_GET['dir']));
    $query_select_pic = 'SELECT * FROM  `gallery` WHERE `dir_name`="' . $dir . '" ORDER BY  `gallery`.`pic_name` ASC ';

    $selectPic = mysqli_query($db, $query_select_pic);
    if ($selectPic !== FALSE) {
        //$dir_n = array();
        while ($res_obj = mysqli_fetch_assoc($selectPic)) {

            $data['pic'][] = $res_obj;
            /* if ($res_obj['dir_name'] != NULL) {
              $dir_n[] = ($res_obj['dir_name']);
              } */
        }
        $data['dir_name'][] = trim($_GET['dir']);
        #---------------------------------------------

        meta_query('2', $db, $data);

        //echo '<pre>' . print_r($data['dir_name'], TRUE) . '</pre>';
        mysqli_close($db);
    } else {
        header('Location: index.php');
        exit();
    }
} else {
#-----------------------------------------------
    $db = conectTo();

    $query_select_pic = 'SELECT * FROM  `gallery` ORDER BY  `gallery`.`pic_id` ASC ';

    $selectPic = mysqli_query($db, $query_select_pic);
    if ($selectPic !== FALSE) {
        $forbidden = array();
        $data['pic_dir'] = array();
        //$dir_n = array();
        while ($res_obj = mysqli_fetch_assoc($selectPic)) {


            if ($res_obj['dir_name'] != '') {
                //$dir_n[] = ($res_obj['dir_name']);

                if (!in_array($res_obj['dir_name'], $forbidden)) {
                    $forbidden[] = $res_obj['dir_name'];
                    $data['pic'][] = $res_obj;
                } else {
                    $data['pic_dir'][] = $res_obj;
                }
            } else {
                $data['pic'][] = $res_obj;
            }
        }
        $data['dir_name'] = array_unique(($forbidden));

        array_unshift($data['dir_name'], '');
        //var_dump($data['dir_name']);
        //var_dump($data['dir_name']);
        //echo '<pre>' . print_r($data['pic_dir'], TRUE) . '</pre>';
        //echo '<pre>' . print_r($data['dir_name'], TRUE) . '</pre>';
        //echo '<pre>' . print_r($forbidden, TRUE) . '</pre>';
        //echo '<pre>' . print_r($data ['pic'], TRUE) . '</pre>';
#--------------------------------------
        $sql = mysqli_query($db, 'SELECT * FROM `content` ORDER BY  `content`.`id` ASC LIMIT 7, 1');
        if ($sql !== FALSE) {

            while ($res = mysqli_fetch_assoc($sql)) {
                $data['cat_select'][$res['category']] = $res;
            }
        }

#---------------------------------------------

        meta_query('2', $db, $data);


        mysqli_close($db);
    } else {
        header('Location: index.php');
        exit();
    }
}

$data['atm'] = '2';
$data['body'] = './templates/gallery_template.php';

render($data, './templates/layouts/base_layout.php');
