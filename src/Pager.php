<?php

namespace Drupal\negnet_utility;

use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * {@inheritdoc}
 */
class Pager {

  /**
   * {@inheritdoc}
   */
  protected $params;

  /**
   * {@inheritdoc}
   */
  protected $pageCount = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $params = []) {
    $defaultParams = [
      'page' => 0,
      'total' => 0,
      'perPage' => 20,
      'pagesToShow' => 9,
    ];

    $this->params = array_merge($defaultParams, $params);

  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    if (($this->params['page']) > $this->getPageCount()) {
      throw new NotFoundHttpException();
    }

    if ($this->getPageCount() < 1) {
      return NULL;
    }

    $build = [
      '#theme' => 'negnet_utility_pager',
      '#pages' => [
        '#theme' => 'item_list',
        '#items' => $this->buildPages(),
        '#attributes' => ['class' => ['pager__items', 'js-pager__items']],
      ],
      '#cache' => [
        'contexts' => ['url', 'url.query_args'],
      ],
      '#attached' => [
        'library' => 'negnet_utility/pager',
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPageCount() {
    if ($this->pageCount === NULL) {
      $this->pageCount = floor($this->params['total'] / $this->params['perPage']);

      if ($this->params['total'] == $this->params['perPage']) {
        $this->pageCount = 0;
      }
    }

    return $this->pageCount;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPages() {

    $pages = [];

    $currentPage = $this->params['page'];

    $middle = ceil($this->params['pagesToShow'] / 2);
    $current = $currentPage;
    $first = $current - $middle;
    $last = $current + $this->params['pagesToShow'] - $middle;

    $max = $this->getPageCount();

    $i = $first;
    if ($last > $max) {
      $i = $i + ($max - $last);
      $last = $max;
    }

    if ($i < 0) {
      $last = $last + (1 - $i);
      $i = 0;
    }

    if ($currentPage > 0) {
      $pages[] = $this->getPageLink(0, t('<< First'), [
        'pager__item',
        'pager__item--first',
      ]);
    }

    if (($currentPage - 1) >= 0) {
      $link = $this->getPageLink($currentPage - 1, '‹‹', [
        'pager__item',
        'pager__item--prev',
      ]);

      $link['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'link',
          '#value' => '',
          '#attributes' => [
            'rel' => 'prev',
            'href' => $this->getPageUrl($currentPage - 1)->setAbsolute(TRUE)->toString(),
          ],
        ],
        'pager_rel_prev',
      ];

      $pages[] = $link;
    }

    if ($i != $max && $i > 0) {
      $pages[] = [
        '#markup' => '…',
      ];
    }

    for (; $i <= $last && $i <= ($max); $i++) {
      $classes = [
        'pager__item',
      ];

      if ($i == $currentPage) {
        $classes[] = 'is-active';
      }

      $pages[] = $this->getPageLink($i, NULL, $classes);
    }

    if ($last < $this->getPageCount()) {
      $pages[] = [
        '#markup' => '…',
      ];
    }

    if (($currentPage + 1) <= $this->getPageCount()) {

      $link = $this->getPageLink($currentPage + 1, '››', [
        'pager__item',
        'pager__item--next',
      ]);

      $link['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'link',
          '#value' => '',
          '#attributes' => [
            'rel' => 'next',
            'href' => $this->getPageUrl($currentPage + 1)->setAbsolute(TRUE)->toString(),
          ],
        ],
        'pager_rel_next',
      ];

      $pages[] = $link;

    }

    if ($currentPage != ($this->getPageCount())) {
      $pages[] = $this->getPageLink($this->getPageCount(), t('Last >>'), [
        'pager__item',
        'pager__item--last',
      ]);
    }

    return $pages;
  }

  /**
   * {@inheritdoc}
   */
  protected function basePath() {
    $base = substr(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), 0, -1) . $_SERVER['REQUEST_URI'];

    return $base;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPageUrl($page) {
    $url = $this->basePath();
    $parts = parse_url($url);

    if (!isset($parts['query'])) {
      $parts['query'] = '';
    }

    $query = explode('&', $parts['query']);

    foreach ($query as $index => $q) {
      $p = explode('=', $q);
      if ($p[0] === 'page') {
        unset ($query[$index]);
      }
    }

    if ($page !== 0) {
      $query[] = 'page=' . $page;
    }

    $parts['query'] = implode('&', $query);

    return Url::fromUri($this->unparse_url($parts));
  }

  /**
   * {@inheritdoc}
   */
  protected function unparse_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
  }

  /**
   * {@inheritdoc}
   */
  protected function getPageLink($page, $title = NULL, $classes = [
    'pager__item',
  ]) {

    $url = $this->getPageUrl($page);

    $title = ($title === NULL) ? $page + 1 : $title;
    $link = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#attributes' => [
        'class' => $classes,
        'data-page' => $page,
      ],
    ];

    if (in_array('pager__item--next', $classes) !== FALSE) {
      $link['#attributes']['rel'] = 'next';
    }
    if (in_array('pager__item--prev', $classes) !== FALSE) {
      $link['#attributes']['rel'] = 'prev';
    }

    return $link;
  }

}
