<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use Illuminate\Http\Request;
class AuditLogController extends Controller {
 public function index(Request $request) { $logs=AuditLog::latest('created_at')->paginate(25)->withQueryString(); return view('audit-logs.index',compact('logs')); }
 public function latest() { return response()->json(AuditLog::latest('created_at')->take(25)->get()->map(fn($l)=>['id'=>$l->id,'at'=>$l->created_at?->format('M j, Y g:i A'),'user'=>$l->user_name??'System','action'=>$l->action,'type'=>$l->auditable_type??'—','label'=>$l->label,'ip'=>$l->ip_address,'values'=>$l->new_values])); }
}
