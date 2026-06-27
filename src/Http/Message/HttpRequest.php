<?php
/**
 * HttpRequest HTTP 请求
 */
namespace BoxPHP\Server\Http\Message;

class HttpRequest
{
    protected string $method;
    protected string $path;
    protected array $headers;
    protected array $query;
    protected array $body;
    protected array $cookie;
    protected string $rawBody;
    protected string $clientIp;

    public function __construct(array $data = [])
    {
        $this->method = strtoupper($data['method'] ?? 'GET');
        $this->path = $data['path'] ?? '/';
        $this->headers = $data['headers'] ?? [];
        $this->query = $data['query'] ?? [];
        $this->body = $data['body'] ?? [];
        $this->cookie = $data['cookie'] ?? [];
        $this->rawBody = $data['rawBody'] ?? '';
        $this->clientIp = $data['ip'] ?? '127.0.0.1';
    }

    public static function fromWorkerman($request): self
    {
        return new self([
            'method' => $request->method,
            'path' => $request->path(),
            'headers' => $request->header(),
            'query' => $request->get ?? [],
            'body' => $request->post ?? [],
            'cookie' => $request->cookie ?? [],
            'rawBody' => $request->rawBody(),
            'ip' => $request->clientIp ?? '127.0.0.1',
        ]);
    }

    public function getMethod(): string { return $this->method; }
    public function getPath(): string { return $this->path; }
    public function getHeaders(): array { return $this->headers; }
    public function getQuery(): array { return $this->query; }
    public function getBody(): array { return $this->body; }
    public function getCookie(): array { return $this->cookie; }
    public function getRawBody(): string { return $this->rawBody; }
    public function getClientIp(): string { return $this->clientIp; }

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function json(bool $assoc = true): mixed
    {
        return json_decode($this->rawBody, $assoc);
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'headers' => $this->headers,
            'query' => $this->query,
            'body' => $this->body,
            'cookie' => $this->cookie,
            'ip' => $this->clientIp,
        ];
    }
}
