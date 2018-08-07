<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Filter(
 *   id = "filter_responsive_images",
 *   title = @Translation("Responsive Images Filter"),
 *   description = @Translation("Converts img tags to responsive images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterResponsiveImages extends FilterBase {
  public function process($text, $langcode) {

    if( preg_match_all('/<img[\\w\\W]+?class="([a-zA-Z-_0-9]+)"[\\w\\W]+?src="\/sites\/default\/files\/([\\w\\s-?=\\.\/]+)"[\\w\\W]+?\/>/u', $text, $matches_code) ){
      foreach($matches_code[0] as $ci => $code){
        //get image filename
        $img = $matches_code[2][$ci];
        $class = $matches_code[1][$ci];
        $classes = explode(' ',$class);

        //remove any parameters
        $imgf = strstr($img, '?', true);
        if($imgf !== false){
          $img = $imgf;
        }

        $uri = "public://".$img;

        $image = array(
          '#responsive_image_style_id' => isset($this->settings['responsive_image_style']) ? $this->settings['responsive_image_style'] : null,
          '#uri' => $uri,
          '#theme' => 'responsive_image',
          '#width' => null,
          '#height' => null,
          '#attributes' => [
            'class' => $classes,
          ],
        );

        $i = \Drupal::service('image.factory')->get($uri);
        if($i->isValid()){
          $image['#height'] = $i->getHeight();
          $image['#width'] = $i->getWidth();
        }

        $replacement = \Drupal::service('renderer')->render($image);
        $text = str_replace($code, $replacement, $text);

      }
    }

    return new FilterProcessResult($text);
  }
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();
    $responsive_image_styles = array();
    foreach($image_styles as $id => $style){
      $responsive_image_styles[$id] = $style->label();
    }

    $form['responsive_image_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Responsive Image Style to Use'),
      '#options' => $responsive_image_styles,
      '#required' => TRUE,
      '#default_value' => isset($this->settings['responsive_image_style']) ? $this->settings['responsive_image_style'] : '',
    );
    return $form;
  }
}
