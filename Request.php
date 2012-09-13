<?php
/**
 * Created by SaphirAngel
 * User: SaphirAngel
 */

include 'filters/IntegerFilter.php';
include 'filters/FloatFilter.php';
include 'filters/ArrayFilter.php';
include 'filters/booleanFilter.php';
include 'filters/DateFilter.php';
include 'filters/MailFilter.php';
include 'filters/StringFilter.php';


define ('HTML_SECURE', 1);
define ('SQL_SECURE', 2);

define ('DEFAULT_FLAG', 0);
define ('NOT_EMPTY', 6);
define ('NOT_NULL', 4);
define ('CHECK', 8);
define ('NUMERIC', 16);

define ('DEFAULT_DATE_FORMAT', 'Y-m-d');
define ('DEFAULT_TIME_FORMAT', 'H:i:s');

class REQUEST implements ArrayAccess
{

    private $requestMethod;
    private $checkFunctions = array();
    private $advanceCheckFunctions = array();

    private $defaultFlag;
    private $arrayData;
    private $finalData = array();
    private $errorsList = array();

    private $shieldFlags;
    private $shieldKeys = array();


    /**
     * Construction de l'instance
     * @param string $requestMethod Méthode de récupération des données (POST, GET, ALL => POST + GET). En cas de ALL POST est prioritaire si doublon
     * @param string $defaultFlag Flag par défaut
     */
    public function __construct($requestMethod = 'POST', $defaultFlag = 'default')
    {
        if ($requestMethod == 'POST') $this->arrayData = $_POST;
        if ($requestMethod == 'GET') $this->arrayData = $_GET;
        if ($requestMethod == 'REQUEST') $this->arrayData = $_REQUEST;

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
        $this->add_check_class("IntegerFilter");
        $this->add_check_class("FloatFilter");
        $this->add_check_class("BooleanFilter");
        $this->add_check_class("DateFilter");
        $this->add_check_class("StringFilter");
        $this->add_check_class("MailFilter");
        $this->add_check_class("ArrayFilter");

    }

    /**
     * Sécurise les données de type HTML
     * @param string $data Données à sécuriser
     * @param bool $flag Flag de sécurisation HTML
     * @return string La valeur sécurisée
     */
    private function html_secure($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $d) {
                $data[$key] = $this->html_secure($d);
            }
        } else {
            $data = preg_replace(
                array(
                    // Remove invisible content
                    '@<head[^>]*?>.*?</head>@siu',
                    '@<style[^>]*?>.*?</style>@siu',
                    '@<script[^>]*?.*?</script>@siu',
                    '@<object[^>]*?.*?</object>@siu',
                    '@<embed[^>]*?.*?</embed>@siu',
                    '@<applet[^>]*?.*?</applet>@siu',
                    '@<noframes[^>]*?.*?</noframes>@siu',
                    '@<noscript[^>]*?.*?</noscript>@siu',
                    '@<noembed[^>]*?.*?</noembed>@siu',
                    // Add line breaks before and after blocks
                    '@</?((address)|(blockquote)|(center)|(del))@iu',
                    '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
                    '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
                    '@</?((table)|(th)|(td)|(caption))@iu',
                    '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
                    '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
                    '@</?((frameset)|(frame)|(iframe))@iu',
                ),
                array(
                    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', "$0", "$0", "$0", "$0", "$0", "$0", "$0", "$0",), $data);

            $data = htmlspecialchars($data, ENT_QUOTES);
        }
        return $data;
    }

    private function sql_secure($data)
    {
        return $data;
    }

    /**
     * Ajoute une erreur au tableau d'erreur
     * @param $code Code de l'erreur
     * @param $comment Description détaillée de l'erreur
     */
    private function add_error($code, $comment)
    {
        $this->errorsList[$code][] = $comment;
    }

    /**
     * Verifie les données si le flag CHECK est présent
     * @param $key Clé de la valeur à vérifier
     * @param $value Valeur à vérifier
     * @param array $checkOption Options passées lors de l'appel de la vérification
     * @return bool|mixed Résultat de la vérification
     */
    private function basic_check($key, $value, $checkOption = array())
    {
        $class = '';
        if (!isset($this->checkFunctions[$checkOption])) return false;

        $checkFunction = $this->checkFunctions[$checkOption]['function'];
        if (isset($this->checkFunctions[$checkOption]['class']))
            $class = $this->checkFunctions[$checkOption]['class'];
        $options = $this->checkFunctions[$checkOption]['options'];

        if ($class != '')
            return call_user_func(array($class, $checkFunction), $key, $value, $options);
        else
            return call_user_func($checkFunction, $key, $value, $options);
    }

    /**
     * Verifie les données de façon plus élaboré
     * @param $key Clé de la valeur à vérifier
     * @param $value Valeur à vérifier
     * @param string $checkName Identifiant du filtre
     * @param array $checkParam Paramètre nécessaire au bon fonctionnement des différents filtres
     * @return bool|mixed Retourne false si le filtre à échoué et True sinon
     */
    private function advance_check($key, $value, $checkName, $checkParam)
    {
        if (!isset($this->advanceCheckFunctions[$checkName])) return false;
        $checkFunction = $this->advanceCheckFunctions[$checkName]['function'];
        $class = $this->advanceCheckFunctions[$checkName]['class'];
        $options = $this->advanceCheckFunctions[$checkName]['options'];

        return call_user_func(array($class, $checkFunction), $key, $value, $options, $checkParam);
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
        $this->finalData = array();

        if ($flags == DEFAULT_FLAG) $flags = $this->defaultFlag;


        $notEmpty = $flags & NOT_EMPTY;
        $notNull = $flags & NOT_NULL;
        $check = $flags & CHECK;
        $numeric = $flags & NUMERIC;

        if (!is_array($keys)) {
            $key = $keys;
            $keys = array($key);
        }

        foreach ($keys as $count => $key) {
            $this->finalData[$key] = '';

            if (isset($this->arrayData[$key])) {
                if ($numeric && !is_numeric($this->arrayData[$key])) {
                    $this->add_error('NUMERIC_ERROR',
                        'La donnée contenue dans $_' . $this->requestMethod . '[\'' . $key . '\'] n\'est pas une valeur numérique (\'' . $this->arrayData[$key] . '\)');
                    continue;
                }

                $this->finalData[$key] = $this->arrayData[$key];
            } else if ($notNull == true) {
                $this->add_error('NULL_ERROR', 'La valeur $_' . $this->requestMethod . '[\'' . $key . '\'] n\'existe pas');
                continue;
            }

            if (empty($this->finalData[$key]) && $notEmpty == true) {
                $this->add_error('EMPTY_ERROR', 'La donnée contenue dans $_' . $this->requestMethod . '[\'' . $key . '\'] est vide');
                continue;
            }

            if ($check) {
                $checkOption = '';
                if (!is_array($checkOptions)) $checkOption = $checkOptions;
                else {
                    if (isset($checkOptions[$count]))
                        $checkOption = $checkOptions[$count];
                }

                if ($checkOption != '' && !$this->basic_check($key, $this->finalData[$key], $checkOption)) {
                    $this->add_error('CHECK_ERROR',
                        'La donnée contenue dans $_' . $this->requestMethod . '[\'' . $key . '\'] n\'est pas valide' .
                            '(' . $this->finalData[$key] . ' n\'est pas de type ' . $this->checkFunctions[$checkOption]['name'] . ')');
                    continue;
                }
            }
        }

        return $this;
    }

    /**
     * @return array|bool Retourne false si un erreur a été détecté et le tableau des valeurs validées en cas de succès
     */
    public function isValid()
    {
        if (count($this->errorsList) > 0) return false;
        return $this->finalData;
    }

    /**
     * Valide les variables précédemment checker basiquement et remplace par une valeur par défaut
     * @param $checkOrders Les filtres à appliquer aux valeurs
     * @param $defaultValue La valeur par défaut à appliquer si les filtres ont échoués
     * @return mixed La valeur vérifié ou un tableau contenant toutes les valeurs vérifiés
     */
    public function check($checkOrders, $defaultValue)
    {
        $countData = 0;
        $uniq = false;
        $results = array();

        if (!$this->isValid()) return $defaultValue;
        if (count($this->finalData) == 1) $uniq = true;

        foreach ($this->finalData as $key => $value) {
            foreach ($checkOrders as $checkOrder => $checkOptions) {
                if (!$this->advance_check($key, $value, $checkOrder, $checkOptions)) {
                    if ($uniq === true) return $defaultValue;

                    if (is_array($defaultValue) && count($defaultValue) > $countData)
                        $value = $defaultValue[$countData];
                    else if (is_array($defaultValue))
                        $value = null;
                    else
                        $value = $defaultValue;

                    break;
                }
            }
            if ($uniq === true) return $this->secure($key, $value);
            $results[$key] = $this->secure($key, $value);
            $countData++;
        }

        return $results;
    }

    /**
     * Valide les variables précédemment checker basiquement
     * @param $checkOrders Les filtres à appliquer aux valeurs
     * @return array|string Les valeurs filtrées si tout les filtres ont réussis
     * @throws Exception Si un des filtres à échoué
     */
    public function validate($checkOrders)
    {

        $uniq = false;
        $results = array();

        if (!$this->isValid()) throw new Exception("Les données ne sont pas valides");
        if (count($this->finalData) == 1) $uniq = true;

        foreach ($this->finalData as $key => $value) {
            foreach ($checkOrders as $checkOrder => $checkOptions) {
                if (!$this->advance_check($key, $value, $checkOrder, $checkOptions)) {
                    throw new Exception('ADVANCE_CHECK_ERROR');
                }
            }
            if ($uniq === true) return $this->secure($key, $value);
            $results[$key] = $this->secure($key, $value);
        }

        return $results;
    }


    /**
     * Sécurise les données selon l'état du bouclier
     * @param $key  Clé dont la valeur est à sécuriser selon l'état du bouclier
     * @param $value Valeur demandant a être sécurisé selon l'état du bouclier
     * @return string   Valeur sécurisé ou non selon l'état du bouclier
     */
    public function secure($key, $value)
    {
        $htmlSecure = $this->shieldFlags & HTML_SECURE;
        $sqlSecure = $this->shieldFlags & SQL_SECURE;

        if ($htmlSecure && (count($this->shieldKeys[$htmlSecure]) == 0 || in_array($key, $this->shieldKeys[$htmlSecure])))
            $value = $this->html_secure($value);
        if ($sqlSecure && (count($this->shieldKeys) == 0 || in_array($key, $this->shieldKeys)))
            $value = $this->sql_secure($value);

        return $value;
    }

    /**
     * Permet d'ajouter des filtres contenu dans une classe
     * @param $className Nom de la classe contenant des filtres
     */
    public function add_check_class($className)
    {
        $checkMethod = $className::get_check_functions();
        $advanceCheckMethod = $className::get_advance_check_functions();

        foreach ($checkMethod as $method) {
            $this->checkFunctions[$method['id']] = $method;
        }
        foreach ($advanceCheckMethod as $method) {
            $this->advanceCheckFunctions[$method['id']] = $method;
        }
    }

    /**
     * Retourne les erreurs
     * @return array Le tableau contenant les erreurs
     */
    public function get_errors_list()
    {
        return $this->errorsList;
    }

    /**
     * Extinction du bouclier et vidage des variables
     */
    public function shield_off()
    {
        $this->shieldFlags = 0;
        $this->shieldKeys = array();
    }

    /**
     * Active le bouclier HTML et/ou SQL
     * @param int $flags Type de bouclier à enclencher
     * @param array $keys Liste des clés sur lesquelles le bouclier est effectif, si vide toute les clés sont comprises
     */
    public function shield_on($flags = 0, $keys = array())
    {
        $this->shieldFlags = SQL_SECURE | HTML_SECURE;
        if ($flags != 0) $this->shieldFlags = $flags;
        if (is_array($keys) && count($keys) > 0) {
            $this->shieldKeys[$this->shieldFlags] = $keys;
        }
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->arrayData[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (!isset($this->arrayData[$offset])) return null;
        return $this->secure($offset, $this->arrayData[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->arrayData[] = $value;
        } else {
            $this->arrayData[$offset] = $value;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->arrayData[$offset]);
    }

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

/*

Initialisation des instances

$post = new REQUEST('POST');
$get = new REQUEST('GET');
$request = new REQUEST('ALL');
*/