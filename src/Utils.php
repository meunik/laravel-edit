<?php

namespace Meunik\Edit;

use Carbon\Carbon;

trait Utils
{
    private function date($date)
    {
        try {
            return ($this->validateDate($date)) ? Carbon::parse($date)->format('Y-m-d') : $date;
        } catch (\Exception $e) {
            return $date;
        }
    }
    private function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        try {
            $d = Carbon::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function snake_caseToCamelCase($string, $countOnFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$countOnFirstCharacter) $str[0] = strtolower($str[0]);
        return $str;
    }

    private static function camelCaseToSnake_case($string)
    {
        return strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["_$1", "_$1_$2"], lcfirst($string) ) );
    }

    private static function error(string $message = "", int $code = 400)
    {
        throw new \Exception($message, $code);
    }
}
