<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ZraSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $minimumTlsVersion
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $minimumTlsVersion = '1.2')
    {
        // Check if HTTPS is being used in production
        if (!$request->secure() && app()->environment('production')) {
            if (config('zra.force_secure', true)) {
                // If the request should be redirected to HTTPS
                if (config('zra.redirect_to_https', true)) {
                    $secureUrl = str_replace('http://', 'https://', $request->fullUrl());
                    return redirect()->to($secureUrl);
                }

                // Otherwise abort with a 403 error
                abort(Response::HTTP_FORBIDDEN, 'HTTPS connection is required for ZRA Smart Invoice API.');
            } else {
                // Just log a warning if not forcing secure connections
                Log::warning('Insecure connection detected for ZRA Smart Invoice API', [
                    'ip' => $request->ip(),
                    'uri' => $request->path(),
                ]);
            }
        }

        // Check TLS version in production
        if (app()->environment('production')) {
            $tlsVersion = $this->getTlsVersion($request);
            if ($tlsVersion && version_compare($tlsVersion, $minimumTlsVersion, '<')) {
                abort(Response::HTTP_FORBIDDEN, "TLS version {$minimumTlsVersion} or higher is required for security reasons.");
            }
        }

        // Check content-type for API requests
        if ($request->is('api/*') && $request->isMethod('POST')) {
            // Ensure JSON content type is used for API POST requests
            if (!$request->isJson() && !str_contains($request->header('Content-Type', ''), 'application/json')) {
                abort(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Content-Type must be application/json for API requests.');
            }
        }

        // Add security headers to response
        $response = $next($request);

        return $this->addSecurityHeaders($response);
    }

    /**
     * Get the TLS version from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTlsVersion(Request $request): ?string
    {
        // Attempt to get the TLS version from server variables
        if (isset($_SERVER['SSL_PROTOCOL'])) {
            return $this->extractTlsVersion($_SERVER['SSL_PROTOCOL']);
        }

        if (isset($_SERVER['HTTPS_PROTOCOL'])) {
            return $this->extractTlsVersion($_SERVER['HTTPS_PROTOCOL']);
        }

        return null;
    }

    /**
     * Extract the TLS version from a protocol string.
     *
     * @param  string  $protocol
     * @return string|null
     */
    protected function extractTlsVersion(string $protocol): ?string
    {
        if (preg_match('/TLSv(\d+\.\d+)/', $protocol, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Add security headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addSecurityHeaders($response)
    {
        // Only add security headers if enabled in config
        if (config('zra.add_security_headers', true)) {
            $headers = [
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'",
                'Referrer-Policy' => 'no-referrer-when-downgrade',
                'Feature-Policy' => "geolocation 'none'; microphone 'none'; camera 'none'",
            ];

            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
