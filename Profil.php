<?php
/**
 * User: SaphirAngel
 */

include 'config.inc.php';

class Profil
{
    private $name;
    private $check;
    private $advance;
    private $other;

    public function __construct($name)
    {
        if (empty($name)) throw new Exception("Un nom de profil doit être indiqué");
        $this->name = $name;
        $this->other = NOT_ACCEPTED;
    }

    public function check($keys, $flags = DEFAULT_FLAG, $options = '')
    {
        if (!empty($options))
            $flags |= CHECK;

        if (!is_array($keys)) $keys = array($keys);

        foreach ($keys as $num => $key) {
            if (!empty($options)) {
                if (is_array($options) && isset($options[$num])) $option = $options[$num];
                else $option = $options;
            }
            $this->check[$key] = [$flags, $option];
        }

        return $this;
    }

    public function advance($keys, $orders, $default = null)
    {
        $defaultValue = null;
        if (!is_array($keys)) $keys = array($keys);

        foreach ($keys as $num => $key) {
            if ($default !== null) {
                if (!is_array($default)) $defaultValue = $default;
                else {
                    if (isset($default[$num]))
                        $defaultValue = $default[$num];
                }
            }
            if (!isset($this->advance[$key]))
                $this->advance[$key] = array($orders, $defaultValue);
            else {
                $this->advance[$key][0] = array_merge($this->advance[$key][0], $orders);
                $this->advance[$key][1] = $defaultValue;
            }
        }

        return $this;
    }


    public function other($flag)
    {
        if (in_array($flag, [ACCEPTED, NOT_ACCEPTED])) {
            $this->other = $flag;
        } else {
            $this->other = NOT_ACCEPTED;
        }

    }


    public function get_advance() {
        return $this->advance;
    }

}
