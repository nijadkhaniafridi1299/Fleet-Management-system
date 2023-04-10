<?php

namespace App\Http\Middleware;

use Closure;
use Session;

class AuditLogMiddleware
{
	/**
	* Handle an incoming request.
	*
	* @param  \Illuminate\Http\Request  $request
	* @param  \Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next)
	{
		return $next($request);
	}

    public function terminate($request, $response) {
        $responseData = json_decode($response->getContent(), true);
    
        if (isset($responseData['code']) && ($responseData['code'] == 200 || $responseData['code'] == 201)) {
            if (isset($responseData['module'])) {
                $module_id = \App\Model\FleetModule::where('key', $responseData['module'])->value('module_id');

                //log response to db
                $data['module_id'] = $module_id;
                $data['request_log_id'] = $responseData['request_log_id'];

                $audit_log = new \App\Model\AuditLog();
                $audit_log = $audit_log->add($data);
                $errors = \App\Message\Error::get('auditlog.add');
                print_r($errors);
            }
        }
    }
}
