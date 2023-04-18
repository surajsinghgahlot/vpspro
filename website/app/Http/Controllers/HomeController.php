<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\MarchantExport;
use App\Exports\MemberExport;
use App\Exports\ProductExport;
use App\Models\UserMembership;
use App\Models\Membership;
use App\Models\Transaction;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\User\AuthController;
use App\Helpers\NotificationHelper;
use App\Models\UserInstance;
use App\User as Marchant;
use App\User as Member;
use App\User;
use DB;
use App\Models\Product;
use Auth;
use Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('privacyPolicy');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if(auth::user()->role_id == '1')
           $instances = UserInstance::orderBy('expiry_date','asc')->get();
        else
           $instances = UserInstance::where('user_id',auth::id())->orderBy('expiry_date','asc')->get();
        return view('home',compact('instances'));
    }

    public function exportMarchants(Request $request){
      $data = User::select('users.id','users.name','users.email','users.phone','users.is_active','users.shop_name','users.shop_link','users.shop_start_time','users.shop_end_time','users.address','users.city','users.zip_code','users.created_at')
                    ->where('users.role_id','2')
                    ->where(function($query) use ($request){
                      if(isset($request->search) && !empty($request->search)){
                        $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                              ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                              ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                              ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%')
                              ->orWhereRaw('LOWER(shop_name) like ?' , '%'.strtolower($request->search).'%');
                     }
                     if(isset($request->status) && !empty($request->status)){
                          if($request->status == 'active')
                               $query->where('is_active','1');
       
                           if($request->status == 'deactive')
                               $query->where('is_active','0');
                     }
                    })
                    ->whereNull('users.deleted_at')
                    ->orderBy('users.id','desc')
                    ->get();
      if($data->toarray())
              return Excel::download(new MarchantExport($data), 'marchants'.date('Y-m-d').'.xlsx');
      else 
            return back()->with('status',false)->with('message','Record Not found');
    }

    public function exportMembers(Request $request){
      $data = User::leftJoin('user_bank_details','users.id','=','user_bank_details.user_id')->select('users.id','users.name','users.email','users.phone','users.is_active','users.shop_name','users.shop_link','users.shop_start_time','users.shop_end_time','users.address','users.city','users.zip_code','users.created_at','user_bank_details.account_holder_name','user_bank_details.bank_name','user_bank_details.account_number','user_bank_details.tac_code')
                    ->where('users.role_id','3')
                    ->where(function($query) use ($request){
                      if(isset($request->search) && !empty($request->search)){
                         $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                               ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                               ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                               ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%');
                      }
                      if(isset($request->status) && !empty($request->status)){
                           if($request->status == 'active')
                                $query->where('is_active','1');
        
                            if($request->status == 'deactive')
                                $query->where('is_active','0');
                      }
                  })
                    ->whereNull('users.deleted_at')
                    ->orderBy('users.id','desc')
                    ->get();
                    if($data->toarray())
                       return Excel::download(new MemberExport($data), 'members'.date('Y-m-d').'.xlsx');
                    else
                       return back()->with('status',false)->with('message','Record Not found');
    }

    public function exportProducts(Request $request){
      $data = Product::join('users','products.marchant_id','=','users.id')->select('products.*','users.id','users.name','users.email','users.phone','users.is_active','users.shop_name','users.shop_link','users.shop_start_time','users.shop_end_time','users.address','users.city','users.zip_code','users.created_at')
                      ->whereNull('products.deleted_at')
                      ->where(function($query) use ($request){
                        if(isset($request->search) && !empty($request->search)){
                           $query->whereRaw('LOWER(title) like ?' , '%'.strtolower($request->search).'%');
                          }
                          if($request->marchant){
                            $query->where('marchant_id',$request->marchant);
                          }
                        })
                      ->orderBy('users.id','desc')
                      ->get();
      if($data->toarray())
         return Excel::download(new ProductExport($data), 'products'.date('Y-m-d').'.xlsx');
      else
         return back()->with('status',false)->with('message','Record Not found');
    }

    public function profile(Request $request){
       return view('profile');
    }

    public function updateProfile(Request $request){

      $input = $request->all();
      $id = auth::id();
      $rules = [
          'name'   => 'required|string|max:255|unique:users,name,'.$id.',id,deleted_at,NULL',
          'email'  => 'required|string|email|max:255|unique:users,email,'.$id.',id,deleted_at,NULL',
          'phone'  => 'string|unique:users,phone,'.$id.',id,deleted_at,NULL',
       ];
       
      $request->validate($rules);

       $fileName = null;
       if ($request->hasFile('profile_image')) {
           $fileName = str_random('10').'.'.time().'.'.request()->profile_image->getClientOriginalExtension();
           request()->profile_image->move(public_path('images/profile/'), $fileName);
       }

       $User = User::find($id);
       $User->name    = $input['name'];
       if(auth::user()->role_id == '1')
         $User->email   = $input['email'];
       $User->phone   = $input['phone'] ?? NULL;
       $User->address = $input['address'] ?? NULL;

       if($fileName){
         $User->profile_image = $fileName;
       }
 
       if($User->save())
           return redirect()->back()->with('status',true)->with('message','Successfully updated profile');
         else
           return redirect()->back()->with('status',false)->with('message','Failed to update profile');
   }

    public function changePassword(Request $request){
        $input    = $request->all();
        $rules = [
                  'old_password'      => 'required',
                  'new_password'      => 'min:6|required_with:confirm_password|same:confirm_password',
                  'confirm_password'  => 'required|min:6',
                 ];

        $request->validate($rules);

       if (!(Hash::check($request->old_password, auth()->user()->password))) {
            return redirect()->back()->with('status',false)->with('message','Your old password does not matches with the current password  , Please try again');
       }
       elseif(strcmp($request->old_password, $request->new_password) == 0){
            return redirect()->back()->with('status',false)->with('message','New password cannot be same as your current password,Please choose a different new password');
       }

        $User  = User::find(auth::id());
        $User->password = Hash::make($input['new_password']);
        $User->is_change_password = '1';
        if($User->update()){
          return redirect()->back()->with('status',true)->with('message','Successfully changed password');
       }
          return redirect()->back()->with('status',false)->with('message','Failed to change passsword');
    }

    public function changeDefaultPassword(Request $request){

        $input    = $request->all();
        $rules = [
                  'new_password'      => 'min:6|required_with:confirm_password|same:confirm_password',
                  'confirm_password'  => 'required|min:6',
                ];

        $request->validate($rules);

        $User  = User::find(auth::id());
        $User->password = Hash::make($input['new_password']);
        $User->is_change_password = '1';
        if($User->update()){
          return redirect()->back()->with('status',true)->with('message','Successfully changed password');
      }
          return redirect()->back()->with('status',false)->with('message','Failed to change passsword');
    }

    public function marchants(Request $request)
    {
        $marchats = Marchant::where(function($query) use ($request){
              if(isset($request->search) && !empty($request->search)){
                 $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(shop_name) like ?' , '%'.strtolower($request->search).'%');
              }
              if(isset($request->status) && !empty($request->status)){
                   if($request->status == 'active')
                        $query->where('is_active','1');

                    if($request->status == 'deactive')
                        $query->where('is_active','0');
              }
          })
          ->where('role_id','2')
          ->whereNull('deleted_at')
          ->orderBy('id','desc')
          ->paginate('10');
        $data['marchants'] = $marchats;
        return view('marchant.index',compact('data'));
    }

     public function marchantDetails($id){
        $marchat = Marchant::find($id);
        $data['marchant'] = $marchat;
        $data['transactionHistory'] = Transaction::where(function($query) use ($id){
          $query->where('credit_user_id',$id)->orWhere('debit_user_id',$id);
        })->orderBy('id','desc')->get();
        return view('marchant.show',compact('data'));
     }



    public function members(Request $request)
    {
        $members = Member::where(function($query) use ($request){
              if(isset($request->search) && !empty($request->search)){
                 $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                       ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%');
              }
              if(isset($request->status) && !empty($request->status)){
                   if($request->status == 'active')
                        $query->where('is_active','1');

                    if($request->status == 'deactive')
                        $query->where('is_active','0');
              }
          })
          ->where('role_id','3')
          ->whereNull('deleted_at')
          ->orderBy('id','desc')
          ->paginate('10');
        $data['members'] = $members;
        return view('member.index',compact('data'));
    }

    public function memberDetails($id){
        
      $member = User::find($id);
        $data['member'] = $member;
        $data['transactionHistory'] = Transaction::where(function($query) use ($id){
          $query->where('credit_user_id',$id)->orWhere('debit_user_id',$id);
        })->orderBy('id','desc')->get();

        return view('member.show',compact('data'));
    }

    public function products(Request $request){

      $products = Product::where(function($query) use ($request){
          if(isset($request->search) && !empty($request->search)){
             $query->whereRaw('LOWER(title) like ?' , '%'.strtolower($request->search).'%');
            }
            if($request->marchant){
              $query->where('marchant_id',$request->marchant);
            }
          })
      ->whereNull('deleted_at');
      if($request->hot_sell){
        $products = $products->orderBy('total_sell','desc')->paginate('10');
      }else{
        $products = $products->orderBy('id','desc')->paginate('10');
      }

    $data['products'] = $products;
    $data['marchants'] = Marchant::where('role_id','2')->whereNull('deleted_at')->orderBy('name','asc')->get();
    return view('product.index',compact('data'));
  }

  public function productDetails($id){
    $product = Product::find($id);
    $data['product'] = $product;
    return view('product.show',compact('data'));
  }

    public function deleteAccount(Request $request){
        $user = User::find($request->id);
        $email    = $user->email;
        $user->deleted_reason = $request->reason;
        $user->status = 'DELETE';
        $user->deleted_at     = date('Y-m-d H:i:s');
         if($user->update()){

          \DB::table('user_status_history')->insert([
            'user_id'=>$user->id,'status'=>'DELETE','reason'=>$reason
          ]);
            
            if($request->is_notify == '1'){
                $data = array(
                  'to'     => $email,
                  'due'    =>  $request->reason
                );

                \Mail::send('Mails.delete_account', $data, function ($message) use($data) {
                  $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                  $message->to($data['to'])->subject('Account Deleted');
                });
            }

            return redirect()->route('marchants')->with('status',true)->with('message','Successfully deleted account');
         }
         return redirect()->route('marchants')->with('status',false)->with('message','Failed to delete account');
    }

    public function activeAccount(Request $request){
        $user = User::find($request->id);
        $user->is_active        = '1';
        $user->status = 'ACTIVE';
        $email    = $user->email;
         if($user->update()){

          \DB::table('user_status_history')->insert([
            'user_id'=>$user->id,'status'=>'ACTIVE'
          ]);
            
            if($request->is_notify == '1'){
                $data = array(
                  'to'     => $email
                );

                \Mail::send('Mails.active_account', $data, function ($message) use($data) {
                  $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                  $message->to($data['to'])->subject('Active Account');
                });
            }

            return redirect()->route('marchants')->with('status',true)->with('message','Successfully actived account');
         }
         return redirect()->route('marchants')->with('status',false)->with('message','Failed to active account');
    }

    public function deactiveAccount(Request $request){
        $user = User::find($request->id);
        $email    = $user->email;
        $user->is_active        = '0';
        $user->status           = 'DEACTIVE';
        $user->deactive_reason  = $request->reason;
         if($user->update()){
            
          \DB::table('user_status_history')->insert([
            'user_id'=>$user->id,'status'=>'DEACTIVE','reason'=>$request->reason
          ]);

            if($request->is_notify == '1'){
                $data = array(
                  'to'     => $email,
                  'due'    =>  $request->reason
                );

                \Mail::send('Mails.deactive_account', $data, function ($message) use($data) {
                  $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                  $message->to($data['to'])->subject('Deactive Account');
                });
            }

            return redirect()->route('marchants')->with('status',true)->with('message','Successfully deactive account');
         }
         return redirect()->route('marchants')->with('status',false)->with('message','Failed to deactive account');
    }

    public function deleteProduct(Request $request){
      $product = Product::find($request->id);
      $email    = $product->marchant->email;
      $product->deleted_reason = $request->reason;
      $product->deleted_at     = date('Y-m-d H:i:s');
       if($product->update()){
          
        \DB::table('user_status_history')->insert([
          'user_id'=>$user->id,'status'=>'DELETE','reason'=>$reason
        ]);

          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email,
                'id'     => $product->id,
                'title'  => $product->title,
                'due'    =>  $request->reason
              );

              \Mail::send('Mails.delete_product', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Product Deleted');
              });
          }

          return redirect()->route('products')->with('status',true)->with('message','Successfully deleted product');
       }
       return redirect()->route('products')->with('status',false)->with('message','Failed to delete product');
    }

    public function banAccount(Request $request){
      $user = User::find($request->id);
      $email    = $user->email;
      $reason   = $request->reason;
      $user->status = 'BAN';
      $user->is_ban  = '1';
       if($user->update()){

        \DB::table('user_status_history')->insert([
                  'user_id'=>$user->id,'status'=>'BAN','reason'=>$reason
               ]);
          
          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email,
                'due'    =>  $request->reason
              );

              \Mail::send('Mails.ban_account', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Ban Account');
              });
          }
          return redirect()->back()->with('status',true)->with('message','Successfully banned account');
       }
       return redirect()->back()->with('status',false)->with('message','Failed to ban account');
    }

    public function unbanAccount(Request $request){
      $user = User::find($request->id);
      $user->is_ban        = '0';
      $user->status        = 'UNBAN';
      $email    = $user->email;
       if($user->update()){

        \DB::table('user_status_history')->insert([
          'user_id'=>$user->id,'status'=>'UNBAN'
        ]);
          
          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email
              );

              \Mail::send('Mails.unban_account', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Unban Account');
              });
          }

          return redirect()->back()->with('status',true)->with('message','Successfully unbaned account');
       }
       return redirect()->back()->with('status',false)->with('message','Failed to ban account');
    }

    public function holdAccount(Request $request){
      $user = User::find($request->id);
      $email    = $user->email;
      $reason   = $request->reason;
      $user->status        = 'HOLD';
      $user->is_hold = '1';
       if($user->update()){

        \DB::table('user_status_history')->insert([
                  'user_id'=>$user->id,'status'=>'HOLD','reason'=>$reason
               ]);
          
          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email,
                'due'    =>  $request->reason
              );

              \Mail::send('Mails.hold_account', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Hold Account');
              });
          }
          return redirect()->back()->with('status',true)->with('message','Successfully holed account');
       }
       return redirect()->back()->with('status',false)->with('message','Failed to hold account');
    }

    public function unholdAccount(Request $request){
      $user = User::find($request->id);
      $user->is_hold        = '0';
      $user->status        = 'UNHOLD';
      $email    = $user->email;
       if($user->update()){

        \DB::table('user_status_history')->insert([
          'user_id'=>$user->id,'status'=>'UNHOLD'
        ]);
          
          if($request->is_notify == '1'){
              $data = array(
                'to'     => $email
              );

              \Mail::send('Mails.unhold_account', $data, function ($message) use($data) {
                $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
                $message->to($data['to'])->subject('Unhold Account');
              });
          }
          return redirect()->back()->with('status',true)->with('message','Successfully unholed account');
       }
       return redirect()->back()->with('status',false)->with('message','Failed to unhold account');
    }

    public function creditBalance(Request $request){

      if(empty($request->id) || ($request->grc == '0' || empty($request->grc)  && ($request->rm == '0' || empty($request->rm)))){
        return redirect()->back()->with('status',false)->with('message',__('Please enter RM Or GRC Amount'));
      }

      $input = $request->all();

      $user = User::find($input['id']);
      
      if(!empty($input['rm']) && $input['rm'] != '0')
         $user->withdraw_amount =  ($user->withdraw_amount + $input['rm']);

      if(!empty($input['grc']) && $input['grc'] != '0')
         $user->spend_amount    =  ($user->spend_amount + $input['grc']);

      if($user->update()){

        if($request->is_notify == '1' && ($request->grc != '0' || $request->rm != '0')){
          $data = array(
            'to'  => $user->email,
            'rm'  => $input['rm'] ?? '0',
            'grc' => $input['grc'] ?? '0'
          );

          \Mail::send('Mails.credit_amount', $data, function ($message) use($data) {
            $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
            $message->to($data['to'])->subject('Credit Amount');
          });
      }
      
      if ($request->rm){
         $rmCreditId =  \DB::table('transactions')->insertGetId([
            'credit_user_id'     => $input['id'],
            'debit_user_id'      => auth::id(),
            'invoice_id'         => time(),
            'transaction_id'     => time(),
            'transaction_amount' => $input['rm'],
            'amount_type'        => 'RM',
            'transaction_type'   => 'credit',
            'transaction_status' => '1'
         ]);
      }

       if($request->grc){
        $grcCreditId = \DB::table('transactions')->insertGetId([
          'credit_user_id'     => $input['id'],
          'debit_user_id'      => auth::id(),
          'invoice_id'         => time(),
          'transaction_id'     => time(),
          'transaction_amount' => $input['grc'],
          'amount_type'        => 'GRC',
          'transaction_type'   => 'credit',
          'transaction_status' => '1'
       ]);
       }

       if ($request->grc) {
           $notifyData = array();
           $notifyData['title']       = 'Credit Balance';
           $notifyData['body']        = 'Your account has been credited GRC' . $request->grc . 'balance';
           $notifyData['type']        = 'credit';
           $notifyData['id']          = $grcCreditId;
           $notifyData['sender_id']   = auth::id();
           $notifyData['receiver_id'] = [$input['id']];
           NotificationHelper::broadcast($notifyData);
       }

       if ($request->rm) {
          $notifyData = array();
          $notifyData['title']       = 'Credit Balance';
          $notifyData['body']        = 'Your account has been credited RM' . $request->rm . 'balance';
          $notifyData['type']        = 'credit';
          $notifyData['id']          = $rmCreditId;
          $notifyData['sender_id']   = auth::id();
          $notifyData['receiver_id'] = [$input['id']];
          NotificationHelper::broadcast($notifyData);
       }

        return redirect()->back()->with('status',true)->with('message',__('Balance Credited'));
      }else{
        return redirect()->back()->with('status',false)->with('message',__('Failed to credit'));
      }

    }

    public function undoBalance(Request $request){


      $transaction = Transaction::find($request->id);

      $amount      = $transaction->transaction_amount;
      $amountType  = $transaction->amount_type;
      $user        = User::find($transaction->credit_user_id);

      if($user->amount_type == 'rm')
         $user->withdraw_amount =  ($user->withdraw_amount - $user->rm);

      if($user->amount_type == 'grc')
         $user->spend_amount    =  ($user->spend_amount + $user->grc);

      if($user->update()){

        $data = array(
          'to'  => $user->email,
          'msg' => 'Your account has been debited ' . $amountType . ' ' . $amount
        );

        if($request->is_notify == '1' && ($request->grc != '0' || $request->rm != '0')){

          \Mail::send('Mails.debit_amount', $data, function ($message) use($data) {
            $message->from( env('MAIL_FROM') , env('MAIL_FROM_NAME') );
            $message->to($data['to'])->subject('Debit Amount');
          });
      }
      
          $debitId = \DB::table('transactions')->insertGetId([
            'credit_user_id'     => auth::id(),
            'debit_user_id'      => $user->id,
            'invoice_id'         => time(),
            'transaction_id'     => time(),
            'amount_type'        => $amountType,
            'transaction_amount' => $amount,
            'transaction_type'   => 'debit',
            'is_undo'            => '1',
            'transaction_status' => '1'
          ]);
          $transaction->is_undo = '1';
          $transaction->update();

            $notifyData = array();
            $notifyData['title']       = 'Dedit Balance';
            $notifyData['body']        = $data['msg'];
            $notifyData['type']        = 'debit';
            $notifyData['id']          = $debitId;
            $notifyData['sender_id']   = auth::id();
            $notifyData['receiver_id'] = [$user->id];
            NotificationHelper::broadcast($notifyData);

        return redirect()->back()->with('status',true)->with('message',__('Balance Debited'));
      }else{
        return redirect()->back()->with('status',false)->with('message',__('Failed to Debit'));
      }

    }

    public function privacyPolicy(){
      echo '<div style="padding:50px 50px;text-align:center"><h1>Privacy Policy</h1><p>GR is the most advance rewards App that gathers resources from wide variety of businesses to provide consumers with totally FREE gift.

      GR App allows you to earn bonus and get paid to shop with our membership program.
      
      GR App are building a new market ecology for alliance merchants to manage thier business with ease.</p></div>';
      die;
    }

    public function membershipRequest(Request $request){
      $members = Member::select('user_memberships.*','users.name','users.email',
                 'users.phone')
                  ->join('user_memberships','users.id','=','user_memberships.user_id')
                  ->where(function($query) use ($request){
                  if(isset($request->search) && !empty($request->search)){
                  $query->whereRaw('LOWER(name) like ?' , '%'.strtolower($request->search).'%')
                  ->orWhereRaw('LOWER(email) like ?' , '%'.strtolower($request->search).'%')
                  ->orWhereRaw('LOWER(phone) like ?' , '%'.strtolower($request->search).'%')
                  ->orWhereRaw('LOWER(address) like ?' , '%'.strtolower($request->search).'%');
                  }
                  if(isset($request->status) && !empty($request->status)){
                  if($request->status == 'active')
                  $query->where('users.is_active','1');

                  if($request->status == 'deactive')
                  $query->where('users.is_active','0');
                  }
                  })
                  ->where('users.role_id','3')
                  ->whereNull('users.deleted_at')
                  ->where('user_memberships.is_active','0')
                  ->whereNotNull('user_memberships.receipt')
                  ->orderBy('user_memberships.id','asc')
                  ->paginate('10');

      $data['members'] = $members;
      return view('membership_request.index',compact('data'));
    }

    public function membershipRequestDetails(Request $request){
       $member = Member::select('user_memberships.*','users.name','users.email',
      'users.phone')
                      ->join('user_memberships','users.id','=','user_memberships.user_id')
                      ->where('user_memberships.id',$request->id)
                      ->first();
       $data['member'] = $member;
       return view('membership_request.show',compact('data'));
    }

    public function approveMembership(Request $request){
      
      $input = $request->all();

      $rules = [
        'id'            => 'required'
      ];

       $UserPlan = UserMembership::where('id',$input['id'])->orderBy('id','desc')->whereNull('deleted_at')->first();
       
       if(empty($UserPlan) || is_null($UserPlan)){
           return back()->with('status',false)->with('message','Failed to approve membership');
       }
       if($UserPlan->is_active == '1'){
        return back()->with('status',false)->with('message','Failed to approve membership');
       }
       $User = User::find($UserPlan->user_id);

       if($User->is_active != '1'){
          return back()->with('status',false)->with('message','message','Your are inactive , Please contact with your administrator');
       }

        $Membership = Membership::find($UserPlan->membership_id);
       
        if(empty($Membership) || is_null($Membership)){
              return back()->with('status',false)->with('message','This membership does not exist');
        }
      
      if($UserPlan){
          
          if($UserPlan->membership_id == '1'){
                if($UserPlan->membership_id > 1){
                  return back()->with('status',false)->with('message','Can not downgrade membership');
                }
            }
            if($UserPlan->membership_id == '2'){
              if($UserPlan->membership_id > 2){
                return back()->with('status',false)->with('message','Can not downgrade membership');
              }
            }
            if($UserPlan->membership_id == '3'){
              if($UserPlan->membership_id > 3){
                return back()->with('status',false)->with('message','Can not downgrade membership');
              }
            }
            if($UserPlan->membership_id == '4'){
              if($UserPlan->membership_id > 4){
                return back()->with('status',false)->with('message','Can not downgrade membership');
              }
            }
       }

       $referralCode = $User->invite_referral_code;

       $firstLevelRefferralUser  = array();
       $secondLevelRefferralUser = array();
       $thirdLevelRefferralUser  = array();

       if($referralCode && $UserPlan->membership_id != '4'){

           $i = 0;

           $AuthController = new AuthController;
           
           do{
               
               if(empty($referralCode) || is_null($referralCode)){
                  break;
               }

               $status =  $AuthController->isExistReferralCode($referralCode);

           
               if(!$AuthController->isExistReferralCode($referralCode)){
                 break;
               }

                 $i++;
                 $GRP_TOTAL_PLAN  = 0;
                 $GRG_TOTAL_PLAN  = 0;
                 $GRS_TOTAL_PLAN  = 0;
                 $GRM_TOTAL_PLAN  = 0;

                 $referralUser    = User::where('referral_code',$referralCode)->whereNull('deleted_at')->first();
                 
                 if(empty($referralUser) || is_null($referralUser)){
                     break;
                 }

                 $membershipData  = UserMembership::where('referral_code',$referralCode)->get();

                 foreach($membershipData as $key => $value){
                     $value->code == 'GRP'   ? $GRP_TOTAL_PLAN+=1 : '';
                     $value->code == 'GRG'   ? $GRG_TOTAL_PLAN+=1 : '';
                     $value->code == 'GRS'   ? $GRS_TOTAL_PLAN+=1 : '';
                     $value->code == 'GRM'   ? $GRM_TOTAL_PLAN+=1 : '';
                 }

               if($i == 1){
                   $planPrice      = $Membership->price;
                   $planCommision  = $Membership->first_generation;
                   $tatalCommision = ($planCommision/100)*$planPrice;
                   $tatalCommision = $tatalCommision + $Membership->recruit_rm_bonus_amount;
                   $wihdrawAmount  = number_format($tatalCommision - (70/100)*$tatalCommision);
                   $spendAmount    = number_format($tatalCommision - (30/100)*$tatalCommision);
                   $firstLevelRefferralUser = [
                       'user_id'                => $referralUser->id,
                       'total_bonus'            => $tatalCommision,
                       'generation_bonus_level' => '1',
                       'withdraw_amount'         => $wihdrawAmount,
                       'spend_amount'            => $spendAmount
                   ];
               }

               if($i == 2){
                       $planPrice       = $Membership->price;
                       $planCommision   = $Membership->second_generation;
                       $tatalCommision  = ($planCommision/100)*$planPrice;
                       $tatalCommision = $tatalCommision + $Membership->recruit_rm_bonus_amount;
                       $wihdrawAmount = number_format($tatalCommision - (70/100)*$tatalCommision);
                       $spendAmount    = number_format($tatalCommision - (30/100)*$tatalCommision);
                       $secondLevelRefferralUser = [
                       'user_id'                => $referralUser->id,
                       'total_bonus'            => $tatalCommision,
                       'generation_bonus_level' => '2',
                       'withdraw_amount'         => $wihdrawAmount,
                       'spend_amount'            => $spendAmount
                       ];
               }

               if($i == 3){
                   $totalPlan = $GRP_TOTAL_PLAN + $GRG_TOTAL_PLAN + $GRS_TOTAL_PLAN;
                   if($totalPlan >= 5){
                       $planPrice      = $Membership->price;
                       $planCommision  = $Membership->third_generation;
                       $tatalCommision = ($planCommision/100)*$planPrice;
                       $tatalCommision = $tatalCommision + $Membership->recruit_rm_bonus_amount;
                       $wihdrawAmount = number_format($tatalCommision - (70/100)*$tatalCommision);
                       $spendAmount    = number_format($tatalCommision - (30/100)*$tatalCommision);
                       $thirdLevelRefferralUser = [
                       'user_id'                => $referralUser->id,
                       'total_bonus'            => $tatalCommision,
                       'generation_bonus_level' => '3',
                       'withdraw_amount'         => $wihdrawAmount,
                       'spend_amount'            => $spendAmount
                       ];
                   }
               }

               $referralUser    = User::find($referralUser->id)->whereNull('deleted_at')->first();
               $referralCode    = $referralUser->invite_referral_code ?? NULL;
            
           }while($i < 3);
          
       }

        $updateData = [
          'is_active'      => '1',
          'receipt_status' => '1'
        ];

        DB::beginTransaction();
        
        try{
            
           $userMembershipId = DB::table('user_memberships')->where('id',$request->id)->update($updateData);

            if($firstLevelRefferralUser){
                $firstLevelRefferralUser['user_membership_id'] = $userMembershipId;
                DB::table('user_referral_bonus')->insert($firstLevelRefferralUser);
                $user = DB::table('users')->select('id','withdraw_amount','spend_amount')->where('id',$firstLevelRefferralUser['user_id'])->first();
                DB::table('users')->where('id',$user->id)->update([
                    'withdraw_amount' => $user->withdraw_amount + $firstLevelRefferralUser['withdraw_amount'],
                    'spend_amount'    => $user->spend_amount + $firstLevelRefferralUser['spend_amount']
                ]);
            }

            if($secondLevelRefferralUser){
               $secondLevelRefferralUser['user_membership_id'] = $userMembershipId;
               DB::table('user_referral_bonus')->insert($secondLevelRefferralUser);
               $user = DB::table('users')->select('id','withdraw_amount','spend_amount')->where('id',$secondLevelRefferralUser['user_id'])->first();
                DB::table('users')->where('id',$user->id)->update([
                   'withdraw_amount' => $user->withdraw_amount + $firstLevelRefferralUser['withdraw_amount'],
                   'spend_amount'    => $user->spend_amount + $firstLevelRefferralUser['spend_amount']
                ]);
            }

            if($thirdLevelRefferralUser){
               $thirdLevelRefferralUser['user_membership_id'] = $userMembershipId;
               DB::table('user_referral_bonus')->insert($thirdLevelRefferralUser);
               $user = DB::table('users')->select('id','withdraw_amount','spend_amount')->where('id',$thirdLevelRefferralUser['user_id'])->first();
                DB::table('users')->where('id',$user->id)->update([
                   'withdraw_amount' => $user->withdraw_amount + $firstLevelRefferralUser['withdraw_amount'],
                   'spend_amount'    => $user->spend_amount + $firstLevelRefferralUser['spend_amount']
                ]);
            }
            DB::commit();
            return redirect()->route('membershipRequest')->with('status',true)->with('message','Successfully activated membership');
        }catch(\Exception $e){
            DB::rollback();
            return $e->getMessage();
            return redirect()->route('membershipRequest')->with('status',false)->with('message','Failed to active membership');
        }

    }

    public function declineMembership(Request $request){
      $userMembership = UserMembership::find($request->id);
      $userMembership->receipt_status  = '2';
      $userMembership->is_active       = '2';
      $userMembership->receipt_decline_status = $request->reason;
      if($userMembership->update())
          return redirect()->route('membershipRequest')->with('status',true)->with('message','Successfully declined');
      else
          return redirect()->route('membershipRequest')->with('status',true)->with('message','Failed to decline');
    }

}
