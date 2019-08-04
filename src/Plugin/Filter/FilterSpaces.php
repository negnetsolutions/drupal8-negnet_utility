<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;

/**
 * @file
 * Text filter for Negnet Solutions.
 */

/**
 * Class FilterSpaces.
 *
 * @Filter(
 *   id = "filter_negnet_spaces",
 *   title = @Translation("Strip Non-Breaking spaces from html."),
 *   description = @Translation("Strips Non-Breaking spaces from html."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterSpaces extends FilterBase {

  /**
   * Implements process.
   */
  public function process($text, $langcode) {
    $text = str_replace('&nbsp;', ' ', $text);
    return new FilterProcessResult($text);
  }

}
