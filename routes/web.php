<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET,HEAD,PUT,POST,DELETE,PATCH,OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Events\LiveLocationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Mail;
// use App\Events\OrderShipped as OrderShipped;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/key', function() {
  	return \Illuminate\Support\Str::random(32);
});

$router->post('sensor', ['as' => 'fm.trip.listing1', 'uses' => 'Controller@sensor']);
$router->get('sensor', ['as' => 'fm.trip.listing1', 'uses' => 'Controller@sensor']);



$router->group(['prefix' => 'api','middleware'=>'SaveRequest'], function () use ($router) {
	$router->get('ship', ['as' => 'fm.ship', 'uses' => 'EventController@callEvent']);
	//Login Routes
	$router->post('login', 'AuthController@login');
	$router->get('azure-login', 'AuthController@azureLogin');
	$router->post('azure-report', 'AuthController@azureReportData');
	$router->post('azure-embed-token', 'AuthController@azureEmbedToken');


	//cronjobURLs
	$router->post('set-area/{limit}', ['as' => 'fm.set.area', 'uses' => 'SensorController@setArea']);
	$router->post('update-vehicle-location/{limit}', ['as' => 'fm.update.vehicle.location', 'uses' => 'GraphController@updateVehicleLocation']);
	$router->post('update-device-status/{limit}', ['as' => 'fm.update.device.status', 'uses' => 'GraphController@updateDeviceStatus']);
	
	$router->group(['middleware' => 'jwtverification'], function()use ($router)  {
		// $router->group(['middleware' => 'roleaccess'], function () use ($router) {


			//TripsRoutes
			$router->get('trip-listing/{store_id}/{date}', ['as' => 'fm.trip.listing', 'uses' => 'TripsController@getTripsAction']);//On live button
			$router->get('trip-defaults/{store_id}', ['as' => 'fm.trip.defaults', 'uses' => 'TripsController@getTripDefaultsAction']); //Trip Listing Module - contains raw data mainly
			$router->get('trip-listing-detailed/{store_id}', ['as' => 'fm.trip.listing.detail', 'uses' => 'TripsController@getTripListingDetailedAction']); //Filters Data. Used In Trip Listing
			$router->get('trip-listing-details/{store_id}', ['as' => 'fm.trip.listing.details', 'uses' => 'TripsController@getTripListingDetails']); //Pagination For Trip Listing Details.
			$router->get('trip-deliveries-listing/{store_id}/{delivery_trip_id}', ['as' => 'fm.trip.deliveries.listing', 'uses' => 'TripsController@getTripDeliveriesListingAction']); //Single trip details on tripid. TripInfo Section
			$router->get('update-trip/{store_id}', ['as' => 'fm.trip.change', 'uses' => 'TripsController@updateDeliveryTripAction']);//Update Trip Date and Vehicle
			$router->post('delete-trip/{store_id}', ['as' => 'fm.trip.remove', 'uses' => 'TripsController@deleteDeliveryTripAction']); //Delete Delivery Trip
			$router->post('create-static-trip/{store_id}', ['as' => 'fm.trip.static.create', 'uses' => 'TripsController@generateStaticTripsAction']); //Create Static Trip
			$router->post('create-trip/{store_id}', ['as' => 'fm.trip.custom.create', 'uses' => 'TripsController@createCustomTripAction']);//Trip Planning. Creation Of Custom Trips
			$router->post('cancel-trip', ['as' => 'fm.trip.remove', 'uses' => 'TripsController@cancelDeliveryTripAction']);
			$router->post('create-dynamic-trip/{store_id}', ['as' => 'fm.trip.dynamic.create', 'uses' => 'TripsController@generateDynamicTripsAction']);
			$router->get('trip-batches/{store_id}', ['as' => 'fm.trip.batches', 'uses' => 'BatchController@bactchListingAction']);//Get All Batches List
			$router->get('batch-detail/{store_id}', ['as' => 'fm.trip.batch.detail', 'uses' => 'BatchController@bactchDetailAction']); //Get Specific Batch Detail
			$router->get('approve-delivery-trip/{store_id}', ['as' => 'fm.trip.approve', 'uses' => 'TripsController@ApproveRejectTripAction']); //Approve A Trip By Admin
			$router->get('update-delivery-trip/{store_id}','TripsController@updateTripAction'); //Update Delivery Trip For Multiple Orders
			$router->get('get-trip-data/{delivery_trip_id}','TripsController@getTripData');//Vehicle Location on TripID. On Mini map in trip Info
			$router->post('change-trip-address','TripsController@changeTripAddress');//dispatcher can change pickup and/or drop-off location for started trips 
			$router->get('trip-pickup-location/{order_number}','TripsController@tripPickupLocation');// show pickup and dropoff locations based on order number,info will be used for editing trip locations
			$router->get('show-unposted-posted-trips','TripsController@showUnpostedPostedTrips');// show pickup and dropoff locations based on order number,info will be used for editing trip locations
			$router->post('modify-trip-data','TripsController@modifyTripData');// show pickup and dropoff locations based on order number,info will be used for editing trip locations

			//customer
			$router->post('customer/add', 'CustomerController@create');
			$router->post('customer/edit/{customer_id}', 'CustomerController@update');
			$router->post('customer/delete/{customer_id}', 'CustomerController@remove');


			

			//Graph Api's 
			$router->get('order-status-graph/{store_id}', ['as' => 'fm.order.status.graph', 'uses' => 'GraphController@OrderStatusGraph']);
			$router->get('order-delivered-graph/{store_id}', ['as' => 'fm.order.delivered,graph', 'uses' => 'GraphController@OrderDeliveredGraph']);
		    $router->get('vehicle-status-graph/{store_id}/{date}', ['as' => 'fm.vehcile.status.graph,graph', 'uses' => 'GraphController@VehicleStatusGraph']);
			$router->get('delivered-order-perchannel/{store_id}', ['as' => 'fm.delivered.order.perchannel', 'uses' => 'GraphController@DeliveredOrderPerChannel']);
			$router->get('trip-type-graph/{store_id}', ['as' => 'fm.trip.type', 'uses' => 'GraphController@TripTypeGraph']);
			$router->get('trip-status-graph/{store_id}', ['as' => 'fm.trip.status', 'uses' => 'GraphController@TripStatusGraph']);
			$router->get('peak-time-graph/{store_id}', ['as' => 'fm.peak.time', 'uses' => 'GraphController@PeakTimeGraph']);
			$router->get('payment-methods-graph/{store_id}', ['as' => 'fm.payment.methods', 'uses' => 'GraphController@PaymentMethodsGraph']);
			$router->get('hot-areas-graph/{store_id}', ['as' => 'fm.hot.areas', 'uses' => 'GraphController@HotAreasGraph']);
            $router->get('area-wise-count/{store_id}', ['as' => 'fm.area.count', 'uses' => 'GraphController@HotAreasByOrders']);
			$router->get('delivered-cartonquantity-perchannel/{store_id}', ['as' => 'fm.carton.quantity', 'uses' => 'GraphController@DeliveredCartonQuantityPerChannel']);
			$router->get('ave-carton-per-deliveredorder/{store_id}', ['as' => 'fm.avg.carton.quantity', 'uses' => 'GraphController@AveCartonPerDeliveredOrder']);
			$router->get('home-delivery-ave-carton-quantity/{store_id}', ['as' => 'fm.homedelivery.avg.carton.quantity', 'uses' => 'GraphController@HomeDeliveryAveCartonQuantity']);
			$router->get('total-order-summary-by-creationdate/{store_id}', ['as' => 'fm.totalordersummary', 'uses' => 'GraphController@TotalOrderSummaryByCreationDate']);
			$router->get('carton-quanity-mergedchannel-by-creationdate/{store_id}', ['as' => 'fm.cartonquantity.mergedchannek', 'uses' => 'GraphController@CartonQuantityMergedChannelByCreationDate']);
			$router->get('hd-order-status-by-creationdate/{store_id}', ['as' => 'fm.orderstatus.creationdate', 'uses' => 'GraphController@HDOrderStatusByCreationDate']);
			$router->get('order-general-count/{store_id}', ['as' => 'fm.order.generalcount', 'uses' => 'GraphController@OrderGeneralCount']);
			$router->get('avg-orders-classified-on-trip-type-by-trip-date/{store_id}', ['as' => 'fm.avgorderclassified', 'uses' => 'GraphController@AvgOrdersClassified']);
			$router->get('customer-activity/{store_id}', ['as' => 'fmcustomer.activity', 'uses' => 'GraphController@CustomerActivity']);
			$router->get('staff-activity/{store_id}', ['as' => 'fm.staff.activity', 'uses' => 'GraphController@StaffActivity']);
			$router->get('driver-activity/{store_id}', ['as' => 'fm.driver.activity', 'uses' => 'GraphController@DriverActivity']);
			$router->get('avg-orders-classified-on-trip-type-by-trip-date/{store_id}', ['as' => 'fm.average.ordersclassified', 'uses' => 'GraphController@AvgCostClassified']);
			$router->get('avg-product-classified-on-trip-type-by-trip-date/{store_id}', ['as' => 'fm.average.productsclassified', 'uses' => 'GraphController@AvgProductClassified']);
			$router->get('delivered-order-lead-time/{store_id}', ['as' => 'fm.deliveredorder.leadtime', 'uses' => 'GraphController@DeliveredOrderLeadTime']);
			$router->get('trip-general-count/{store_id}', ['as' => 'fm.tripgeneralcount', 'uses' => 'GraphController@TripGeneralCount']);
            $router->get('order-coordinates/{store_id}', ['as' => 'fm.ordercoordinates', 'uses' => 'GraphController@OrderCoordinates']);
            $router->get('vehiclelocationsupdate', ['as' => 'fm.vehicle.location', 'uses' => 'GraphController@updateDeviceStatus']);

			//Delivery Order Routes
			$router->get('cancel-reasons/{store_id}', ['as' => 'fm.cancel.reasons', 'uses' => 'TowerController@getCancelReasonsAction']);
			$router->get('update-delivery-order/{store_id}', ['as' => 'fm.delivery.change', 'uses' => 'TowerController@updateOrderDeliveryAction']);
			$router->post('cancel-orders/{store_id}', ['as' => 'fm.cancel.orders', 'uses' =>  'TowerController@cancelOrderAction']);
			$router->get('routing/{store_id}/{type}/{fdate}/{tdate}', ['as' => 'fm.routing', 'uses' => 'RoutingController@routingAndCapacityAction']);
			$router->get('get-affiliated-orders/{store_id}/{trip_id}/{fromdate}/{todate}', ['as' => 'fm.affiliated.orders', 'uses' => 'RoutingController@tripAffiliatedOrdersAction']);
            
			//Vehicle Routes
			$router->get('vehicles', ['as' => 'fm.vehicles', 'uses' => 'VehicleController@index']);
			$router->get('vehicle/{vehicleId}', ['as' => 'fm.vehicle', 'uses' => 'VehicleController@show']);
			$router->post('vehicle/add', ['as' => 'fm.vehicle.add', 'uses' => 'VehicleController@create']);
			$router->post('vehicle/change/{vehicleId}', ['as' => 'fm.vehicle.change', 'uses' => 'VehicleController@change']);
			$router->post('vehicle/remove/{vehicleId}', ['as' => 'fm.vehicle.remove', 'uses' => 'VehicleController@remove']);
			$router->post('get-available-vehicles', ['as' => 'fm.vehicle.available', 'uses' => 'VehicleController@getAvailableVehiclesAction']);
			$router->get('vehicle/get/rawData', ['as' => 'fm.vehicle.masterdata', 'uses' => 'VehicleController@rawData']);
			$router->get('get-nearest-vehicle', ['as' => 'fm.vehicle.nearest', 'uses' => 'VehicleController@getNearestVehicle']);
			$router->get('multi-unit-flow', ['as' => 'fm.multi.unit.flow', 'uses' => 'VehicleController@getMultiUnitFlow']);
			$router->get('equipments/list', ['uses' => 'VehicleController@equipment']);
			$router->post('dropoff-calculations', ['uses' => 'VehicleController@pickToDropoff']);
			
			//VehicleType Routes
			$router->get('vehicle-types', ['as' => 'fm.vehicle.types', 'uses' => 'VehicleTypeController@index']);
			$router->get('vehicle-type/{vehicleTypeId}', ['as' => 'fm.vehicle.type', 'uses' => 'VehicleTypeController@show']);
			$router->post('vehicle-type/add', ['as' => 'fm.vehicle.type.add', 'uses' => 'VehicleTypeController@create']);
			$router->post('vehicle-type/change/{vehicleTypeId}', ['as' => 'fm.vehicle.type.change', 'uses' => 'VehicleTypeController@change']);
			$router->post('vehicle-type/remove/{vehicleTypeId}', ['as' => 'fm.vehicle.type.remove', 'uses' => 'VehicleTypeController@remove']);

			//VehicleGroup Routes
			$router->get('vehicle-groups', ['as' => 'fm.vehicle.groups', 'uses' => 'VehicleGroupController@index']);
			$router->get('vehicle-group/{vehicleGroupId}', ['as' => 'fm.vehicle.group', 'uses' => 'VehicleGroupController@show']);
			$router->post('vehicle-group/add', ['as' => 'fm.vehicle.group.add', 'uses' => 'VehicleGroupController@create']);
			$router->post('vehicle-group/change/{vehicleGroupId}', ['as' => 'fm.vehicle.group.change', 'uses' => 'VehicleGroupController@change']);
			$router->post('vehicle-group/remove/{vehicleGroupId}', ['as' => 'fm.vehicle.group.remove', 'uses' => 'VehicleGroupController@remove']);

			//Device Routes
			$router->get('devices', ['as' => 'fm.devices', 'uses' => 'DeviceController@index']);
			$router->get('device/{deviceId}', ['as' => 'fm.device', 'uses' => 'DeviceController@show']);
			$router->post('device/add', ['as' => 'fm.device.add', 'uses' => 'DeviceController@create']);
			$router->post('device/change/{deviceId}', ['as' => 'fm.device.change', 'uses' => 'DeviceController@change']);
			$router->post('device/remove/{deviceId}', ['as' => 'fm.device.remove', 'uses' => 'DeviceController@remove']);

			//Device Protocol Routes
			$router->get('device-protocols', ['as' => 'fm.device.protocols', 'uses' => 'DeviceProtocolController@index']);
			$router->get('device-protocol/{deviceProtocolId}',  ['as' => 'fm.device.protocol', 'uses' => 'DeviceProtocolController@show']);
			$router->post('device-protocol/add',  ['as' => 'fm.device.protocol.add', 'uses' => 'DeviceProtocolController@create']);
			$router->post('device-protocol/change/{deviceProtocolId}', ['as' => 'fm.device.protocol.change', 'uses' => 'DeviceProtocolController@change']);
			$router->post('device-protocol/remove/{deviceProtocolId}',  ['as' => 'fm.device.protocol.remove', 'uses' => 'DeviceProtocolController@remove']);

			//Sensor Type Routes
			$router->get('sensor-types', ['as' => 'fm.sensor.types', 'uses' => 'SensorTypeController@index']);
			$router->get('sensor-type/{sensorTypeId}', ['as' => 'fm.sensor.type', 'uses' => 'SensorTypeController@show']);
			$router->post('sensor-type/add', ['as' => 'fm.sensor.type.add', 'uses' => 'SensorTypeController@create']);
			$router->post('sensor-type/change/{sensorTypeId}', ['as' => 'fm.sensor.type.change', 'uses' => 'SensorTypeController@change']);
			$router->post('sensor-type/remove/{sensorTypeId}', ['as' => 'fm.sensor.type.remove', 'uses' => 'SensorTypeController@remove']);

			//Parameter Routes
			$router->get('parameters', ['as' => 'fm.parameters', 'uses' => 'ParameterController@index']);
			$router->get('parameter/{parameterId}', ['as' => 'fm.parameter', 'uses' => 'ParameterController@show']);
			$router->post('parameter/add', ['as' => 'fm.parameter.add', 'uses' => 'ParameterController@create']);
			$router->post('parameter/change/{parameterId}', ['as' => 'fm.parameter.change', 'uses' => 'ParameterController@change']);
			$router->post('parameter/remove/{parameterId}', ['as' => 'fm.parameter.remove', 'uses' => 'ParameterController@remove']);
            
			//Geo Fence Routes
			$router->get('geofence', ['as' => 'fm.geofence', 'uses' => 'GeoFenceController@getGeoFences']);
			$router->post('geofence/add', ['as' => 'fm.geofence.add', 'uses' => 'GeoFenceController@create']);
			$router->post('geofence/change/{geofenceid}', ['as' => 'fm.geofence.change', 'uses' => 'GeoFenceController@change']);
			$router->post('geofence/remove/{geofenceid}', ['as' => 'fm.geofence.remove', 'uses' => 'GeoFenceController@remove']);
			$router->get('geofence/check', ['as' => 'fm.geofence.check', 'uses' => 'GeoFenceController@checkGeoFence']);
			//Sensor Routes
			$router->get('sensors', ['as' => 'fm.sensors', 'uses' => 'SensorController@index']);
			$router->get('sensor/{sensorId}', ['as' => 'fm.sensor', 'uses' => 'SensorController@show']);
			$router->post('sensor/add', ['as' => 'fm.sensor.add', 'uses' => 'SensorController@create']);
			$router->post('sensor/change/{sensorId}', ['as' => 'fm.sensor.change', 'uses' => 'SensorController@change']);
			$router->post('sensor/remove/{sensorId}', ['as' => 'fm.sensor.remove', 'uses' => 'SensorController@remove']);

			//Tracker Routes
			$router->get('tracker-data/{store_id}/{vehicle_id}', ['as' => 'fm.tracker.data', 'uses' => 'SensorController@getTrackerData']);
			$router->post('sensor-messages', ['as' => 'fm.tracker.data', 'uses' => 'SensorController@getSensorMessages']);

			
			//Service Routes
			$router->get('services', ['as' => 'fm.services', 'uses' => 'ServiceController@index']);
			$router->get('service/{serviceId}', ['as' => 'fm.service', 'uses' => 'ServiceController@show']);
			$router->post('service/add', ['as' => 'fm.service.add', 'uses' => 'ServiceController@create']);
			$router->post('service/change/{serviceId}', ['as' => 'fm.service.change', 'uses' => 'ServiceController@change']);
			$router->post('service/remove/{serviceId}', ['as' => 'fm.service.remove', 'uses' => 'ServiceController@remove']);

			//Command Routes
			$router->get('commands', ['as' => 'fm.commands', 'uses' => 'CommandController@index']);
			$router->get('command/{commandId}', ['as' => 'fm.command', 'uses' => 'CommandController@show']);
			$router->post('command/add', ['as' => 'fm.command.add', 'uses' => 'CommandController@create']);
			$router->post('command/change/{commandId}', ['as' => 'fm.command.change', 'uses' => 'CommandController@change']);
			$router->post('command/remove/{commandId}', ['as' => 'fm.command.remove', 'uses' => 'CommandController@remove']);


			//Driver Behavior Routes
			$router->get('driver-behaviors', ['as' => 'fm.driver.behaviors', 'uses' => 'DriverBehaviorController@index']);
			$router->get('driver-behavior/{driverBehaviorId}', ['as' => 'fm.driver.behavior', 'uses' => 'DriverBehaviorController@show']);
			$router->post('driver-behavior/add', ['as' => 'fm.driver.behavior.add', 'uses' => 'DriverBehaviorController@create']);
			$router->post('driver-behavior/change/{driverBehaviorId}', ['as' => 'fm.driver.behavior.change', 'uses' => 'DriverBehaviorController@change']);
			$router->post('driver-behavior/remove/{driverBehaviorId}', ['as' => 'fm.driver.behavior.remove', 'uses' => 'DriverBehaviorController@remove']);

			//Driver Routes
			$router->get('drivers', ['as' => 'fm.drivers', 'uses' => 'DriverController@index']);
			$router->post('createdriver/{store_id}', ['as' => 'fm.driver.add', 'uses' => 'DriverController@createDriver']);
			$router->post('updatedriver/{store_id}/{driver_id}', ['as' => 'fm.driver.change', 'uses' => 'DriverController@updateDriver']);
			$router->get('viewdriver/{store_id}/{driver_id}', ['as' => 'fm.driver', 'uses' => 'DriverController@viewDriver']);
			$router->post('deletedriver/{store_id}/{driver_id}', ['as' => 'fm.driver.remove', 'uses' => 'DriverController@deleteDriver']);

			//Driver Groups Routes
			$router->get('driver-groups', ['as' => 'fm.driver.groups', 'uses' => 'DriverGroupController@index']);
			$router->get('driver-group/{driverGroupId}', ['as' => 'fm.driver.group', 'uses' => 'DriverGroupController@show']);
			$router->post('driver-group/add', ['as' => 'fm.driver.group.add', 'uses' => 'DriverGroupController@create']);
			$router->post('driver-group/change/{driverGroupId}', ['as' => 'fm.driver.group.change', 'uses' => 'DriverGroupController@change']);
			$router->post('driver-group/remove/{driverGroupId}', ['as' => 'fm.driver.group.remove', 'uses' => 'DriverGroupController@remove']);

			//StoreRoutes
			$router->get('getStores', ['as' => 'fm.store.actions', 'uses' => 'StoreController@getStoresAction']);
			$router->get('get-store-constraints/{store_id}', ['as' => 'fm.store.constraints', 'uses' => 'StoreController@GetStoreConstraints']);
			$router->post('update-store-constraints/{store_id}', ['as' => 'fm.store.constraint.change', 'uses' => 'StoreController@updateStoreConstraintsAction']);


			//User Routes
			$router->get('users', ['as' => 'fm.users', 'uses' => 'UserController@index']);
			$router->get('user/{userId}', ['as' => 'fm.user', 'uses' => 'UserController@show']);
			$router->post('user/add/{store_id}', ['as' => 'fm.user.add', 'uses' => 'UserController@create']);
			$router->post('user/change/{userId}', ['as' => 'fm.user.change', 'uses' => 'UserController@change']);
			$router->post('user/remove/{userId}', ['as' => 'fm.user.remove', 'uses' => 'UserController@remove']);
			$router->get('user/active/sessions', ['as' => 'fm.user.active.sessions', 'uses' => 'UserController@getActiveSessions']);

			//Logout users
			$router->post('logout/{userId}', 'AuthController@logout');
			$router->post('update-password', ['as' => 'fm.password.change', 'uses' => 'TowerController@updatePassword']);

			//Trailer Routes
			$router->get('trailers', ['as' => 'fm.trailers', 'uses' => 'TrailerController@index']);
			$router->get('trailer/{trailerId}', ['as' => 'fm.trailer', 'uses' => 'TrailerController@show']);
			$router->post('trailer/add', ['as' => 'fm.trailer.add', 'uses' => 'TrailerController@create']);
			$router->post('trailer/change/{trailerId}', ['as' => 'fm.trailer.change', 'uses' => 'TrailerController@change']);
			$router->post('trailer/remove/{trailerId}', ['as' => 'fm.trailer.remove', 'uses' => 'TrailerController@remove']);

			//Events Routes
			$router->get('events', ['as' => 'fm.events', 'uses' => 'EventController@index']);
			$router->get('event/{eventId}', ['as' => 'fm.event', 'uses' => 'EventController@show']);
			$router->post('event/add', ['as' => 'fm.event.add', 'uses' => 'EventController@create']);
			$router->post('event/change/{eventId}', ['as' => 'fm.event.change', 'uses' => 'EventController@change']);
			$router->post('event/remove/{eventId}', ['as' => 'fm.event.remove', 'uses' => 'EventController@remove']);
			$router->post('event/send/notification', ['as' => 'fm.send.notification', 'uses' => 'EventController@sendNotification']);
			$router->get('event/vehicle/logs/{vehicle_id}', 'EventController@vehicleEventsLog');
			$router->get('event/on-vehicles/{event_id}', 'EventController@eventsOnVehicles');

			$router->get('liveMonitoring', ['as' => 'fm.sendlocation', 'uses' => 'EventController@liveMonitoring']);

            //Suggested Path Update
		   $router->get('update-suggested_path/{delivery_trip_id}', 'TripsController@Updatesuggested_path');

          
			//Status Routes
			$router->get('statuses', ['as' => 'fm.statuses', 'uses' => 'StatusController@index']);
			$router->get('status/{statusId}', ['as' => 'fm.status', 'uses' => 'StatusController@show']);
			$router->post('status/add', ['as' => 'fm.status.add', 'uses' => 'StatusController@create']);
			$router->post('status/change/{statusId}', ['as' => 'fm.status.change', 'uses' => 'StatusController@change']);
			$router->post('status/remove/{statusId}', ['as' => 'fm.status.remove', 'uses' => 'StatusController@remove']);

			//Event Status Routes
			$router->get('event-statuses', ['as' => 'fm.event.statuses', 'uses' => 'EventStatusController@index']);
			$router->get('event-status/{eventStatusId}', ['as' => 'fm.event.status', 'uses' => 'EventStatusController@show']);
			$router->post('event-status/add', ['as' => 'fm.event.status.add', 'uses' => 'EventStatusController@create']);
			$router->post('event-status/change/{eventStatusId}', ['as' => 'fm.event.status.change', 'uses' => 'EventStatusController@change']);
			$router->post('event-status/remove/{eventStatusId}', ['as' => 'fm.event.status.remove', 'uses' => 'EventStatusController@remove']);

			//Monitoring Routes
			$router->get('monitoring', ['as' => 'fm.monitoring', 'uses' => 'MonitoringController@index']);
			$router->get('monitoring-group', ['as' => 'fm.monitoring-group', 'uses' => 'MonitoringController@groupMonitoring']);

			//Audit Log Routes
			$router->get('audit-log/search', ['as' => 'fm.audit.log.search', 'uses' => 'AuditLogController@search']);

			//Role routes
			$router->get('roles', 'RoleController@index');
			$router->post('user-role/add', 'RoleController@create');
			$router->post('user-role/change/{roleId}', 'RoleController@change');
			$router->post('role/remove/{roleId}', 'RoleController@remove');
			$router->get('role/add/bulk', 'RoleController@bulk');

			//UserGroup routes
			$router->get('user-groups', ['as' => 'fm.user.groups', 'uses' => 'UserGroupController@index']);
			$router->post('user-group/add', ['as' => 'fm.user.group.add', 'uses' => 'UserGroupController@create']);
			$router->post('user-group/change/{groupId}', ['as' => 'fm.user.group.change', 'uses' => 'UserGroupController@change']);
			$router->post('user-group/remove/{groupId}', ['as' => 'fm.user.group.remove', 'uses' => 'UserGroupController@remove']);

			//Dashboard routes
			$router->get('dashboard/all', ['as' => 'fm.dashboard', 'uses' => 'DashboardController@index']);
			$router->get('dashboard/all/data', ['as' => 'fm.dashboard.data', 'uses' => 'DashboardController@indexWithData']);
			$router->get('dashboard/moving/all', ['as' => 'fm.dashboard.moving', 'uses' => 'DashboardController@moving']);
			$router->get('dashboard/moving/engine', ['as' => 'fm.dashboard.moving.engine.on', 'uses' => 'DashboardController@moving_engine_on']);
			$router->get('dashboard/others', ['as' => 'fm.dashboard.all.data', 'uses' => 'DashboardController@all_data']);
			$router->get('dashboard/linked/devices', ['as' => 'fm.dashboard.linked.devices', 'uses' => 'DashboardController@linkedDevices']);
			$router->get('dashboard/nonlinked/devices', ['as' => 'fm.dashboard.nonlinked.devices', 'uses' => 'DashboardController@nonLinkedDevices']);
			$router->get('dashboard/online/vehicles', ['as' => 'fm.dashboard.online.devices', 'uses' => 'DashboardController@onlineDevices']);
			$router->get('dashboard/offline/vehicles', ['as' => 'fm.dashboard.offline.devices', 'uses' => 'DashboardController@offlineDevices']);
			$router->get('dashboard/stationary/engine-off', ['as' => 'fm.dashboard.offline.devices', 'uses' => 'DashboardController@stationary_engine_off']);
			$router->get('dashboard/stationary/engine-on', ['as' => 'fm.dashboard.offline.devices', 'uses' => 'DashboardController@stationary_engine_on']);

			//Orders
			$router->post('supervisor_order_update', 'OrderController@supervisorOrderUpdate');
			$router->post('supervisor_order_detail', 'OrderController@orderDetailSupervisor');
			$router->get('raw_data_supervisor', 'OrderController@rawDataSupervisor');
			$router->get('pending_orders', ['as' => 'fm.order.pending', 'uses' => 'OrderController@pendingOrders']);
			$router->get('list_for_supervisor', ['as' => 'fm.order.supervisor', 'uses' => 'OrderController@listForSupervisor']);
			$router->post('order_update', ['as' => 'fm.order.update', 'uses' => 'OrderController@orderUpdate']);
			$router->post('cancel_order', ['as' => 'fm.order.cancel', 'uses' => 'OrderController@cancelOrder']);
			$router->get('logistics_orders', ['as' => 'fm.order.logistics.orders', 'uses' => 'OrderController@logisticsOrders']);
			$router->get('all_orders_supervisor', ['as' => 'fm.order.all.orders', 'uses' => 'OrderController@allOrdersSupervisor']);
			$router->get('customers_list', ['as' => 'fm.order.customers.list', 'uses' => 'OrderController@customersListing']);
			$router->post('getorder_detail', 'OrderController@getOrderDetail');
			$router->get('dashboard_data', ['as' => 'fm.order.all.dashboard.data', 'uses' => 'OrderController@dashboardData']);
			$router->post('order_complete', 'OrderController@orderComplete');
			$router->post('approve_order', 'OrderController@approveOrder');

			//Addresses
			$router->post('add_aqg_addresses', 'AddressController@addUpdateAQGAddress');
			$router->get('get_aqg_addresses', 'AddressController@getAQGAddress');

			//AssetTransaction
			$router->post('allocate_items', 'AssetTransactionController@allocateItems');
			$router->get('get_allocated_items', 'AssetTransactionController@getAllocatedItems');

			//Inventory
			$router->get('inventory_list', 'ServiceCategoryController@inventoryList');

			//Skips
			$router->get('skips', ['as' => 'fm.skips', 'uses' => 'SkipController@index']);
			$router->post('create-skip', 'SkipController@create');
			$router->get('skip-assignment-info', 'SkipController@getSkipAssignmentInfo');
			$router->post('skip-assignment-receival', 'SkipController@skipAssignmentReceival');



			$router->get('/import_excel', 'ImportExcelController@index');
			$router->post('/import_excel/import', 'ImportExcelController@import');

			//material
			$router->get('material_list', 'MaterialController@index');
			$router->post('material/add', 'MaterialController@create');
			$router->post('material/remove/{material_id}', 'MaterialController@remove');

			//Notifications
			$router->get('notifications', 'NotificationController@getAllNotifications');
			$router->patch('read-notifications/{notification_id}', 'NotificationController@setNotificationAsRead');

			$router->get('fetch-sap-assets', 'VehicleController@fetchSAPAssets');

		// });
	});
});

$router->group(['prefix' => 'oms','middleware'=>'SaveMobileRequest'], function () use ($router) {
	$router->post('login', 'AuthController@omslogin');
	$router->get('azure-login', 'AuthController@azureLogin');
	$router->post('azure-report', 'AuthController@azureReportData');
	$router->get('admin-login', 'AuthController@authenticate');
	$router->post('azure-embed-token', 'AuthController@azureEmbedToken');


	$router->post('get_company_settings', 'OptionController@getCompanySettings');
	
	

	$router->group(['middleware' => 'jwtomsverification'], function()use ($router)  {

		//change customer password
		$router->post('change-password/{customerId}', 'CustomerController@changePassword');

		//Logout users
		$router->get('logout/{customerId}', 'AuthController@omslogout');

		//products
		$router->get('categories', 'ProductController@index');

		//cart
		$router->post('cart_parameters', 'CartController@getCartParameters');
		
		//order
		$router->post('place_order', 'OrderController@placeOrder');
		$router->post('getorders', 'OrderController@getOrders');
		$router->post('orders', 'OrderController@getCustomerOrders');
		$router->post('getorder_detail', 'OrderController@getOrderDetail');
		$router->get('raw_data_customer', 'OrderController@rawDataCustomer');
		$router->get('dashboard_data', ['as' => 'fm.order.all.dashboard.data', 'uses' => 'OrderController@dashboardData']);
		
		//addresses
		$router->post('get_addresses', 'AddressController@getAddresses');
		$router->post('add_update_addresses', 'AddressController@addNewAddress');
		$router->post('address/add', 'AddressController@create');
		$router->get('customer_sites', 'AddressController@customerSitesData');

		//Customer order monitoring
		$router->get('monitoring/{customerId}', 'CustomerOrderMonitoringController@index');

		//Customer Trips
		$router->get('trip-listing/{customer_id}/{date}', 'TripsController@getCustomerTripsAction');
		$router->get('trip-defaults/{customer_id}', 'TripsController@getCustomerTripDefaultsAction');
		$router->get('trip-listing-detailed/{store_id}', 'TripsController@getTripListingDetailedAction');
		$router->get('trip-deliveries-listing/{store_id}/{delivery_trip_id}', 'TripsController@getTripDeliveriesListingAction');
		$router->get('get-trip-data/{delivery_trip_id}','TripsController@getTripData');
      
			
			

		//Service Categories
		$router->get('assets_list', 'ServiceCategoryController@Assetslist');

		//Skips
		$router->get('skips', 'SkipController@getCustomerSkips');
		$router->get('myskips', 'SkipController@index');
		
	
		//Lots
		$router->get('lots', 'LotController@index');
		$router->post('lot/add', 'LotController@create');
		
		//contracts
		$router->get('raw_data_contract', 'ContractController@contractRawData');
		$router->post('contract/add', 'ContractController@create');
		$router->get('contract/data', 'ContractController@getContractData');
		$router->get('contract/list_for_lots', 'ContractController@listForLots');


		//Notifications
		$router->get('notifications', 'NotificationController@getAllNotifications');
		$router->patch('read-notifications/{notification_id}', 'NotificationController@setNotificationAsRead');


	});

});

$router->group(['prefix' => 'externalsap/api/v1','middleware'=>'SaveRequest'], function () use ($router) {
	$router->post('login', 'AuthController@saplogin');
	$router->group(['middleware' => 'jwtverification'], function()use ($router)  {
		//Vehicle Insertion from SAP
		$router->post('add-asset', ['as' => 'fm.vehicles.sapadd', 'uses' => 'VehicleController@insertionFromSAP']);
		
		$router->post('add-driver', ['as' => 'fm.driver.add', 'uses' => 'DriverController@addDriverFromSaAP']);

		$router->post('update-material', 'TripsController@assignedMaterialChangeSAP');
		//for logout
		$router->post('logout/{userId}', 'AuthController@logout');
		$router->post('update-password', ['as' => 'fm.password.change', 'uses' => 'TowerController@updatePassword']);
	});
});

$router->group(['prefix' => 'driver/api/v1'
 ,'middleware'=>'SaveMobileRequest'
], function () use ($router) {
	$router->post('login', 'AuthController@driverlogin');
	// $router->post('get_company_settings', 'OptionController@getCompanySettings');
	

	$router->group(['middleware' => 'jwtverification'], function()use ($router)  {
		//Logout users
		// $router->get('logout/{customerId}', 'AuthController@omslogout');

		// Trips
		$router->get('trips_list', 'TripsController@getTripsList'); //Driver Login 
		$router->get('trip_info', 'TripsController@tripInfo'); //Main Dashboard Window
		$router->post('start_trip', 'TripsController@startTrip'); //Start Trip Button
		$router->post('load_unload_stock', 'TripsController@loadUnloadStock'); //Load and Unload Button
		$router->post('check_in', 'TripsController@checkIn'); // Checkin and checkout swipe screen
		$router->post('end_trip', 'TripsController@endTrip'); // unload button 
		$router->post('save-vehicle-loc','TripsController@saveVehicleLocation');  // to save vehicle location,runs in background
		$router->get('load-material-SAP/{delivery_trip_id}/{status_code}','TripsController@loadMaterialFromSAP');// 

	});
});

$router->group(['prefix' => 'api-iot','middleware'=>'SaveMobileRequest'], function () use ($router) {
	$router->post('action', 'IotController@action');

});
