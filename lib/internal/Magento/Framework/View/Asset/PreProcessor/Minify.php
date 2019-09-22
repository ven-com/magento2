<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Asset\PreProcessor;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Code\Minifier\AdapterInterface;
use Magento\Framework\View\Asset\Minification;
use Magento\Framework\View\Asset\PreProcessor;
use Magento\Framework\View\Asset\PreProcessorInterface;

/**
 * Assets minification pre-processor
 */
class Minify implements PreProcessorInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Minification
     */
    protected $minification;

    /**
     * JS/CSS minification cache folder
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $cacheDir;

    /**
     * @param AdapterInterface $adapter
     * @param Minification $minification
     */
    public function __construct(AdapterInterface $adapter,
                                Minification $minification,
                                \Magento\Framework\Filesystem $filesystem)
    {
        $this->adapter = $adapter;
        $this->minification = $minification;
        $this->cacheDir = $filesystem->getDirectoryWrite(DirectoryList::TMP_MATERIALIZATION_DIR);
    }

    /**
     * Transform content and/or content type for the specified preprocessing chain object
     *
     * @param PreProcessor\Chain $chain
     * @return void
     */
    public function process(PreProcessor\Chain $chain)
    {
        if ($this->minification->isEnabled(pathinfo($chain->getTargetAssetPath(), PATHINFO_EXTENSION)) &&
            $this->minification->isMinifiedFilename($chain->getTargetAssetPath()) &&
            !$this->minification->isMinifiedFilename($chain->getOrigAssetPath())
        ) {
            // format cache file path based on original file name and it's modification time
            $cacheKeyData = [
                $chain->getOrigAssetPath(),
                filemtime($chain->getOrigAssetPath())
            ];
            $cacheFile = "_minify_cache/" . $chain->getOrigContentType() . "/"
                . md5(join($cacheKeyData)) . "." . $chain->getOrigContentType();

            // if content was not processed by pre-processors yet (this is 1st preprocessor in chain)
            // then create cachable result as a file or re-use existing one
            if (!$chain->isChanged()) {
                if (!is_readable($this->cacheDir->getAbsolutePath($cacheFile))) {
                    $content = $this->adapter->minify($chain->getContent());
                    $this->cacheDir->writeFile($cacheFile, $content);
                }
                $chain->setCachedResultPath($this->cacheDir->getAbsolutePath($cacheFile));
            } else {
                // if asset content has been modified by other preprocessor
                // then process it without caching
                $content = $this->adapter->minify($chain->getContent());
                $chain->setContent($content);
            }
        }
    }
}
