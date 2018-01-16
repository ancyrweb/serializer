<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;


class Context {
  private $metadataCollection;
  private $navigator;

  /**
   * If it's an array, the normalizer must use this data as the view
   * If it's a string, it's a view name, and the normalizer must map this view name to a configured
   * view in the class metadata. e.g $metadata->getViewOrNull($context->getView())
   * @var string|array
   */
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
  public function useView($view) {
    $this->view = $view;
    return $this;
  }

  /**
   * Allows the user to render custom fields
   * @param array $data
   */
  public function renderFields(array $data) {
    $this->view = $data;
  }

  /**
   * @return Navigator
   */
  public function getNavigator(): Navigator {
    return $this->navigator;
  }
}