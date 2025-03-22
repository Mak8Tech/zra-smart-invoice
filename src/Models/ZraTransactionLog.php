<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;

class ZraTransactionLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_type',
        'reference',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_payload' => 'json',
        'response_payload' => 'json',
    ];

    /**
     * Create a new transaction log entry
     *
     * @param string $type
     * @param string|null $reference
     * @param array $request
     * @param array|null $response
     * @param string $status
     * @param string|null $errorMessage
     * @return self
     */
    public static function createLog(
        string $type,
        ?string $reference,
        array $request,
        ?array $response,
        string $status,
        ?string $errorMessage = null
    ): self {
        // Remove sensitive data before logging
        $sanitizedRequest = self::sanitizeData($request);
        $sanitizedResponse = $response ? self::sanitizeData($response) : null;

        return self::create([
            'transaction_type' => $type,
            'reference' => $reference,
            'request_payload' => $sanitizedRequest,
            'response_payload' => $sanitizedResponse,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Sanitize sensitive data in payloads
     *
     * @param array $data
     * @return array
     */
    protected static function sanitizeData(array $data): array
    {
        $result = $data;

        // Redact API keys and other sensitive information
        if (isset($result['api_key'])) {
            $result['api_key'] = '********';
        }

        if (isset($result['key'])) {
            $result['key'] = '********';
        }

        // Add more sensitive fields as needed

        return $result;
    }
}
