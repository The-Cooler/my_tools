<?php

declare(strict_types=1);

namespace JsonFormatter;

use InvalidArgumentException;

final class JsonService
{
    public function format(string $input): string
    {
        $decoded = $this->decode($input);

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: '{}';
    }

    public function minify(string $input): string
    {
        $decoded = $this->decode($input);

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: '{}';
    }

    public function validate(string $input): string
    {
        $this->decode($input);

        return "JSON 有效";
    }

    private function decode(string $input): mixed
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            throw new InvalidArgumentException('请输入 JSON 内容。');
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('JSON 解析错误: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
