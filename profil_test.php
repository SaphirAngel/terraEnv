<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaphirAngel
 * Date: 12/10/12
 * Time: 17:12
 * To change this template use File | Settings | File Templates.
 */

include 'Profil.php';

$userConnection = new Profil('user_connection_data');
$userConnection->check(['login', 'password'], NOT_EMPTY, 's')
          ->advance('login', ['size' => [5, 255]], 'default')
          ->advance('password',['size' => [8, INF], 'reg'  => '/[azAZ09]*/']);

var_dump($userConnection->get_advance()['login']);

