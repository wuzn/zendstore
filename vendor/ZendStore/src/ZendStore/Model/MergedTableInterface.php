<?php

namespace ZendStore\Model;

use Zend\Db\TableGateway\TableGatewayInterface;

interface MergedTableInterface
{
	/**
	 * Get a joined table
	 *
	 * @param string $name
	 * @return TableGatewayInterface
	 * @throws Exception\JoinedTableNotFoundException
	 */
	public function getJoinedTable($name);
	
	/**
	 * Set a joined table
	 *
	 * @param string $name
	 * @param TableGatewayInterface $table
	 * @return MergedTableInterface
	 */
	public function setJoinedTable($name, TableGatewayInterface $table);
	
	/**
	 * Get joinded tables
	 * 
	 * @return array
	 */
	public function getJoinedTables();
}