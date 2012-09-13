<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaphirAngel
 * Date: 13/09/12
 * Time: 11:00
 * To change this template use File | Settings | File Templates.
 */
class ArrayFilter
{
    function arr($key, $value, $options = array())
    {
        if (!is_array($value)) return false;

        return true;
    }

    public static function get_check_functions() {
        $functions[] = ['id' => 'arr', 'name' => 'array', 'class' => 'ArrayFilter',  'function' => 'arr', 'options' => []];

        return $functions;
    }

    public static function get_advance_check_functions() {
        return array();
    }
}
