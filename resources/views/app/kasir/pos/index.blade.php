@extends('app.layouts.app')

@section('title', 'Kasir')

@push('scripts')
<script>
    let cart = [];
    let discountType = '';
    let discountValue = 0;
    const taxEnabled = {{ ($branchSetting->tax_enabled ?? false) ? 'true' : 'false' }};
    const taxPercentage = {{ ($branchSetting->tax_percentage ?? 0) }};

    function addToCart(productId, productName, baseUnit, unitPrice, unitId, unitName) {
        const existing = cart.findIndex(i => i.product_id === productId &&
            (i.product_unit_id ?? '') === (unitId ?? ''));
        if (existing >= 0) {
            cart[existing].quantity += 1;
        } else {
            cart.push({
                product_id: productId,
                product_name: productName,
                base_unit: baseUnit,
                unit_name: unitName || baseUnit,
                product_unit_id: unitId || '',
                unit_price: parseFloat(unitPrice),
                quantity: 1,
            });
        }
        renderCart();
    }

    function updateQty(index, newQty) {
        newQty = parseFloat(newQty) || 0;
        if (newQty <= 0) {
            cart.splice(index, 1);
        } else {
            cart[index].quantity = newQty;
        }
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart-items');
        const subtotalEl = document.getElementById('subtotal');
        const discountEl = document.getElementById('discount-info');
        const taxEl = document.getElementById('tax-info');
        const totalEl = document.getElementById('total-amount');
        const checkoutBtn = document.getElementById('checkout-btn');

        if (cart.length === 0) {
            container.innerHTML = `<div class="text-center text-muted-foreground py-12">
                <p class="text-lg mb-2">Keranjang kosong</p>
                <p class="text-sm">Pilih produk dari daftar</p>
            </div>`;
            subtotalEl.textContent = 'Rp 0';
            discountEl.innerHTML = '';
            taxEl.innerHTML = '';
            totalEl.textContent = 'Rp 0';
            checkoutBtn.disabled = true;
            checkoutBtn.classList.add('opacity-50', 'cursor-not-allowed');
            checkoutBtn.classList.remove('hover:bg-primary/90');
            return;
        }

        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        checkoutBtn.classList.add('hover:bg-primary/90');

        let html = '';
        let subtotal = 0;
        cart.forEach((item, i) => {
            const lineTotal = item.quantity * item.unit_price;
            subtotal += lineTotal;
            html += `<div class="flex items-start gap-3 p-3 bg-background rounded-[var(--radius)] border border-border">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-foreground truncate">${item.product_name}</p>
                    <p class="text-xs text-muted-foreground">@ ${formatRupiah(item.unit_price)} / ${item.unit_name}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="updateQty(${i}, ${item.quantity - 1})" class="w-8 h-8 flex items-center justify-center rounded-full bg-muted text-foreground hover:bg-muted-foreground/20 text-lg font-bold">-</button>
                    <input type="number" value="${item.quantity}" min="0.01" step="0.01"
                        onchange="updateQty(${i}, this.value)"
                        class="w-16 text-center border border-input rounded-[var(--radius)] px-2 py-1 text-sm" />
                    <button type="button" onclick="updateQty(${i}, ${item.quantity + 1})" class="w-8 h-8 flex items-center justify-center rounded-full bg-muted text-foreground hover:bg-muted-foreground/20 text-lg font-bold">+</button>
                    <span class="w-20 text-right font-semibold text-foreground">${formatRupiah(lineTotal)}</span>
                    <button type="button" onclick="removeItem(${i})" class="text-destructive hover:text-destructive/80 ml-1">×</button>
                </div>
            </div>`;
        });

        container.innerHTML = html;

        subtotalEl.textContent = formatRupiah(subtotal);

        const discountTypeVal = document.querySelector('input[name="discount_type"]:checked')?.value;
        const discountVal = parseFloat(document.getElementById('discount_value_input')?.value) || 0;
        let discountAmount = 0;
        if (discountTypeVal === 'percent') {
            discountAmount = subtotal * (discountVal / 100);
        } else if (discountTypeVal === 'nominal') {
            discountAmount = Math.min(discountVal, subtotal);
        }
        discountAmount = Math.min(discountAmount, subtotal);

        if (discountAmount > 0) {
            discountEl.innerHTML = `<div class="flex justify-between text-warning"><span>Diskon</span><span>-${formatRupiah(discountAmount)}</span></div>`;
        } else {
            discountEl.innerHTML = '';
        }

        const taxBase = subtotal - discountAmount;
        let taxAmount = 0;
        if (taxEnabled && taxPercentage > 0) {
            taxAmount = taxBase * (taxPercentage / 100);
            taxEl.innerHTML = `<div class="flex justify-between"><span>Pajak (${taxPercentage}%)</span><span>${formatRupiah(taxAmount)}</span></div>`;
        } else {
            taxEl.innerHTML = '';
        }

        const total = taxBase + taxAmount;
        totalEl.textContent = formatRupiah(total);
    }

    function formatRupiah(value) {
        return 'Rp ' + Math.round(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function submitCheckout() {
        if (cart.length === 0) return;

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        if (!paymentMethod) {
            alert('Pilih metode pembayaran.');
            return;
        }

        const customerName = document.getElementById('customer_name')?.value || '';
        const discountType = document.querySelector('input[name="discount_type"]:checked')?.value || '';
        const discountValue = parseFloat(document.getElementById('discount_value_input')?.value) || 0;

        const form = document.getElementById('checkout-form');
        const itemsInput = document.getElementById('items-json');
        itemsInput.value = JSON.stringify(cart.map(item => ({
            product_id: item.product_id,
            product_unit_id: item.product_unit_id || null,
            quantity: item.quantity,
            unit_price: item.unit_price,
        })).filter(item => item.quantity > 0));

        document.getElementById('checkout-customer_name').value = customerName;
        document.getElementById('checkout-discount_type').value = discountType;
        document.getElementById('checkout-discount_value').value = discountValue;
        document.getElementById('checkout-payment_method').value = paymentMethod;

        form.submit();
    }

    function searchProducts() {
        const query = document.getElementById('search-input').value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const sku = card.dataset.sku?.toLowerCase() || '';
            card.style.display = (name.includes(query) || sku.includes(query)) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('search-input')?.addEventListener('input', searchProducts);
        document.getElementById('search-input')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstVisible = document.querySelector('.product-card:not([style*="display: none"])');
                if (firstVisible) {
                    firstVisible.querySelector('.add-to-cart-btn')?.click();
                }
                this.value = '';
                searchProducts();
            }
        });
    });
</script>
@endpush

@section('content')
@if(isset($shift) && $shift)
    <div class="mb-3 flex items-center justify-between bg-primary/5 border border-primary/20 rounded-[var(--radius)] px-4 py-2 text-sm">
        <div class="flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
            <span class="text-foreground">Shift aktif sejak <strong>{{ $shift->opened_at->format('d/m/Y H:i') }}</strong></span>
            <span class="text-muted-foreground">| Kas awal: {{ format_currency($shift->opening_cash) }}</span>
        </div>
        <a href="{{ route('app.kasir.shifts.close') }}"
            class="px-3 py-1.5 bg-destructive text-destructive-foreground rounded-[var(--radius)] text-xs font-semibold hover:bg-destructive/90 transition">
            Tutup Shift
        </a>
    </div>
@endif
<div class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-8rem)]">
    <div class="flex-1 flex flex-col min-h-0">
        <div class="mb-3 flex gap-2">
            <input type="text" id="search-input" placeholder="Cari produk (ketik SKU/nama, lalu Enter)..."
                class="flex-1 border border-input rounded-[var(--radius)] px-4 py-3 text-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                autofocus />
        </div>

        <div class="flex-1 overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 content-start">
            @forelse($products as $product)
                @php
                    $defaultPrice = (float) $product->selling_price;
                @endphp
                <div class="product-card bg-card border border-border rounded-[var(--radius)] p-3 shadow-sm hover:shadow-md transition cursor-pointer"
                     data-name="{{ strtolower($product->name) }}"
                     data-sku="{{ strtolower($product->sku ?? '') }}">
                    <div class="aspect-square bg-muted rounded-[var(--radius)] mb-2 flex items-center justify-center overflow-hidden">
                        @if($product->getFirstMediaUrl('product_images'))
                            <img src="{{ $product->getFirstMediaUrl('product_images') }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-muted-foreground text-3xl">📦</span>
                        @endif
                    </div>
                    <p class="font-semibold text-foreground text-sm truncate">{{ $product->name }}</p>
                    <p class="text-xs text-muted-foreground mb-1">{{ $product->base_unit }} | Stok: {{ format_number($product->available_stock) }}</p>
                    <p class="text-primary font-bold text-sm">{{ format_currency($defaultPrice) }}</p>

                    <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $product->base_unit }}', {{ $defaultPrice }}, '', '{{ $product->base_unit }}')"
                        class="add-to-cart-btn mt-2 w-full bg-primary text-primary-foreground rounded-[var(--radius)] px-3 py-2 text-sm font-semibold hover:bg-primary/90 transition">
                        + Tambah
                    </button>

                    @if($product->units->count())
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($product->units as $unit)
                                @php
                                    $unitPrice = $unit->price_override ?? ($defaultPrice * $unit->conversion_to_base);
                                @endphp
                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $product->base_unit }}', {{ $unitPrice }}, {{ $unit->id }}, '{{ $unit->unit_name }}')"
                                    class="add-to-cart-btn text-xs px-2 py-1 rounded-full bg-muted text-muted-foreground hover:bg-secondary hover:text-secondary-foreground transition">
                                    {{ $unit->unit_name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-center text-muted-foreground py-12">
                    <p class="text-lg">Tidak ada produk dengan stok tersedia</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="w-full lg:w-96 xl:w-[420px] bg-card border border-border rounded-[var(--radius)] shadow-sm flex flex-col">
        <div class="p-4 border-b border-border">
            <h3 class="text-lg font-bold text-foreground">Keranjang</h3>
        </div>

        <div id="cart-items" class="flex-1 overflow-y-auto p-3 space-y-2">
            <div class="text-center text-muted-foreground py-12">
                <p class="text-lg mb-2">Keranjang kosong</p>
                <p class="text-sm">Pilih produk dari daftar</p>
            </div>
        </div>

        <div class="p-4 border-t border-border space-y-2">
            <div class="flex justify-between text-sm text-muted-foreground">
                <span>Subtotal</span>
                <span id="subtotal" class="font-semibold text-foreground">Rp 0</span>
            </div>
            <div id="discount-info"></div>
            <div id="tax-info" class="text-sm text-muted-foreground"></div>
            <hr class="border-border">
            <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span id="total-amount" class="text-primary">Rp 0</span>
            </div>

            <div class="pt-2 space-y-2">
                <div class="flex gap-2">
                    <label class="flex items-center gap-1 text-sm cursor-pointer">
                        <input type="radio" name="discount_type" value="percent" onchange="renderCart()"> Diskon %
                    </label>
                    <label class="flex items-center gap-1 text-sm cursor-pointer">
                        <input type="radio" name="discount_type" value="nominal" onchange="renderCart()"> Diskon Rp
                    </label>
                    <button type="button" onclick="document.querySelectorAll('input[name=\'discount_type\']').forEach(r => r.checked = false); document.getElementById('discount_value_input').value = ''; renderCart();" class="text-xs text-muted-foreground hover:text-foreground">Hapus</button>
                </div>
                <input type="number" id="discount_value_input" placeholder="Nilai diskon" min="0" step="0.01"
                    oninput="renderCart()"
                    class="w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" />
            </div>

            <div class="pt-2 space-y-1">
                <p class="text-xs text-muted-foreground font-medium">Metode Pembayaran</p>
                <div class="flex gap-2">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="payment_method" value="tunai" checked class="sr-only peer">
                        <div class="text-center px-3 py-2 border border-border rounded-[var(--radius)] text-sm peer-checked:bg-primary peer-checked:text-primary-foreground peer-checked:border-primary hover:bg-muted transition">Tunai</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="payment_method" value="kredit" class="sr-only peer">
                        <div class="text-center px-3 py-2 border border-border rounded-[var(--radius)] text-sm peer-checked:bg-warning peer-checked:text-warning-foreground peer-checked:border-warning hover:bg-muted transition">Kredit</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="payment_method" value="transfer" class="sr-only peer">
                        <div class="text-center px-3 py-2 border border-border rounded-[var(--radius)] text-sm peer-checked:bg-secondary peer-checked:text-secondary-foreground peer-checked:border-secondary hover:bg-muted transition">Transfer</div>
                    </label>
                </div>
            </div>

            <input type="text" id="customer_name" placeholder="Nama pelanggan (opsional)" class="w-full border border-input rounded-[var(--radius)] px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:border-transparent" />

            <button id="checkout-btn" onclick="submitCheckout()" disabled
                class="w-full bg-primary text-primary-foreground rounded-[var(--radius)] py-4 text-lg font-bold opacity-50 cursor-not-allowed transition">
                Bayar
            </button>
        </div>
    </div>
</div>

<form id="checkout-form" method="POST" action="{{ route('app.kasir.sales.checkout') }}" class="hidden">
    @csrf
    <input type="hidden" id="items-json" name="items" value="" />
    <input type="hidden" id="checkout-customer_name" name="customer_name" value="" />
    <input type="hidden" id="checkout-discount_type" name="discount_type" value="" />
    <input type="hidden" id="checkout-discount_value" name="discount_value" value="" />
    <input type="hidden" id="checkout-payment_method" name="payment_method" value="" />
</form>
@endsection
