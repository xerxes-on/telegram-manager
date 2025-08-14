<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->paycom_transaction_id,
            'time' => $this->safeIntCast($this->paycom_time),
            'amount' => $this->amount,
            'account' => [
                'order_id' => $this->order_id,
            ],
            'create_time' => $this->safeIntCast($this->paycom_time),
            'perform_time' => $this->safeIntCast($this->perform_time_unix ?? 0),
            'cancel_time' => $this->safeIntCast($this->cancel_time ?? 0),
            'transaction' => $this->id,
            'state' => $this->state,
            'reason' => $this->reason
        ];
    }
    
    /**
     * Safely cast a value to integer, handling large numbers correctly.
     * This prevents 32-bit integer overflow issues with PayMe timestamps.
     */
    private function safeIntCast($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        // Handle string values that might represent large integers
        $stringValue = trim((string) $value);
        
        if (!is_numeric($stringValue)) {
            return 0;
        }
        
        // If the value looks like it was corrupted by 32-bit overflow, try to detect and fix
        $numValue = floatval($stringValue);
        
        // If we have a negative number that could be an overflow, check if it makes sense
        if ($numValue < 0 && $numValue > -2147483648) {
            // This might be a 32-bit overflow - try to recover the original value
            $recovered = $numValue + 4294967296; // Add 2^32
            
            // Check if the recovered value makes sense as a PayMe timestamp (should be around current time in ms)
            $currentTimeMs = time() * 1000;
            $oneYearInMs = 365 * 24 * 60 * 60 * 1000;
            
            if ($recovered > ($currentTimeMs - $oneYearInMs) && $recovered < ($currentTimeMs + $oneYearInMs)) {
                return (int) $recovered;
            }
        }
        
        // For very large numbers, ensure they fit in 64-bit int
        if ($numValue > PHP_INT_MAX) {
            return PHP_INT_MAX;
        }
        
        if ($numValue < PHP_INT_MIN) {
            return PHP_INT_MIN;
        }
        
        return (int) $numValue;
    }
}
