<?php
/**
 * BoxPHP Framework
 *
 * Copyright 2026 BoxPHP
 * By tvjojo, asterhuang, 黄波涛; 5viv.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * HttpResponse HTTP 响应
 */
namespace BoxPHP\Server\Http\Message;

class HttpResponse
{
    protected int $status;
    protected array $headers;
    protected mixed $body;
    protected string $contentType;

    public function __construct(int $status = 200, mixed $body = null, array $headers = [])
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
        $this->contentType = $headers['content-type'] ?? 'application/json';
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, $data, ['Content-Type' => 'application/json']);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, ['Content-Type' => 'text/html']);
    }

    public static function text(string $text, int $status = 200): self
    {
        return new self($status, $text, ['Content-Type' => 'text/plain']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self($status, null, ['Location' => $url]);
    }

    public static function error(int $code, string $message = ''): self
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];
        return new self($code, [
            'error' => $messages[$code] ?? 'Error',
            'message' => $message,
        ]);
    }

    public function getStatus(): int { return $this->status; }
    public function getBody(): mixed { return $this->body; }
    public function getHeaders(): array { return $this->headers; }

    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function send($connection): void
    {
        $body = is_array($this->body) ? json_encode($this->body, JSON_UNESCAPED_UNICODE) : ($this->body ?? '');

        $contentType = $this->contentType;
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                $contentType = $value;
                break;
            }
        }

        $statusTexts = [
            200 => 'OK', 201 => 'Created', 204 => 'No Content',
            301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed', 429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];
        $statusText = $statusTexts[$this->status] ?? 'Unknown';

        $headers = "Content-Type: {$contentType}\r\n";
        $headers .= "Content-Length: " . strlen($body) . "\r\n";
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) !== 'content-type' && strtolower($key) !== 'content-length') {
                $headers .= "{$key}: {$value}\r\n";
            }
        }
        $headers .= "Connection: keep-alive\r\n";

        $response = "HTTP/1.1 {$this->status} {$statusText}\r\n{$headers}\r\n{$body}";
        $connection->send($response);
    }
}
