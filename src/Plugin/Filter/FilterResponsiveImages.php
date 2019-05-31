<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Text filter for responsive images.
 */

/**
 * Class FilterResponsiveImages.
 *
 * @Filter(
 *   id = "filter_responsive_images",
 *   title = @Translation("Responsive Images Filter"),
 *   description = @Translation("Converts img tags to responsive images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterResponsiveImages extends FilterBase {

  /**
   * Implements process().
   */
  public function process($text, $langcode) {

    $dom = new \DOMDocument();
    $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
    $images = $dom->getElementsByTagName('img');
    foreach ($images as $image) {
      $classes = explode(' ', $image->getAttribute('class'));
      if (!in_array('responsive-image', $classes)) {
        // Need to process.
        $src = $image->getAttribute('src');
        $alt = $image->getAttribute('alt');
        $title = $image->getAttribute('title');

        if (substr($src, 0, 4) === 'http') {
          // Check to see if the path is absolute to this site.
          $host = \Drupal::request()->getSchemeAndHttpHost();
          if (substr($src, 0, strlen($host)) === $host) {
            // This is an absolute path to a local file.
            $src = str_replace($host, '', $src);
          }
          else {
            continue;
          }
        }

        $imgf = strstr($src, '?', TRUE);
        if ($imgf !== FALSE) {
          $src = $imgf;
        }

        // Make sure we are dealing with a managed image.
        if (substr($src, 0, 20) !== '/sites/default/files') {
          continue;
        }

        $src = str_replace('/sites/default/files', '', $src);

        $uri = "public:/" . $src;

        $new_image = [
          '#responsive_image_style_id' => isset($this->settings['responsive_image_style']) ? $this->settings['responsive_image_style'] : NULL,
          '#uri' => $uri,
          '#theme' => 'responsive_image',
          '#width' => NULL,
          '#height' => NULL,
          '#attributes' => [
            'class' => $classes,
          ],
        ];

        if (strlen($title) > 0) {
          $new_image['#attributes']['title'] = $title;
        }
        if (strlen($alt) > 0) {
          $new_image['#attributes']['alt'] = $alt;
        }

        $i = \Drupal::service('image.factory')->get($uri);
        if ($i->isValid()) {
          $new_image['#height'] = $i->getHeight();
          $new_image['#width'] = $i->getWidth();
        }

        $replacement = (string) \Drupal::service('renderer')->render($new_image);
        $this->setInnerHtml($dom, $image, $replacement);
      }
    }

    $text = $dom->saveHTML();

    return new FilterProcessResult($text);
  }

  /**
   * Sets innerHtml on DOMObject.
   */
  protected function setInnerHtml(&$dom, $el, $newInnerHTML) {
    $tmpDoc = new \DOMDocument();
    $tmpDoc->loadHTML(mb_convert_encoding($newInnerHTML, 'HTML-ENTITIES', 'UTF-8'));
    foreach ($tmpDoc->getElementsByTagName('img') as $node) {
      $newElement = $dom->importNode($node, TRUE);
      $el->parentNode->insertBefore($newElement, $el);
    }
    $el->parentNode->removeChild($el);

    return $newElement;
  }

  /**
   * Implements settingsForm().
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();
    $responsive_image_styles = [];
    foreach ($image_styles as $id => $style) {
      $responsive_image_styles[$id] = $style->label();
    }

    $form['responsive_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive Image Style to Use'),
      '#options' => $responsive_image_styles,
      '#required' => TRUE,
      '#default_value' => isset($this->settings['responsive_image_style']) ? $this->settings['responsive_image_style'] : '',
    ];
    return $form;
  }

}
