<?php

namespace Catalog\Model;

use ZendStore\Model\AbstractMergedTable;

class MergedProductTable extends AbstractMergedTable
{
	public function getMergedProduct()
	{
		$this->sql->select()
				  ->from($this->getJoinedTable('ProductTable')->getTable());	
	}
	
	/**
	 * Save merged model
	 * 
	 * @param MergedProduct $mergedProduct
	 * @return bool
	 */
	public function saveMergedProduct(MergedProduct $mergedProduct)
	{
		// Save Product
		$product	  = new Product();
		$productTable = new ProductTable($this->adapter);
		$product->getInputFilter()->setData($mergedProduct->toArray());
		if ($product->getInputFilter()->isValid()) {
			$product->exchangeArray($product->getInputFilter()->getValues());			
		}
		$productTable->saveProduct($product);
		echo '<pre>';print_r($product);exit;		
		// Save ProductDescription
		$productDescription 	 = new ProductDescription();
		$productDescriptionTable = new ProductDescriptionTable($this->adapter);
		$productDescription->exchangeArray($mergedProduct->toArray());
		$productDescriptionTable->saveProductDescription($droductDescription);
		
		return true;
	}
	
	public function deleteMergedProduct()
	{
	
	}
	
	/**
	 * @see AbstractMergedTable::_initializeJoinedTables()
	 */
	protected function _initializeJoinedTables()
	{
		$this->joinedTables = array(
			'ProductTable' 				=> new ProductTable($this->adapter),
			'ProductDescriptionTable'	=> new ProductDescriptionTable($this->adapter),
		);
	}

}