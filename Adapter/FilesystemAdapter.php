<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Cache\Adapter;

use Symfony\Component\Cache\Adapter\FilesystemAdapter as BaseFilesystemAdapter;

/**
 * Filesystem Cache Adapter.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FilesystemAdapter extends BaseFilesystemAdapter implements AdapterInterface
{
    use AdapterTrait;

    /**
     * {@inheritdoc}
     */
    protected function doClearByPrefix($namespace, $prefix)
    {
        $ok = true;
        $directory = $this->getPropertyValue('directory');

        /* @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory,
                \FilesystemIterator::SKIP_DOTS)) as $file) {
            $this->doClearFile($ok, $file, $prefix);
        }

        return $ok;
    }

    /**
     * Action to clear all items in file starting with the prefix.
     *
     * @param bool         $ok     The delete status
     * @param \SplFileInfo $file   The spl file info
     * @param string       $prefix The prefix
     */
    private function doClearFile(&$ok, \SplFileInfo $file, $prefix)
    {
        $keys = array();

        if ($file->isFile()) {
            $key = $this->getFileKey($file);

            if (null !== $key && ($prefix === '' || 0 === strpos($key, $prefix))) {
                $keys[] = $key;
            }
        }

        $ok = ($file->isDir() || $this->deleteItems($keys) || !file_exists($file)) && $ok;
    }

    /**
     * Get the key of file.
     *
     * @param \SplFileInfo $file The spl file info
     *
     * @return string|null
     */
    private function getFileKey(\SplFileInfo $file)
    {
        $key = null;

        if ($h = @fopen($file, 'rb')) {
            rawurldecode(rtrim(fgets($h)));
            $value = stream_get_contents($h);
            fclose($h);
            $key = substr($value, 0, strpos($value, "\n"));
        }

        return $key;
    }
}
