<?php

namespace Drupal\negnet_utility;

/**
 * Popup Class.
 */
class Popup {

  /**
   * Renders a popup.
   */
  public static function render($entity, &$variables) {
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($entity, 'full');
    $variables['page_bottom'][] = $build;
  }

}
