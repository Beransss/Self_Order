@extends('guest.layouts.master_guest')

@section('container')
    <form action="{{ route('guest.cart.store') }}" method="POST">
        @csrf
        <div class="container-fluid">
            <div class="border-bottom container">
                {{-- Select All --}}
                <div class="row mb-2">
                    <div class="col-6 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" onchange="SelectAllCart()" id="select-all">
                            <label class="form-check-label" for="select-all">
                                Select All
                            </label>
                        </div>
                    </div>
                    {{-- Delete Selected --}}
                    <div class="col-6 text-end">
                        <button class="btn btn-light btn-sm" name="delete-selected-items" type="submit">
                            <i class='bx bx-trash' style="font-size: 20px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Main Content Items --}}
            @foreach ($groupedCarts as $carts => $items)
                @php
                    $item = $items->first();
                @endphp
                <div class="border-bottom container my-2">
                    <div class="row mb-2">
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
                        <div class="col-4 justify-content-center d-flex align-items-center">
                            <img src="{{ asset('/storage/uploads/' . $item->menu->image_path) }}" class="img-fluid rounded">
                        </div>
                        <div class="col-7">
                            <h5 class="card-title">{{ $item->menu->name }}</h5>
                            <p class="card-text">{{ $item->menu->description }}</p>
                        </div>
                    </div>
                    <div class="row my-2">
                        <div class="col-6 d-flex align-items-center">
                            <input type="hidden" name="normal-price[]" value="{{ $item->menu->amount }}">
                            <h5 class="total-price">Rp.{{ $item->menu->amount * $item->order_qty }}
                            </h5>
                        </div>
                        <div class="col-6 text-end">
                            <button class="cs-decrease shadow-sm" type="button" onclick="OnDecrease(this)">
                                <i class='bx bx-minus'></i>
                            </button>
                            <input class="cs-form-quantity order-qty border-bottom text-center" type="number"
                                name="quantityInput[]" value="{{ $item->order_qty }}" min="1" max="99">
                            <button class="cs-increase shadow-sm" type="button" onclick="OnIncrease(this)">
                                <i class='bx bx-plus'></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
        @php
            $totalSubtotal = 0;
        @endphp
        @foreach ($groupedCarts as $carts => $items)
            @php
                $item = $items->first();
                $subtotal = $item->menu->amount * $item->order_qty;
                $totalSubtotal += $subtotal;
            @endphp
        @endforeach
        <footer class="fixed-bottom navbar navbar-expand-lg navbar-dark bg-dark" style="height: 60px;">
            <div class="container-fluid d-flex align-items-center">
                <div class="ml-auto">
                    <h5 class="navbar-brand" id="sub-total">Rp.{{ $totalSubtotal }}</h5>
                </div>
                <div class="mr-auto">
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
        </footer>
    </form>
@endsection

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

{{-- Modal Payment Option --}}
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
