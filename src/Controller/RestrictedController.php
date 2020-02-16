<?php

namespace Drupal\negnet_utility\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Restricted Access Controller.
 */
class RestrictedController extends ControllerBase {

  /**
   * Renders restricted access page.
   */
  public function renderAccessPage() {
    $form = \Drupal::formBuilder()->getForm('Drupal\negnet_utility\Form\LoginForm');

    $variables['#attached']['library'][] = 'neg_paragraphs/reset';
    $variables['#attached']['library'][] = 'negnet_utility/grid';
    return [
      '#theme' => 'negnet_restricted_access',
      '#webform' => $form,
      '#attached' => [
        'library' => [
          'neg_paragraphs/reset',
          'negnet_utility/grid',
        ],
      ],
      '#cache' => [
        'tags' => ['restricted-access'],
      ],
    ];
  }

}
