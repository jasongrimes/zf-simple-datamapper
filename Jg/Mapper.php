<?php
/**
 * Interface for a simple data mapper
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    Mapper
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

interface Jg_Mapper {
  
  /**
   * Get a domain object by its ID (i.e. primary key)
   *
   * @param mixed $id The primary key value
   * @return Jg_DomainObject A domain object, or null if no object was found with that ID
   */
  public function get($id);

  /**
   * Find a collection of domain objects by the specified criteria.
   *
   * @param array $criteria An array of criteria to search by
   * @param array $options An array of options, with at least the following options:<pre>
   *   get_lazy_collection - Boolean. Whether to retrieve a Jg_Mapper_LazyCollection instead of a Jg_Mapper_Collection. Default false.
   * </pre>
   * @return Jg_Mapper_Collection A collection of domain objects, or an empty collection if no objects were found
   */
  public function findBy(array $criteria, $options = array());

  /**
   * Get a collection of all domain objects.
   *
   * @param array $options An array of options, with at least the following options:<pre>
   *   get_lazy_collection - Boolean. Whether to retrieve a Jg_Mapper_LazyCollection instead of a Jg_Mapper_Collection. Default false.
   * </pre>
   * @return Jg_Mapper_Collection
   */
  public function findAll($options = array());

  /**
   * Save a domain object. Modifies the passed object if saving caused any properties
   * to change (ex. set the ID of a new object)
   * 
   * @param Jg_DomainObject $object
   * @return void
   * @throws Exception if $object is of the wrong type
   */
  public function save(Jg_DomainObject $object);

  /**
   * Delete a domain object from the database (or mark it as deleted)
   * 
   * @param Jg_DomainObject $object
   * @return void
   * @throws Exception if $object is of the wrong type
   */
  public function delete(Jg_DomainObject $object);

  /**
   * Create a domain object instance from the specified data.
   * 
   * Normally, this method should not be called directly. It is used internally
   * to get additional data that wasn't retrieved by the select object, and by
   * Jg_Mapper_Collection to create objects as they are needed.
   *
   * @param array $data
   * @return Jg_DomainObject
   */
  public function createObject(array $data);

  /**
   * Get the class name of the domain objects this mapper works with.
   *
   * @return string
   */
  public function getDomainObjectClass();

}
