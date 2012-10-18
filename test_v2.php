<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaphirAngel
 * Date: 18/10/12
 * Time: 18:01
 * To change this template use File | Settings | File Templates.
 */
include 'Request.php';
$post = new REQUEST('POST', 'default');
$get = new REQUEST('GET');
$request = new REQUEST('ALL');


$_POST['login'] = 'bonjour';
$_POST['password'] = 'test';

include 'profil_test.php';

$post->load('user_login_data');

if ($post['login'] == 'bonjour' && $post['password'] == 'test') {
    echo 'you are connected';
}