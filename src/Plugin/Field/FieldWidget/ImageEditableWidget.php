<?php

namespace Drupal\negnet_utility\Plugin\Field\FieldWidget;

use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * File widget with support for editing the referenced file inline.
 *
 * @FieldWidget(
 *   id = "image_editable",
 *   label = @Translation("Editable Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageEditableWidget extends ImageWidget {

  /**
   * Implements Image Process.
   */
  public static function process($element, FormStateInterface $form_state, $form) {

    $element = parent::process($element, $form_state, $form);

    if (!$element['#files']) {
      return $element;
    }

    foreach ($element['#files'] as $fid => $file) {
      /** @var \Drupal\file\FileInterface $file */
      $element['edit_button'] = [
        '#name' => "file_editable_$fid",
        '#type' => 'submit',
        '#value' => t('Edit'),
        '#ajax' => [
          'url' => Url::fromRoute('entity.file.inline_edit_form', ['file' => $fid]),
        ],
        '#access' => $file->access('update'),
      ];
    }

    return $element;
  }

}
