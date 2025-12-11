<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Checkout;
use App\Events\CartEvents;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // New
        $uuid = $request->cookie('UUID');

        if (!$uuid) {
            $uuid = Str::uuid();

            return redirect()->route('guest.menu')
                ->cookie('UUID', $uuid, 60 * 24);
        }

        $categories = Category::all();
        $menus = Menu::all();

        $carts = Cart::where('uuid', $uuid)->get();
        $groupCarts = $carts->groupBy('menu_id');

        $summary = DB::table('carts')
            ->selectRaw('uuid, SUM(menu_price) as total_amount')
            ->where('uuid', $uuid)
            ->groupBy('uuid')
            ->first();

        return view('guest.pages.cart', [
            'title' => 'Checkout',
            'brand' => 'Your Cart',
            'categories' => $categories,
            'menus' => $menus,
            'uuid' => $uuid,
            'summary' => $summary,
            'groupedCarts' => $groupCarts,
        ]);
    }

    public function waitingList()
    {
        $orders = Checkout::where('order_status', 'W')->latest()->paginate(10);
        return view('employee.pages.order.waiting', [
            'title' => 'Waiting List',
            'orders' => $orders
        ]);
    }

    public function order()
    {
        $orders = Checkout::where('order_status', 'P')->latest();
        return view('employee.pages.order.order', [
            'title' => 'Orders',
            'orders' => $orders->get(),
        ]);
    }

    public function store(Request $request)
    {
        $uuid = $request->input('user_id');
        $menu_ids = $request->input('menu_id');
        $select_items = $request->input('select-item');
        $quantityInputs = $request->input('quantityInput');

        if ($request->has('delete-selected-items')) {
            if ($select_items != null) {
                foreach ($select_items as $items) {
                    $this->DeleteRecordCart($uuid, $items);
                }
            }

            $this->DeleteRecordCheckout($uuid);
            return back();
        }

        foreach ($menu_ids as $key => $menu_id) {
            // Cek apakah sudah ada order dengan uuid dan menu_id yang sama DAN belum ada payment
            $existingOrder = Checkout::where([
                'uuid' => $uuid, 
                'menu_id' => $menu_id
            ])
            ->whereNull('payment')
            ->whereNull('order_status')
            ->first();
            
            if (!$existingOrder) {
                // Buat order baru
                $checkout = new Checkout();
                $checkout->uuid = $uuid;
                $checkout->menu_id = $menu_id;
                $checkout->order_qty = $quantityInputs[$key];
                $checkout->save();
            } else {
                // Update qty order yang sudah ada
                $existingOrder->order_qty = $quantityInputs[$key];
                $existingOrder->save();
            }
        }

        // Buat sesseion modal table no
        session()->flash('ShowTableOrder', true);
        return back();
    }

    // Table input
    public function save_table(Request $request)
    {
        // Cek apakah sudah pernah set table untuk UUID ini (dalam 10 detik terakhir)
        $recentTable = Checkout::where('uuid', $request->input('uuid'))
                                ->whereNotNull('table_no')
                                ->whereNull('payment')
                                ->where('created_at', '>', now()->subSeconds(10))
                                ->exists();
        
        if ($recentTable) {
            // Sudah ada table yang baru di-set, skip untuk cegah double submit
            session()->flash('ShowPayment');
            return back();
        }
        
        session()->flash('ShowPayment');

        // Update hanya order yang belum punya table_no (order baru)
        Checkout::where('uuid', $request->input('uuid'))
                ->whereNull('table_no')
                ->update(['table_no' => $request->input('table_no')]);

        return back();
    }

    // Payment method
    public function payment(string $uuid, string $payment)
    {
        // Ambil ID order pertama sebelum update untuk tracking
        $firstOrderId = Checkout::where('uuid', $uuid)
                               ->whereNull('payment')
                               ->whereNull('order_status')
                               ->min('id');
        
        // Update payment method HANYA untuk order yang belum punya payment
        $order = Checkout::where('uuid', $uuid)
                        ->whereNull('payment')
                        ->whereNull('order_status')
                        ->update([
                            'payment' => $payment, 
                            'order_status' => 'W'
                        ]);

        if ($order) {
            // Ambil hanya order yang baru saja diupdate
            $orders = Checkout::where('uuid', $uuid)
                             ->where('payment', $payment)
                             ->where('order_status', 'W')
                             ->whereNotNull('table_no')
                             ->get();

            foreach ($orders as $ordersend) {
                $message = "You have a new order!";
                event(new CartEvents($message, $ordersend));
            }
            
            // Simpan ID order pertama ke session untuk ditampilkan di invoice
            session(['last_order_id' => $firstOrderId]);
        }

        // Delete Cart
        $this->DeleteRecordCart($uuid, '');

        // GENERATE UUID BARU untuk order berikutnya
        $newUuid = Str::uuid();
        
        return redirect(route('guest.invoice'))
                ->cookie('UUID', $newUuid, 60 * 24);
    }

    public function orderStatus(string $uuid, string $status, string $menu_id)
    {
        session()->flash('MessageModal', true);
        Checkout::where(['uuid' => $uuid, 'menu_id' => $menu_id])->update(['order_status' => $status]);

        return response()->json(['status' => $status, 'message' => 'Pesanan sudah di terima.']);
    }

    private function UpdateRecord(string $uuid, string $menu_id): bool
    {
        $lpass = false;

        $checkout = Checkout::where(['uuid' => $uuid, 'menu_id' => $menu_id])->first();
        $cart = Cart::where(['uuid' => $uuid, 'menu_id' => $menu_id])->first();

        $checkout->order_qty = $cart->order_qty;
        $checkout->save();

        if ($checkout->save()) {
            return $lpass = true;
        }

        return $lpass;
    }

    // Checkrecord function
    private function Checkrecord(string $uuid, string $menu_id): bool
    {
        $lpass = false;
        $order = Checkout::where(['uuid' => $uuid, 'menu_id' => $menu_id])->first();

        if ($order != null) {
            return $lpass = true;
        }

        return $lpass;
    }

    // Deleterecord Function
    private function DeleteRecordCheckout(string $uuid): bool
    {
        $lpass = false;

        $orders = Checkout::where('uuid', $uuid)->get();

        if ($orders) {
            foreach ($orders as $order) {
                $order->delete();
            }
            return $lpass = true;
        }

        return $lpass;
    }

    private function DeleteRecordCart(string $uuid, string $menu_id): bool
    {
        $lpass = false;

        if ($menu_id != '') {
            $carts = Cart::where(['uuid' => $uuid, 'menu_id' => $menu_id])->get();
            foreach ($carts as $cart) {
                $cart->delete();
            }

            return $lpass = true;
        }

        $carts = Cart::where('uuid', $uuid)->get();

        if ($carts) {
            foreach ($carts as $cart) {
                $cart->delete();
            }
            return $lpass = true;
        }

        return $lpass;
    }
}