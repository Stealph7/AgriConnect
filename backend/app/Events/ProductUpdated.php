<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $user;
    public $changes;

    public function __construct(Product $product, array $changes)
    {
        $this->product = $product;
        $this->user = $product->user;
        $this->changes = $changes;
    }

    public function broadcastOn()
    {
        return new Channel('products');
    }
}
