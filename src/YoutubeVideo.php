<?php

namespace Drupal\negnet_utility;

use Drupal\Core\File\FileSystemInterface;

/**
 * Class YoutubeVideo.
 */
class YoutubeVideo {

  const CLIENTKEY = 'AIzaSyC_HfswGm350J3vb3_o8h92aNVw1fN9FPM';
  const IMAGE_DIRECTORY = 'youtube_images';
  const CACHETIMEOUT = 300;
  protected $data;
  protected $id;
  protected $client;

  /**
   * Implements __construct().
   */
  public function __construct($url) {

    $this->client = $this->getClient();
    $this->id = $this->getVideoId($url);

    if (strlen($this->id) === 0) {
      throw new \Exception("Could not find or access youtube video at $url!");
    }

    $this->data = $this->fetchDetails($this->id);
  }

  /**
   * Gets the youtube client.
   */
  protected function getClient() {
    $client = new \Google_Client();
    $client->setApplicationName('Drupal8 Video Paragraph');
    $client->setDeveloperKey(self::CLIENTKEY);

    $service = new \Google_Service_YouTube($client);
    return $service;
  }

  /**
   * Gets the video id from the video url.
   */
  protected function getVideoId($url) {

    if (strstr($url, 'youtube.com/embed') !== FALSE) {
      return substr($url, strrpos($url, '/') + 1);
    }
    elseif (strstr($url, 'youtu.be') !== FALSE) {
      return substr($url, strrpos($url, '/') + 1);
    }
    elseif (preg_match('/\\?v=([a-zA-Z-_0-9]+)/u', $url, $matches) !== FALSE) {
      return $matches[1];
    }

    return FALSE;
  }

  /**
   * Fetches details from vimeo.
   */
  protected function fetchDetails($id) {

    $cid = 'youtube.fetchall.' . md5($id);

    if ($cache = \Drupal::cache()->get($cid)) {
      // We have cache!
      return $cache->data;
    }

    $queryParams = [
      'id' => $id,
    ];

    try {
      $response = $this->client->videos->listVideos('snippet', $queryParams);
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $items = $response->getItems();

    if (count($items) === 0) {
      throw new \Exception('Could not locate youtube video!');
    }

    $video = $items[0];
    $data = $video->snippet;

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
      '#width' => $largest->getWidth(),
      '#height' => $largest->getHeight(),
      '#responsive_image_style_id' => 'rs_image',
      '#uri' => $this->getImageUri(),
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
    return $largest->getUrl();
  }

  /**
   * Gets the correct external image size.
   */
  protected function getLargestExternalImage() {
    $thumbnails = $this->data['thumbnails'];

    if (isset($thumbnails['maxres'])) {
      return $this->data->thumbnails->getMaxres();
    }

    if (isset($thumbnails['high'])) {
      return $this->data->thumbnails->getHigh();
    }

    if (isset($thumbnails['medium'])) {
      return $this->data->thumbnails->getMedium();
    }

    if (isset($thumbnails['default'])) {
      return $this->data->thumbnails->getDefault();
    }

    if (isset($thumbnails['standard'])) {
      return $this->data->thumbnails->getStandard();
    }

    throw new \Exception("Could not load thumbnail from youtube for video: " . $this->id);
  }

  /**
   * Gets the image filename.
   */
  protected function getImageFilename() {
    $path = parse_url($this->getExternalImageUri());
    $parts = pathinfo($path['path']);
    $extension = $parts['extension'];
    $name = $this->id . '.' . $parts['extension'];
    return $name;
  }

}
