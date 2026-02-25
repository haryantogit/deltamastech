<?php

namespace App\Filament\Pages\Pos;

use App\Models\FavoriteProduct;
use App\Models\Outlet;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CashierPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected string $view = 'filament.pages.pos.cashier';

    protected static ?string $title = 'Kasir';

    protected static bool $shouldRegisterNavigation = false;

    // Outlet
    public ?int $outletId = null;
    public ?Outlet $outlet = null;

    // Search
    public string $search = '';

    // Category Filter
    public ?int $activeCategoryId = null;

    // Cart items: [ ['product_id' => ..., 'name' => ..., 'sku' => ..., 'price' => ..., 'qty' => ..., 'image' => ...], ... ]
    public array $cart = [];

    // Payment modal
    public bool $showPayment = false;
    public string $paymentMethod = 'cash';
    public string $customerName = '';
    public float $cashReceived = 0;

    public function mount(): void
    {
        $this->outletId = request()->query('outlet');

        if ($this->outletId) {
            $this->outlet = Outlet::find($this->outletId);
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product)
            return;

        // Check if already in cart
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] === $productId) {
                $this->cart[$key]['qty']++;
                return;
            }
        }

        // Get first image
        $image = null;
        if (is_array($product->image) && count($product->image) > 0) {
            $image = $product->image[0] ?? null;
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku ?? '-',
            'price' => (float) $product->sell_price,
            'qty' => 1,
            'image' => $image,
        ];
    }

    public function incrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
        }
    }

    public function decrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']--;
            if ($this->cart[$index]['qty'] <= 0) {
                $this->removeFromCart($index);
            }
        }
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
    }

    public function getTaxProperty(): float
    {
        return 0; // No tax for now
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->tax;
    }

    public function getChangeProperty(): float
    {
        return max(0, $this->cashReceived - $this->total);
    }

    public function openPayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->body('Tambahkan produk ke keranjang terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }
        $this->cashReceived = $this->total;
        $this->showPayment = true;
    }

    public function closePayment(): void
    {
        $this->showPayment = false;
    }

    public function processPayment(): void
    {
        if ($this->paymentMethod === 'cash' && $this->cashReceived < $this->total) {
            Notification::make()
                ->title('Uang tidak cukup')
                ->body('Jumlah uang yang diterima kurang dari total tagihan.')
                ->danger()
                ->send();
            return;
        }

        // Generate order number
        $lastOrder = PosOrder::latest('id')->first();
        $nextNum = $lastOrder ? ($lastOrder->id + 1) : 1;
        $number = 'POS-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        // Create order
        $order = PosOrder::create([
            'number' => $number,
            'outlet_id' => $this->outletId ?? 1,
            'user_id' => Auth::id(),
            'customer_name' => $this->customerName ?: null,
            'transaction_date' => now(),
            'subtotal' => $this->subtotal,
            'discount' => 0,
            'tax' => $this->tax,
            'total' => $this->total,
            'payment_method' => $this->paymentMethod,
            'status' => 'completed',
        ]);

        // Create order items
        foreach ($this->cart as $item) {
            PosOrderItem::create([
                'pos_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['qty'],
                'discount' => 0,
                'subtotal' => $item['price'] * $item['qty'],
            ]);
        }

        // Reset
        $this->cart = [];
        $this->customerName = '';
        $this->cashReceived = 0;
        $this->showPayment = false;

        Notification::make()
            ->title('Transaksi berhasil!')
            ->body("Pesanan {$number} telah disimpan.")
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $query = Product::where('can_be_sold', true)
            ->where('is_active', true)
            ->where('is_fixed_asset', false);

        // Apply filtering based on outlet settings
        if ($this->outlet) {
            if ($this->outlet->product_display_type === 'category') {
                $categoryIds = $this->outlet->categories()->pluck('categories.id')->toArray();
                $query->whereIn('category_id', $categoryIds);

                // If currently filtered categories don't include active category, reset it
                if ($this->activeCategoryId && !in_array($this->activeCategoryId, $categoryIds)) {
                    $this->activeCategoryId = null;
                }
            } elseif ($this->outlet->product_display_type === 'per_product') {
                $productIds = $this->outlet->products()->pluck('products.id')->toArray();
                $query->whereIn('id', $productIds);
                $this->activeCategoryId = null; // Categories not relevant for per_product
            }
        }

        // Apply active category filter
        if ($this->activeCategoryId) {
            $query->where('category_id', $this->activeCategoryId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        $allProducts = $query->orderBy('name')->limit(50)->get();

        // Apply price adjustments
        $products = $allProducts->map(function ($product) {
            $originalPrice = (float) $product->sell_price;
            $adjustedPrice = $originalPrice;

            if ($this->outlet && $this->outlet->price_adjustment != 0) {
                $adjustment = (float) $this->outlet->price_adjustment;
                $ispercentage = $this->outlet->price_unit === 'percentage';
                $isMarkup = $this->outlet->price_type === 'markup';

                if ($ispercentage) {
                    $change = $originalPrice * ($adjustment / 100);
                } else {
                    $change = $adjustment;
                }

                if ($isMarkup) {
                    $adjustedPrice = $originalPrice + $change;
                } else {
                    $adjustedPrice = $originalPrice - $change;
                }
            }

            $product->sell_price = $adjustedPrice;
            return $product;
        });

        // Get favorites for this outlet (or global favorites)
        $favoriteQuery = FavoriteProduct::with('product');
        if ($this->outletId) {
            $favoriteQuery->where(function ($q) {
                $q->where('outlet_id', $this->outletId)
                    ->orWhereNull('outlet_id');
            });
        }
        $favorites = $favoriteQuery->orderBy('sort_order')->get()->map(function ($fav) {
            $product = $fav->product;
            if (!$product)
                return $fav;

            $originalPrice = (float) $product->sell_price;
            $adjustedPrice = $originalPrice;

            if ($this->outlet && $this->outlet->price_adjustment != 0) {
                $adjustment = (float) $this->outlet->price_adjustment;
                $ispercentage = $this->outlet->price_unit === 'percentage';
                $isMarkup = $this->outlet->price_type === 'markup';

                if ($ispercentage) {
                    $change = $originalPrice * ($adjustment / 100);
                } else {
                    $change = $adjustment;
                }

                if ($isMarkup) {
                    $adjustedPrice = $originalPrice + $change;
                } else {
                    $adjustedPrice = $originalPrice - $change;
                }
            }

            $fav->product->sell_price = $adjustedPrice;
            return $fav;
        });

        // Get available categories for display
        $categoriesQuery = \App\Models\Category::query();
        if ($this->outlet && $this->outlet->product_display_type === 'category') {
            $allowedCatIds = $this->outlet->categories()->pluck('categories.id')->toArray();
            $categoriesQuery->whereIn('id', $allowedCatIds);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        return [
            'products' => $products,
            'favorites' => $favorites,
            'categories' => $categories,
        ];
    }
}
