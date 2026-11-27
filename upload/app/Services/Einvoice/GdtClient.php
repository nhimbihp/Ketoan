<?php

namespace App\Services\Einvoice;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the tax portal API (port of HttpGet / HttpPost / getCompanyName in mdHoadondientu.vb).
 */
class GdtClient
{
    public const PURCHASE = 'purchase';
    public const SOLD = 'sold';
    public const SOURCE_QUERY = 'query';         // normal invoices
    public const SOURCE_SCO = 'sco-query';       // invoices from cash registers

    public function __construct(private ?string $token = null) {}

    public function withToken(string $token): static
    {
        return new static($token);
    }

    /** @return array{key: string, svg: string} */
    public function captcha(): array
    {
        $json = $this->json($this->send(fn (PendingRequest $http) => $http->get($this->url('/captcha'))));

        if (empty($json['key']) || empty($json['content'])) {
            throw new GdtException('Cổng thuế không trả về captcha.');
        }

        return ['key' => trim($json['key']), 'svg' => $json['content']];
    }

    /** Returns the bearer token. */
    public function login(string $username, string $password, string $captchaValue, string $captchaKey): string
    {
        $response = $this->send(fn (PendingRequest $http) => $http
            ->timeout(config('einvoice.login_timeout'))
            ->post($this->url('/security-taxpayer/authenticate'), [
                'username' => $username,
                'password' => $password,
                'cvalue' => $captchaValue,
                'ckey' => $captchaKey,
            ]));

        if (! $response->successful()) {
            throw new GdtException('Đăng nhập thất bại, vui lòng kiểm tra lại tên đăng nhập, mật khẩu hoặc mã captcha.', GdtException::AUTH, $response->status());
        }

        $token = $this->json($response)['token'] ?? null;
        if (! $token) {
            throw new GdtException('Đăng nhập thất bại: cổng thuế không trả về token.', GdtException::AUTH);
        }

        return trim($token);
    }

    /** Taxpayer profile, contains 'name' (company name). */
    public function profile(): array
    {
        return $this->json($this->get('/security-taxpayer/profile'));
    }

    /**
     * One page of the invoice list.
     *
     * @return array{datas: array, state: ?string}
     */
    public function listInvoices(string $direction, string $source, string $search, ?string $state = null): array
    {
        // Built as a raw string like the VB code: the API expects ';', '=', ':' and '/' unescaped in "search"
        $path = "/{$source}/invoices/{$direction}?sort=".config('einvoice.sort')
            .'&size='.config('einvoice.page_size')
            .($state !== null ? '&state='.rawurlencode($state) : '')
            .'&search='.$search;

        $json = $this->json($this->get($path));

        return ['datas' => $json['datas'] ?? [], 'state' => $json['state'] ?? null];
    }

    public function detail(string $source, array $key): array
    {
        return $this->json($this->get("/{$source}/invoices/detail?".$this->invoiceQuery($key)));
    }

    /** Raw zip content of one invoice. */
    public function exportXml(string $source, array $key): string
    {
        $response = $this->send(fn (PendingRequest $http) => $http
            ->accept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
            ->get($this->url("/{$source}/invoices/export-xml?".$this->invoiceQuery($key))));

        if ($response->status() === 500) {
            throw new GdtException('Không tồn tại hồ sơ gốc của hóa đơn.', GdtException::NOT_FOUND, 500);
        }
        $this->throwIfFailed($response);

        return $response->body();
    }

    /** @param array{nbmst: string, khhdon: string, shdon: string|int, khmshdon: string|int} $key */
    private function invoiceQuery(array $key): string
    {
        return http_build_query([
            'nbmst' => $key['nbmst'],
            'khhdon' => $key['khhdon'],
            'shdon' => $key['shdon'],
            'khmshdon' => $key['khmshdon'],
        ]);
    }

    private function get(string $path): Response
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get($this->url($path)));
        $this->throwIfFailed($response);

        return $response;
    }

    private function send(callable $call): Response
    {
        $http = Http::withUserAgent(config('einvoice.user_agent'))
            ->timeout(config('einvoice.request_timeout'))
            ->acceptJson();

        if ($this->token) {
            $http = $http->withToken($this->token);
        }

        try {
            return $call($http);
        } catch (ConnectionException $e) {
            throw new GdtException('Không kết nối được cổng hóa đơn điện tử: '.$e->getMessage(), GdtException::NETWORK);
        }
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw match (true) {
            in_array($response->status(), [401, 403]) => new GdtException('Phiên đăng nhập cổng thuế đã hết hạn, vui lòng đăng nhập lại.', GdtException::AUTH, $response->status()),
            $response->status() === 429 => new GdtException('Lỗi 429: gửi quá nhiều yêu cầu tới cổng thuế. Chờ vài phút rồi bấm Tải tiếp.', GdtException::RATE_LIMIT, 429),
            default => new GdtException('Cổng thuế trả về lỗi HTTP '.$response->status().'.', GdtException::OTHER, $response->status()),
        };
    }

    private function json(Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            throw new GdtException('Cổng thuế trả về dữ liệu không hợp lệ.');
        }

        return $json;
    }

    private function url(string $path): string
    {
        return rtrim(config('einvoice.base_url'), '/').$path;
    }
}
