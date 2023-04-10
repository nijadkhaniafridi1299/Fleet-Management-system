<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Command;

class CommandController extends Controller

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
    *   path="/commands",
    *   summary="Return the list of commands",
    *   tags={"commands"},
    *    @OA\Response(
    *      response=200,
    *      description="List of commands",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of commands",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index() {
        $commands = Command::orderBy('created_at','DESC')->get();
        return ["data" => $commands];
    }

    public function show($commandId) {
        $command = Command::find($commandId);

        return response()->json([
            "command" => $command
        ]);
    }

    /**
    * @OA\Post(
    *   path="/command/add",
    *   summary="Add new command",
    *   operationId="create",
    *   tags={"command"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Command has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
     
        $command = new Command();

        $command = $command->add($data);

        $errors = \App\Message\Error::get('command.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "id" => $command->command_id,
            "message" => 'New Command has been created.'
        ]);
    }

    public function change(Request $request, $commandId) {
        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $command = new Command();

            $command = $command->change($data, $commandId);

            if (!is_object($command)) {
                $errors = \App\Message\Error::get('command.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Command has been updated successfully."
            ]);
        }
    }

    public function remove($commandId)
    {
        $command = Command::find($commandId);

        $command->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Command has been deleted.'
        ]);
    }
}
