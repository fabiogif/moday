<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 
        'identify', 
        'client_id', 
        'table_id', 
        'total', 
        'status',
        'origin',
        'comment',
        'is_delivery',
        'use_client_address',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_zip_code',
        'delivery_neighborhood',
        'delivery_number',
        'delivery_complement',
        'delivery_notes',
        'payment_method',
        'shipping_method'
    ];

    protected $casts = [
        'is_delivery' => 'boolean',
        'use_client_address' => 'boolean',
        'total' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function products() 
    {
        return $this->belongsToMany(Product::class);
    }

    public function OrderEvaluation()
    {
        return $this->hasMany(OrderEvaluation::class);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_delivery_address'];

    /**
     * Get the full delivery address formatted
     */
    public function getFullDeliveryAddressAttribute()
    {
        try {
            if ($this->use_client_address && $this->client) {
                // Check if client has getFullAddressAttribute method
                if (method_exists($this->client, 'getFullAddressAttribute')) {
                    return $this->client->getFullAddressAttribute();
                }
                // Fallback to building address from client fields
                return $this->buildClientAddress();
            }

            // Build delivery address from order fields
            return $this->buildDeliveryAddress();
        } catch (\Exception $e) {
            \Log::warning('Error in getFullDeliveryAddressAttribute: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build delivery address from order fields
     */
    private function buildDeliveryAddress()
    {
        if (!$this->delivery_address) {
            return null;
        }

        $addressParts = array_filter([
            $this->delivery_address,
            $this->delivery_number ? "#" . $this->delivery_number : null,
            $this->delivery_complement,
            $this->delivery_neighborhood,
            $this->delivery_city,
            $this->delivery_state,
            $this->delivery_zip_code ? "CEP: " . $this->delivery_zip_code : null
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Build address from client fields
     */
    private function buildClientAddress()
    {
        if (!$this->client) {
            return null;
        }

        $addressParts = array_filter([
            $this->client->address ?? null,
            $this->client->number ? "#" . $this->client->number : null,
            $this->client->complement ?? null,
            $this->client->neighborhood ?? null,
            $this->client->city ?? null,
            $this->client->state ?? null,
            $this->client->zip_code ? "CEP: " . $this->client->zip_code : null
        ]);

        return implode(', ', $addressParts);
    }
}
