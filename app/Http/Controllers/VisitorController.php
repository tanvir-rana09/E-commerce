<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\Request;

use function Pest\Laravel\get;

class VisitorController extends Controller
{
    public function trackVisit()
    {
        $ip = request()->ip();
        $visit = Visitor::create(['ip' => $ip]);
        return response()->json(['ip' => $visit->ip]);
    }

    public function getVisitCount()
    {
        $visit = Visitor::first();
        return response()->json(['ip' => $visit->ip ?? 0]);
    }

    public function statistics(Request $request)
    {
        // Retrieve date range from the request
        $startDate = $request->input('start_date', null);
        $endDate = $request->input('end_date', null);

        // Define a date filter
        $dateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        };

        // Orders
        $totalOrders = Order::where($dateFilter)->count();
        $pendingOrders = Order::where('status', 'pending')->where($dateFilter)->count();
        $successfulOrders = Order::where('status', 'completed')->where($dateFilter)->count();
        $canceledOrders = Order::where('status', 'canceled')->where($dateFilter)->count();

        // Products
        $totalProducts = Product::where($dateFilter)->count();
        $activeProducts = Product::where('status', 1)->where($dateFilter)->count();
        $inactiveProducts = Product::where('status', 0)->where($dateFilter)->count();
        $lowStockProducts = Product::where('stock', '<=', 5)->where($dateFilter)->count();

        // Categories
        $totalCategories = Category::where($dateFilter)->count();
        $activeCategories = Category::where('status', 1)->where($dateFilter)->count();
        $inactiveCategories = Category::where('status', 0)->where($dateFilter)->count();

        // Users
        $totalUsers = User::where($dateFilter)->count();
        $adminUsers = User::where('role', 'admin')->where($dateFilter)->count();
        $moderatorUsers = User::where('role', 'moderator')->where($dateFilter)->count();
        $editorUsers = User::where('role', 'editor')->where($dateFilter)->count();


        $totalRevenue = Order::where('status', 'completed')
            ->where($dateFilter)
            ->sum('total_price');

        $totalOrder = Order::count();

        $returningCustomers = User::with(['orders' => function ($query) use ($startDate, $endDate) {
            $query->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
                ->when($endDate, function ($query) use ($endDate) {
                    $query->whereDate('created_at', '<=', $endDate);
                });
        }])
            ->get()
            ->filter(function ($user) {
                return $user->orders->count() > 1;
            })->count();


        $returningCustomersPercentage = $totalOrder > 0 ? ($returningCustomers / $totalOrder) * 100 : 0;

        $totalVisitors = Visitor::where($dateFilter)->count();
        $conversionRate = $totalVisitors > 0 ? ($totalOrders / $totalVisitors) * 100 : 0;


        $data = [
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'successful' => $successfulOrders,
                'canceled' => $canceledOrders,
            ],
            'products' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'inactive' => $inactiveProducts,
                'low_stock' => $lowStockProducts,
            ],
            'categories' => [
                'total' => $totalCategories,
                'active' => $activeCategories,
                'inactive' => $inactiveCategories,
            ],
            'users' => [
                'total' => $totalUsers,
                'admins' => $adminUsers,
                'moderators' => $moderatorUsers,
                'editors' => $editorUsers,
            ],
            'revenue' => $totalRevenue.' TK',
            'returning_customers' => round($returningCustomersPercentage, 2).'%',
            'conversion_rate' => round($conversionRate,2).'%',
        ];

        return response()->json(['data' => $data, 'status' => 200], 200);
    }
}
