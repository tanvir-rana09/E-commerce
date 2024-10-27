<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post("/register", [UserController::class, "register"]);
Route::post("/login", [UserController::class, "login"]);

// user route
Route::middleware(["auth:api"])->prefix("auth")->group(function () {
    Route::get("/profile", [UserController::class, "profile"]);
    Route::get("/logout", [UserController::class, "logout"]);
});

// product crud operation route
Route::middleware(["auth:api"])->prefix("product")->group(function () {
    Route::get("/", [ProductController::class, "getAllProducts"]);
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
