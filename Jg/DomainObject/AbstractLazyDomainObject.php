<?php
/**
 * Abstract class for a simple domain object with support for lazy-loaded properties.
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    DomainObject
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

abstract class Jg_DomainObject_LazyAbstractDomainObject extends Jg_DomainObject_AbstractDomainObject {

  /**
   * Define a list of lazy-loaded property names.
   *
   * @var array
   */
  protected $_lazy_properties = array();

  /**
   * @var array A list of the lazy properties which have actually been loaded
   */
  protected $_loaded_lazy_properties = array();

  /**
   * The name of the database adapter to use when lazy-loading object properties
   * (this only works if the getter methods in child classes respect it).
   *
   * @var string A registered database adapter name.
   */
  protected $_lazy_load_db_name = 'db_slave';

  /**
   * Magic set method
   *
   * @param string $name
   * @param mixed $value
   * @return void
   * @throws Jg_DomainObject_Exception if property is invalid
   * @todo Mark properties as "dirty" when they are set, so the mapper can check which properties have changed and save only those. Need a markClean() method so properties can be unmarked as dirty after populating initial values.
   */
  public function __set($name, $value) {
    if ($this->_isLazyProperty($name)) {
      $this->_setLazyProperty($name, $value);
    } else {
      parent::__set($name, $value);
    }
  }

  /**
   * Magic get method
   *
   * @param string $name
   * @return mixed
   */
  public function __get($name) {
    if ($this->_isLazyProperty($name)) {
      return $this->_getLazyProperty($name);
    }
    return parent::__get($name);
  }

  protected function _setLazyProperty($name, $value) {
    $setter_method_name = $this->_getLazyPropertySetterName($name);
    if (method_exists($this, $setter_method_name)) {
      $this->$setter_method_name($value);
    } else {
      throw new Jg_DomainObject_Exception(__METHOD__ . ': No setter method exists for lazy property "' . $name . '"');
    }
  }

  protected function _getLazyProperty($name) {
    $getter_method_name = $this->_getLazyPropertyGetterName($name);
    if (method_exists($this, $getter_method_name)) {
      $this->_setLazyPropertyLoaded($name);
      return $this->$getter_method_name($name);
    }
    return null;
  }

  /**
   * Magic isset method
   *
   * @param string $name
   * @return boolean
   */
  public function __isset($name) {
    return (isset($this->_data[$name]) || $this->_isLazyPropertyLoaded($name));
  }

  protected function _getLazyPropertyGetterName($property) {
    return '_get' . $this->_convertUnderscoresToCamelCase($property);
  }

  protected function _getLazyPropertySetterName($property) {
    return '_set' . $this->_convertUnderscoresToCamelCase($property);
  }

  protected function _getLazyPropertySaverName($property) {
    return '_save' . $this->_convertUnderscoresToCamelCase($property);
  }

  protected function _convertUnderscoresToCamelCase($word) {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
  }

  protected function _getLoadedLazyProperties() {
    return array_keys($this->_loaded_lazy_properties);
  }

  protected function _isLazyPropertyLoaded($name) {
    return (in_array($name, $this->_getLoadedLazyProperties()));
  }

  protected function _setLazyPropertyLoaded($name, $is_loaded = true) {
    if (!$this->_isLazyProperty($name)) {
      throw new Jg_DomainObject_Exception(__METHOD__ . ': ' . $name . ' is not a valid lazy property name');
    }

    if ($is_loaded && !$this->_isLazyPropertyLoaded($name)) {
      $this->_loaded_lazy_properties[$name] = true;

    } elseif (!$is_loaded && $this->_isLazyPropertyLoaded($name)) {
      unset($this->_loaded_lazy_properties[$name]);
    }
  }

  protected function _isLazyProperty($name) {
    return (in_array($name, $this->_lazy_properties));
  }

  /**
   * Save the object to persistent storage (i.e. the database)
   *
   * @return void
   */
  public function save() {
    parent::save();
    
    // Save the lazy-loaded properties too (those which have been loaded and have a saver method)
    foreach ($this->_getLoadedLazyProperties() as $property) {
      $method_name = $this->_getLazyPropertySaverName($property);
      if (method_exists($this, $method_name)) {
        $this->$method_name();
      }
    }
  }

  /**
   * Specify the database adapter from which lazy loaded properties should be retrieved.
   *
   * @param string $db_name A registered database adapter name.
   * @throws Jg_DomainObject_Exception if $db_name isn't valid
   * @return void
   */
  public function setLazyLoadDb($db_name) {
    if (Zend_Registry::isRegistered($db_name) && Zend_Registry::get($db_name) instanceof Zend_Db_Adapter_Abstract) {
      $this->_lazy_load_db_name = $db_name;
    } else {
      throw new Jg_DomainObject_Exception(__METHOD__ . ': "' . $db_name . '" is not a registered database adapter name');
    }
  }

}
