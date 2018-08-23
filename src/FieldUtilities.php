<?php

namespace Drupal\negnet_utility;

use Drupal\Core\Render\Element;

/**
 * Negnet Solution Field Utilities.
 */
class FieldUtilities {

  private static $numberMappings = ['one', 'two', 'three',
    'four', 'five', 'six',
  ];

  /**
   * Converts underscores to Camel Case.
   */
  public static function camelCaseString(string $string) {
    $words = explode('_', $string);
    array_walk($words, 'self::ucfirst');
    $words[0] = strtolower($words[0]);
    return implode('', $words);
  }

  /**
   * Implements uc_words for array_walk.
   */
  protected static function ucfirst(string &$string) {
    $string = ucfirst($string);
  }

  /**
   * Returns true or false if element has children.
   */
  public static function fieldHasChildren($element, $field) {
    if (is_array($element)) {
      if (isset($element[$field]) && isset($element[$field][0])) {
        return TRUE;
      }
    }
    elseif ($element->hasField($field)) {
      if ($element->get($field)->isEmpty()) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns all of a render array's children.
   */
  public static function elementChildren(array &$elements) {
    $children = Element::children($elements);
    return self::subArray($elements, $children);
  }

  /**
   * Returns all of the field's values.
   */
  public static function fieldChildren(&$elements) {
    $children = [];
    foreach ($elements as $e) {
      $children[] = $e->value;
    }

    return $children;
  }

  /**
   * Returns a portion of an array based on array keys.
   */
  public static function subArray(array $haystack, array $needle) {
    return array_intersect_key($haystack, array_flip($needle));
  }

  /**
   * Converts an integer into a named number.
   */
  public static function numberToName(int $number) {
    $number -= 1;

    if (isset(self::$numberMappings[$number])) {
      return self::$numberMappings[$number];
    }
    return FALSE;
  }

}
