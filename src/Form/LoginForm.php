<?php

namespace Drupal\negnet_utility\Form;

use Drupal\negnet_utility\RestrictedAccess;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Login form.
 */
class LoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (!isset($_GET['return'])) {
      throw new NotFoundHttpException();
    }

    if (preg_match('/[0-9]+/u', $_GET['return']) == 0) {
      throw new NotFoundHttpException();
    }

    $form['text'] = [
      '#markup' => '<p>Please enter the password to proceed.</p>',
    ];
    $form['password'] = [
      '#type' => 'password',
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'webform-component',
          'webform-component-textfield',
          'webform-component--password',
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $password = $form_state->getValue('password');

    if (strlen($password) === 0) {
      $form_state->setErrorByName('password', t('Please enter a password to login!'));
    }

    preg_match('/[0-9]+/u', $_GET['return'], $matches);
    $nid = $matches[0];
    $node = Node::load($nid);
    if (!$node) {
      $form_state->setErrorByName('password', t('Could not load content. Please try again!'));
      return;
    }

    if (!RestrictedAccess::checkPassword($node, RestrictedAccess::hash($password))) {
      $form_state->setErrorByName('password', t('Incorrect password!'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    preg_match('/[0-9]+/u', $_GET['return'], $matches);
    $nid = $matches[0];
    $node = Node::load($nid);

    RestrictedAccess::setCookie($node);

    // Return to node.
    $response = new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString());
    $response->send();
    exit;
  }

}
