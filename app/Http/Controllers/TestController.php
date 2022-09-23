<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardMembers;
use Illuminate\Support\Facades\Auth;


class TestController extends ApiController
{
    public function test()
    {
        $authUser = Auth::user();
        $query = BoardMembers::where("user_id", $authUser->id)->where("isBoardOwner", 0)->paginate(10);

        $result = [
            "boards" => [],
            "currentPage" => $query->currentPage(),
            "hasMorePages" => $query->hasMorePages(),
            "lastPage" => $query->lastPage()
        ];

        foreach ($query as $userBoardMember) {
            $board = $userBoardMember->getBoards;

            if (!$board->isArchived) {
                $result["boards"][] = $board;
            }
        }

        return $this->sendResponse($result);
    }

}
