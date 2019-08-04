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
 * Class FilterStyles.
 *
 * @Filter(
 *   id = "filter_negnet_styles",
 *   title = @Translation("Strip style overrides from html."),
 *   description = @Translation("Strips style overrides from html."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterStyles extends FilterBase {

  /**
   * Implements process.
   */
  public function process($text, $langcode) {
    $text = preg_replace('/style=[\'"].+[\'"]/u', '', $text);
    return new FilterProcessResult($text);
  }

}
