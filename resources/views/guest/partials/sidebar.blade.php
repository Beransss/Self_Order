<div class="container-fluid">
    <form action="{{ route('guest.cart.store') }}" method="POST">
        @csrf
        @method('POST')
        {{-- Cart --}}
        <div class="card mb-4 rounded shadow" style="border: none;">
            <div class="card-header" style="border: none;">
                Your Cart
            </div>
            <div class="card-body overflow-auto" style="max-height: 450px;">
                <div class="border-bottom container">
                    {{-- Select All --}}
                    <div class="row mb-2">
                        <div class="col-12 col-lg-6 col-sm-6 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" onchange="SelectAllCart()"
                                    id="select-all">
                                <label class="form-check-label" for="select-all">
                                    Select All
                                </label>
                            </div>
                        </div>
                        {{-- Delete Selected --}}
                        <div class="col-12 col-lg-6 col-sm-6 text-end">
                            <button class="btn btn-light btn-sm" name="delete-selected-items" type="submit">
                                <i class='bx bx-trash' style="font-size: 20px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Main Content Items --}}
                @if ($carts->count() > 0)
                    @foreach ($carts as $items => $item)
                        @php
                            $item = $item->first();
                        @endphp
                        <div class="border-bottom container my-2" id="cart-container">
                            <div class="row mb-2">
                                <div class="col-12">
                                    <h5 class="card-title" id="menu-title">{{ $item->menu->name }}</h5>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-1 d-flex align-items-center">
                                    <div class="form-check">
                                        <input type="hidden" name="user_id" value="{{ $uuid }}">
                                        <input type="hidden" name="menu_id[]" value="{{ $item->menu_id }}"
                                            data-menu-id="{{ $item->menu_id }}">
                                        <input type="hidden" name="menu_price[]" value="{{ $item->menu->amount }}"
                                            data-menu-price="{{ $item->menu->amount }}">
                                        <input class="form-check-input" type="checkbox" name="select-item[]"
                                            value="{{ $item->menu->id }}">
                                    </div>
                                </div>
                                <div
                                    class="col-12 col-lg-3 col-md-10 justify-content-center d-flex align-items-center mb-2">
                                    <img src="{{ asset('/storage/uploads/' . $item->menu->image_path) }}"
                                        class="img-fluid rounded" alt="" id="menu-image">
                                </div>
                                <div class="col-12 col-lg-8 col-md-12">
                                    <p class="card-text" id="menu-description">{{ $item->menu->description }}</p>
                                </div>
                                <div class="col-12 col-md-12 col-lg-6 d-flex align-items-center pt-1">
                                    <h5 class="total-price">Rp.{{ $item->menu->amount * $item->order_qty }}</h5>
                                </div>
                                <div class="col-12 col-md-12 col-lg-6 text-end">
                                    {{-- Quantity --}}
                                    <button class="cs-decrease shadow-sm" type="button" onclick="OnDecrease(this)">
                                        <i class='bx bx-minus'></i>
                                    </button>
                                    <input class="cs-form-quantity order-qty border-bottom text-center" type="number"
                                        name="quantityInput[]" value="{{ $item->order_qty }}" min="1"
                                        max="99">
                                    <button class="cs-increase shadow-sm" type="button" onclick="OnIncrease(this)">
                                        <i class='bx bx-plus'></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="container my-5 text-center">
                        <p>Your cart is empty.</p>
                    </div>
                @endif

            </div>
        </div>


        {{-- Checkout --}}
        @php
            $totalSubtotal = 0;
        @endphp
        <div class="card rounded shadow" style="border:none;">
            <div class="card-header" style="border: none;">
                Checkout
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">Your cart subtotal</div>
                    <div class="col-1">:</div>
                    <div class="col text-end" id="sub-total">
                        Rp.{{ $summary != null ? $summary->total_amount : $totalSubtotal }}
                    </div>
                </div>
            </div>
            <div class="card-footer" style="border: none;">
                <div class="row">
                    <div class="col text-end">
                        <button class="btn btn-dark btn-sm" type="submit" id="checkout-btn" onclick="disableCheckout(this)">
                            Checkout
                        </button>

                        <script>
                        function disableCheckout(button) {
                            // Disable button setelah diklik
                            button.disabled = true;
                            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
                            
                            // Submit form
                            button.form.submit();
                        }
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Table Number --}}
<div class="modal fade" tabindex="-1" id="modal-table-no">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        {{-- Payment Table --}}
                        <div class="col-12 mb-3 text-center">
                            <h4>Choose Table Number</h4>
                        </div>
                        <form action="{{ route('guest.cart.table') }}" method="POST">
                            @csrf
                            @method('POST')
                            {{-- Payment Table --}}
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-center align-items-center">
                                    <input type="hidden" name="uuid" value="{{ $uuid }}">
                                    <input class="form-control form-control-lg cs-table-number text-center"
                                        type="text" name="table_no" id="table_no" style="width: 70px;" autofocus
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 3);">

                                </div>
                            </div>
                            <div class="col-12 mb-3 text-end">
                                <button class="btn btn-dark" type="submit">Next</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Payment Option - Bank Transfer --}}
<div class="modal fade" tabindex="-1" id="modal-payment">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Metode Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        {{-- BCA --}}
                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Bca']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='bx bxs-bank' style="font-size: 32px; color: #003d7a;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Bank BCA</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Transfer ke rekening BCA
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- Mandiri --}}
                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Mandiri']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='bx bxs-bank' style="font-size: 32px; color: #003d7a;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Bank Mandiri</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Transfer ke rekening Mandiri
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- BNI --}}
                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Bni']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='bx bxs-bank' style="font-size: 32px; color: #e67e22;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Bank BNI</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Transfer ke rekening BNI
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- BRI --}}
                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Bri']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='bx bxs-bank' style="font-size: 32px; color: #00529c;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Bank BRI</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Transfer ke rekening BRI
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- BSI --}}
                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Bsi']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='bx bxs-bank' style="font-size: 32px; color: #00a651;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Bank BSI</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Transfer ke rekening BSI
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-12 mb-3">
                            <a class="text-decoration-none text-dark"
                                href="{{ route('guest.cart.payment', ['uuid' => $uuid, 'payment' => 'Cash']) }}">
                                <div class="card hover-shadow">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3">
                                            <i class='dollar' style="font-size: 32px; color: #00a651;"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">Pembayaran Tunai/Cash</h5>
                                            <h6 class="card-subtitle text-body-secondary">
                                                Tagihan Dibayarkan ke Kasir
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .hover-shadow:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
</style>

@if (session('ShowTableOrder'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('modal-table-no'));
            modal.show();
        });
    </script>
@elseif (session('ShowPayment'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('modal-payment'));
            modal.show();
        });
    </script>
@endif