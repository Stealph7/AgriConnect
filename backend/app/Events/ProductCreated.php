<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $user;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->user = $product->user;
    }

    public function broadcastOn()
    {
        return new Channel('products');
    }
}
