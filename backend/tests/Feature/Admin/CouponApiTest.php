<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
class CouponApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create([
            'account_status' => 'active',
        ]);
        $this->user = User::factory()->for($this->tenant)->create();
        $this->actingAs($this->user, 'api');

        Storage::fake('public');
        Storage::fake('products');
        Storage::fake('volume');
    }

    #[Test]
    public function can_create_coupon_without_image()
    {
        $couponData = [
            'name' => 'BLACK FRIDAY',
            'code' => 'BF2025',
            'description' => '20% off on all products',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'is_featured' => false,
        ];

        $response = $this->postJson('/api/marketing/coupons', $couponData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'BLACK FRIDAY']);

        $this->assertDatabaseHas('coupons', [
            'tenant_id' => $this->tenant->id,
            'code' => 'BF2025',
            'image' => null,
        ]);
    }

    #[Test]
    public function can_create_coupon_with_image()
    {
        $couponData = [
            'name' => 'CHRISTMAS SALE',
            'code' => 'XMAS25',
            'description' => '25% off',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
            'is_featured' => true,
            'image' => UploadedFile::fake()->image('coupon.jpg', 380, 220),
        ];

        $response = $this->postJson('/api/marketing/coupons', $couponData);

        $response->assertStatus(201);
        
        $coupon = Coupon::first();
        $this->assertNotNull($coupon->image);
        Storage::disk('public')->assertExists($coupon->image);
    }

    #[Test]
    public function can_update_coupon_details()
    {
        $coupon = Coupon::factory()->for($this->tenant)->create(['name' => 'Old Name']);

        $updateData = [
            'name' => 'New Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/marketing/coupons/{$coupon->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function can_update_coupon_with_new_image()
    {
        $coupon = Coupon::factory()->for($this->tenant)->create();
        $newImage = UploadedFile::fake()->image('new_coupon.png', 380, 220);

        $updateData = [
            'name' => $coupon->name,
            'image' => $newImage,
        ];

        $response = $this->postJson("/api/marketing/coupons/{$coupon->uuid}", $updateData + ['_method' => 'PUT']);
        
        $response->assertStatus(200);
        $coupon->refresh();
        $this->assertNotNull($coupon->image);
        Storage::disk('public')->assertExists($coupon->image);
    }

    #[Test]
    public function can_remove_coupon_image_on_update()
    {
        $coupon = Coupon::factory()->for($this->tenant)->create(['image' => 'dummy/path.jpg']);
        
        $updateData = [
            'name' => $coupon->name,
            'remove_image' => true,
        ];

        $response = $this->postJson("/api/marketing/coupons/{$coupon->uuid}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'image' => null]);
    }

    #[Test]
    public function can_toggle_coupon_status()
    {
        $coupon = Coupon::factory()->for($this->tenant)->create(['is_active' => true]);

        $response = $this->patchJson("/api/marketing/coupons/{$coupon->uuid}/toggle", ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJsonFragment(['is_active' => false]);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'is_active' => false]);
    }

    #[Test]
    public function can_delete_coupon()
    {
        $coupon = Coupon::factory()->for($this->tenant)->create();

        $response = $this->deleteJson("/api/marketing/coupons/{$coupon->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function can_create_coupon_via_multipart_form_data_like_frontend()
    {
        $response = $this->post('/api/marketing/coupons', [
            'code' => 'PROMO2024',
            'name' => 'Cupom Frontend',
            'description' => 'Teste via FormData',
            'discount_type' => 'percentage',
            'discount_value' => '25',
            'is_active' => '1',
            'is_featured' => '0',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'PROMO2024', 'name' => 'Cupom Frontend']);

        $this->assertDatabaseHas('coupons', [
            'tenant_id' => $this->tenant->id,
            'code' => 'PROMO2024',
            'discount_value' => 25,
            'is_active' => true,
            'is_featured' => false,
        ]);
    }

    #[Test]
    public function can_validate_and_apply_coupon_on_public_store()
    {
        $this->tenant->update(['slug' => 'loja-teste']);

        Coupon::factory()->for($this->tenant)->create([
            'code' => 'DESC10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 50,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/store/loja-teste/coupons/validate?' . http_build_query([
            'code' => 'DESC10',
            'subtotal' => 100,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.discount_amount', '10.00')
            ->assertJsonPath('data.final_total', '90.00');
    }

    #[Test]
    public function public_store_rejects_invalid_coupon_code()
    {
        $this->tenant->update(['slug' => 'loja-teste']);

        $response = $this->getJson('/api/store/loja-teste/coupons/validate?' . http_build_query([
            'code' => 'INVALIDO',
            'subtotal' => 100,
        ]));

        $response->assertStatus(422);
    }

    #[Test]
    public function cannot_create_coupon_with_duplicate_code()
    {
        Coupon::factory()->for($this->tenant)->create(['code' => 'DUPLICATE']);

        $couponData = [
            'name' => 'Another Coupon',
            'code' => 'DUPLICATE',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'is_active' => true,
            'is_featured' => false,
        ];

        $response = $this->postJson('/api/marketing/coupons', $couponData);

        $response->assertStatus(422);

        $this->assertSame(
            'Já existe um cupom com este código. Escolha outro código.',
            $response->json('message')
        );
    }
}