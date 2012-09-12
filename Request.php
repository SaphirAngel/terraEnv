<?php
/**
 * Created by SaphirAngel
 * User: SaphirAngel
 */


define ('DEFAULT_FLAG', 0);
define ('HTML_SECURE', 1);
define ('NOT_EMPTY', 6);
define ('NOT_NULL', 4);
define ('CHECK', 8);
define ('NUMERIC', 16);

define ('DEFAULT_DATE_FORMAT', 'Y-m-d');
define ('DEFAULT_TIME_FORMAT', 'H:i:s');

class REQUEST
{

    private $requestMethod;
    private $checkFunctions = array();
    private $defaultFlag;
    private $arrayData;
    private $errorsList = array();

    /**
     * Construction de l'instance
     * @param string $requestMethod Méthode de récupération des données (POST, GET, ALL => POST + GET). En cas de ALL POST est prioritaire si doublon
     * @param string $defaultFlag Flag par défaut
     */
    public function __construct($requestMethod = 'POST', $defaultFlag = 'default')
    {
        if ($requestMethod == 'POST') $this->arrayData = $_POST;
        if ($requestMethod == 'GET') $this->arrayData = $_GET;
        if ($requestMethod == 'ALL') $this->arrayData = array_merge($_GET, $_POST);

        $this->requestMethod = $requestMethod;

        if ($defaultFlag == 'default') $this->defaultFlag = NOT_NULL | NOT_EMPTY;
        else $this->defaultFlag = $defaultFlag;

        $this->init_default_filter();
    }

    /**
     * Initialisation des filtres par défaut
     */
    private function init_default_filter()
    {
        $this->add_check('i', 'integer', 'integer');
        $this->add_check('pi', 'positive integer','integer', ['positive' => true]);
        $this->add_check('ni', 'negative integer','integer', ['negative' => true]);
        $this->add_check('f', 'float', 'float');
        $this->add_check('pf', 'positive float', 'float', ['positive' => true]);
        $this->add_check('nf', 'negative float', 'float', ['negative' => true]);
        $this->add_check('b', 'boolean', 'boolean');
        $this->add_check('s', 'string', 'string');
        $this->add_check('m', 'mail', 'mail_filter');
        $this->add_check('d', 'date', 'date_time');
    }

    /**
     * Sécurise les données de type HTML
     * @param string $data Données à sécuriser
     * @param bool $flag Flag de sécurisation HTML
     * @return string La valeur sécurisée
     */
    private function html_secure($data, $flag)
    {
        if ($flag == false) return $data;
        $data = htmlspecialchars($data);

        return $data;
    }

    /**
     * Ajoute une erreur au tableau d'erreur
     * @param $code Code de l'erreur
     * @param $comment Description détaillée de l'erreur
     */
    private function add_error($code, $comment) {
        $this->errorsList[$code][] = $comment;
    }

    /**
     * Verifie les données si le flag CHECK est présent
     * @param $key Clé de la valeur à vérifier
     * @param $value Valeur à vérifier
     * @param array $checkOption Options passées lors de l'appel de la vérification
     * @return bool|mixed Résultat de la vérification
     */
    private function check($key, $value, $checkOption = array())
    {
        if (!isset($this->checkFunctions[$checkOption])) return false;

        $checkFunction = $this->checkFunctions[$checkOption]['function'];
        $options = $this->checkFunctions[$checkOption]['options'];

        return call_user_func($checkFunction, $key, $value, $options);
    }

    /**
     * Valide les données en entrées selon différents paramètres
     * @param mixed $keys Une clé texte ou un tableau de clé
     * @param int $flags Flag de vérification
     * @param string $checkOptions Si le flag CHECK est présent alors contient les id des filtres
     * @return array|bool Retourne un tableau ou false si les données ne sont pas valides
     */
    public function __invoke($keys, $flags = DEFAULT_FLAG, $checkOptions = '')
    {
        //On nettoie les erreurs à chaque nouvelle requète
        $this->errorsList = array();

        if ($flags == DEFAULT_FLAG) $flags = $this->defaultFlag;

        $notEmpty = $flags & NOT_EMPTY;
        $notNull = $flags & NOT_NULL;
        $htmlSecure = $flags & HTML_SECURE;
        $check = $flags & CHECK;
        $numeric = $flags & NUMERIC;

        if (!is_array($keys)) {
            $key = $keys;
            $keys = array($key);
        }

        foreach ($keys as $count => $key) {
            $data[$key] = '';

            if (isset($this->arrayData[$key])) {
                if ($numeric && !is_numeric($this->arrayData[$key])) {
                    $this->add_error('NUMERIC_ERROR',
                                     'La donnée contenue dans $_'.$this->requestMethod.'[\''.$key.'\'] n\'est pas une valeur numérique (\''.$this->arrayData[$key].'\)');
                    continue;
                }
                $data[$key] = $this->html_secure($this->arrayData[$key], $htmlSecure);
            } else if ($notNull == true) {
                $this->add_error('NULL_ERROR', 'La valeur $_'.$this->requestMethod.'[\''.$key.'\'] n\'existe pas');
                continue;
            }

            if (empty($data[$key]) && $notEmpty == true) {
                $this->add_error('EMPTY_ERROR', 'La donnée contenue dans $_'.$this->requestMethod.'[\''.$key.'\'] est vide');
                continue;
            }

            if ($check) {
                $checkOption = '';
                if (!is_array($checkOptions)) $checkOption = $checkOptions;
                else {
                    if (isset($checkOptions[$count]))
                        $checkOption = $checkOptions[$count];
                }

                if ($checkOption != '' && !$this->check($key, $data[$key], $checkOption)) {
                    $this->add_error('CHECK_ERROR',
                                     'La donnée contenue dans $_'.$this->requestMethod.'[\''.$key.'\'] n\'est pas valide'.
                                     '('.$data[$key].' n\'est pas de type '.$this->checkFunctions[$checkOption]['name'].')');
                    continue;
                }
            }


        }

        if (count($this->errorsList) > 0) return false;
        else return $data;
    }

    /**
     * Permet d'ajouter une fonction de verification personnalisée
     * @param $id Identifiant du filtre
     * @param $name Nom du filtre
     * @param $functionPtr Fonction qui s'occupe de vérifier la valeur
     * @param array $options Options à passer à la fonction lors de l'appel
     * @return bool True si la fonction a été ajouté, False si la fonction n'est pas accessible
     */
    public function add_check($id, $name, $functionPtr, $options = array())
    {
        if (is_callable($functionPtr)) {
            $this->checkFunctions[$id]['name'] = $name;
            $this->checkFunctions[$id]['function'] = $functionPtr;
            $this->checkFunctions[$id]['options'] = $options;

            return true;
        }
        return false;
    }

    /**
     * Retourne les erreurs
     * @return array Le tableau contenant les erreurs
     */
    public function get_errors_list() {
        return $this->errorsList;
    }
}


function integer($key, $value, $options = array())
{
    $filterOptions = array();

    if (isset($options['positive']) && $options['positive'] == true) $filterOptions['min_range'] = 0;
    if (isset($options['negative']) && $options['negative'] == true) $filterOptions['max_range'] = 0;

    if (filter_var($value, FILTER_VALIDATE_INT, array('options' => $filterOptions)) === false) return false;

    return true;
}

function float($key, $value, $options = array())
{
    if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) return false;

    if (isset($options['positive']) && $options['positive'] == true && $value < 0) return false;
    if (isset($options['negative']) && $options['negative'] == true && $value > 0) return false;

    return true;
}

function character($key, $value, $options = array())
{
    $enabledClasses = ['alnum', 'alpha', 'blank',
        'ctrl', 'digit', 'graph',
        'print', 'punct', 'space',
        'upper', 'xdigit'];

    if (!is_string($value) || strlen($value) != 1) return false;
    if (isset($options['classe']) && !in_array($options['classe'], $enabledClasses)) return false;

    if (isset($options['classe']) && preg_match('/[[:' . $options['classe'] . ':]]/', $value) == 0) return false;

    return true;
}

function mail_filter($key, $value, $options = array()) {
    if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) return false;

    return true;
}

function date_time($key, $value, $options = array()) {
    $date = date_create($value);
    if (!$date) return false;

    return true;
}

/**
 * @param $key La clé de la valeur passée en post
 * @param $value La valeur passée en post
 * @param array $options Options passé à la méthode de vérification
 * @return bool False si la chaine fait moins de 2 caractères ou si le le regex à échoué
 */
function string($key, $value, $options = array())
{
    if (!is_string($value) || strlen($value) < 2) return false;

    if (isset($options['expr']) && preg_match('/' . $options['expr'] . '/', $value) == 0) return false;

    return true;
}

function boolean($key, $value, $options = array())
{
    if (filter_var($value, FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE)) === null) return false;

    return true;
}

function arr($key, $value, $options = array())
{
    if (!is_array($value)) return false;

    return true;
}


/*

Initialisation des instances

$post = new REQUEST('POST');
$get = new REQUEST('GET');
$request = new REQUEST('ALL');
*/