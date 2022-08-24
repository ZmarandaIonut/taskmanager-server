<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardInvites;
use App\Models\BoardMembers;
use App\Models\Boards;
use App\Models\User;
use App\Notifications\SendBoardInvite;
use App\Notifications\VerifyEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notification as NotificationsNotification;
use Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use Illuminate\Support\Str;

class BoardController extends ApiController
{

    public function add(Request $request)
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

            $boardMember = new BoardMembers();
            $boardMember->board_id = $board->id;
            $boardMember->user_id = $board->owner_id;
            $boardMember->role = "Admin";

            $boardMember->save();


            return $this->sendResponse($board->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $board = Board::find($id);
            if (!$board) {
                return $this->sendError("Board not found", [], Response::HTTP_NOT_FOUND);
            }

            $user = Auth::user();

            $foundUser = BoardMembers::where("board_id", $id)->where("user_id", $user->id)->first();

            if (!$foundUser || $foundUser->role !== "Admin") {
                return $this->sendError("Not allowed to update this board", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
            ]);

            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }

            $board->name = $request->get("name");

            $board->save();
            return $this->sendResponse($board->toArray());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id)
    {
        try {
            $board = Board::find($id);
            if (!$board) {
                return $this->sendError("Board not found", [], Response::HTTP_NOT_FOUND);
            }
            $user = Auth::user();

            if ($user->id !== $board->owner_id) {
                return $this->sendError("Not allowed to delete this board", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            DB::beginTransaction();
            $board->delete();
            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function sendInvite(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'board_id' => 'required|exists:boards,id',
                'email' => 'required|email'
            ]);
            if ($validate->fails()) {
                return $this->sendError($validate->messages()->toArray());
            }
            $authUser = Auth::user();
            $foundUser = BoardMembers::where("board_id", $request->get("board_id"))->where("user_id", $authUser->id)->first();

            if (!$foundUser || $foundUser->role !== "Admin") {
                return $this->sendError("Not allowed to invite users to this board", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            $lastUserboardInvite = BoardInvites::where('email', $request->get('email'))
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($lastUserboardInvite) {
                if ($lastUserboardInvite->created_at > Carbon::now()->subHour()) {
                    return $this->sendError('Your can invite this user again after 1 hour!', [], Response::HTTP_NOT_ACCEPTABLE);
                }
            }

            $code = Str::random(6);

            FacadesNotification::route("mail", $request->get("email"))->notify(new SendBoardInvite($code, $authUser->name));
            $boardInvites = new BoardInvites();
            $boardInvites->board_id = $request->get("board_id");
            $boardInvites->email = $request->get("email");
            $boardInvites->code = $code;
            $boardInvites->save();

            return $this->sendResponse(['Code for joining the board has been sent to the user email.']);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
