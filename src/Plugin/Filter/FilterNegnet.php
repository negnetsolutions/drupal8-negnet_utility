<?php

namespace Drupal\negnet_utility\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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

        //find internal node links and convert them to their named routes
        if (preg_match_all('/href=[\'"]\\/?node\\/([0-9]+)[\'"]/u', $text, $matches_code)) {
            foreach ($matches_code[1] as $ci => $nid) {
                $routeName = 'entity.node.canonical';
                $routeParameters = ['node' => $nid];
                $url = new Url($routeName, $routeParameters);
                $href = 'href="'.$url->toString().'"';
                $text = str_replace($matches_code[0][$ci], $href, $text);
            }
        }

        return new FilterProcessResult($text);
    }
}
