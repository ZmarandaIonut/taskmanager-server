<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardInvites;
use App\Models\BoardMembers;
use App\Models\Boards;
use App\Models\Task;
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
use \Laravel\Sanctum\PersonalAccessToken;

class BoardController extends ApiController
{

    public function add(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
            ]);
            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }
            $authUser = Auth::user();

            $board = new Board();
            $board->name = $request->get("name");
            $board->owner_id = $authUser->id;
            $board->slug = Str::random(15);

            $board->save();

            $boardMember = new BoardMembers();
            $boardMember->board_id = $board->id;
            $boardMember->user_id = $board->owner_id;
            $boardMember->role = "Admin";
            $boardMember->isBoardOwner = 1;

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

            $authUser = Auth::user();
            $foundUser = BoardMembers::where("board_id", $id)->where("user_id", $authUser->id)->first();

            if (!$foundUser || $foundUser->role !== "Admin") {
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
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

    public function archive($id)
    {
        try {
            $board = Board::find($id);
            $user = Auth::user();

            $foundUser = BoardMembers::where("board_id", $id)->where("user_id", $user->id)->first();

            if (!$foundUser || ($user->id !== $board->owner_id)) {
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if (!$board) {
                return $this->sendError('Board not found!', [], Response::HTTP_NOT_FOUND);
            }

            $board->isArchived = $board->isArchived ? false : true;
            $board->save();

            return $this->sendResponse($board->toArray());
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
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            $getInvitedUser = User::where("email", $request->get("email"))->first();

            if ($getInvitedUser) {
                $foundInvitedUserAsMemeber = BoardMembers::where("board_id", $request->get("board_id"))->where("user_id", $getInvitedUser->id)->first();

                if ($foundInvitedUserAsMemeber) {
                    return $this->sendError("User already is a board member!", [], Response::HTTP_NOT_ACCEPTABLE);
                }
            }
            $lastUserboardInvite = BoardInvites::where('email', $request->get('email'))
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($lastUserboardInvite) {
                if ($lastUserboardInvite->created_at > Carbon::now()->subHour()) {
                    return $this->sendError('You can invite this user again after 1 hour!', [], Response::HTTP_NOT_ACCEPTABLE);
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
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function acceptInvite(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'code' => 'required'
            ]);

            if ($validate->fails()) {
                return $this->sendError($validate->messages()->toArray());
            }
            $authUser = Auth::user();
            $checkUser = BoardInvites::where("code", $request->get("code"))->first();
            if (!$checkUser) {
                return $this->sendError("Invalid code", []);
            }

            if ($authUser->email !== $checkUser->email) {
                return $this->sendError("Not allowed to use this code", [], Response::HTTP_NOT_ACCEPTABLE);
            }

            $boardMember = new BoardMembers();
            $boardMember->board_id = $checkUser->board_id;
            $boardMember->user_id = $authUser->id;
            $boardMember->save();
            $checkUser->delete();

            return $this->sendResponse(["You successfully joined the board!"]);
        } catch (Exception $exception) {
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getBoardsWhereUserIsMember()
    {
        try {
            $authUser = Auth::user();
            $getUsers = BoardMembers::query();
            $getUser = $getUsers->where("user_id", $authUser->id)->where("isBoardOwner", 0)->paginate(10);
            $result = [
                "boards" => [],
                "currentPage" => $getUser->currentPage(),
                "hasMorePages" => $getUser->hasMorePages(),
                "lastPage" => $getUser->lastPage()
            ];
            foreach ($getUser->items() as $userBoardMember) {
                $board = $userBoardMember->getBoards;
                $result["boards"][] = $board;
            }
            return $this->sendResponse($result);
        } catch (Exception $exception) {
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getBoard($slug)
    {
        try {
            $board = Board::where("slug", $slug)->first();
            if (!$board) {
                return $this->sendError("Board not found", [], Response::HTTP_NOT_FOUND);
            }
            $authUser = Auth::user();

            $getUserAsBoardMember = BoardMembers::where("board_id", $board->id)->where("user_id", $authUser->id)->first();

            if (!$getUserAsBoardMember) {
                return $this->sendError("Not allowed to visit this board", [], Response::HTTP_FORBIDDEN);
            }

            $boardStatuses = $board->statuses;
            $boardContent = [];
            for ($i = 0; $i < count($boardStatuses); $i++) {
                $boardContent[] = $boardStatuses[$i]->toArray();
                $boardContent[$i]["tasks"] = $boardStatuses[$i]->tasks;
            }
            $result = [
                "statuses" => $boardContent,
                "userRole" => $getUserAsBoardMember->role,
                "isBoardOwner" => $getUserAsBoardMember->isBoardOwner
            ];
            return $this->sendResponse($result);
        } catch (Exception $exception) {
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getBoardMembers($slug)
    {
        try {
            $board = Board::where("slug", $slug)->first();
            $authUser = Auth::user();

            if (!$board) {
                return $this->sendError("Board not found", [], Response::HTTP_NOT_FOUND);
            }

            $isAuthUserMember = BoardMembers::where("board_id", $board->id)->where("user_id", $authUser->id)->first();

            if (!$isAuthUserMember) {
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            $members = BoardMembers::query();

            $boardMembers = $members->where("board_id", $board->id)->paginate(30);
            $result = [
                "members" => $boardMembers->items(),
                "currentPage" => $boardMembers->currentPage(),
                "hasMorePages" => $boardMembers->hasMorePages(),
                "lastPage" => $boardMembers->lastPage(),
                "totalMembers" => $boardMembers->total()
            ];
            return $this->sendResponse($result);
        } catch (Exception $exception) {
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
