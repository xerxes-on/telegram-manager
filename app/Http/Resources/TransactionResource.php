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
            'time' => (int) $this->paycom_time,
            'amount' => $this->amount,
            'account' => [
                'order_id' => $this->order_id,
            ],
            'create_time' => (int) $this->paycom_time,
            'perform_time' => (int) ($this->perform_time_unix ?? 0),
            'cancel_time' => (int) ($this->cancel_time ?? 0),
            'transaction' => $this->id,
            'state' => $this->state,
            'reason' => $this->reason
        ];
    }
}
