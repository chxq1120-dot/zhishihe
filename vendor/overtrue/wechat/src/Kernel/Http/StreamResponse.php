<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyWeChat\Kernel\Http;

use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use EasyWeChat\Kernel\Support\File;

/**
 * Class StreamResponse.
 *
 * @author overtrue <i@overtrue.me>
 */
class StreamResponse extends Response
{
    /**
     * @return bool|int
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function save(string $directory, string $filename = '', bool $appendSuffix = true, $is_content = 0)
    {
        $this->getBody()->rewind();
        $contents = $this->getBody()->getContents();
        if (empty($contents) || '{' === $contents[0]) {
            throw new RuntimeException('Invalid media response content.');
        }
        if ($is_content == 1) {
            return $contents;
        }
        $directory = rtrim($directory, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // @codeCoverageIgnore
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf("'%s' is not writable.", $directory));
        }
        if (empty($filename)) {
            if (preg_match('/filename="(?<filename>.*?)"/', $this->getHeaderLine('Content-Disposition'), $match)) {
                $filename = $match['filename'];
            } else {
                $filename = md5($contents);
            }
        }
        if ($appendSuffix && empty(pathinfo($filename, PATHINFO_EXTENSION))) {
            $filename .= File::getStreamExt($contents);
        }
        file_put_contents($directory.'/'.$filename, $contents);
        return $filename;
    }

    /**
     * @return bool|int
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function saveAs(string $directory, string $filename, bool $appendSuffix = true)
    {
        return $this->save($directory, $filename, $appendSuffix);
    }

    /**
     * @return bool|int
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function saveContent(string $directory='', string $filename='', bool $appendSuffix = true)
    {
        return $this->save($directory, $filename, $appendSuffix, 1);
    }
}
