<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends ApiController
{
    public function add(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'comment' => 'required|max:200',
                'task_id' => 'required|exists:tasks,id'
            ]);

            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }


            $taskComment = new TaskComment();
            $taskComment->comment = $request->get('comment');
            $taskComment->task_id = $request->get('task_id');
            $taskComment->user_id = Auth::user()->id;
            $taskComment->save();

            return $this->sendResponse($taskComment->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get($task_id)
    {
        try {
            $task = Task::find($task_id);
            $comments = $task->comments;
            if (!$comments) {
                return $this->sendError('Comments not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($comments->toArray());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($comment_id)
    {
        try {
            $comment = TaskComment::find($comment_id);
            if (!$comment) {
                return $this->sendError('Comment not found!', [], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();
            $comment->delete();
            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
