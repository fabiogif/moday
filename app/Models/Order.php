<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Coupon;

class Order extends Model
{
    use HasFactory;

    public const STATUS_ARCHIVED = 'Arquivado';

    protected $fillable = [
        'tenant_id', 
        'identify', 
        'client_id', 
        'table_id', 
        'total', 
        'subtotal',
        'discount_amount',
        'coupon_id',
        'coupon_code',
        'coupon_name',
        'coupon_snapshot',
        'status',
        'order_status_id',
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
        'payment_method_id',
        'split_payments',
        'precisa_troco',
        'valor_recebido',
        'shipping_method',
        'archived_at',
    ];

    protected $with = ['orderStatus'];

    protected $casts = [
        'is_delivery' => 'boolean',
        'use_client_address' => 'boolean',
        'precisa_troco' => 'boolean',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'valor_recebido' => 'decimal:2',
        'coupon_snapshot' => 'array',
        'split_payments' => 'array',
        'archived_at' => 'datetime',
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

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
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
