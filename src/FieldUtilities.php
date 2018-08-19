<?php

namespace Drupal\negnet_utility;

use Drupal\Core\Render\Element;

class FieldUtilities
{

    private static $numberMappings = array('one','two','three','four','five','six');

    /**
   * Returns true or false if element has children
   */
    public static function fieldHasChildren($element, $field) 
    {
        if (is_array($element)) {
            if (isset($element[$field]) && isset($element[$field][0])) {
                return true;
            }
        }
        else if ($element->hasField($field)) {
            if ($element->get($field)->isEmpty()) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
   * Returns all of a render array's children.
   */
    public static function elementChildren(array &$elements)
    {
        $children = Element::children($elements);
        return self::sub_array($elements, $children);
    }

    /**
   * Returns all of the field's values
   */
    public static function fieldChildren(&$elements) 
    {
        $children = [];
        foreach($elements as $e) {
            $children[] = $e->value;
        }

        return $children;
    }

    /*
    * Returns a portion of an array based on array keys
    */
    public static function sub_array(array $haystack, array $needle)
    {
        return array_intersect_key($haystack, array_flip($needle));
    }


    /*
    * Converts an integer into a named number
    * or returns false if can't find a mapping
    */
    public static function numberToName(int $number)
    {
        $number -= 1;

        if(isset(self::$numberMappings[$number])) {
            return self::$numberMappings[$number];
        }
        return false;
    }
}
