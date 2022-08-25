<?php

namespace App\Http\Controllers;

use App\Models\BoardMembers;
use App\Models\Task;
use App\Models\TaskAssignedTo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class TaskAssignedToController extends ApiController
{
    public function changeTaskStatus($id)
    {
        try {
            $task = Task::find($id);
            $taskAssignedTo = TaskAssignedTo::where('task_id', $task->id)->first();
            $user = Auth::user();

            $foundUser = BoardMembers::where("board_id", $task->status->board->id)->where("user_id", $user->id)->first();

            if (!$foundUser) {
                return $this->sendError("Not allowed to update this task", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }
            if (!$task) {
                return $this->sendError('task not found!', [], Response::HTTP_NOT_FOUND);
            }

            $taskAssignedTo->isActive = $taskAssignedTo->isActive ? TaskAssignedTo::INACTIVE : TaskAssignedTo::ACTIVE;
            $taskAssignedTo->save();

            return $this->sendResponse($taskAssignedTo->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
