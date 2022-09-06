<?php

namespace App\Http\Controllers;

use App\Models\BoardMembers;
use App\Models\TaskAssignedTo;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskAssignedToController extends ApiController
{
    public function assignTaskToUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "task_id" => "required|exists:tasks,id",
                "board_id" => "required",
                "email" => "required|exists:users,email"
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->toArray());
            }
            $user = User::where("email", $request->get("email"))->first();
            $userID = $user->id;
            $authUser = Auth::user();
            $isAuthUserBoardMember = BoardMembers::where("user_id", $authUser->id)->where("board_id", $request->get("board_id"))->first();
            if (!$isAuthUserBoardMember) {
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }
            $isUserBoardMember = BoardMembers::where("user_id", $userID)->where("board_id", $request->get("board_id"))->first();
            if (!$isUserBoardMember) {
                return $this->sendError("This user is not a board member", []);
            }
            $isUserAlreadyAsigned = TaskAssignedTo::where("task_id", $request->get("task_id"))->where("assigned_to", $userID)->first();
            if ($isUserAlreadyAsigned) {
                return $this->sendError("This user is already assigned", []);
            }

            $assignUser = new TaskAssignedTo();
            $assignUser->task_id = $request->get("task_id");
            $assignUser->assigned_to = 1;
            $assignUser->save();

            return $this->sendResponse([], 201);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
