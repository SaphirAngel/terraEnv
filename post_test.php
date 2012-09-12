<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaphirAngel
 * Date: 10/09/12
 * Time: 09:07
 * To change this template use File | Settings | File Templates.
 */
include 'Request.php';


/*
********
* FLAG *
********
HTML_SECURE
NOT_EMPTY
NOT_NULL
CHECK

**************
* CHECK MODE *
**************
i   integer
ip  positive integer
in  negative integer
f   float
fp  positive float
fn  negative float
s   string
c   character
b   boolean

***********************
* PERSONAL CHECK MODE *
***********************
r   range => array(min, max)


*********
* TYPES *
*********

integer
float
string
boolean
character


*/

$_POST['titre'] = 'bo';
$_POST['x'] = 'test';
$_POST['x_empty'] = '';
$_POST['ND'] = "60";
$_POST['age'] = "50.9";
$_POST['hidden'] = "false";
$_POST['test'] = "ok";


$post = new REQUEST('POST');
$get = new REQUEST('GET');
$request = new REQUEST('ALL');

$titre = $post('titre', HTML_SECURE);

var_dump($titre);

$userData = $post(['x', 'y'], NOT_EMPTY | NOT_NULL | HTML_SECURE);
$userData2 = $post(['x_empty'], NOT_EMPTY | HTML_SECURE);
$userData3 = $post(['x'], NOT_EMPTY | HTML_SECURE);

$ND_1 = $post('ND', CHECK, 'i');

$ND_2 = $post(['ND', 'age'], CHECK, 'pi');

var_dump($ND_2);

$ND_4 = $post(['ND', 'age', 'test']);

//var_dump($post->get_errors_list());

var_dump($ND_1);
$ND_3 = $post(['ND', 'age'], NUMERIC);
var_dump($ND_3);

var_dump($ND_4);

$hidden = $post('hidden', CHECK, 'b');
var_dump($hidden);

$filters = [ 'r' => [0, 2],
             's' => '/.*/' ];

$userData = $post(['ND', 'titre'], CHECK | NOT_EMPTY, $filters);
var_dump($userData);


echo 'test';
/*
$dataNews = $post(['ND', 'titre', 'contenu'],
                  NOT_EMPTY | HTML_SECURE | CHECK,
                  ['pi', 's', 's']);

if ($dataNews === false) {
    var_dump($post->get_errors_list());
} else {

}


/*
$post->getType('titre');
$post->isType('titre', 's');
$post->isType(['titre', 'ND'], ['s', 'ip']);


$post->add_check($checkName, $fct);
*/

/*
   Ajouter la possibilité de préciser les check :
   $check_filter = [ 'ir' => [0, 1],
                     's' => '/[a-z]{8,}/' ];

    $userValues = $post(['activation_flag', 'password'], CHECK, $check_filter);
 */