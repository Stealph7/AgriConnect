<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public $buyer;
    public $seller;
    public $product;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->buyer = $transaction->buyer;
        $this->seller = $transaction->seller;
        $this->product = $transaction->product;
    }

    public function broadcastOn()
    {
        return [
            new Channel('transactions'),
            new Channel('user.' . $this->buyer->id),
            new Channel('user.' . $this->seller->id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'transaction_id' => $this->transaction->id,
            'product_name' => $this->product->name,
            'quantity' => $this->transaction->quantity,
            'total_amount' => $this->transaction->total_amount,
            'buyer_name' => $this->buyer->name,
            'seller_name' => $this->seller->name,
            'completed_at' => $this->transaction->completed_at,
        ];
    }
}
