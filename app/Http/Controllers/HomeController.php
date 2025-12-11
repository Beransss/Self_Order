<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Checkout;
use App\Events\CartEvents;
use App\Models\TableOrder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;

class HomeController extends Controller
{
    /**
     * Home or Menu For Guest
     */
    public function index(Request $request)
    {
        $uuid = $request->cookie('UUID');

        if (!$uuid) {
            $uuid = Str::uuid();

            return redirect()->route('guest.menu')
                ->cookie('UUID', $uuid, 60 * 24);
        }

        $categories = Category::get();
        $menus = Menu::latest();

        // Check filter
        session()->forget('OpenCollapse');
        if (request('filter')) {
            $menus = Menu::where('category_code', request('filter'));
            session()->put('OpenCollapse', true);
        }

        $carts = Cart::where('uuid', $uuid)->get();
        $groupCarts = $carts->groupBy('menu_id');

        $summary = DB::table('carts')
            ->selectRaw('uuid, SUM(menu_price) as total_amount')
            ->where('uuid', $uuid)
            ->groupBy('uuid')
            ->first();

        return view("guest.pages.menu", [
            'title' => 'Menus',
            'brand' => 'Bajawa',
            'menus' => $menus->get(),
            'categories' => $categories,
            'carts' => $groupCarts,
            'summary' => $summary,
            'uuid' => $uuid
        ]);
    }

    public function invoice(Request $request)
    {
        // Ambil order_id dari session (yang di-set di CheckoutController)
        $orderId = session('last_order_id');
        
        if ($orderId) {
            // Tampilkan order berdasarkan ID yang baru saja di-checkout
            $orders = Checkout::where('id', '>=', $orderId)
                             ->where('order_status', 'W')
                             ->get();
        } else {
            // Fallback: ambil UUID dari cookie
            $uuid = $request->cookie('UUID');
            
            // Ambil HANYA order terbaru yang sudah ada payment dan status W
            $orders = Checkout::where('uuid', $uuid)
                             ->whereNotNull('payment')
                             ->where('order_status', 'W')
                             ->orderBy('created_at', 'desc')
                             ->get();
            
            // Group berdasarkan created_at untuk ambil order batch terakhir saja
            if ($orders->isNotEmpty()) {
                $latestCreatedAt = $orders->first()->created_at;
                $orders = $orders->filter(function($order) use ($latestCreatedAt) {
                    return $order->created_at->eq($latestCreatedAt);
                });
            }
        }

        $groupedOrder = $orders->groupBy('menu_id');
        $uuid = $request->cookie('UUID');

        return view("guest.pages.invoice", [
            'title' => 'Invoice',
            'groupedOrder' => $groupedOrder,
            'uuid' => $uuid
        ]);
    }

    public function resetCookie()
    {
        return redirect(route('guest.menu'))->withCookie(Cookie::forget('UUID'));
    }
}