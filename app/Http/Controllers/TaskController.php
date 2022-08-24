<?php

namespace App\Http\Controllers;

use App\Models\BoardMembers;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends ApiController
{
    public function add(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'status_id' => 'required|exists:statuses,id',
            ]);
            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }

            $task = new Task();
            $task->name = $request->get("name");
            $task->status_id = $request->get("status_id");

            $task->save();

            return $this->sendResponse($task->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllTasksForStatus($statusId)
    {
        try {
            $status = Status::find($statusId);
            $tasks = $status->tasks;

            if (!$tasks) {
                return $this->sendError('tasks not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($tasks->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get($id)
    {
        try {
            $task = Task::find($id);

            if (!$task) {
                return $this->sendError('task not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($task->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $task = Task::find($id);
            $user = Auth::user();
            if ($user->id != $task->status->board->owner_id) {
                return $this->sendError("Not allowed to update this task", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if (!$task) {
                return $this->sendError('task not found!', [], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');


            $task->name = $name;
            $task->save();

            return $this->sendResponse($task->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id)
    {
        try {
            $task = Task::find($id);
            $user = Auth::user();
            if ($user->id != $task->status->board->owner_id) {
                return $this->sendError("Not allowed to update this task", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if (!$task) {
                return $this->sendError('task not found!', [], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            $task->delete();

            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function archive($id)
    {
        try {
            $task = Task::find($id);
            $user = Auth::user();

            $foundUser = BoardMembers::where("board_id", $task->status->board->id)->where("user_id", $user->id)->first();

            if (!$foundUser || $foundUser->role != BoardMembers::ADMIN) {
                return $this->sendError("Not allowed to update this task", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if (!$task) {
                return $this->sendError('task not found!', [], Response::HTTP_NOT_FOUND);
            }

            $task->isArchived = $task->isArchived ? false : true;
            $task->save();

            return $this->sendResponse($task->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
