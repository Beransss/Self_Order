@extends('employee.layouts.master')

@section('container')
    {{-- Notification Error Success --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Alert Container untuk Notifikasi Dynamic --}}
    <div id="alert-container"></div>

    {{-- Order Listing --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card rounded shadow" style="border: none;">
                <div class="card-body">
                    {{-- Caption Header --}}
                    <div class="container mb-3 mt-3">
                        <div class="row">
                            <div class="col-6">
                                <div class="row">
                                    <div class="col-12">
                                        <b>Waiting List</b>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-secondary">A list of all waiting order for your employee
                                            here.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <button class="btn-outline-dark btn btn-sm" id="btn-accept" type="button"
                                    onclick="OnButtonAccept()">Accept</button>
                            </div>
                        </div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <input class="form-check-input" type="checkbox" id="select-all" onchange="SelectAll()">
                                </th>
                                <th scope="col">Table Number</th>
                                <th scope="col">Description</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Payment Method</th>
                            </tr>
                        </thead>
                        <tbody id="table-orders">
                            @if ($orders->count() > 0)
                                @foreach ($orders as $order)
                                    <tr id="order-row-{{ $order->id }}" data-order-id="{{ $order->id }}">
                                        <td>
                                            <input class="form-check-input order-checkbox" type="checkbox" 
                                                value="{{ $order->id }}"
                                                name="select-item[]" 
                                                data-uuid="{{ $order->uuid }}" 
                                                data-status="P"
                                                data-menu-id="{{ $order->menu_id }}">
                                        </td>
                                        <td>{{ $order->table_no }}</td>
                                        <td>{{ $order->menu->name }}</td>
                                        <td>{{ $order->order_qty }}</td>
                                        <td>
                                            @switch($order->payment)
                                                @case('BCA')
                                                @case('Bca')
                                                    Transfer BCA
                                                @break

                                                @case('Mandiri')
                                                    Transfer Mandiri
                                                @break

                                                @case('BNI')
                                                @case('Bni')
                                                    Transfer BNI
                                                @break

                                                @case('BRI')
                                                @case('Bri')
                                                    Transfer BRI
                                                @break

                                                @case('BSI')
                                                @case('Bsi')
                                                    Transfer BSI
                                                @break

                                                @case('Cash')
                                                    Pembayaran Tunai/Cash
                                                @break

                                                @default
                                                    {{ $order->payment }}
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="no-record">
                                    <td class="text-center" colspan="5">
                                        There is no record for this menus
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Select All Checkbox
        function SelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Accept Button Handler
        function OnButtonAccept() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            
            if (checkboxes.length === 0) {
                showAlert('Please select at least one order to accept', 'warning');
                return;
            }

            // Disable button untuk cegah double click
            const btnAccept = document.getElementById('btn-accept');
            btnAccept.disabled = true;
            btnAccept.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';

            let processedCount = 0;
            const totalOrders = checkboxes.length;

            checkboxes.forEach((checkbox, index) => {
                const uuid = checkbox.getAttribute('data-uuid');
                const status = checkbox.getAttribute('data-status');
                const menuId = checkbox.getAttribute('data-menu-id');
                const orderId = checkbox.value;

                // AJAX Request
                fetch(`/order/waiting-list/status/${uuid}/${status}/${menuId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        // Remove row dari table dengan animasi
                        const row = document.getElementById(`order-row-${orderId}`);
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            
                            setTimeout(() => {
                                row.remove();
                                
                                // Check jika sudah tidak ada order lagi
                                const remainingRows = document.querySelectorAll('#table-orders tr[data-order-id]');
                                if (remainingRows.length === 0) {
                                    document.getElementById('table-orders').innerHTML = `
                                        <tr id="no-record">
                                            <td class="text-center" colspan="5">
                                                There is no record for this menus
                                            </td>
                                        </tr>
                                    `;
                                }
                            }, 300);
                        }
                    }
                    
                    processedCount++;
                    
                    // Jika semua sudah diproses
                    if (processedCount === totalOrders) {
                        // Enable button kembali
                        btnAccept.disabled = false;
                        btnAccept.innerHTML = 'Accept';
                        
                        // Uncheck select-all
                        document.getElementById('select-all').checked = false;
                        
                        // Show success notification
                        showAlert(`${totalOrders} order(s) successfully accepted!`, 'success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    processedCount++;
                    
                    if (processedCount === totalOrders) {
                        btnAccept.disabled = false;
                        btnAccept.innerHTML = 'Accept';
                        showAlert('Some orders failed to process. Please try again.', 'danger');
                    }
                });
            });
        }

        // Show Alert Function
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        alertContainer.innerHTML = '';
                    }, 150);
                }
            }, 5000);
        }
    </script>
@endsection