<?php
/**
 * Created by SaphirAngel
 * User: SaphirAngel
 */
include 'Request.php';


/*
********
* FLAG *
********
HTML_SECURE     ok
NOT_EMPTY       ok
NOT_NULL        ok
CHECK           ok
NUMERIC         ok

////// CHECK FLAG       ok

**************
* CHECK MODE *
**************
i   integer             ok
ip  positive integer    ok
in  negative integer    ok
f   float               ok
fp  positive float      ok
fn  negative float      ok
s   string              ok
c   character           ok
b   boolean             ok
m   mail                ok
d   date                ok


***********************
* ADVANCED CHECK MODE *
***********************
ir   integer_range => array(min, max)   nok
fr   float_range => array(min, max)     nok
sr   string_regex => '/regex/'          nok

////// get_type | is_type METHOD      nok

*********
* TYPES *
*********
integer     nok
float       nok
string      npk
boolean     nok
character   nok


*/

// For test
$_POST['titre'] = '<span>Hello world</span>';
$_POST['x'] = 'test';
$_POST['x_empty'] = '';
$_POST['ND'] = "60";
$_POST['age'] = "50.9";
$_POST['hidden'] = "false";
$_POST['test'] = "ok";
$_POST['contenu'] = "";


$post = new REQUEST('POST');
$get = new REQUEST('GET');
$request = new REQUEST('ALL');

/***NORMAL FLAG***/

echo 'Securisation HTML';
$titre = $post('titre', HTML_SECURE);
var_dump($titre);

echo '<br />Valeur inexistante';
$userDataTest_1 = $post(['x', 'y'], NOT_EMPTY | NOT_NULL | HTML_SECURE);
if (!$userDataTest_1) var_dump($post->get_errors_list());
else var_dump($userDataTest_1);

echo '<br />Donnée vide';
$userDataTest_2 = $post(['x_empty'], NOT_EMPTY | HTML_SECURE);
if (!$userDataTest_2) var_dump($post->get_errors_list());
else var_dump($userDataTest_2);

echo '<br />Valeur existante';
$userDataTest_3 = $post(['x'], NOT_EMPTY | HTML_SECURE);
if (!$userDataTest_3) var_dump($post->get_errors_list());
else var_dump($userDataTest_3);

echo '<br />Valeur numérique';
$userDataNumeric = $post(['ND', 'age'], NUMERIC);
if (!$userDataNumeric) var_dump($post->get_errors_list());
else var_dump($userDataNumeric);

// Default flag
echo '<br />Valeur avec flag par défaut';
$userDataTest_default = $post(['ND', 'age', 'test']);
if (!$userDataTest_default) var_dump($post->get_errors_list());
else var_dump($userDataTest_default);

// CHECK FLAG
echo '<br />Check integer ok';
$userDataTest_4 = $post('ND', CHECK, 'i');
if (!$userDataTest_4) var_dump($post->get_errors_list());
else var_dump($userDataTest_4);

echo '<br />check positive integer avec echec';
$userDataTest_5 = $post(['ND', 'age'], CHECK, 'pi');
if (!$userDataTest_5) var_dump($post->get_errors_list());
else var_dump($userDataTest_5);

echo '<br />check valeur booléenne';
$hidden = $post('hidden', CHECK, 'b');
if (!$hidden) var_dump($post->get_errors_list());
else var_dump($hidden);

echo '<br />check simulation post ajout news basique (echec car contenu vide)';
$dataNews = $post(['ND', 'titre', 'contenu'],
    NOT_EMPTY | HTML_SECURE | CHECK,
    ['pi', 's', 's']);
if (!$dataNews) var_dump($post->get_errors_list());
else var_dump($dataNews);

/*

$post->add_check($checkName, $fct);

/***Not implemented***/
/*
$filters = [ 'r' => [0, 2],
             's' => '/./' ];

$userData = $post(['ND', 'titre'], CHECK | NOT_EMPTY, $filters);
var_dump($userData);

$post->get_type('titre');
$post->is_type('titre', 's');
$post->is_type(['titre', 'ND'], ['s', 'ip']);



*/

/*
   Ajouter la possibilité de préciser les check :
   $check_filter = [ 'ir' => [0, 1],
                     's' => '/[a-z]{8,}/' ];

    $userValues = $post(['activation_flag', 'password'], CHECK, $check_filter);
 */