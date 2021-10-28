<?php

namespace Drupal\negnet_utility\TwigExtension;

use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Class HtmlEntities.
 *
 * @package Drupal\negnet_utility\TwigExtension
 */
class HtmlEntities extends Twig_Extension {

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   Twig Name.
   */
  public function getName() {
    return 'neg_utilities.twig_extension.html_entities';
  }

  /**
   * In this function we can declare the extension function.
   */
  public function getFilters() {
    return [
      new Twig_SimpleFilter('html_entities', [$this, 'renderEntities']),
    ];
  }

  /**
   * Renders an inline svg using the @param $svg.
   *
   * @param string $svg
   *   SVG Filename.
   *
   * @return string
   *   svg code.
   */
  public static function renderEntities($content) {
    if (is_string($content)) {
      return htmlentities($content);
    }

    return $content;
  }

}
