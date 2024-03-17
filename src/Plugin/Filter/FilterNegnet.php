<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @file
 * Text filter for Negnet Solutions.
 */

/**
 * Class FilterNegnet.
 *
 * @Filter(
 *   id = "filter_negnet",
 *   title = @Translation("Negnet Solutions Base Filters"),
 *   description = @Translation("Add several filters for basic operations such as {{ current_year }}"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterNegnet extends FilterBase {

  /**
   * Implements process.
   */
  public function process($text, $langcode) {

    // Set cache defaults.
    $maxAge = FALSE;
    $cacheTags = [];

    // Match {{ current_year }}.
    if (preg_match_all('/{{[\\s]*current_year[\\s]*}}/u', $text, $matches_code)) {
      foreach ($matches_code[0] as $ci => $code) {
        $text = str_replace($code, date('Y', time()), $text);
      }

      $nextYear = strtotime((date('Y', time()) + 1) . '-01-01 00:00:00');
      $maxAge = $nextYear - time();
    }

    // Find internal node links and convert them to their named routes.
    if (preg_match_all('/href=[\'"]\\/?node\\/([0-9]+)[\'"]/u', $text, $matches_code)) {
      foreach ($matches_code[1] as $ci => $nid) {
        $routeName = 'entity.node.canonical';
        $routeParameters = ['node' => $nid];
        $url = new Url($routeName, $routeParameters);
        $href = 'href="' . $url->toString() . '"';
        $text = str_replace($matches_code[0][$ci], $href, $text);
        $cacheTags[] = 'node:' . $nid;
      }
    }

    if (strlen($text) > 0) {
      $dom = new \DOMDocument();
      libxml_use_internal_errors(TRUE);
      $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
      libxml_clear_errors();
      $links = $dom->getElementsByTagName('a');
      foreach ($links as $link) {
        // Look for children images.
        $images = $link->getElementsByTagName('img');

        // Get classes.
        $classes = explode(' ', $link->getAttribute('class'));

        // text-only link.
        if ($images->count() == 0) {
          $classes[] = 'text-only';
        }
        else {
          $classes[] = 'img-link';
        }

        $link->setAttribute('class', trim(implode(' ', $classes)));
      }

      $text = $dom->saveHTML();

    }

    $result = new FilterProcessResult($text);

    if ($maxAge !== FALSE) {
      $result->setCacheMaxAge($maxAge);
    }

    if (count($cacheTags) > 0) {
      $result->setCacheTags($cacheTags);
    }

    return $result;
  }

}
