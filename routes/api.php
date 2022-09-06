<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TaskAssignedToController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post("/register", [UserController::class, "register"]);
Route::post("/login", [UserController::class, "login"]);
Route::post("/verify-email", [UserController::class, "verifyEmail"]);
Route::post("/resend-verify-email", [UserController::class, "resendVerifyEmailCode"]);

Route::middleware(['auth:sanctum'])->group(function () {
  Route::get("/user", [UserController::class, "getUser"]);
  Route::get("/get-user-boards", [UserController::class, "getUserBoards"]);
  Route::get("/get-user-archived-boards", [UserController::class, "getUserArchivedBoards"]);

  Route::post("/create-board", [BoardController::class, "add"]);
  Route::post("/accept-board-invite", [BoardController::class, "acceptInvite"]);
  Route::post("/send-invite", [BoardController::class, "sendInvite"]);
  Route::put("/update-board/{id}", [BoardController::class, "update"]);
  Route::put("/archive-board/{id}", [BoardController::class, "archive"]);
  Route::delete("/delete-board/{id}", [BoardController::class, "delete"]);
  Route::get("/get-joined-boards", [BoardController::class, "getBoardsWhereUserIsMember"]);
  Route::get("/board/{slug}", [BoardController::class, "getBoard"]);
  Route::get("/get-board-members/{slug}", [BoardController::class, "getBoardMembers"]);
  Route::put("/change-boardmember-role", [BoardController::class, "changeBoardMemberRole"]);

  Route::get("/get-statuses/{id}", [StatusController::class, "getAllStatusesForBoard"]);
  //Route::get("/get-status/{id}", [StatusController::class, "get"]);
  Route::post("/create-status", [StatusController::class, "add"]);
  Route::put("/update-status/{id}", [StatusController::class, "update"]);
  Route::delete("/delete-status/{id}", [StatusController::class, "delete"]);

  Route::post("/create-task", [TaskController::class, "add"]);
  Route::delete("/delete-task/{id}", [TaskController::class, "delete"]);
  Route::get("/get-tasks/{id}", [TaskController::class, "getAllTasksForStatus"]);
  //  Route::get("/get-task/{id}", [TaskController::class, "get"]);
  Route::put("/update-task/{id}", [TaskController::class, "update"]);
  Route::put("/archive-task/{id}", [TaskController::class, "archive"]);

  Route::get("/get-task-assigned-users/{id}", [TaskAssignedToController::class, "getAssignedUsers"]);
  Route::post("/assign-task-to-user", [TaskAssignedToController::class, "assignTaskToUser"]);
});
