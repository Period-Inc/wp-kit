<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface HtaccessWriterInterface
{
    public function write(string $path, string $rules): HtaccessWriteResult;
}
