<?php

namespace App\Http\Controllers;

use App\Actions\SendPushNotificationToAllAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function send(Request $request, SendPushNotificationToAllAction $sendAction)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }

        $sendAction->execute($request->title, $request->body);

        return Redirect::back()->with('success', 'Notificação enviada com sucesso!');
    }
} 