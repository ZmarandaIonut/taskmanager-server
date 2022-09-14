<?php

namespace App\Http\Controllers;

use App\Models\BoardMembers;
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

            $comment = $request->get('comment');
            $task_id = $request->get('task_id');
            $user = Auth::user();
            $board = Task::find($task_id)->status->board;

            if (!BoardMembers::where("user_id", $user->id)->where("board_id", $board->id)->first()) {
                return $this->sendError("Not allowed", [], Response::HTTP_UNAUTHORIZED);
            }

            $taskComment = new TaskComment();
            $taskComment->comment =  $comment;
            $taskComment->task_id = $task_id;
            $taskComment->user_id = $user->id;
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
            if (!$task) {
                return $this->sendError('Task not found!', [], Response::HTTP_NOT_FOUND);
            }

            $user = Auth::user();
            $board = $task->status->board;

            if (!BoardMembers::where("user_id", $user->id)->where("board_id", $board->id)->first()) {
                return $this->sendError("Not allowed", [], Response::HTTP_UNAUTHORIZED);
            }

            $comments = TaskComment::where('task_id', $task->id)->paginate(10);
            $result = [
                "comments" => [],
                "currentPage" => $comments->currentPage(),
                "hasMorePages" => $comments->hasMorePages(),
                "lastPage" => $comments->lastPage()
            ];

            foreach ($comments as $comment) {
                $result["comments"][] = $comment;
            }

            return $this->sendResponse($result);
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
            $user = Auth::user();
            $board = Task::find($comment->task_id)->status->board;

            if (!BoardMembers::where("user_id", $user->id)->where("board_id", $board->id)->first()) {
                return $this->sendError("Not allowed", [], Response::HTTP_UNAUTHORIZED);
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
