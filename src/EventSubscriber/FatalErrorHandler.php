<?php

namespace Drupal\negnet_utility\EventSubscriber;

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
    return 1;
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

    // With verbose logging, we will also include a backtrace.
    $backtrace_exception = $exception;
    while ($backtrace_exception
      ->getPrevious()) {
      $backtrace_exception = $backtrace_exception
        ->getPrevious();
    }
    $backtrace = $backtrace_exception
      ->getTrace();

    // First trace is the error itself, already contained in the message.
    // While the second trace is the error source and also contained in the
    // message, the message doesn't contain argument values, so we output it
    // once more in the backtrace.
    array_shift($backtrace);

    // Generate a backtrace containing only scalar argument values.
    $error['@backtrace'] = Error::formatBacktrace($backtrace);
    $error['%host'] = \Drupal::request()->getSchemeAndHttpHost();
    $message = new FormattableMarkup('%host: %type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);

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

    // Only show error (and send to postage) if we can't show the error to the screen.
    if (!$this->isErrorDisplayable($error)) {
      $this->sendError($exception);
      $this->on500($event);
    }
  }

}
