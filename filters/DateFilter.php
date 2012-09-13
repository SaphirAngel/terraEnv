<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaphirAngel
 * Date: 13/09/12
 * Time: 10:46
 * To change this template use File | Settings | File Templates.
 */
class DateFilter
{

    public static function date_time($key, $value, $options = array()) {
        $date = date_create($value);
        if (!$date) return false;

        return true;
    }

    public static function get_check_functions() {
        $functions[] = ['id' => 'd', 'name' => 'date', 'class' => 'DateFilter',  'function' => 'date_time', 'options' => []];

        return $functions;
    }

    public static function get_advance_check_functions() {
        return array();
    }
}
