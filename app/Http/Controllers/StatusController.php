<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardMembers;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatusController extends ApiController
{
    public function add(Request $request): JsonResponse
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'board_id' => 'required|exists:boards,id',
            ]);
            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }

            $status = new Status();
            $status->name = $request->get("name");
            $status->board_id = $request->get("board_id");

            $status->save();

            return $this->sendResponse($status->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllStatusesForBoard($boardId): JsonResponse
    {
        try {
            //$statuses = Status::where('board_id', $boardId)->get();
            $board = Board::find($boardId);
            $statuses = $board->statuses;

            if (!$statuses) {
                return $this->sendError('statuses not found!', [], Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse($statuses->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get($id): JsonResponse
    {
        try {

            $status = Status::find($id);

            if (!$status) {
                return $this->sendError('status not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($status->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request): JsonResponse
    {
        try {
            $status = Status::find($id);
            $user = Auth::user();
            if ($user->id != $status->board->owner_id) {
                return $this->sendError("Not allowed to update this status", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if (!$status) {
                return $this->sendError('status not found!', [], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');


            $status->name = $name;
            $status->save();

            return $this->sendResponse($status->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $status = Status::find($id);

            if (!$status) {
                return $this->sendError('status not found!', [], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            $status->delete();

            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
