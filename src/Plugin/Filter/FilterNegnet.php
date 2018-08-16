<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Filter(
 *   id = "filter_negnet",
 *   title = @Translation("Negnet Solutions Base Filters"),
 *   description = @Translation("Add several filters for basic operations such as {{ current_year }}"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterNegnet extends FilterBase
{
    public function process($text, $langcode) 
    {

        //match {{ current_year }}
        if (preg_match_all('/{{[\\s]*current_year[\\s]*}}/u', $text, $matches_code) ) {
            foreach ($matches_code[0] as $ci => $code)
            {
                $text = str_replace($code, date('Y', time()), $text);
            }
        }

        return new FilterProcessResult($text);
    }
}
