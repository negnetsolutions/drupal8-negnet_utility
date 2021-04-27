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
    if ($entity->status->value == 1) {
      $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($entity, 'full');
    }
    else {
      $build = [
        '#type' => 'markup',
        '#markup' => '<div class="scheduled-popup" style="display: none;"></div>',
        '#cache' => [
          'contexts' => ['url'],
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }
    $variables['page_bottom'][] = $build;
  }

}
