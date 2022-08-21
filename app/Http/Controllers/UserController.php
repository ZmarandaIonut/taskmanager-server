<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends ApiController
{

    public function getUser()
    {
        try {
            $user = Auth::user();
            return $this->sendResponse([
                "user" => $user
            ]);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function register(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed'
            ]);

            if ($validate->fails()) {
                return $this->sendError('Bad request!', $validate->messages()->toArray());
            }

            $user = new User();
            $user->name = $request->get("name");
            $user->email = $request->get("email");
            $user->password = Hash::make($request->get("password"));
            $user->verify_token = Str::random(10);
            $user->save();

            $user->notify(new VerifyEmail($user->verify_token));

            return $this->sendResponse(["Code for email verification send"], Response::HTTP_CREATED);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function login(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validate->fails()) {
                return $this->sendError('Bad request!', $validate->messages()->toArray());
            }

            $error = false;

            $user = User::where('email', $request->get("email"))->first();
            if (!$user) {
                $error = true;
            } else {
                if (!Hash::check($request->get("password"), $user->password)) {
                    $error = true;
                }
            }
            if ($error) {
                return $this->sendError('Bad credentials!');
            }
            if (!$user->email_verified_at) {
                return $this->sendError('User didn\'t verify email address', [], Response::HTTP_NOT_ACCEPTABLE);
            }
            $token = $user->createToken("app");

            return $this->sendResponse([
                "token" => $token->plainTextToken,
                "user" => $user->toArray()
            ]);
        } catch (Exception $exception) {
            error_log($request->get("password"));
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function verifyEmail(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                "email" => 'required|email|exists:users,email',
                "code" => 'required'
            ]);

            if ($validate->fails()) {
                return $this->sendError('Bad request!', $validate->messages()->toArray());
            }

            $user = User::where("email", $request->get("email"))->where("verify_token", $request->get("code"))->first();

            if (!$user) {
                return $this->sendError('Bad code or email!');
            }

            $user->email_verified_at = now();
            $user->verify_token = null;
            $user->save();

            return $this->sendResponse([
                'data' => 'Email verified'
            ]);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resendVerifyEmailCode(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "email" => 'required|email|exists:users,email'
        ]);

        if ($validate->fails()) {
            return $this->sendError('Bad request!', $validate->messages()->toArray());
        }

        $user = User::where("email", $request->get("email"))->first();

        if ($user->email_verified_at) {
            return $this->sendError('Email already verified', [], Response::HTTP_NOT_ACCEPTABLE);
        }

        $user->notify(new VerifyEmail($user->verify_token));

        return $this->sendResponse([
            'data' => 'Code for email verification sent!'
        ]);
    }
}
