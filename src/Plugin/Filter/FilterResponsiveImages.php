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
class FilterResponsiveImages extends FilterBase
{
    public function process($text, $langcode) 
    {

        $dom = new \DOMDocument;
        $dom->loadHTML($text);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            $classes = explode(' ', $image->getAttribute('class'));
            if (!strstr($classes, 'responsive-image')) {
                //need to process
                $src = $image->getAttribute('src');
                $imgf = strstr($src, '?', true);
                if($imgf !== false) {
                    $src = $imgf;
                }

                $src = str_replace('/sites/default/files', '', $src);

                $uri = "public:/".$src;

                $new_image = array(
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
                if ($i->isValid()) {
                      $new_image['#height'] = $i->getHeight();
                      $new_image['#width'] = $i->getWidth();
                }

                $replacement = (string) \Drupal::service('renderer')->render($new_image);
                $this->setInnerHTML($dom, $image, $replacement);
            }
        }

        $text = $dom->saveHTML();

        return new FilterProcessResult($text);
    }

    protected function setInnerHTML(&$dom, $el, $newInnerHTML)
    {
        $tmpDoc = new \DOMDocument();
        $tmpDoc->loadHTML($newInnerHTML);
        foreach ($tmpDoc->getElementsByTagName('img') as $node) {
            $newElement = $dom->importNode($node, true);
            $el->parentNode->insertBefore($newElement, $el);
        }
        $el->parentNode->removeChild($el);

        return $newElement;
    }

    public function settingsForm(array $form, FormStateInterface $form_state) 
    {
        $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();
        $responsive_image_styles = array();
        foreach($image_styles as $id => $style){
            $responsive_image_styles[$id] = $style->label();
        }

        $form['responsive_image_style'] = array(
        '#type' => 'select',
        '#title' => $this->t('Responsive Image Style to Use'),
        '#options' => $responsive_image_styles,
        '#required' => true,
        '#default_value' => isset($this->settings['responsive_image_style']) ? $this->settings['responsive_image_style'] : '',
        );
        return $form;
    }
}
