<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\AuditLog;

class AuditLogController extends Controller

{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
    * @OA\Get(
    *   path="/AuditLogs",
    *   summary="Return the list of audit logs",
    *   tags={"AuditLogs"},
    *    @OA\Response(
    *      response=200,
    *      description="List of Audit Logs",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of Audit Logs",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index() {
        $audit_logs = AuditLog::with('logRequest', 'module')->all();
        return ["data" => $audit_logs];
    }

    public function show($auditLogId) {
        $validator = Validator::make([    
            'audit_log_id' => $auditLogId
        ],[
            'audit_log_id' => 'int|min:1|exists:fm_audit_logs,audit_log_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $audit_log = AuditLog::find($auditLogId);

        return response()->json([
            "audit_log" => $audit_log
        ]);
    }

    /**
    * @OA\Post(
    *   path="/audit-log/add",
    *   summary="Add new audit log",
    *   operationId="create",
    *   tags={"audit log"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Audit Log has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $audit_log = new AuditLog();

        //print_r($data); exit;
        $audit_log = $audit_log->add($data);

        $errors = \App\Message\Error::get('auditlog.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Audit Log has been created.'
        ]);
    }

    public function search(Request $request) {
        $filter = $request->all();

        $query = AuditLog::with('logRequest', 'module')->orderBy('created_at', 'desc');
        if (isset($filter['module'])) {
            $module = $filter['module'];
            $query->whereHas('module', function($query) use ($module) {
                $query->where('key', $module);  
            });
        }

        $results = $query->get()->toArray();

        return response()->json([
            "code" => "200",
            "results" => $results
        ]);
    }
}
