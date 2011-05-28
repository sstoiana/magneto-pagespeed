<?php

class Magneto_PageSpeed_Model_Design_Package extends Mage_Core_Model_Design_Package
{
    /**
     * Get skin file url
     *
     * @param string $file
     * @param array $params
     * @return string
     */
    public function getSkinUrl($file = null, array $params = array())
    {
		return Mage::helper('pagespeed')->getTimestampedUrl(parent::getSkinUrl($file, $params));
    }

    /**
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $targetFilename = md5(implode(',', $files)) . '.js';
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }

        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, array($this, 'beforeMergeJs'), 'js')) {
            return Mage::helper('pagespeed')->getTimestampedUrl(Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename);
        }
        return '';
    }

    /**
     * Merge specified css files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        // secure or unsecure
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }

        // base hostname & port
        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port = parse_url($baseMediaUrl, PHP_URL_PORT);
        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }

        // merge into target file
        $targetFilename = md5(implode(',', $files) . "|{$hostname}|{$port}") . '.css';
		if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, array($this, 'beforeMergeCss'), 'css')) {
            return Mage::helper('pagespeed')->getTimestampedUrl($baseMediaUrl . $mergerDir . '/' . $targetFilename);
        }
        return '';
    }

    /**
     * Before merge js callback function
     *
     * @param string $file
     * @param string $contents
     * @return string
     */
    public function beforeMergeJs($file, $contents)
    {
       $this->_setCallbackFileDir($file);
	   
       $temp_file = tempnam(sys_get_temp_dir(), 'mrgjs');
	   file_put_contents($temp_file, $contents);

       return shell_exec("java -jar lib/yuicompressor-2.4.6/build/yuicompressor-2.4.6.jar --type js $temp_file");
    }

    /**
     * Before merge css callback function
     *
     * @param string $file
     * @param string $contents
     * @return string
     */
    public function beforeMergeCss($file, $contents)
    {
       $this->_setCallbackFileDir($file);

       $cssImport = '/@import\\s+([\'"])(.*?)[\'"]/';
       $contents = preg_replace_callback($cssImport, array($this, '_cssMergerImportCallback'), $contents);

       $cssUrl = '/url\\(\\s*([^\\)\\s]+)\\s*\\)?/';
       $contents = preg_replace_callback($cssUrl, array($this, '_cssMergerUrlCallback'), $contents);

       $temp_file = tempnam(sys_get_temp_dir(), 'mrgcss');
	   file_put_contents($temp_file, $contents);

       return shell_exec("java -jar lib/yuicompressor-2.4.6/build/yuicompressor-2.4.6.jar --type css $temp_file");
    }

    /**
     * Prepare url for css replacement
     *
     * @param string $uri
     * @return string
     */
    protected function _prepareUrl($uri)
    {
        return Mage::helper('pagespeed')->getTimestampedUrl(parent::_prepareUrl($uri));
    }
}
