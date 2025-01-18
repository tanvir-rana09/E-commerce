<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;


Route::post("/register", [UserController::class, "register"]);
Route::post("/login", [UserController::class, "login"]);
Route::post('/track-visit', [VisitorController::class, 'trackVisit']);
Route::get('/visit-count', [VisitorController::class, 'getVisitCount']);
Route::get('/site-settings', [SiteSettingController::class, 'getAllSettings']);
Route::get("/category", [CategoryController::class, "getAllCategories"]);
Route::get('/category/ids-names', [CategoryController::class, 'getCategoryIdsAndNames']);
Route::get("/section", [SectionController::class, "getAllSections"]);
Route::get('/product/ids-names', [ProductController::class, 'getProductIdsAndNames']);
Route::get('/products', [ProductController::class, 'getAllProductIdsAndNames']);
Route::post("/order/create", [OrderController::class, "createOrder"]);
Route::middleware(["auth:api"])->prefix("auth")->group(function () {
    Route::get("/profile", [UserController::class, "profile"]);
    Route::get("/logout", [UserController::class, "logout"]);
    Route::put("/update-profile", [UserController::class, "changeProfile"]);
    Route::put("/update-details", [UserController::class, "updateUserDetails"]);
    Route::put("/update-password", [UserController::class, "changePassword"]);
});
Route::middleware(["auth:api"])->prefix("users")->group(function () {
    Route::get("/all-users", [UserController::class, "getAllUsers"])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::put("/update-role/{id}", [UserController::class, "updateUserRole"])->middleware(['role:admin']);
    Route::delete("/delete/{id}", [UserController::class, "deleteUser"])->middleware(['role:admin']);
});


Route::middleware(["auth:api"])->prefix("product")->group(function () {
    Route::get("/", [ProductController::class, "getAllProducts"])->withoutMiddleware(["auth:api"]);
    Route::get("/comments/{id}", [ProductController::class, "productComments"]);
    Route::post("/add", [ProductController::class, "addProduct"])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::put("/update/{id}", [ProductController::class, "updateProduct"])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::delete("/delete/{id}", [ProductController::class, "deleteProduct"])->middleware(['role:admin']);
});


Route::middleware(["auth:api",'checkUserRole:moderator,reader,admin'])->prefix("category")->group(function () {
    Route::post("/add", [CategoryController::class, "addCategory"]);
    Route::put("/update/{id}", [CategoryController::class, "updateCategory"]);
    Route::delete("/delete/{id}", [CategoryController::class, "deleteCategory"]);

});

// category blog operation route
Route::middleware(["auth:api"])->prefix("blog")->group(function () {
    Route::get("/", [BlogController::class, "getAllBlogs"]);
    Route::post("/add", [BlogController::class, "addBlog"]);
    Route::put("/update/{id}", [BlogController::class, "updateBlog"]);
    Route::delete("/delete/{id}", [BlogController::class, "deleteBlog"]);
});

// category blog operation route
Route::middleware(["auth:api"])->prefix("comment")->group(function () {
    Route::get("/", [CommentController::class, "getAllComments"]);
    Route::post("/add", [CommentController::class, "addComment"]);
    Route::put("/update/{id}", [CommentController::class, "updateComment"]);
    Route::delete("/delete/{id}", [CommentController::class, "deleteComment"]);
});


// category blog operation route
Route::middleware(["auth:api","checkUserRole:moderator,reader,admin"])->prefix("section")->group(function () {
    Route::post("/add", [SectionController::class, "addSection"]);
    Route::put("/update/{id}", [SectionController::class, "updateSection"]);
    Route::delete("/delete/{id}", [SectionController::class, "deleteSection"]);
});

// category blog operation route
Route::middleware(["auth:api"])->prefix("order")->group(function () {
    Route::get("/product/{id?}", [OrderController::class, "orderedItems"]);
    Route::get("/{id?}", [OrderController::class, "userOrders"]);
    Route::put("/cancel/{id}", [OrderController::class, "cancelOrder"]);
    Route::put("/item/cancel", [OrderItemsController::class, "cancelOrderItem"]);
    Route::get("/order-items/{id}", [OrderItemsController::class, "OrderItems"]);
    Route::middleware(["checkUserRole:moderator,reader,admin"])->prefix('admin')->group(function () {
        Route::get("/all-orders", [OrderController::class, "Allorders"]);
        Route::get("/single-order", [OrderController::class, "singleOrder"]);
        Route::put("/update/{id}", [OrderController::class, "adminOrderUpdate"]);
        Route::delete("/delete/{id}", [OrderController::class, "adminDestroy"]);
    });
});


Route::middleware(["auth:api"])->group(function () {
    Route::post('/site-settings', [SiteSettingController::class, 'updateSettings'])->middleware(['role:admin']);
    Route::get('/statistics', [VisitorController::class, 'statistics'])->middleware(['checkUserRole:moderator,reader,admin']);
});


Route::middleware(["auth:api"])->prefix('coupons')->group(function () {
    Route::post('/', [CouponController::class, 'createCoupon'])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::get('/', [CouponController::class, 'getCoupons']);
    Route::post('/apply', [CouponController::class, 'applyCoupon']);
    Route::delete('/{id}', [CouponController::class, 'deleteCoupon'])->middleware(['role:admin']);
});

Route::middleware(["auth:api"])->prefix('discounts')->group(function () {
    Route::post('/add', [DiscountController::class, 'setDiscount'])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::delete('/{id}', [DiscountController::class, 'removeDiscount'])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::get('/', [DiscountController::class, 'getDiscounts']);
});


Route::prefix('content')->group(function () {
    Route::post('/{type}', [ContentController::class, 'upsertContent'])->middleware(["auth:api"])->middleware(['checkUserRole:moderator,reader,admin']);
    Route::get('/{type}', [ContentController::class, 'getContent']);
});