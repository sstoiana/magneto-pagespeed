<?php 

class Magneto_PageSpeed_Helper_Data 
{
	public function getTimestampedUrl($url)
	{
		if(!$timestamp = Mage::app()->loadCache('getSkinUrlTs')) 
		{
			$timestamp = time();
			Mage::app()->saveCache($timestamp, 'getSkinUrlTs', array(), 604800);
		}
		return $url."?ts={$timestamp}";
	}
}
