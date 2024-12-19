<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
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

// user route
Route::middleware(["auth:api"])->prefix("auth")->group(function () {
    Route::get("/profile", [UserController::class, "profile"]);
    Route::get("/logout", [UserController::class, "logout"]);
});

// product crud operation route
Route::middleware(["auth:api"])->prefix("product")->group(function () {
    Route::get("/", [ProductController::class, "getAllProducts"]);
    Route::get("/comments/{id}", [ProductController::class, "productComments"]);
    Route::post("/add", [ProductController::class, "addProduct"]);
    Route::put("/update/{id}", [ProductController::class, "updateProduct"]);
    Route::delete("/delete/{id}", [ProductController::class, "deleteProduct"]);
});

// category crud operation route
Route::middleware(["auth:api"])->prefix("category")->group(function () {
    Route::get("/", [CategoryController::class, "getAllCategories"]);
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
Route::middleware(["auth:api"])->prefix("section")->group(function () {
    Route::get("/", [SectionController::class, "getAllSections"]);
    Route::post("/add", [SectionController::class, "addSection"]);
    Route::put("/update/{id}", [SectionController::class, "updateSection"]);
    Route::delete("/delete/{id}", [SectionController::class, "deleteSection"]);
});

// category blog operation route
Route::middleware(["auth:api"])->prefix("order")->group(function () {
    Route::get("/product/{id?}", [OrderController::class, "orderedItems"]);
    Route::get("/{id?}", [OrderController::class, "userOrders"]);
    Route::post("/create", [OrderController::class, "createOrder"]);
    Route::put("/cancel/{id}", [OrderController::class, "cancelOrder"]);
    Route::get("/order-items/{id}", [OrderItemsController::class, "OrderItems"]);
    Route::prefix('admin')->group(function () {
        Route::get("/all-orders", [OrderController::class, "Allorders"]);
        Route::get("/single-order", [OrderController::class, "singleOrder"]);
        Route::put("/update/{id}", [OrderController::class, "adminOrderUpdate"]);
        Route::delete("/delete/{id}", [OrderController::class, "adminDestroy"]);
    });
});
Route::middleware(["auth:api"])->group(function () {
    Route::get('/site-settings', [SiteSettingController::class, 'getAllSettings']);
    Route::post('/site-settings', [SiteSettingController::class, 'updateSettings']);
});
