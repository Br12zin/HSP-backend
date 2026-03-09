<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Solicitar reset de senha (COM ENVIO DE EMAIL)
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Gerar token aleatório
        $token = Str::random(60);

        // Remover tokens antigos deste email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Salvar novo token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // 🔥 ENVIAR EMAIL COM LINK DE RESET
        $this->sendResetEmail($request->email, $token);

        return response()->json([
            'message' => 'Email de recuperação enviado com sucesso! Verifique sua caixa de entrada.'
        ]);
    }

    // Função para enviar o email
    private function sendResetEmail($email, $token)
    {
        $frontendUrl = 'http://localhost:3000'; // URL do seu frontend
        $resetLink = "{$frontendUrl}/redefinir-senha?token={$token}&email={$email}";

        $data = [
            'link' => $resetLink,
            'email' => $email,
            'appName' => env('APP_NAME', 'HSP')
        ];

        Mail::send('emails.password-reset', $data, function ($message) use ($email) {
            $message->to($email)
                    ->subject('Recuperação de Senha - HSP')
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });
    }

    // Validar token
    public function validateToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required'
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Token inválido ou expirado'
            ], 400);
        }

        // Verificar se token expirou (24 horas)
        $createdAt = Carbon::parse($reset->created_at);
        if (Carbon::now()->diffInHours($createdAt) > 24) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token expirado'
            ], 400);
        }

        return response()->json([
            'message' => 'Token válido',
            'email' => $request->email
        ]);
    }

    // Resetar senha
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6'
        ]);

        // Verificar token
        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Token inválido ou expirado'
            ], 400);
        }

        // Verificar expiração
        $createdAt = Carbon::parse($reset->created_at);
        if (Carbon::now()->diffInHours($createdAt) > 24) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token expirado'
            ], 400);
        }

        // Atualizar senha
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Remover token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Senha alterada com sucesso'
        ]);
    }
}