<?php

namespace Drupal\negnet_utility;

use Drupal\Core\Render\RenderContext;

/**
 * Persistent render cache generator.
 */
class PersistentRenderCache {

  /**
   * Clears a view cache.
   */
  public static function clearCachedView($entity, $view_mode) {
    return self::getCacheBin()->delete(self::getCacheKey($entity, $view_mode));
  }

  /**
   * Get's a cached view of an entity.
   */
  public static function getCachedView($entity, $view_mode, $overwriteCache = FALSE) {

    $rendered_view = NULL;

    if ($overwriteCache === FALSE) {
      $rendered_view = self::getCache($entity, $view_mode);
    }

    if (!$rendered_view) {
      // We don't have cache. Let's build it.
      $build = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);

      $rendered_view = NULL;
      \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use (&$build, &$rendered_view) {
        $rendered_view = render($build);
      });

      self::getCacheBin()->set(self::getCacheKey($entity, $view_mode), $rendered_view);
    }

    return [
      '#markup' => $rendered_view,
      '#cache' => [
        'tags' => $entity->getCacheTags(),
      ],
    ];
  }

  /**
   * Gets a cache key for an entity.
   */
  protected static function getCacheKey($entity, $view_mode) {
    return 'persistent_entity_view:' . $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $view_mode;
  }

  /**
   * Get's a cached view.
   */
  protected static function getCache($entity, $view_mode) {
    $cache = self::getCacheBin()->get(self::getCacheKey($entity, $view_mode));

    if (empty($cache)) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * Get's the cache bin.
   */
  protected static function getCacheBin() {
    return \Drupal::state();
  }

}
