<?php

namespace Drupal\negnet_utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Restricted Access Class.
 */
class RestrictedAccess {

  /**
   * Checks against the nodes password.
   */
  public static function checkPassword(Node $node, $hash) {
    $config = \Drupal::config('negnet_utility.restricted_access');
    $actual_password = $config->get('password.' . $node->id());

    if (self::hash($actual_password) === $hash) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Sets the cookie.
   */
  public static function setCookie(Node $node) {
    $config = \Drupal::config('negnet_utility.restricted_access');
    $actual_password = $config->get('password.' . $node->id());
    setcookie('anid_' . $node->id(), self::hash($actual_password), time() + (60 * 60));
  }

  /**
   * Hashes a password.
   */
  public static function hash($password) {
    return md5($password);
  }

  /**
   * Checks if the current user has access to the node.
   */
  public static function userHasAccessToNode(Node $node) {

    if ($node->access('update')) {
      return TRUE;
    }

    if (!isset($_COOKIE['anid_' . $node->id()])) {
      return FALSE;
    }

    return self::checkPassword($node, $_COOKIE['anid_' . $node->id()]);
  }

  /**
   * Checks if a node is restricted.
   */
  public static function nodeIsRestricted(Node $node) {
    $config = \Drupal::config('negnet_utility.restricted_access');
    return ($config->get('access.' . $node->id()) !== NULL) ? $config->get('access.' . $node->id()) : FALSE;
  }

  /**
   * Validates restricted access form.
   */
  public static function nodeFormValidate($form, FormStateInterface $form_state) {
    $submit_handlers = $form_state->getSubmitHandlers();
    array_unshift($submit_handlers, 'Drupal\negnet_utility\RestrictedAccess::nodeFormSubmit');
    $form_state->setSubmitHandlers($submit_handlers);

    if ($form_state->hasValue('restricted_enabled')) {
      $enabled = $form_state->getValue('restricted_enabled');

      if ($enabled) {
        if (!$form_state->hasValue('restricted_password')) {
          $form_state->setErrorByName('restricted_password', t('You must set a password to enable restricted access!'));
        }

        $password = $form_state->getValue('restricted_password');
        if (strlen($password) === 0) {
          $form_state->setErrorByName('restricted_password', t('You must set a password to enable restricted access!'));
        }
      }
    }
  }

  /**
   * Submit handler for restricted access form.
   */
  public static function nodeFormSubmit($form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->getEditable('negnet_utility.restricted_access');
    $node = $form_state->getFormObject()->getEntity();

    if ($form_state->hasValue('restricted_enabled')) {
      $enabled = $form_state->getValue('restricted_enabled');

      if ($enabled) {
        $config->set('access.' . $node->id(), $enabled);
      }
      else {
        $config->clear('access.' . $node->id());
      }
    }

    if ($form_state->hasValue('restricted_password')) {
      $password = $form_state->getValue('restricted_password');

      if (strlen($password) > 0) {
        $config->set('password.' . $node->id(), $password);
      }
      else {
        $config->clear('password.' . $node->id());
      }
    }

    $config->save();
  }

}
