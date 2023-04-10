<?php

namespace App\Http\Middleware;

use Closure;

class SaveRequest
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
		$route = $request->route();

		list($controller, $method) = explode('@', $route[1]['uses']);

		$body = $request->getContent();//json_encode($request->getContent(), JSON_UNESCAPED_UNICODE);

		$user = auth()->user();
		$data['user_id'] = (isset($user)) ? $user->user_id : null;
		$data['body'] = $body;
		$data['fname'] = $method;
		$data['controller'] = $controller;

		$fleet_request = new \App\Model\FleetRequest();
		$fleet_request = $fleet_request->add($data);

		//$request->session()->put('request_log_id', $fleet_request['id']);
		//$request["request_log_id"] => $fleet_request->id]);

		$modules = \App\Model\FleetModule::get()->pluck('key')->toArray();
		//echo print_r($modules, true); exit;

		$splitted_arr = explode("\\", $controller);
		$cont = $splitted_arr[count($splitted_arr) - 1];
		$cont = strtolower($cont);
		$cont = str_replace('controller', '', $cont);

		foreach($modules as $module) {
			if ($cont === strtolower($module)) {
				$request->merge(['request_log_id' => $fleet_request->id]);
			}
		}


		return $next($request);
	}

	public function terminate($request, $response) {
    }
}
