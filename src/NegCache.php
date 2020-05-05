<?php

namespace Drupal\negnet_utility;

use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\Cache;

/**
 * Cache helper.
 */
class NegCache {

  /**
   * Invalidates a taxonomy term.
   */
  public static function invalidateTerm(Term $term) {
    Cache::invalidateTags(['taxonomy_term:' . $term->getVocabularyId()]);
  }

  /**
   * Invalidates a node.
   */
  public static function invalidateNode(Node $node) {
    Cache::invalidateTags(['node:' . $node->getType()]);
  }

}
