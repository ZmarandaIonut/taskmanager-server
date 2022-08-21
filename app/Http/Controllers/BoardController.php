<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Boards;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BoardController extends ApiController
{

    public function createBoard(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'owner_id' => 'required|exists:users,id',
            ]);
            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }

            $board = new Board();
            $board->name = $request->get("name");
            $board->owner_id = $request->get("owner_id");
            $board->slug = Str::random(15);

            $board->save();

            return $this->sendResponse($board->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            //    error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
