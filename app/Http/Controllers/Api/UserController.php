<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;
use App\Models\Code;
use Carbon\Carbon;
use GuzzleHttp\Client;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return UserResource::collection(User::paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $inputs['password'] = Hash::make($request->password);
        $user = User::create($inputs);
        return response()->json($user, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        return response()->json($user, Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json($user, Response::HTTP_NO_CONTENT);
    }

    public function check(Request $request)
    {
        $user = User::where('email', $request->username)
            ->orWhere('phone', $request->username)->first();
        $type = $request->type;
        if ($user == null) {
            $this->SendCode($request->username, $type,true);
        }

        return $user ? response()->json($user, Response::HTTP_OK) :
            response()->json(null, Response::HTTP_CREATED);
    }

    public function checkCode(Request $request)
    {
        $now = Carbon::now();
        $code = Code::where([['username', '=', $request->username], ['token', '=', $request->token]])->first();
        if ($code) {
            $diffMinutes = $now->diffInRealMinutes($code->created_at);
        }

        return $code && $diffMinutes < 2 ? response(null, Response::HTTP_BAD_REQUEST) : response(['message'=>'دوباره تلاش کنید'], Response::HTTP_UNAUTHORIZED);
    }


    public function createAccount(Request $request)
    {

        $now = Carbon::now();
        $code = Code::where([['username', '=', $request->username], ['token', '=', $request->token]])->first();
        if ($code) {
            $diffMinutes = $now->diffInRealMinutes($code->created_at);
        }

        if ($code) {
            if ($code->type == 'phone' && $diffMinutes < 2) {
                User::create(
                    [
                        'phone' => $request->username,
                        'password' => Hash::make($request->password)
                    ]
                );
                return response(null, Response::HTTP_BAD_REQUEST);
            } else if ($code->type == 'email' && $diffMinutes < 5) {
                User::create(
                    [
                        'phone' => $request->username,
                        'password' => Hash::make($request->password)
                    ]
                );
                return response(null, Response::HTTP_BAD_REQUEST);
            }
        }
        return response(null, Response::HTTP_CREATED);
    }


    public function forgetPassword(Request $request)
    {
        $user = $request->type == 'phone' ? User::where('phone', $request->username)->first()
            :
            User::where('email', $request->username)->first();

        if ($user) {
            $this->SendCode($request->username, $request->type, false);
        }else{
            return response(['message'=>'کاربر مورد نظر وجود ندارد.'],Response::HTTP_NOT_FOUND);
        }
    }


    public function resetPassword(Request $request){
        $user = $request->type =='phone' ? User::where('phone',$request->phone)->first()
             : User::where('email',$request->phone)->first();
            if($user){
                $user->update(['password'=>Hash::make($request->password)]);
            }
    }

    private function SendCode($username, $type, $createAccount)
    {
        Code::where('username',$username)->delete();

        $random = random_int(100000, 999999);

        if ($type == 'phone') {
            Code::create(['username' => $username, 'type' => 'phone', 'token' => $random]);
            $client = new Client();
            if ($createAccount) {
                $response = $client->request(
                    'GET',
                    "http://ippanel.com:8080/?apikey=PNMq0O4Q9xfMZMThVjQVoGxpo3EHayWnarfpDol6n7Q=&pid=6ani8z6cz8n20po&fnum=3000505&tnum=$username&p1=verification-code&v1=$random"
                );
            }else{
                $response = $client->request(
                    'GET',
                    "http://ippanel.com:8080/?apikey=PNMq0O4Q9xfMZMThVjQVoGxpo3EHayWnarfpDol6n7Q=&pid=hhnh39swn4p6erv&fnum=3000505&tnum=$username&p1=code&v1=$random"                );
            }
        }
    }
}
