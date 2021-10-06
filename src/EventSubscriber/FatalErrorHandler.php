<?php

namespace Drupal\negnet_utility\EventSubscriber;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Utility\Error;
use Drupal\negnet_utility\Postage;

/**
 * Last-chance handler for exceptions.
 *
 * This handler will catch any exceptions not caught elsewhere and send a themed
 * error page as a response.
 */
class FatalErrorHandler extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // A very low priority so that custom handlers are almost certain to fire
    // before it, even if someone forgets to set a priority.
    return -255;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return [
      'html',
    ];
  }

  /**
   * The default 500 content.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on500(GetResponseForExceptionEvent $event) {
    $content = file_get_contents(__DIR__ . '/../../templates/500-error.html');
    $response = new Response($content, 500);
    $event->setResponse($response);
  }

  /**
   * Sends message to postage.
   */
  protected function sendError($exception) {
    $error = Error::decodeException($exception);

    $error['%host'] = \Drupal::request()->getSchemeAndHttpHost();
    unset($error['backtrace']);
    $message = new FormattableMarkup('%host: %type: @message in %function (line %line of %file). <pre class="backtrace"> @backtrace_string </pre>', $error);

    $postage = new Postage();
    $postage->setKey('1435301-2725-7848-6672-574100246885');
    $postage->addFrom('andy@negnet.co', 'Drupal Fatal Error');
    $postage->addTo('andy@negnet.co', 'Andrew Johnson');
    $postage->subject($error['%host'] . ': FATAL ERROR');
    $postage->messageHtml($message);

    $postage->send();
  }

  /**
   * Wrapper for error_displayable().
   *
   * @param $error
   *   Optional error to examine for ERROR_REPORTING_DISPLAY_SOME.
   *
   * @return bool
   *
   * @see \error_displayable
   */
  protected function isErrorDisplayable($error) {
    return error_displayable($error);
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    if ($exception instanceof HttpExceptionInterface) {
      $code = $exception->getStatusCode();
      // Only run on true error 500 exceptions.
      if ($code < 500 || $code >= 600) {
        return;
      }
    }

    // Only show error (and send to postage) if we can't show the error to the screen.
    if (!$this->isErrorDisplayable($error)) {
      $this->sendError($exception);
      $this->on500($event);
    }
  }

}
