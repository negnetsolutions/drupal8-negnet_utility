<?php

namespace Drupal\negnet_utility\TwigExtension;

/**
 * Class SvgFilter.
 *
 * @package Drupal\negnet_utility\TwigExtension
 */
class SvgFilter extends \Twig_Extension {

  /**
   * Implements getFunctions().
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
          'svg',
          [$this, 'renderSvg'],
          ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   Twig Name.
   */
  public function getName() {
    return 'neg_utilities.twig_extension.svg';
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
  public static function renderSvg($svg) {
    $theme_handler = \Drupal::service('theme_handler');
    $default_theme = $theme_handler->getDefault();
    $theme_path = $theme_handler->getTheme($default_theme)->getPath();

    $file = $theme_path . $svg;

    if (!is_file($file)) {
      throw new \Exception("$file doesn't exist!");
    }

    return file_get_contents($file);
  }

}
