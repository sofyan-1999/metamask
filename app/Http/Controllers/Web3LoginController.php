<?php

namespace App\Http\Controllers;

use App\Models\User;
use Elliptic\EC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class Web3LoginController extends Controller
{
    public function signature(): string
    {
        $nonce = Str::random();
        $message = "Sign this message to confirm you own this wallet address. This action will not cost any gas fees.\n\nNonce: ".$nonce;

        session()->put('sign_message', $message);

        return $message;
    }

    public function authenticate(Request $request): string
    {
        $this->verifySignature(session()->pull('sign_message'), $request->input('signature'), $request->input('address'));

        $user = User::firstOrCreate([
            'eth_address' => $request->address,
        ], [
            'name' => $request->address,
            'email' => $request->address.'@gmail.com',
            'password' => Hash::make(123456),
            'eth_address' => $request->address,
        ]);

        auth()->login($user);
        session()->forget('sign_message');

        return true;
    }

    protected function verifySignature(string $message, string $signature, string $address): bool
    {
        $hash = Keccak::hash(sprintf("\x19Ethereum Signed Message:\n%s%s", strlen($message), $message), 256);
        $sign = [
            'r' => substr($signature, 2, 64),
            's' => substr($signature, 66, 64),
        ];
        $recid = ord(hex2bin(substr($signature, 130, 2))) - 27;

        if ($recid != ($recid & 1)) {
            return false;
        }

        $pubkey = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recid);
        $derived_address = '0x'.substr(Keccak::hash(substr(hex2bin($pubkey->encode('hex')), 1), 256), 24);

        return Str::lower($address) === $derived_address;
    }
}
