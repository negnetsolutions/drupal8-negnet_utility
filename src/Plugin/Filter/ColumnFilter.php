<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @file
 * Text filter for Negnet Solutions.
 */

/**
 * Class ColumnFilter.
 *
 * @Filter(
 *   id = "filter_negnet_column",
 *   title = @Translation("Negnet Column Filter"),
 *   description = @Translation("Substitutes [column:number] with responsive columns"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class ColumnFilter extends FilterBase {

  /**
   * Implements process.
   */
  public function process($text, $langcode) {

      //find all columns
    if (preg_match_all("/(<[\\/]?p>)??\\[columns:([0-9]+)\\](<[\\/]?p>)??(.*)(<[\\/]?p>)??\\[\\/columns\\](<[\\/]?p>)??/uiUs", $text, $matches_code)) {

      foreach($matches_code[0] as $ci => $code){

        $grid_count = "cols_".implode(" ",explode('.',$matches_code[2][$ci]));
        $grid_interior = $matches_code[4][$ci];

        $replacement = '<div class="columns '.$grid_count.'">'."\n";
        $replacement .= $grid_interior;
        $replacement .= "</div>\n";

        $text = str_replace($code, $replacement, $text);
      }
    }

    $result = new FilterProcessResult($text);

    $result->setAttachments([
      'library' => ['negnet_utility/columns'],
    ]);

    return $result;
  }

}
