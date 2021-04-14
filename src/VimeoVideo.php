<?php

namespace Drupal\negnet_utility;

use Vimeo\Vimeo;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class VimeoVideo.
 */
class VimeoVideo {

  const CLIENTID = '33005cef24ead0acb7ade6b3cb83bd6f7c689145';
  const SECRETID = 'O376Z7N5c2JmjX/E3XxUOz83IpULqYeyfj01bFyyxt0q/t4Aye25lVdemZoMdhPK3+xtwL+wiVXp+75g9TLaWlqBZpl8t7zM8Zi0Mbmfcd7dBDxu6ARYnVpXSU2x1H2w';
  const CONFIG_KEY = 'vimeo.config';
  const VIMEOSCOPE = 'public*';
  const IMAGE_DIRECTORY = 'vimeo_images';
  const CACHETIMEOUT = 300;
  protected $data;
  protected $id;

  /**
   * Implements __construct().
   */
  public function __construct($url) {

    $this->config = \Drupal::config(self::CONFIG_KEY);
    $this->vimeo = new Vimeo(self::CLIENTID, self::SECRETID);

    $components = parse_url($url);
    $parts = explode('/', $components['path']);
    $last = end($parts);
    $this->id = preg_replace('/\D/', '', $last);

    if (strlen($this->id) === 0) {
      throw new \Exception("Could not find or access vimeo video at $url!");
    }

    $this->data = $this->fetchDetails('/videos/' . $this->id, [], 'GET');
  }

  /**
   * Fetches details from vimeo.
   */
  protected function fetchDetails($request, array $params, $type, array $data = []) {

    $cid = 'vimeo.fetchall.' . md5($request);

    if ($cache = \Drupal::cache()->get($cid)) {
      // We have cache!
      return $cache->data;
    }

    try {
      $response = $this->vimeo->request($request, $params, $type);
    }
    catch (\Exception $e) {
      throw new \Exception("Could not make request to vimeo!");
    }

    if (isset($response['body']['error'])) {
      throw new \Exception($response['body']['error']);
    }

    if (isset($data['body']['paging']) && isset($data['body']['paging']['next']) && $data['body']['paging']['next'] !== NULL) {
      $data = array_merge($data, $response['body']['data']);
      $data = $this->fetchDetails($data['body']['paging']['next'], $params, $type, $data);
    }
    else {
      $data = $response['body'];
    }

    $expire = time() + self::CACHETIMEOUT;
    \Drupal::cache()->set($cid, $data, $expire);

    return $data;
  }

  /**
   * Sets the access token.
   */
  protected function setAccessToken() {
    $token = $this->getAccessToken();
    $this->vimeo->setToken($token['body']['access_token']);
  }

  /**
   * Gets vimeo access token.
   */
  protected function getAccessToken() {
    $access_token = $this->config->get('access_token');

    if ($access_token === NULL) {
      // Let's fetch one.
      $access_token = $this->vimeo->clientCredentials(self::VIMEOSCOPE);

      if (!isset($access_token['body']['access_token'])) {
        throw new \Exception("Could not get access token from vimeo!");
      }

      $config = \Drupal::service('config.factory')->getEditable(self::CONFIG_KEY);
      $config->set('access_token', $access_token)->save();
    }

    return $access_token;
  }

  /**
   * Get a Vimeo Link.
   */
  public function getLink() {
    $uri = $this->data['uri'];
    return 'https://vimeo.com/' . basename($uri);
  }

  /**
   * Get the name.
   */
  public function getName() {
    return $this->data['name'];
  }

  /**
   * Get Tags.
   */
  public function getTags() {
    $tags = [];
    $raw = $this->data['tags'];

    foreach ($raw as $tag) {
      $tags[] = [
        'name' => $tag['name'],
        'canonical' => $tag['canonical'],
      ];
    }

    return $tags;
  }

  /**
   * Renders the image.
   */
  public function render() {
    $render = [
      '#theme' => 'shop_vimeo_video',
      '#title' => $this->getName(),
      '#link' => $this->getLink(),
      '#image' => $this->renderCoverImage(),
      '#cache' => ['max-age' => self::CACHETIMEOUT],
    ];

    return $render;
  }

  /**
   * Gets the cover image.
   */
  public function renderCoverImage() {
    if (!$this->isImageDownloaded()) {
      if (!$this->downloadImage()) {
        return [];
      }
    }

    $largest = $this->getLargestExternalImage();

    $image = [
      '#theme' => 'responsive_image',
      '#width' => $largest['width'],
      '#height' => $largest['height'],
      '#responsive_image_style_id' => 'rs_image',
      '#uri' => $this->getImageUri(),
      '#attributes' => [
        'alt' => $this->getName(),
      ],
    ];

    return $image;
  }

  /**
   * Checks to see if downloaded image exists.
   */
  protected function isImageDownloaded() {
    $uri = $this->getImageUri();
    if (is_file($uri)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Fetches remote image.
   */
  protected function downloadImage() {
    $external_uri = $this->getExternalImageUri();

    if (\Drupal::service('file_system')->prepareDirectory($this->getImageDirectory(), FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      return $this->fetch(
        $this->getExternalImageUri(),
        $this->getImageUri()
      );
    }

    return FALSE;
  }

  /**
   * Fetches external link to cachepath.
   */
  protected function fetch($url, $cachepath) {
    $http   = \Drupal::httpClient();
    $result = $http->request('get', $url);
    $code   = floor($result->getStatusCode() / 100) * 100;
    if (!empty($result->getBody()) && $code != 400 && $code != 500) {
      return \Drupal::service('file_system')->saveData($result->getBody(), $cachepath, FileSystemInterface::EXISTS_REPLACE);
    }

    return FALSE;
  }

  /**
   * Gets image storage path.
   */
  protected function getImageDirectory() {
    $default_scheme = \Drupal::config('system.file')->get('default_scheme');
    return $default_scheme . '://' . self::IMAGE_DIRECTORY;
  }

  /**
   * Gets local image path.
   */
  protected function getImageUri() {
    return $this->getImageDirectory() . '/' . $this->getImageFilename();
  }

  /**
   * Gets the external vimeo image uri.
   */
  protected function getExternalImageUri() {
    $largest = $this->getLargestExternalImage();
    return $largest['link'];
  }

  /**
   * Gets the correct external image size.
   */
  protected function getLargestExternalImage() {
    $sizes = $this->data['pictures']['sizes'];
    return end($sizes);
  }

  /**
   * Gets the image filename.
   */
  protected function getImageFilename() {
    $path = parse_url($this->getExternalImageUri());
    $path = $path['path'];
    return basename($path);
  }

}
