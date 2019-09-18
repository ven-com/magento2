<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\App\View\Asset\MaterializationStrategy;

use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\View\Asset;

class Hardlink extends Copy
{
    /**
     * Publish file by creating hardlink
     *
     * @param WriteInterface $sourceDir
     * @param WriteInterface $targetDir
     * @param string $sourcePath
     * @param string $destinationPath
     * @return bool
     */
    public function publishFile(
        WriteInterface $sourceDir,
        WriteInterface $targetDir,
        $sourcePath,
        $destinationPath
    ) {
        $sourceAbsolute      = $sourceDir->getAbsolutePath($sourcePath);
        $destinationAbsolute = $targetDir->getAbsolutePath($destinationPath);

        $destinationDir = dirname($destinationAbsolute);
        if (!$targetDir->isExist($destinationDir)) {
            $targetDir->create($destinationDir);
        }

        // No verification, just try to create hardlink quickly (for better performance)
        $linkResult = link($sourceAbsolute, $destinationAbsolute);

        // Fallback to standard implementation in case of failure
        if (!$linkResult) {
            return parent::publishFile($sourceDir, $targetDir, $sourcePath, $destinationPath);
        }

        return $linkResult;
    }
}
