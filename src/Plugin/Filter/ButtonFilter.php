<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @file
 * Text filter for Negnet Solutions.
 */

/**
 * Class ButtonFilter.
 *
 * @Filter(
 *   id = "filter_negnet_buttons",
 *   title = @Translation("Button Container"),
 *   description = @Translation("Places button links together in a .button_container."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class ButtonFilter extends FilterBase {

  /**
   * Implements process.
   */
  public function process($text, $langcode) {

    if (strlen($text) > 0) {

      $dom = new \DOMDocument();
      libxml_use_internal_errors(TRUE);
      $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
      libxml_clear_errors();
      $links = $dom->getElementsByTagName('a');
      foreach ($links as $link) {

        // Get classes.
        $classes = explode(' ', $link->getAttribute('class'));
        if (in_array('btn', $classes)) {
          $parentClasses = explode(' ', $link->parentNode->getAttribute('class'));
          $parentClasses[] = 'button_container';
          $parentClasses = array_unique($parentClasses);
          $link->parentNode->setAttribute('class', trim(implode(' ', $parentClasses)));
        }
      }

      $text = $dom->saveHTML();
    }

    return new FilterProcessResult($text);
  }

}
