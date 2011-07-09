<?php
/**
 * Interface for a simple domain object
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    DomainObject
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

interface Jg_DomainObject {

  /**
   * Populate object properties from an array.
   *
   * @param array $data
   * @return Jg_DomainObject The object itself. Provides a fluent interface.
   */
  public function populate(array $data);


  /**
   * Save the object to persistent storage (i.e. the database)
   *
   * @return void
   */
  public function save();

  /**
   * Delete the object from persistent storage (i.e. the database)
   *
   * @return void
   */
  public function delete();


  /**
   * Convert the object to an array. It should be possible to pass the result to populate().
   *
   * @return array
   */
  public function toArray();

  /**
   * Get the ID of the domain object
   *
   * @return mixed
   */
  public function getId();

}
