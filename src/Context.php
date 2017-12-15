<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer;


class Context {
  private $metadataCollection;
  private $navigator;
  private $view = null;

  public function __construct() {
    $this->navigator = new Navigator();
  }

  /**
   * @param ClassMetadataCollection $metadataCollection
   * @return $this
   */
  public function setMetadataCollection(ClassMetadataCollection $metadataCollection) {
    $this->metadataCollection = $metadataCollection;
    $this->navigator->setMetadataCollection($metadataCollection);
    return $this;
  }

  /**
   * @return ClassMetadataCollection
   */
  public function getMetadataCollection() {
    return $this->metadataCollection;
  }

  /**
   * @return null
   */
  public function getView() {
    return $this->view;
  }

  /**
   * @param null $view
   * @return Context
   */
  public function setView($view) {
    $this->view = $view;
    return $this;
  }

  /**
   * @return Navigator
   */
  public function getNavigator(): Navigator {
    return $this->navigator;
  }
}