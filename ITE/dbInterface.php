<?php
namespace ITE;

/**
 * Interface that manages all database connections
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
interface dbInterface {
    public function select($table,$where,$order,$sum,$log);
    public function insert($tabla,$campos,$valores);
    public function update($tabla,$valores,$id, $where = "",$preselect = 1);
    public function delete($tabla,$id);
}