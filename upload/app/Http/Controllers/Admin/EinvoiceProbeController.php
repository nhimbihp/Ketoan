<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/** Diagnostic: can this hosting reach the tax portal API (port 30000)? Read-only, sends no credentials. */
class EinvoiceProbeController extends Controller
{
    public function __invoke(): View
    {
        $url = rtrim(config('einvoice.base_url'), '/').'/captcha';
        $started = microtime(true);
        $result = ['url' => $url, 'ok' => false, 'status' => null, 'error' => null, 'body' => null];

        try {
            $response = Http::withUserAgent(config('einvoice.user_agent'))->timeout(15)->get($url);
            $result['status'] = $response->status();
            $result['ok'] = $response->successful() && isset($response->json()['key']);
            $result['body'] = mb_substr($response->body(), 0, 150);
        } catch (ConnectionException $e) {
            $result['error'] = $e->getMessage();
        }
        $result['seconds'] = round(microtime(true) - $started, 2);
        $result['curl'] = function_exists('curl_version') ? curl_version()['version'] : 'không có cURL';

        return view('einvoices.probe', $result);
    }
}
